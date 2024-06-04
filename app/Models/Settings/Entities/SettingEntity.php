<?php

namespace App\Models\Settings\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SettingEntity extends Model
{
    use HasFactory, Notifiable;

    public $timestamps = false;

    protected $table = 'settings';

    protected $fillable = [
        'delay_minutes',
        'schedule_check_interval_minutes',
        'task_completion_alert_threshold_minutes',
        'download_task_alert_threshold_minutes',
        'domain_list_chuck',
        'slack_webhook_url',
        'email',
    ];

    public function routeNotificationForSlack($notification)
    {
        return $this->slack_webhook_url;
    }

    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }
}
