<?php

namespace App\Console\Commands;

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
 * @package App\Console\Commands
 * @Author  : steatng
 * @DateTime: 2024/5/30 下午5:38
 */
class HandleExecuteScheduleCommand extends Command
{
    /**
     * @var string
     * @Author  : steatng
     * @DateTime: 2024/5/30 下午5:38
     */
    protected $signature = 'command:handle_execute_schedule';

    /**
     * @var string
     * @Author  : steatng
     * @DateTime: 2024/5/30 下午5:38
     */
    protected $description = '處理 execute_schedule ';

    private $CdnNetworkService;
    private $UserEntities;
    private $UserDomainList = [];
    protected $Setting;
    protected $DomainToServiceType = [];

    /**
     * @Author  : steatng
     * @DateTime: 2024/5/30 下午5:38
     */
    public function handle()
    {
        $this->startInfo(self::class);

        # 取得列表
        $ExecuteScheduleEntities =
            app(ExecuteScheduleEntity::class)
                ->where("status", "initial")
                ->get()
            ;

        # 取得設定
        $this->Setting = app(SettingEntity::class)
            ->find(1);

        # 取得User
        $this->UserEntities =
            app(UserEntity::class)
                ->whereIn("idx", $ExecuteScheduleEntities->pluck("user_idx")->toArray())
                ->get()
                ->keyBy("idx")
        ;

        $ExecuteScheduleEntities->each(function ($ExecuteScheduleEntity) {
            $this->startInfo(sprintf('ExecuteScheduleEntity id = %s',$ExecuteScheduleEntity->id));
            $this->DomainToServiceType = [];
            # 修改執行時間
            $ExecuteScheduleEntity->update(["process_time_start" => now()->toDateTimeString(), "status" => "in progress"]);
//            $ExecuteScheduleEntity->update(["status" => "initial"]);

            $this->initCdnNetworkService($ExecuteScheduleEntity->user_idx);

            # 取得 Domain List
            foreach ($this->getDomainList($ExecuteScheduleEntity->user_idx) as $data){
                $this->DomainToServiceType[$data['domain-name']] = $data['service-type'];
            }
//
//            $this->DomainToServiceType = ['sddolo.win1167.com' => 1115];
//            # 500 個一組
            $UserDomainLists = array_chunk(array_keys($this->DomainToServiceType), Arr::get($this->Setting,'domain_list_chuck',500));

            foreach ($UserDomainLists as $DomainLists){
                $this->startInfo("getDownloadLinkByDomains");
                # 取得下載連結
                $DownloadLinkLists =
                    $this->getCdnNetworkService()
                        ->getDownloadLinkByDomains(
                            $DomainLists,
                            [
                                Arr::get($ExecuteScheduleEntity, "log_time_start"),
                                Arr::get($ExecuteScheduleEntity, "log_time_end")
                            ]
                        );
                $this->endInfo("getDownloadLinkByDomains");

                if ($DownloadLinkLists != false && empty($DownloadLinkLists['logs']) == false){
                    # 儲存下載連結
                    $InsertData = [];
                    foreach ($DownloadLinkLists['logs'] as $DomainLogData){
                        foreach ($DomainLogData['files'] as $DownloadLinks){
                            $InsertData[] = [
                                "user_idx" => $ExecuteScheduleEntity->user_idx,
                                "url" => Arr::get($DownloadLinks, 'logUrl'),
                                "domain_name" => $DomainLogData["domainName"],
                                "service_type" =>Arr::get($this->DomainToServiceType,$DomainLogData["domainName"],null),
                                "log_time_start" => $this->handleDateTimeFormat(Arr::get($DownloadLinks, 'dateFrom')),
                                "log_time_end" => $this->handleDateTimeFormat(Arr::get($DownloadLinks, 'dateTo')),
                                "type" => "initial",
                                "status" => "initial"
                            ];
                        }

                        app(DownloadEntity::class)
                            ->insert($InsertData);

                        $InsertData = [];
                    }
                } else {
                    $this->errorInfo("getDownloadLinkByDomains 異常");
                }
            }

            #釋放不需要的資料
            unset($this->UserDomainList[$ExecuteScheduleEntity->user_idx]);
            $this->endInfo(sprintf('ExecuteScheduleEntity id = %s',$ExecuteScheduleEntity->id));
        });

        $this->endInfo(self::class);
    }

    /**
     * @param $user_idx
     *
     * @return $this
     * @Author  : steatng
     * @DateTime: 2024/5/30 下午5:38
     */
    private function initCdnNetworkService($user_idx)
    {
        $UserEntity = $this->UserEntities->get($user_idx);

        $this->CdnNetworkService =
            app(CdnNetworkService::class)
                ->setAccount(Arr::get($UserEntity, 'tswd_account'))
                ->setToken(Arr::get($UserEntity, 'tswd_token'));

        return $this;
    }

    /**
     * @return \App\Models\CdnNetworks\Services\CdnNetworkService
     * @Author  : steatng
     * @DateTime: 2024/5/30 下午5:38
     */
    private function getCdnNetworkService() : CdnNetworkService
    {
        return $this->CdnNetworkService;
    }

    /**
     * @param $user_idx
     *
     * @return array|mixed
     * @Author  : steatng
     * @DateTime: 2024/5/30 下午5:38
     */
    private function getDomainList($user_idx)
    {
        if (isset($this->UserDomainList[$user_idx])){
            return $this->UserDomainList[$user_idx];
        }

        $this->startInfo(debug_backtrace()[0]['function']);

        $DomainLists =
            $this
                ->getCdnNetworkService()
                ->getDomainList();

        if (empty($DomainLists) == true){
            $this->errorInfo(debug_backtrace()[0]['function'] . "為空值");
            exit;
        } else {
            $this->endInfo(debug_backtrace()[0]['function']);
        }


        $this->UserDomainList[$user_idx] = $DomainLists;

        return $DomainLists;
    }

    /**
     * @param $dateString
     *
     * @return string
     * @Author  : steatng
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
     * @param $name
     *
     * @return $this
     * @Author  : steatng
     * @DateTime: 2024/5/30 下午5:39
     */
    private function startInfo($name)
    {
        $this->info(str_replace(
            ["{class}", "{date_time}"],
            [$name, now()->toDateTimeString()],
            "{class} 開始執行 {date_time}"
        ));

        return $this;
    }

    /**
     * @param $name
     *
     * @return $this
     * @Author  : steatng
     * @DateTime: 2024/5/30 下午5:39
     */
    private function endInfo($name)
    {
        $this->info(str_replace(
            ["{class}", "{date_time}"],
            [$name, now()->toDateTimeString()],
            "{class} 執行結束 {date_time}"
        ));

        return $this;
    }

    /**
     * @param $name
     *
     * @Author  : steatng
     * @DateTime: 2024/5/30 下午5:39
     */
    private function errorInfo($name)
    {
        $this->error(str_replace(
            ["{class}", "{date_time}"],
            [$name, now()->toDateTimeString()],
            "{class} 執行結束 {date_time}"
        ));
    }
}
