<?php

namespace App\Models\CdnNetworks\Services;

use App\Helpers\InfluxDB;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use InfluxDB2\Point;

class InfluxDBService
{
    private string $host;
    private string $token;
    private string $org;
    private string $bucket;

    public function __construct(
        $host,
        $token,
        $org,
        $bucket
    ) {
        $this->host = $host;
        $this->token = $token;
        $this->org = $org;
        $this->bucket = $bucket;
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

    public function handleLogFormat($log)
    { // 轉義特殊字符
        // $escapeCharacters = function ($value) {
        //     $replacements = [
        //         " " => "\ ",
        //         "," => "\,",
        //         "=" => "\=",
        //         "\"" => "\\\"",
        //     ];
        //     return str_replace(array_keys($replacements), array_values($replacements), $value);
        // };

        $tags = Arr::only($log, [
            'hostname',
            'servicegroup',
            'clientIP',
            'uident',
            'uname',
            'method',
            'url',
            'version',
            'code',
            'referer',
            'useragent',
            'cache',
            'origincode',
            'attack-type',
            'firewall-action',
            'content-type'
        ]);

        // 拔除後方的反斜線
        if (!empty($tags['referer'])) {
            $tags['referer'] = rtrim($tags['referer'], "\\");
        }

        return
            $this
            ->handleDataPointFormat(
                [
                    'measurement' => Arr::get($log, 'measurement'),
                    'fields' => Arr::only($log, [
                        'size',
                        'origin-responsetime',
                        'origin-turnaroundtime'
                    ]),
                    'tags' => $tags,
                    'timestamp' => isset($log['timestamp']) ? $this->convertToNanoseconds($log['timestamp']) : time() * 1000000000,
                ]
            );
    }

    public function handleDataPointFormat($data)
    {
        $measurement = $data['measurement'];
        $fields = $data['fields'];
        $tags = $data['tags'] ?? [];
        $timestamp = $data['timestamp'] ?? null;

        /**
         * @var Point
         */
        return new Point($measurement, $tags, $fields, $timestamp);
    }
}
