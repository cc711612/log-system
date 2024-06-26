<?php

namespace Database\Seeders;

use App\Helpers\Enums\StatusEnum;
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
            'display_name'             => 'Admin',
            'product_type'             => 'tswd',
            'account'                  => 'admin',
            'password'                 => Hash::make(
                '15456123'
            ),
            'email'                    => 'cc711612@gmail.com',
            'tswd_account'             => config('services.tswd.account'),
            'tswd_token'               => config('services.tswd.token'),
            'cf_account'               => null,
            'cf_token'                 => null,
            'elasticsearch_connection' => 'http://172.234.90.209:9200',
            'elasticsearch_token'      => 'RGxfbEtwQUJqME1lSWlRdVU2YjM6MkNrN3VxQTVULVNoMkFQOXlIS2lTZw',
            'elasticsearch_index'      => 'test_log',
            'influx_db_connection'     => config('influxdb.host'),
            'influx_db_bucket'         => config('influxdb.bucket'),
            'influx_db_token'          => config('influxdb.token'),
            'influx_db_org'            => config('influxdb.org'),
        ]);

        app(SettingEntity::class)->create([
            'id'                                      => 1,
            'delay_minutes'                           => 15,
            'schedule_check_interval_minutes'         => 15,
            'task_completion_alert_threshold_minutes' => 15,
            'download_task_alert_threshold_minutes'   => 30,
            'domain_list_chuck'                       => 500,
            'slack_webhook_url'                       => env('SLACK_WEBHOOK_URL'),
            'email'                                   => 'cc711612@gmail.com',
        ]);

//        app(ExecuteScheduleEntity::class)->create(
//            [
//                'id'             => 1,
//                'user_id'        => 1,
//                'log_time_start' => '2024-05-31 20:05:00',
//                'log_time_end'   => '2024-05-31 20:10:00',
//                'status'         => StatusEnum::INITIAL->value,
//            ]);
//
//        app(ExecuteScheduleEntity::class)->create(
//            [
//                'id'             => 2,
//                'user_id'        => 1,
//                'log_time_start' => '2024-05-31 20:10:00',
//                'log_time_end'   => '2024-05-31 20:15:00',
//                'status'         => StatusEnum::INITIAL->value,
//            ]);
    }
}
