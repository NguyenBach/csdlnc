<?php

namespace App\Http\Controllers;

use App\Jobs\LoadDataToRedshiftJob;
use App\Models\Course;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function getData()
    {
        $course = Course::where('id', 35)->first();
        $students = $this->getStudents(35);
        $courseStart = Carbon::createFromTimestamp($course->startdate);
        info($courseStart->toDateString());
        $courseEnd = Carbon::createFromTimestamp($course->enddate);
        foreach ($students as $student) {
            while ($courseStart->lte($courseEnd)) {
                $this->dispatch(new LoadDataToRedshiftJob($course, $student->userid, $courseStart));
                $courseStart = $courseStart->addDay();
            }
        }
    }

    public function getStudents($courseId)
    {
        return DB::table('mdl_role_assignments')
            ->join('mdl_context', 'mdl_role_assignments.contextid', '=', 'mdl_context.id')
            ->where('mdl_context.instanceid', $courseId)
            ->where('mdl_role_assignments.roleid', 5)
            ->get();
    }

}
