<?php

namespace App\Console\Commands;

use App\Models\ExecuteSchedules\Entities\ExecuteScheduleEntity;
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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        # 取得 user
        $UserEntities =
            app(UserEntity::class)
                ->get();

        # 取得執行區間
        $minute_range = 5;
        $TimeRange = $this->handleStartEnd($minute_range);

        $UserEntities->each(function (UserEntity $UserEntity) use ($TimeRange){
            app(ExecuteScheduleEntity::class)
                ->create([
                    "user_id" => $UserEntity->id,
                    "log_time_start" => Arr::get($TimeRange, 'start_at'),
                    "log_time_end" => Arr::get($TimeRange, 'end_at'),
                    "status" => "initial",
                    "process_time_start" => null,
                    "process_time_end" => null
                ]);
        });
    }

    private function handleStartEnd($range)
    {
        $roundedTime = Carbon::now()->subMinutes($range);

        // 取整到每個五分鐘的整點
        $minute = floor($roundedTime->minute / $range) * $range;
        $roundedTime->setMinute($minute);
        $roundedTime->setSecond(0);

        return [
            "start_at" => $roundedTime->format('Y-m-d H:i:00'),
            "end_at" => $roundedTime->addMinutes($range)->format('Y-m-d H:i:00'),
        ];
    }
}
