<?php

namespace App\Models\ExecuteSchedules\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExecuteScheduleEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'execute_schedules';

    protected $fillable = [
        'user_id',
        'log_time_start',
        'log_time_end',
        'status',
        'process_time_start',
        'process_time_end',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'log_time_start',
        'log_time_end',
        'process_time_start',
        'process_time_end',
    ];
}
