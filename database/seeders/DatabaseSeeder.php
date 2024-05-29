<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Users\Entities\UserEntity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Str;

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
            'password' =>  Hash::make(
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
    }
}
