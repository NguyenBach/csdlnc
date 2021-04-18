<?php

namespace App\Jobs;

use App\Models\Assigment;
use App\Models\AssignSubmission;
use App\Models\Course;
use App\Models\MoodleLog;
use App\Models\StatisticDaily;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class LoadDataToRedshiftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Course
     */
    private $course;
    /**
     * @var Carbon
     */
    private $date;
    /**
     * @var int
     */
    private $user;

    /**
     * Create a new job instance.
     *
     * @param Course $course
     * @param User $user
     * @param $date
     */
    public function __construct(Course $course, int $user, Carbon $date)
    {
        $this->course = $course;
        $this->user = $user;
        $this->date = $date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
    	info('statistic');
        $startTime = Carbon::createMidnightDate($this->date->year, $this->date->month, $this->date->day);
        $begin = $startTime->timestamp;
        $endTime = $startTime->addDay()->timestamp;
        $view = $this->getView($this->course->id, $this->user, $begin, $endTime);
        $post = $this->getPost($this->course->id, $this->user, $begin, $endTime);
        $timeOnCourse = $this->getTimeOnCourse($this->course->id, $this->user, $begin, $endTime);
        $submission = $this->getAssigmentSubmission($this->course->id, $this->user, $begin, $endTime);
        $submissionTotal = $submission['total'];
        $submissionLate = $submission['late'];

        StatisticDaily::insert([
            'course_id' => $this->course->id,
            'user_id' => $this->user,
            'date' => $this->date->toDateString(),
            'view' => $view,
            'post' => $post,
            'time_on_course' => $timeOnCourse,
            'assignment_submission' => $submissionTotal,
            'assignment_submission_late' => $submissionLate
        ]);
    }

    private function getView($courseId, $userId, $startTime, $endTime)
    {
        $data = MoodleLog::query()->where('courseid', $courseId)
            ->where('action', 'viewed')
            ->where('edulevel', 2)
            ->whereBetween('timecreated', [$startTime, $endTime])
            ->where('userid', $userId)->count();
        return $data;
    }

    public function getPost($courseId, $userId, $startTime, $endTime)
    {
        return MoodleLog::query()->where('courseid', $courseId)
            ->whereIn('action', ["submitted", "created", "deleted", "updated", "uploaded", "sent","start"])
            ->where('edulevel', 2)
            ->whereBetween('timecreated', [$startTime, $endTime])
            ->where('userid', $userId)->count();
    }

    public function getTimeOnCourse($courseId, $userId, $startTime, $endTime)
    {
        $data = MoodleLog::query()->where('courseid', $courseId)
            ->where('edulevel', 2)
            ->whereBetween('timecreated', [$startTime, $endTime])
            ->where('userid', $userId)
            ->orderBy('timecreated', 'DESC')
            ->get();
        $firstItem = $data->first();
        $timeOnCourse = 0;
        foreach ($data as $item) {
            $timeBetween = $firstItem->timecreated - $item->timecreated;
            if ($timeBetween < 0) {
                continue;
            }
            if ($timeBetween > 20 * 60) {
                $timeOnCourse += 10 * 60;
            } else {
                $timeOnCourse += $timeBetween;
            }
            $firstItem = $item;
        }
        return $timeOnCourse;
    }

    public function getAssigmentSubmission($courseId, $userId, $startTime, $endTime)
    {
        $assignInCourse = Assigment::query()->where('course', $courseId)
            ->get();
        $assignIdsInCourse = $assignInCourse->pluck('id')->toArray();
        $assignInCourse = $assignInCourse->keyBy('id')->toArray();
        $submits = AssignSubmission::query()
            ->where('userid', $userId)
            ->whereBetween('timemodified', [$startTime, $endTime])
            ->whereIn('assignment', $assignIdsInCourse)
            ->get();
        $total = 0;
        $late = 0;
        foreach ($submits as $submit) {
            $assignment = $assignInCourse[$submit->assignment];
            if (!$assignment) {
                continue;
            }
            $total += 1;
            $dueDate = $assignment->duedate;
            if ($dueDate < $submit->timemodified) {
                $late += 1;
            }
        }
        return [
            'total' => $total,
            'late' => $late
        ];
    }
}
