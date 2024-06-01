<?php

namespace App\Models\CdnNetworks\Services;

use App\Helpers\InfluxDB;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class InfluxDBService
{

    public function __construct(
        private string $host,
        private string $token,
        private string $org,
        private string $bucket
    ) {
    }

    public function insertLogs($logs)
    {
        /**
         * @var InfluxDB
         */
        $influxDB = new InfluxDB(
            $this->host,
            $this->token,
            $this->org,
            $this->bucket
        );

        // 測量名稱
        $measurement = 'Service Type';
        $logs = array_map(function ($log) use ($measurement) {
            return [
                'measurement' => $measurement,
                'fields' => Arr::only($log, [
                    'size',
                    'rt'
                ]),
                'tags' => Arr::only($log, [
                    'host',
                    'uident',
                    'uname',
                    'method',
                    'url',
                    'rp',
                    'code',
                    'referer',
                    'ua',
                    'cache',
                    'aty',
                    'ra',
                    'Content-Type'
                ]),
                'timestamp' => isset($log['rt']) ? $this->convertToNanoseconds($log['rt']) : time() * 1000000000,
            ];
        }, $logs);
        // 批次 5000 筆
        $logChunks = array_chunk($logs, 5000);
        foreach ($logChunks as $logs) {
            // 創建批次
            $influxDB->createBatch($logs);
        }
    }

    /**
     * 將日期字符串轉換為納秒
     * @param string $dateStr 日期字符串
     * @return int 納秒
     */
    private function convertToNanoseconds($dateStr)
    {
        try {
            // 使用 Carbon 解析日期字符串
            $dateTime = Carbon::createFromFormat('d/M/Y:H:i:s O', $dateStr);

            // 獲取Unix時間戳並轉換為納秒
            return $dateTime->timestamp * 1000000000;
        } catch (\Exception $e) {
            // 如果日期字符串無法解析，使用當前時間
            return time() * 1000000000;
        }
    }
}
