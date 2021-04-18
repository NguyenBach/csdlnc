<?php

namespace App\Console\Commands;

use App\Jobs\LoadDataToRedshiftForStudentJob;
use App\Jobs\LoadDataToRedshiftJob;
use App\Models\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoadData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redshift:load-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $course = Course::where('id', 35)->first();
        $students = $this->getStudents(35);

        foreach ($students as $student) {
            info('dispatch job for student: ' . $student->userid);
            dispatch(new LoadDataToRedshiftForStudentJob($course, $student->userid));
        }
        return 0;
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
