<?php

namespace App\Models\CdnNetworks\Services;

use App\Exceptions\LogProcessException;
use App\Helpers\CDNNetwork;
use App\Helpers\Enums\StatusEnum;
use App\Helpers\LogParser;
use App\Models\Downloads\Entities\DownloadEntity;
use App\Models\ExecuteSchedules\Entities\ExecuteScheduleEntity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CdnNetworkService
{
    /**
     * 下載位置
     *
     * @var string
     */
    public $driver = 'local';

    /**
     * account
     *
     * @var string
     */
    public $account;

    public $status = true;

    /**
     * token
     *
     * @var string
     */
    public $token;

    private $influxDBService;

    private $elasticsearchService;

    public function setAccount(string $account)
    {
        $this->account = $account;

        return $this;
    }

    public function setToken(string $token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * 取得域名列表
     *
     * @return array
     */
    public function getDomainList()
    {
        /**
         * @var CDNNetwork
         */
        $cdnNetwork = app(CDNNetwork::class);

        return
            json_decode(
                $cdnNetwork
                    ->setUsername($this->account)
                    ->setApiKey($this->token)
                    ->setDateTime()
                    ->getDomainList(),
                1
            );
    }

    /**
     * 取得日誌文件路徑
     *
     * @return array
     */
    public function getDownloadLinkByDomains(array $domains, array $timeRanges = [])
    {
        /**
         * @var CDNNetwork
         */
        $cdnNetwork = app(CDNNetwork::class);
        if (empty($timeRanges)) {
            $timeRanges = $this->getNowTimeRanges();
        }

        return json_decode(
            $cdnNetwork
                ->setUsername($this->account)
                ->setApiKey($this->token)
                ->setDateTime()
                ->getReportDownLoadLink(
                    $timeRanges[0],
                    $timeRanges[1],
                    $domains
                ),
            1
        );
    }

    /**
     * 取得控制組的域名列表
     *
     * @return array
     */
    public function getDomainListOfControlGroup(array $controlGroupCode = [])
    {
        /**
         * @var CDNNetwork
         */
        $cdnNetwork = app(CDNNetwork::class);
        $controlGroups = json_decode(
            $cdnNetwork
                ->setUsername($this->account)
                ->setApiKey($this->token)
                ->setDateTime()
                ->getCGDomainList($controlGroupCode),
            1
        );
        $controlGroups = $controlGroups['msg'] == 'success' ? $controlGroups['data']['controlGroupDetail'] : [];
        $result = [];
        foreach ($controlGroups as $controlGroup) {
            foreach ($controlGroup['domainList'] as $domain) {
                $result[$domain][] = [
                    'controlGroupCode' => $controlGroup['controlGroupCode'],
                    'controlGroupName' => $controlGroup['controlGroupName'],
                    'domain' => $domain,
                ];
            }
        }
        // 整理資料
        foreach ($result as $domain => $values) {
            $controlGroupCodes = array_unique(array_column($values, 'controlGroupCode'));
            $controlGroupName = array_unique(array_column($values, 'controlGroupName'));
            $result[$domain] = [
                'controlGroupCode' => implode(",", $controlGroupCodes),
                'controlGroupName' => implode(",", $controlGroupName)
            ];
        }
        return $result;
    }

    /**
     * 根據下載鏈接處理日誌
     *
     * @param \App\Models\Downloads\Entities\DownloadEntity $download
     *
     * @throws \App\Exceptions\LogFileExtensionException
     */
    public function processLogByDownload(DownloadEntity $download)
    {
        /**
         * @var LogParser
         */
        $logParser = app(LogParser::class);
        /**
         * @var ElasticsearchService
         */
        $this->elasticsearchService = new ElasticsearchService(
            $download->users->elasticsearch_connection,
            $download->users->elasticsearch_token,
            $download->users->elasticsearch_index
        );
        try {
            $download = $this->updateDownLoad($download, ['pid' => getmypid(), "type" => "download"]);
            $downloadLink = $download->url;
            $parsedUrl = parse_url($downloadLink);
            // 提取 URL 路徑部分
            $path = $parsedUrl['path'];
            // 從路徑中提取文件名
            $fileName = basename($path);
            $response = Http::retry(5, 1000)->withHeaders(['Accept-Encoding' => 'gzip,deflate'])->get($downloadLink);
            if ($response->successful()) {
                // 將日誌內容存儲到本地文件
                Storage::disk($this->driver)->put($fileName, $response->body());
                // 讀取日誌文件並分析
                $zipFilePath = Storage::disk($this->driver)->path($fileName);
                $fileInfo = pathinfo($fileName);
                // 如果是.gz格式，則解壓縮
                if ($fileInfo['extension'] == 'gz') {
                    $download = $this->updateDownLoad($download, ["type" => "gunzip"]);
                    system(sprintf("gunzip -f %s", $zipFilePath));
                } else {
                    $this->status = false;
                    Log::channel('download')->error('檔案格式錯誤，只支持.gz格式的日誌文件，檔案格式為:' . $fileInfo['extension']);
                }

                if (
                    $this->status &&
                    Storage::disk($this->driver)->exists($fileInfo['filename'])
                ) {
                    // 解析每一行日誌條目
                    $download = $this->updateDownLoad($download, ["type" => "parse"]);

                    $count = 0;
                    $logs = [];
                    foreach (File::lines(Storage::disk($this->driver)->path($fileInfo['filename'])) as $line) {
                        if ($line == "") {
                            break;
                        }

                        $log = $logParser->parseLogEntry($line, $download->service_type);
                        if (empty($log)) {
                            $this->status = false;
                            Log::error($download->service_type . " download->service_type " . $line);
                            continue;
                        }

                        $log['servicetype'] = $download->service_type;
                        $log['servicegroup'] = $download->control_group_name;

                        array_push($logs, $log);

                        $count++;
                        if ($count >= config('elasticsearch.insertCount')) {
                            $this->insertElasticsearch($logs);
                            $count = 0;
                            $logs = [];
                        }
                    }

                    if (empty($logs) == false) {
                        $this->insertElasticsearch($logs);
                    }
                }

                if ($this->status) {
                    $download = $this->updateDownLoad($download, ["type" => "done", "status" => StatusEnum::SUCCESS->value]);
                } else {
                    $this->updateDownLoad($download, ["status" => StatusEnum::FAILURE->value]);
                }

                # 檢查執行的 execute_schedule_id 是否為最後一筆
                $downloadStatusEntities =
                    app(DownloadEntity::class)
                    ->selectRaw("DISTINCT status")
                    ->where("execute_schedule_id", $download->execute_schedule_id)
                    ->get()
                    ->pluck("status");

                if ($downloadStatusEntities->contains("in process") == false) {
                    app(ExecuteScheduleEntity::class)
                        ->find($download->execute_schedule_id)
                        ->update([
                            'status' => ($downloadStatusEntities->contains(StatusEnum::FAILURE->value) == false) ? StatusEnum::SUCCESS->value : StatusEnum::FAILURE->value,
                            'process_time_end' => now()->toDateTimeString()
                        ]);
                }
            }
            // 刪除解壓後的文件
            Storage::disk($this->driver)->delete($fileInfo['filename']);
        } catch (LogProcessException $e) {
            $download->status = StatusEnum::FAILURE->value;
            $download->error_message = $e->getMessage();
            $download->save();
            Log::error($e->getMessage());
        }
    }

    /**
     * 客製化時間規則
     *
     * @return array
     */
    public function getNowTimeRanges()
    {
        $roundedTime = Carbon::now()->subMinutes(5);

        // 取整到每個五分鐘的整點
        $minute = floor($roundedTime->minute / 5) * 5;
        $roundedTime->setMinute($minute);
        $roundedTime->setSecond(0);

        return [
            $roundedTime->format('Y-m-d H:i:00'),
            $roundedTime->addMinutes(5)->format('Y-m-d H:i:00'),
        ];
    }

    /**
     * @param \App\Models\Downloads\Entities\DownloadEntity $downloadEntity
     * @param array                                         $params
     *
     * @return \App\Models\Downloads\Entities\DownloadEntity
     * @Author  : steatng
     * @DateTime: 2024/5/31 下午9:22
     */
    private function updateDownLoad(DownloadEntity $downloadEntity, array $params): DownloadEntity
    {
        foreach ($params as $index => $value) {
            $downloadEntity->$index = $value;
        }

        $downloadEntity->save();

        return $downloadEntity;
    }

    /**
     * @param     $logs
     * @param int $count
     *
     * @return bool
     * @Author  : steatng
     * @DateTime: 2024/6/2 下午10:02
     */
    private function insertInfluxDB($logs, $count = 0)
    {
        try {
            $this->influxDBService->insertLogs($logs);
            return true;
        } catch (\Exception $exception) {
            if ($count == 3) {
                $this->status = false;
                return false;
            }
            $count++;
            Log::channel('influxdb')->info(sprintf("pid:%s , 第%s次新增失敗", getmypid(), $count));
            Log::channel('influxdb')->error($exception->getMessage());
            sleep(config('influxdb.sleep'));
            return $this->insertInfluxDB($logs, $count);
        }
    }

    private function insertElasticsearch($logs, $count = 0)
    {
        try {
            $this->elasticsearchService->insertLogs($logs);
            return true;
        } catch (\Exception $exception) {
            if ($count == 3) {
                $this->status = false;
                return false;
            }
            $count++;
            Log::channel('elasticsearch')->info(sprintf("pid:%s , 第%s次新增失敗", getmypid(), $count));
            Log::channel('elasticsearch')->error($exception->getMessage());
            sleep(config('elasticsearch.sleep'));
            return $this->insertElasticsearch($logs, $count);
        }
    }
}
