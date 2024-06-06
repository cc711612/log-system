<?php

namespace App\Console\Commands;

use App\Models\ExecuteSchedules\Entities\ExecuteScheduleEntity;
use App\Models\Settings\Entities\SettingEntity;
use App\Models\Users\Entities\UserEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class CreateExecuteScheduleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:create_execute_schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '建立執行列表';

    protected $setting;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 取得 user
        $userEntities =
            app(UserEntity::class)
                ->get();

        // 取得設定
        $this->setting = app(SettingEntity::class)
            ->find(1);

        // 取得執行區間
        $minute_range = Arr::get($this->setting, 'delay_minutes', 5);
        $timeRange = $this->handleStartEnd($minute_range);

        $userEntities->each(function (UserEntity $userEntity) use ($timeRange) {
            app(ExecuteScheduleEntity::class)
                ->firstOrCreate(
                    [
                        'user_id'            => $userEntity->id,
                        'log_time_start'     => Arr::get($timeRange, 'start_at'),
                        'log_time_end'       => Arr::get($timeRange, 'end_at'),
//                        'status'             => 'initial',
                    ],
                    [
                        'user_id'            => $userEntity->id,
                        'log_time_start'     => Arr::get($timeRange, 'start_at'),
                        'log_time_end'       => Arr::get($timeRange, 'end_at'),
                        'status'             => 'initial',
                        'process_time_start' => null,
                        'process_time_end'   => null,
                    ]
                );
        });

//        $this->call('command:handle_execute_schedule');
    }

    private function handleStartEnd($range)
    {
        $roundedTime = Carbon::now()->subMinutes($range);

        // 取整到每個五分鐘的整點
        $minute = floor($roundedTime->minute / $range) * $range;
        $roundedTime->setMinute($minute);
        $roundedTime->setSecond(0);

        return [
            'end_at' => $roundedTime->format('Y-m-d H:i:00'),
            'start_at'   => $roundedTime->subMinutes($range)->format('Y-m-d H:i:00'),
        ];
    }
}
