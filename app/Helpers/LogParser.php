<?php

namespace App\Helpers;

class LogParser
{
    public function parseLogEntry($logEntry)
    {
        $pattern = '/^([\d\.]+) (\S+) (\S+) \[(.*?)\] "(.*?) (.*?) (.*?)" (\d+) (\d+) (\S+) (\d+) (\d+) (\S+) "(.*?)" "(.*?)"/';
        preg_match($pattern, $logEntry, $matches);

        return [
            'host' => $matches[1], // 用戶端 IP
            'uident' => $matches[2], // 使用者識別
            'uname' => $matches[3], // 使用者名稱
            '[rt]' => $matches[4], // 請求處理結束時間
            'method' => $matches[5], // 請求方式
            'url' => $matches[6], // 請求 URL
            'rp' => $matches[7], // 請求的 HTTP 版本號
            'code' => $matches[8], // HTTP 服務狀態碼
            'size' => $matches[9], // 反應大小（包括頭部）
            'cache' => $matches[10], // 快取狀態
            'pic_bhs' => $matches[11], // 回源狀態碼
            'pic_bt' => $matches[12], // 回源時間
            'tru' => $matches[13], // 開始回源到回源結束的時間
            'referer' => $matches[14], // 請求頭 Referer
            'ua' => $matches[15], // 請求頭 User-Agent
        ];
    }
}
