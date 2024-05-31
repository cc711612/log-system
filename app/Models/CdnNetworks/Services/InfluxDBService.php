<?php

namespace App\Models\CdnNetworks\Services;

use App\Helpers\InfluxDB;
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

    public function insertLog($log)
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

        // 將 $log 陣列中的字段值 'size', '回源時間', '開始回源到回源結束的時間' 存入 fields 中
        $fields = Arr::only($log, ['size', 'rt']);

        // 獲取當前時間並轉換為納秒
        $timestamp = time() * 1000000000; // 轉換為納秒

        // 將 $log 陣列中的標籤值提取出來
        $tags = Arr::only($log, [
            'host',        // 客戶端IP
            'uident',      // 用戶識別
            'uname',       // 用戶名
            'method',      // 請求方式
            'url',         // 請求完整uri
            'rp',          // 響應的HTTP版本號
            'code',        // HTTP服務狀態碼
            'referer',     // 請求頭referer
            'ua',          // 請求頭UA
            'cache',       // 緩存狀態
            'aty',         // 攻擊類型
            'ra',          // 防護操作
            'Content-Type' // 響應頭Content-Type
        ]);

        // 創建資料點
        $influxDB->create($measurement, $fields, $tags, $timestamp);
    }
}
