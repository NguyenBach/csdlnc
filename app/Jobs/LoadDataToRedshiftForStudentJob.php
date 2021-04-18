<?php

namespace App\Jobs;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class LoadDataToRedshiftForStudentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Course
     */
    private $course;
    /**
     * @var int
     */
    private $studentId;

    /**
     * Create a new job instance.
     *
     * @param Course $course
     * @param int $studentId
     */
    public function __construct(Course $course, int $studentId)
    {
        $this->course = $course;
        $this->studentId = $studentId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $courseStart = Carbon::createFromTimestamp($this->course->startdate);
        $courseEnd = Carbon::createFromTimestamp($this->course->enddate);
        while ($courseStart->lte($courseEnd)) {
            info('dispatch for student ' . $this->studentId . ' at ' . $courseStart->toDateString());
            dispatch(new LoadDataToRedshiftJob($this->course, $this->studentId, $courseStart));
            $courseStart = $courseStart->addDay();
        }
    }
}
