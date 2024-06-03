<?php

namespace Database\Seeders;

use App\Models\ExecuteSchedules\Entities\ExecuteScheduleEntity;
use App\Models\Settings\Entities\SettingEntity;
use App\Models\Users\Entities\UserEntity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        app(UserEntity::class)->create([
            'display_name'         => 'Admin',
            'product_type'         => 'tswd',
            'account'              => 'admin',
            'password'             => Hash::make(
                '15456123'
            ),
            'email'                => 'cc711612@gmail.com',
            'tswd_account'         => config('services.tswd.account'),
            'tswd_token'           => config('services.tswd.token'),
            'cf_account'           => null,
            'cf_token'             => null,
            'influx_db_connection' => config('influxdb.host'),
            'influx_db_bucket'     => config('influxdb.bucket'),
            'influx_db_token'      => config('influxdb.token'),
            'influx_db_org'        => config('influxdb.org'),
            'slack_webhook_url'    => env('SLACK_WEBHOOK_URL'),
        ]);

        app(SettingEntity::class)->create([
            'id'                                      => 1,
            'delay_minutes'                           => 5,
            'schedule_check_interval_minutes'         => 15,
            'task_completion_alert_threshold_minutes' => 15,
            'download_task_alert_threshold_minutes'   => 30,
            'domain_list_chuck'                       => 500,
        ]);

        app(ExecuteScheduleEntity::class)->create(
            [
                'id'             => 1,
                'user_id'        => 1,
                'log_time_start' => '2024-05-31 20:05:00',
                'log_time_end'   => '2024-05-31 20:10:00',
                'status'         => 'initial',
            ]);

        app(ExecuteScheduleEntity::class)->create(
            [
                'id'             => 2,
                'user_id'        => 1,
                'log_time_start' => '2024-05-31 20:10:00',
                'log_time_end'   => '2024-05-31 20:15:00',
                'status'         => 'initial',
            ]);
    }
}
