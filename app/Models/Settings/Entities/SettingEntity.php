<?php

namespace App\Models\Settings\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingEntity extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'settings';

    protected $fillable = [
        'delay_minutes',
        'schedule_check_interval_minutes',
        'task_completion_alert_threshold_minutes',
        'download_task_alert_threshold_minutes',
        'domain_list_chuck'
    ];
}
