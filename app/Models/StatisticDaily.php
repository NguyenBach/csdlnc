<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class StatisticDaily extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'statistic_daily';
    protected $fillable = [
        'course_id',
        'user_id',
        'date',
        'view',
        'post',
        'time_on_course',
        'assignment_submission_total',
        'assignment_submission_late',
    ];

}
