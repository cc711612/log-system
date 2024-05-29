<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonitorEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'monitoring';

    protected $fillable = [
        'item_name',
        'status',
        'last_check_timestamp',
        'last_setting_id',
        'schedule_check_interval_minutes',
        'task_completion_alert_threshold_minutes',
        'download_task_alert_threshold_minutes',
        'email_alert_address',
        'webhook_url',
        'influx_db_connection',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'last_check_timestamp',
    ];
}
