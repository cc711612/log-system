<?php

namespace Database\Seeders;

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
            'display_name' => 'Admin',
            'product_type' => 'tswd',
            'account' => 'admin',
            'password' => Hash::make(
                '15456123'
            ),
            'tswd_account' => config('services.tswd.account'),
            'tswd_token' => config('services.tswd.token'),
            'cf_account' => null,
            'cf_token' => null,
            'influx_db_connection' => config('influxdb.host'),
            'influx_db_bucket' => config('influxdb.bucket'),
            'influx_db_token' => config('influxdb.token'),
        ]);

        app(SettingEntity::class)->create([
            'id' => 1,
            'delay_minutes' => 5,
            'schedule_check_interval_minutes' => 15,
            'task_completion_alert_threshold_minutes' => 15,
            'download_task_alert_threshold_minutes' => 30,
            'domain_list_chuck' => 500,
        ]);
    }
}
