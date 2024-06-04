<?php

namespace App\Console\Commands;

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

    protected SettingEntity $setting;

    public function __construct()
    {
        parent::__construct();
        $this->setting = (new SettingEntity())->find(1);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
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
            $user->notify(new LogNoticeNotification('MySQL connection failed: ' . $e->getMessage()));
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
                $user->notify(new LogNoticeNotification('InfluxDB connection failed'));
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
            $user->notify(new LogNoticeNotification('下載排程已超過 ' . $this->setting->download_task_alert_threshold_minutes . ' 分鐘未完成，請確認是否有問題'));
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
            || now()->diffInMinutes(Carbon::parse($lastExecuteSchedule->log_time_end)) > $this->setting->schedule_check_interval_minutes
        ) {
            $user->notify(new LogNoticeNotification('會員執行排程已停止，請確認是否有問題'));
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
            ->orderBy('log_time_end', 'desc')
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
            ->where('status', 'in progress')
            ->where('created_at', '<', now()->subMinutes($this->setting->download_task_alert_threshold_minutes))
            ->get();
    }
}
