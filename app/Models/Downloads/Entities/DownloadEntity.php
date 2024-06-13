<?php

namespace App\Models\Downloads\Entities;

use App\Models\Users\Entities\UserEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DownloadEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'downloads';

    protected $fillable = [
        'user_id',
        'execute_schedule_id',
        'url',
        'pid', // 'PID'
        'domain_name',
        'service_type',
        'control_group_name',
        'control_group_code',
        'log_time_start',
        'log_time_end',
        'type',
        'status',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'log_time_start',
        'log_time_end',
    ];

    public function users()
    {
        return $this->belongsTo(UserEntity::class, 'user_id', 'id')
            ->select(['id', 'influx_db_connection', 'influx_db_bucket', 'influx_db_token', 'influx_db_org']);
    }
}
