<?php

namespace App\Models\CdnNetworks\Services;

use App\Exceptions\LogFileExtensionException;
use App\Exceptions\LogProcessException;
use App\Helpers\CDNNetwork;
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

    /**
     * token
     *
     * @var string
     */
    public $token;

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
         * @var InfluxDBService
         */
        $influxDBService = new InfluxDBService($download->users->influx_db_connection, $download->users->influx_db_token, $download->users->influx_db_org, $download->users->influx_db_bucket);
        try {
            $downloadLink = $download->url;
            $parsedUrl = parse_url($downloadLink);
            // 提取 URL 路徑部分
            $path = $parsedUrl['path'];
            // 從路徑中提取文件名
            $fileName = basename($path);
            $download = $this->updateDownLoad($download, ["type" => "download"]);
            $response = Http::withHeaders(['Accept-Encoding' => 'gzip,deflate'])->get($downloadLink);
            if ($response->successful()) {
                // 將日誌內容存儲到本地文件
                Storage::disk($this->driver)->put($fileName, $response->body());
                // 讀取日誌文件並分析
                $zipFilePath = Storage::disk($this->driver)->path($fileName);
                $fileInfo = pathinfo($fileName);
                // 如果是.gz格式，則解壓縮
                if ($fileInfo['extension'] == 'gz') {
                    $download = $this->updateDownLoad($download, ["type" => "gunzip"]);
                    system(sprintf("gunzip %s",$zipFilePath));
//                    $uncompressedData = gzdecode($compressedData);
//                    $uncompressedFileName = $fileInfo['filename'];
//                    // 存儲解壓後的文件
//                    Storage::disk($this->driver)->put($uncompressedFileName, $uncompressedData);
//                    // 刪除壓縮文件
//                    Storage::disk($this->driver)->delete($fileName);
                } else {
                    throw new LogFileExtensionException('檔案格式錯誤，只支持.gz格式的日誌文件，檔案格式為:' . $fileInfo['extension']);
                }
                // 讀取解壓後的日誌文件
//                $uncompressedLogs = Storage::disk($this->driver)->get($fileInfo['filename']);
                // 按行分割日誌
//                $logLines = array_filter(explode("\n", $uncompressedLogs));

                print '解析每一行日誌條目';
                print PHP_EOL;
                // 解析每一行日誌條目
                $download = $this->updateDownLoad($download, ["type" => "parse"]);
                print '迴圈開始';
                print PHP_EOL;
                $count = 0;
                $logs = [];
                foreach (File::lines(Storage::disk($this->driver)->path($fileInfo['filename'])) as $line ){
                    if($line == "") {
                        break;
                    }
                    try {
                        $log = $logParser->parseLogEntry($line, $download->service_type);
                        $log = $influxDBService->handleLogFormat($log);

                        array_push($logs, $log);
                    } catch (\Exception $exception) {
                        Log::error($download->service_type." download->service_type ".$line);
                        Log::error($exception->getMessage());
                    }
                    $count++;
                    if($count > 1000){
                        print '寫入influx db';
                        print PHP_EOL;
                        Log::driver('influxdb')->info('downloadId:'.$download->id.' count:'.$count);
                        $influxDBService->insertLogs($logs);
                        $count = 0;
                        $logs = [];
                    }
                }
                // 批量插入數據庫或其他操作
//                if (!empty($logs)) {
//                    Log::info('message:解析日誌成功:', $logs);
//                    $log_lists = array_chunk($logs, 5000);
//                    $download = $this->updateDownLoad($download, ["type" => "write"]);
//                    foreach ($log_lists as $log_data){
//                        $influxDBService->insertLogs($log_data);
//                    }
                    $download = $this->updateDownLoad($download, ["type" => "done", "status" => "success"]);

                    # 檢查執行的 execute_schedule_id 是否為最後一筆
                    $execute_schedule_count =
                        app(DownloadEntity::class)
                            ->where("execute_schedule_id", $download->execute_schedule_id)
                            ->where("type", "!=", "done")
                            ->count();

                    if($execute_schedule_count == 0){
                        app(ExecuteScheduleEntity::class)
                            ->find($download->execute_schedule_id)
                            ->update([
                                'status' => "success",
                                'process_time_end' => now()->toDateTimeString()
                            ]);
                    }
                }
                // 刪除解壓後的文件
                Storage::disk($this->driver)->delete($fileInfo['filename']);
//            }
        } catch (LogProcessException $e) {
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
    private function updateDownLoad(DownloadEntity $downloadEntity, array $params) : DownloadEntity
    {
        foreach ($params as $index => $value)
        {
            $downloadEntity->$index = $value;
        }

        $downloadEntity->save();

        return $downloadEntity;
    }
}
