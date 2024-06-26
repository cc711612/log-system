<?php

namespace App\Models\Users\Entities;

use App\Models\Downloads\Entities\DownloadEntity;
use App\Models\ExecuteSchedules\Entities\ExecuteScheduleEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'display_name',
        'product_type',
        'account',
        'password',
        'tswd_account',
        'tswd_token',
        'cf_account',
        'cf_token',
        'elasticsearch_connection',
        'elasticsearch_token',
        'elasticsearch_index',
        'influx_db_connection',
        'influx_db_bucket',
        'influx_db_token',
        'influx_db_org',
    ];

    protected $hidden = [
        'password',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function downloads()
    {
        return $this->hasMany(DownloadEntity::class, 'user_id', 'id');
    }

    public function executeSchedules()
    {
        return $this->hasMany(ExecuteScheduleEntity::class, 'user_id', 'id');
    }
}
