<?php

namespace App\Console\Commands;

use App\Jobs\HandleDownloadJob;
use App\Models\CdnNetworks\Services\CdnNetworkService;
use App\Models\Downloads\Entities\DownloadEntity;
use App\Models\ExecuteSchedules\Entities\ExecuteScheduleEntity;
use App\Models\Settings\Entities\SettingEntity;
use App\Models\Users\Entities\UserEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

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

    private $cdnNetworkService;

    private $userEntities;

    private $userDomainList = [];

    protected $setting;

    protected $domainToServiceType = [];

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
                ->where('status', 'initial')
                ->get();

        // 取得設定
        $this->setting = app(SettingEntity::class)
            ->find(1);

        // 取得User
        $this->userEntities =
            app(UserEntity::class)
                ->whereIn('id', $executeScheduleEntities->pluck('user_id')->toArray())
                ->get()
                ->keyBy('id');

        $executeScheduleEntities->each(function ($executeScheduleEntity) {
            $this->startInfo(sprintf('ExecuteScheduleEntity id = %s', $executeScheduleEntity->id));
            $this->domainToServiceType = [];
            // 修改執行時間
             $executeScheduleEntity->update(["process_time_start" => now()->toDateTimeString(), "status" => "in progress"]);
             $executeScheduleEntity->update(["status" => "initial"]);

            $this->initCdnNetworkService($executeScheduleEntity->user_id);

            $this->startInfo('getDomainListOfControlGroup');
            $controlGroupByDomain = $this->getCdnNetworkService()->getDomainListOfControlGroup();
            $this->endInfo('getDomainListOfControlGroup');

            // 取得 Domain List
            foreach ($this->getDomainList($executeScheduleEntity->user_id) as $data) {
                $this->domainToServiceType[$data['domain-name']] = $data['service-type'];
            }
            // 500 個一組
            $userDomainLists = array_chunk(array_keys($this->domainToServiceType), Arr::get($this->setting, 'domain_list_chuck', 500));

            foreach ($userDomainLists as $domainLists) {
                $this->startInfo('getDownloadLinkByDomains');
                // 取得下載連結
                $downloadLinkLists =
                    $this->getCdnNetworkService()
                        ->getDownloadLinkByDomains(
                            $domainLists,
                            [
                                Arr::get($executeScheduleEntity, 'log_time_start'),
                                Arr::get($executeScheduleEntity, 'log_time_end'),
                            ]
                        );
                $this->endInfo('getDownloadLinkByDomains');

                if ($downloadLinkLists != false && empty($downloadLinkLists['logs']) == false) {
                    $this->startInfo('開始儲存 下載連結');
                    // 儲存下載連結
//                    $insertData = [];
                    $count = 1;
                    foreach ($downloadLinkLists['logs'] as $DomainLogData) {
                        $this->startInfo(sprintf('下載 %s 的 downloads',$DomainLogData['domainName']));
                        foreach ($DomainLogData['files'] as $DownloadLinks) {
                            $insertData =
                                [
                                    'user_id' => $executeScheduleEntity->user_id,
                                    'execute_schedule_id' => $executeScheduleEntity->id,
                                    'url' => Arr::get($DownloadLinks, 'logUrl'),
                                    'domain_name' => $DomainLogData['domainName'],
                                    'service_type' => Arr::get($this->domainToServiceType, $DomainLogData['domainName'], null),
                                    'control_group_name' => !empty($controlGroupByDomain[$DomainLogData['domainName']]) ? $controlGroupByDomain[$DomainLogData['domainName']]['controlGroupName'] : null,
                                    'control_group_code' => !empty($controlGroupByDomain[$DomainLogData['domainName']]) ? $controlGroupByDomain[$DomainLogData['domainName']]['controlGroupCode'] : null,
                                    'log_time_start' => $this->handleDateTimeFormat(Arr::get($DownloadLinks, 'dateFrom')),
                                    'log_time_end' => $this->handleDateTimeFormat(Arr::get($DownloadLinks, 'dateTo')),
                                    'type' => 'initial',
                                    'status' => 'initial',
                                ];
                            $DownloadEntity =
                                app(DownloadEntity::class)
                                    ->firstOrCreate([
                                        'user_id' => $executeScheduleEntity->user_id,
                                        'domain_name' => $DomainLogData['domainName'],
                                        'log_time_start' => $this->handleDateTimeFormat(Arr::get($DownloadLinks, 'dateFrom')),
                                        'log_time_end' => $this->handleDateTimeFormat(Arr::get($DownloadLinks, 'dateTo')),
                                        ],
                                        $insertData
                                    );

                            HandleDownloadJob::dispatch($DownloadEntity->id);
                            $this->startInfo(sprintf('第 %s 次 insert',$count));
                            $count++;
                        }
//                        app(DownloadEntity::class)
//                            ->insert($insertData);
//                        $insertData = [];
                    }
                    unset($count);
                    $this->startInfo('開始儲存 下載連結');
                } else {
//                    $executeScheduleEntity->update(['status' => 'failure']);
                    $this->errorInfo('getDownloadLinkByDomains 為空');
                }
                sleep(1);
            }

            //釋放不需要的資料
            unset($this->userDomainList[$executeScheduleEntity->user_id]);
            $this->endInfo(sprintf('ExecuteScheduleEntity id = %s', $executeScheduleEntity->id));
            sleep(5);
        });

        $this->endInfo(self::class);
    }

    /**
     * @return $this
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/30 下午5:38
     */
    private function initCdnNetworkService(int $user_id)
    {
        $userEntity = $this->userEntities->get($user_id);

        $this->cdnNetworkService =
            app(CdnNetworkService::class)
                ->setAccount(Arr::get($userEntity, 'tswd_account'))
                ->setToken(Arr::get($userEntity, 'tswd_token'));

        return $this;
    }

    /**
     * @Author  : steatng
     *
     * @DateTime: 2024/5/30 下午5:38
     */
    private function getCdnNetworkService(): CdnNetworkService
    {
        return $this->cdnNetworkService;
    }

    /**
     * @param int $user_id
     *
     * @return array|mixed
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/30 下午5:38
     */
    private function getDomainList(int $user_id)
    {
        if (isset($this->userDomainList[$user_id])) {
            return $this->userDomainList[$user_id];
        }

        $this->startInfo(debug_backtrace()[0]['function']);

        $domainLists =
            $this
                ->getCdnNetworkService()
                ->getDomainList();

        if (empty($domainLists) == true) {
            $this->errorInfo(debug_backtrace()[0]['function'] . ' 為空值');
            exit;
        } else {
            $this->endInfo(debug_backtrace()[0]['function']);
        }

        $this->userDomainList[$user_id] = $domainLists;

        return $domainLists;
    }

    /**
     * @return string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/30 下午5:38
     */
    private function handleDateTimeFormat($dateString)
    {
        // 解析原始字串
        $year = substr($dateString, 0, 4);
        $month = substr($dateString, 5, 2);
        $day = substr($dateString, 8, 2);
        $hour = substr($dateString, 11, 2);
        $minute = substr($dateString, 13, 2);

        // 創建 Carbon 實例
        $date = Carbon::create($year, $month, $day, $hour, $minute, 0);

        // 返回格式化的字串
        return $date->format('Y-m-d H:i:s');
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

    /**
     * @Author  : steatng
     *
     * @DateTime: 2024/5/30 下午5:39
     */
    private function errorInfo($name)
    {
        $this->line(str_replace(
            ['{class}', '{date_time}'],
            [$name, now()->toDateTimeString()],
            '{class} 執行結束 {date_time}'
        ));
    }
}
