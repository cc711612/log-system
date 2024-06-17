<?php

namespace App\Console\Commands;

use App\Helpers\Enums\StatusEnum;
use App\Jobs\HandleExecuteScheduleJob;
use App\Models\ExecuteSchedules\Entities\ExecuteScheduleEntity;
use App\Models\Settings\Entities\SettingEntity;
use App\Models\Users\Entities\UserEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

/**
 * Class HandleExecuteScheduleCommand
 *
 * @Author  : steatng
 *
 * @DateTime: 2024/5/30 下午5:38
 */
class HandleExecuteScheduleCommand extends Command
{
    /**
     * @var string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/30 下午5:38
     */
    protected $signature = 'command:handle_execute_schedule';

    /**
     * @var string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/30 下午5:38
     */
    protected $description = '處理 execute_schedule ';

    /**
     * @Author  : steatng
     *
     * @DateTime: 2024/5/30 下午5:38
     */
    public function handle()
    {
        $this->startInfo(self::class);

        // 取得列表
        $executeScheduleEntities =
            app(ExecuteScheduleEntity::class)
                ->where('status', StatusEnum::INITIAL->value)
                ->get();

        // 取得設定
        $setting = app(SettingEntity::class)
            ->find(1);

        // 取得User
        $userEntities =
            app(UserEntity::class)
                ->whereIn('id', $executeScheduleEntities->pluck('user_id')->toArray())
                ->get()
                ->keyBy('id');

        $executeScheduleEntities->each(function ($executeScheduleEntity) use($userEntities, $setting) {
            $userEntity = $userEntities->get($executeScheduleEntity->user_id);

            HandleExecuteScheduleJob::dispatch(
                $executeScheduleEntity->id,
                Arr::get($userEntity, 'tswd_account'),
                Arr::get($userEntity, 'tswd_token'),
                Arr::get($setting, 'domain_list_chuck', 500)
            );
            $executeScheduleEntity->status = StatusEnum::QUEUE->value;
            $executeScheduleEntity->save();
        });

        $this->endInfo(self::class);
    }

    /**
     * @return $this
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/30 下午5:39
     */
    private function startInfo($name)
    {
        $this->info(str_replace(
            ['{class}', '{date_time}'],
            [$name, now()->toDateTimeString()],
            '{class} 開始執行 {date_time}'
        ));

        return $this;
    }

    /**
     * @return $this
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/30 下午5:39
     */
    private function endInfo($name)
    {
        $this->info(str_replace(
            ['{class}', '{date_time}'],
            [$name, now()->toDateTimeString()],
            '{class} 執行結束 {date_time}'
        ));

        return $this;
    }
}
