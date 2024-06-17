<?php

namespace App\Jobs;

use App\Helpers\Enums\StatusEnum;
use App\Models\CdnNetworks\Services\CdnNetworkService;
use App\Models\Downloads\Entities\DownloadEntity;
use App\Models\ExecuteSchedules\Entities\ExecuteScheduleEntity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class HandleExecuteScheduleJob
 *
 * @package App\Jobs
 * @Author  : steatng
 * @DateTime: 2024/6/6 下午2:22
 */
class HandleExecuteScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;

    public $execute_schedule_id;
    public $account;
    public $token;
    public $setting_domain_list_chunk;
    public $cdnNetworkService;

    /**
     * HandleExecuteScheduleJob constructor.
     *
     * @param $execute_schedule_id
     * @param $account
     * @param $token
     * @param $setting_domain_list_chuck
     *
     * @Author  : steatng
     * @DateTime: 2024/6/6 下午2:22
     */
    public function __construct(
        $execute_schedule_id,
        $account,
        $token,
        $setting_domain_list_chuck
    )
    {
        $this->onQueue('handle_execute_schedule');

        $this->execute_schedule_id = $execute_schedule_id;
        $this->account = $account;
        $this->token = $token;
        $this->setting_domain_list_chunk = $setting_domain_list_chuck;
    }

    /**
     * @Author  : steatng
     * @DateTime: 2024/6/6 下午2:22
     */
    public function handle()
    {
        ini_set('max_execution_time', $this->timeout);

        $executeScheduleEntity =
            app(ExecuteScheduleEntity::class)
                ->find($this->execute_schedule_id);

        $this->startInfo(sprintf('ExecuteScheduleEntity id = %s', $this->execute_schedule_id));
        // 修改執行時間
        $executeScheduleEntity->update(["process_time_start" => now()->toDateTimeString(), "status" => StatusEnum::PROCESSING->value]);

        $this->initCdnNetworkService();
        $domainToServiceType = $this->getDomainToServiceType();
        $controlGroupByDomain = $this->getControlGroupByDomain();

        // 500 個一組
        $userDomainLists = array_chunk(array_keys($domainToServiceType), $this->setting_domain_list_chunk);

        foreach ($userDomainLists as $domainLists) {
            // 取得下載連結
            $downloadLinkLists =
                $this->getDownloadLinkLists(
                    $domainLists,
                    Arr::get($executeScheduleEntity, 'log_time_start'),
                    Arr::get($executeScheduleEntity, 'log_time_end')
                );

            if ($downloadLinkLists != false && empty($downloadLinkLists['logs']) == false) {
                $this->startInfo('開始儲存 下載連結');
                // 儲存下載連結
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
                                'service_type' => Arr::get($domainToServiceType, $DomainLogData['domainName'], null),
                                'control_group_name' => !empty($controlGroupByDomain[$DomainLogData['domainName']]) ? $controlGroupByDomain[$DomainLogData['domainName']]['controlGroupName'] : null,
                                'control_group_code' => !empty($controlGroupByDomain[$DomainLogData['domainName']]) ? $controlGroupByDomain[$DomainLogData['domainName']]['controlGroupCode'] : null,
                                'log_time_start' => $this->handleDateTimeFormat(Arr::get($DownloadLinks, 'dateFrom')),
                                'log_time_end' => $this->handleDateTimeFormat(Arr::get($DownloadLinks, 'dateTo'), 59),
                                'type' => StatusEnum::INITIAL->value,
                                'status' => StatusEnum::INITIAL->value,
                            ];
                        $DownloadEntity =
                            app(DownloadEntity::class)
                                ->firstOrCreate(
                                    Arr::only($insertData, ["user_id", "domain_name", "log_time_start", "log_time_end"]) ,
                                    $insertData
                                );

                        HandleDownloadJob::dispatch($DownloadEntity->id);
                        $this->startInfo(sprintf('第 %s 次 insert',$count));
                        $count++;
                    }
                }
                unset($count);
                $this->startInfo('開始儲存 下載連結');
            } else {
                $this->errorInfo('getDownloadLinkByDomains 回傳資料為空');
            }
            sleep(1);
        }

        $this->endInfo(sprintf('ExecuteScheduleEntity id = %s', $executeScheduleEntity->id));
    }

    /**
     * @return $this
     * @Author  : steatng
     * @DateTime: 2024/6/6 下午2:22
     */
    private function initCdnNetworkService()
    {
        $this->cdnNetworkService =
            app(CdnNetworkService::class)
                ->setAccount($this->account)
                ->setToken($this->token);

        return $this;
    }

    /**
     * @return \App\Models\CdnNetworks\Services\CdnNetworkService
     * @Author  : steatng
     * @DateTime: 2024/6/6 下午2:22
     */
    private function getCdnNetworkService(): CdnNetworkService
    {
        return $this->cdnNetworkService;
    }

    /**
     * @return array
     * @Author  : steatng
     * @DateTime: 2024/6/6 下午2:22
     */
    private function getDomainToServiceType()
    {
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

        $domainToServiceType = [];

        // 取得 Domain List
        foreach ($domainLists as $data) {
            $domainToServiceType[$data['domain-name']] = $data['service-type'];
        }

        return $domainToServiceType;
    }

    /**
     * @return array
     * @Author  : steatng
     * @DateTime: 2024/6/6 下午2:22
     */
    private function getControlGroupByDomain()
    {
        $this->startInfo(debug_backtrace()[0]['function']);

        $controlGroupByDomain = $this->getCdnNetworkService()->getDomainListOfControlGroup();

        $this->endInfo(debug_backtrace()[0]['function']);

        return $controlGroupByDomain;
    }

    /**
     * @param $domainLists
     * @param $start_at
     * @param $end_at
     *
     * @return array
     * @Author  : steatng
     * @DateTime: 2024/6/6 下午2:22
     */
    private function getDownloadLinkLists(
        $domainLists,
        $start_at,
        $end_at
    )
    {
        $this->startInfo(debug_backtrace()[0]['function']);

        // 取得下載連結
        $downloadLinkLists =
            $this->getCdnNetworkService()
                ->getDownloadLinkByDomains(
                    $domainLists,
                    [
                        $start_at,
                        $end_at,
                    ]
                );

        $this->endInfo(debug_backtrace()[0]['function']);

        return $downloadLinkLists;
    }

    /**
     * @param     $dateString
     * @param int $second
     *
     * @return string
     * @Author  : steatng
     * @DateTime: 2024/6/7 下午4:19
     */
    private function handleDateTimeFormat($dateString, $second = 0)
    {
        // 解析原始字串
        $year = substr($dateString, 0, 4);
        $month = substr($dateString, 5, 2);
        $day = substr($dateString, 8, 2);
        $hour = substr($dateString, 11, 2);
        $minute = substr($dateString, 13, 2);

        // 創建 Carbon 實例
        $date = Carbon::create($year, $month, $day, $hour, $minute, $second);

        // 返回格式化的字串
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param $name
     *
     * @return $this
     * @Author  : steatng
     * @DateTime: 2024/6/6 下午2:23
     */
    private function startInfo($name)
    {
        Log::channel('execute_schedule')->info(str_replace(
            ['{class}', '{date_time}', '{execute_schedule_id}'],
            [$name, now()->toDateTimeString(), $this->execute_schedule_id],
            'execute_schedule_id {execute_schedule_id} {class} 開始執行 {date_time}'
        ));

        return $this;
    }

    /**
     * @param $name
     *
     * @return $this
     * @Author  : steatng
     * @DateTime: 2024/6/6 下午2:23
     */
    private function endInfo($name)
    {
        Log::channel('execute_schedule')->info(str_replace(
            ['{class}', '{date_time}', '{execute_schedule_id}'],
            [$name, now()->toDateTimeString(), $this->execute_schedule_id],
            'execute_schedule_id {execute_schedule_id} {class} 執行結束 {date_time}'
        ));

        return $this;
    }

    /**
     * @param $name
     *
     * @Author  : steatng
     * @DateTime: 2024/6/6 下午2:23
     */
    private function errorInfo($name)
    {
        Log::channel('execute_schedule')->alert(str_replace(
            ['{class}', '{date_time}', '{execute_schedule_id}'],
            [$name, now()->toDateTimeString(), $this->execute_schedule_id],
            'execute_schedule_id {execute_schedule_id} {class} 執行結束 {date_time}'
        ));
    }
}
