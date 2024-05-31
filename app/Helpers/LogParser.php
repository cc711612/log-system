<?php

namespace App\Helpers;

class LogParser
{
    /**
     * Parses a log entry.
     *
     * @param string $logEntry The log entry to parse.
     * @param string|null $serviceType The service type associated with the log entry (optional).
     * @return array
     */
    public function parseLogEntry($logEntry, $serviceType = null)
    {
        switch ($serviceType) {
            case '1115':
                $pattern = '/(\S+) (\S+) (\S+) \[(.+?)\] "(\S+) (\S+) (\S+)" (\d+) (\d+) "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)"/';
                
                break;
            case '1028':
                $pattern = '/^(\S+) (\S+) (\S+) \[([^]]+)\] "(\S+) (\S+) (\S+)" (\d+) (\d+) (\S+) (\S+) (\S+) (\S+) "([^"]*)" "([^"]*)"$/';

                break;
            case '1551':
                $pattern = '/(\S+) (\S+) (\S+) \[(.+?)\] "(\S+) (\S+) (\S+)" (\d+) (\d+) "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)"/';

                break;
            default:
                throw new \Exception('Unknown service type');
        }

        preg_match($pattern, $logEntry, $matches);

        return [
            'host' => $matches[1],                    // 主機
            'uident' => $matches[2],                  // 使用者標識
            'uname' => $matches[3],                   // 使用者名稱
            'rt' => $matches[4],                      // 請求時間
            'method' => $matches[5],                  // 請求方法
            'url' => $matches[6],                     // URL
            'rp' => $matches[7],                      // 路徑
            'code' => $matches[8],                    // 響應狀態碼
            'size' => $matches[9],                    // 響應大小
            'referer' => $matches[10],                // 參考網址
            'ua' => $matches[11],                     // 用戶代理
            'aty' => $matches[12] ?? null,            // 攻擊類型
            'ra' => $matches[13] ?? null,             // 保護操作
            'Content-Type' => $matches[14] ?? null   // 內容類型
        ];
    }
}
