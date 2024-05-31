<?php

namespace App\Models\CdnNetworks\Services;

use App\Exceptions\LogFileExtensionException;
use App\Exceptions\LogProcessException;
use App\Helpers\CDNNetwork;
use App\Helpers\LogParser;
use App\Models\Downloads\Entities\DownloadEntity;
use Illuminate\Support\Carbon;
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
     * @param DownloadEntity $download
     * @return void
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
        $influxDBService = new InfluxDBService($download->user->influx_db_connection, $download->user->influx_db_token, $download->user->influx_db_org, $download->user->influx_db_bucket);
        $logs = [];
        try {
            $downloadLink = $download->url;
            $parsedUrl = parse_url($downloadLink);
            // 提取 URL 路徑部分
            $path = $parsedUrl['path'];
            // 從路徑中提取文件名
            $fileName = basename($path);
            $response = Http::get($downloadLink);
            if ($response->successful()) {
                // 將日誌內容存儲到本地文件
                Storage::disk($this->driver)->put($fileName, $response->body());
                // 讀取日誌文件並分析
                $compressedData = Storage::disk($this->driver)->get($fileName);
                $fileInfo = pathinfo($fileName);
                // 如果是.gz格式，則解壓縮
                if ($fileInfo['extension'] == 'gz') {
                    $uncompressedData = gzdecode($compressedData);
                    $uncompressedFileName = $fileInfo['filename'];
                    // 存儲解壓後的文件
                    Storage::disk($this->driver)->put($uncompressedFileName, $uncompressedData);
                    // 刪除壓縮文件
                    Storage::disk($this->driver)->delete($fileName);
                } else {
                    throw new LogFileExtensionException('檔案格式錯誤，只支持.gz格式的日誌文件，檔案格式為:' . $fileInfo['extension']);
                }
                // 讀取解壓後的日誌文件
                $uncompressedLogs = Storage::disk($this->driver)->get($uncompressedFileName);
                // 按行分割日誌
                $logLines = array_filter(explode("\n", $uncompressedLogs));
                // 解析每一行日誌條目
                foreach ($logLines as $logLine) {
                    $log = $logParser->parseLogEntry($logLine, $download->service_type);
                    array_push($logs, $log);
                }
                // 批量插入數據庫或其他操作
                if (!empty($logs)) {
                    Log::info('message:解析日誌成功:', $logs);
                    $influxDBService->insertLogs($logs);
                    $download->status = 'success';
                    $download->save();
                }
                // 刪除解壓後的文件
                Storage::disk($this->driver)->delete($uncompressedFileName);
            }
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
}
