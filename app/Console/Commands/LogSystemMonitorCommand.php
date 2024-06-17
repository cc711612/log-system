<?php

namespace App\Console\Commands;

use App\Helpers\Enums\StatusEnum;
use App\Helpers\InfluxDB;
use App\Models\Downloads\Entities\DownloadEntity;
use App\Models\ExecuteSchedules\Entities\ExecuteScheduleEntity;
use App\Models\Settings\Entities\SettingEntity;
use App\Models\Users\Entities\UserEntity;
use App\Notifications\LogNoticeNotification;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogSystemMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:log_system_monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '監測系統 log 並新增執行排程';

    protected $setting;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /**
         * @var SettingEntity $setting
         */
        $this->setting = (new SettingEntity())->find(1);
        /**
         * @var UserEntity $userEntity
         */
        $userEntity = new UserEntity();
        $users = $userEntity->all();

        foreach ($users as $user) {
            $this->info('User: ' . $user->name);
            // 檢查 connection
            $this->checkConnection($user);
            // 檢查是否有執行排程
            $this->checkExecuteSchedules($user);
            // 檢查下載排程
            $this->checkDownloadSchedules($user);
        }
    }

    /**
     * 檢查連線
     *
     * @param UserEntity $user
     */
    public function checkConnection(UserEntity $user)
    {
        // 檢查 mysql 連線
        try {
            DB::connection()->getPdo();
            $this->info('MySQL connection OK');
        } catch (ConnectionException $e) {
            Log::error('MySQL connection failed: ' . $e->getMessage());
            $this->setting->notify(new LogNoticeNotification('MySQL connection failed: ' . $e->getMessage()));
        }

        if ($user->influx_db_connection) {
            // 檢查 influxdb 連線
            $influxDB = new InfluxDB(
                $user->influx_db_connection,
                $user->influx_db_token,
                $user->influx_db_org,
                $user->influx_db_bucket
            );

            if ($influxDB->isServiceRunning()) {
                $this->info('InfluxDB connection OK');
            } else {
                $this->info('InfluxDB connection failed');
                $this->setting->notify(new LogNoticeNotification('InfluxDB connection failed'));
                Log::error('userId:' . $user->id . ',InfluxDB connection failed');
            }
        }
    }

    /**
     * 檢查下載排程
     *
     * @param UserEntity $user
     */
    public function checkDownloadSchedules(UserEntity $user)
    {
        $timeoutDownloadSchedules = $this->getTimeoutDownloadSchedules($user->id);
        if ($timeoutDownloadSchedules->count() > 0) {
            $this->setting->notify(new LogNoticeNotification('download 排程已超過 ' . $this->setting->download_task_alert_threshold_minutes . ' 分鐘未完成，請確認是否有問題'));
        }
    }

    /**
     * 檢查是否有執行排程
     *
     * @param UserEntity $user
     */
    public function checkExecuteSchedules(UserEntity $user)
    {
        $lastExecuteSchedule = $this->getLastExecuteScheduleByUserId($user->id);
        if (
            !$lastExecuteSchedule
            || now()->diffInMinutes(Carbon::parse($lastExecuteSchedule->process_time_start)) > $this->setting->schedule_check_interval_minutes
        ) {
            $this->setting->notify(new LogNoticeNotification('ExecuteSchedule 執行排程已執行超過 ' . $this->setting->schedule_check_interval_minutes . ' 分鐘未完成，請確認是否有問題'));
        }
    }

    /**
     * 取得最後一次執行排程
     *
     * @param int $userId
     * @return ExecuteScheduleEntity
     */
    public function getLastExecuteScheduleByUserId(int $userId)
    {
        /**
         * @var ExecuteScheduleEntity $executeScheduleEntity
         */
        $executeScheduleEntity = app(ExecuteScheduleEntity::class);
        return $executeScheduleEntity
            ->where('user_id', $userId)
            ->orderBy('id')
            ->whereNotNull('process_time_start')
            ->whereNull('process_time_end')
            ->first();
    }

    /**
     * 取得超過時間的下載排程
     *
     * @param int $userId
     * @return Collection
     */
    public function getTimeoutDownloadSchedules(int $userId): Collection
    {
        /**
         * @var DownloadEntity $downloadEntity
         */
        $downloadEntity = app(DownloadEntity::class);

        return $downloadEntity
            ->where('user_id', $userId)
            ->where('status', StatusEnum::PROCESSING->value)
            ->where('created_at', '>', now()->subHour())
            ->where('updated_at', '<', now()->subMinutes($this->setting->download_task_alert_threshold_minutes))
            ->get();
    }
}
