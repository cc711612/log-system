<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

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
        $logEntry = trim($logEntry);
        try {
            switch ($serviceType) {
                case '1115':
                    // %host %uident %uname [%rt] "%method %url %rp" %code %size "%referer" "%ua"
                    $pattern = '/^(?P<host>\d+\.\d+\.\d+\.\d+) (?P<uident>[^ ]+) (?P<uname>[^ ]+) \[(?P<rt>[^\]]+)\] "(?P<method>\S+) (?P<url>\S+) (?P<http_version>[^"]+)" (?P<code>\d+) (?P<size>\d+) (?P<cache_status>\S+) (?P<http_status>\d+) (?P<unknown>\d+) - "(?P<referer>[^"]*)" "(?P<ua>[^"]+)"$/';
                    preg_match($pattern, $logEntry, $matches);
                    return [
                        'host' => $matches['host'],
                        'uident' => $matches['uident'],
                        'uname' => $matches['uname'],
                        'rt' => $matches['rt'],
                        'method' => $matches['method'],
                        'url' => $matches['url'],
                        'http_version' => $matches['http_version'],
                        'code' => $matches['code'],
                        'size' => $matches['size'],
                        'referer' => $matches['referer'],
                        'ua' => $matches['ua'],
                    ];
                case '1028':
                    // %host %uident %uname [%rt] "%method %url %rp" %code %size %cache %pic_bhs %pic_bt %tru "%referer" "%ua"
                    $pattern = '/^(\S+) (\S+) (\S+) \[([^]]+)\] "(\S+) (\S+) (\S+)" (\d+) (\d+) (\S+) (\S+) (\S+) (\S+) "([^"]*)" "([^"]*)"$/';
                    preg_match($pattern, $logEntry, $matches);
                    return [
                        'host' => $matches[1],
                        'uident' => $matches[2],
                        'uname' => $matches[3],
                        'rt' => $matches[4],
                        'method' => $matches[5],
                        'url' => $matches[6],
                        'http_version' => $matches[7],
                        'code' => $matches[8],
                        'size' => $matches[9],
                        'cache' => $matches[10],
                        'pic_bhs' => $matches[11],
                        'pic_bt' => $matches[12],
                        'tru' => $matches[13],
                        'referer' => $matches[14],
                        'ua' => $matches[15],
                    ];
                case '1551':
                    // %host %uident %uname [%rt] "%method %url %rp" %code %size "%referer" "%ua" "%aty" "%ra" "%Content-Type"
                    $pattern = '/(\S+) (\S+) (\S+) \[(.+?)\] "(\S+) (\S+) (\S+)" (\d+) (\d+) "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)"/';
                    preg_match($pattern, $logEntry, $matches);
                    return [
                        'host' => $matches[1],
                        'uident' => $matches[2],
                        'uname' => $matches[3],
                        'rt' => $matches[4],
                        'method' => $matches[5],
                        'url' => $matches[6],
                        'http_version' => $matches[7],
                        'code' => $matches[8],
                        'size' => $matches[9],
                        'referer' => $matches[10],
                        'ua' => $matches[11],
                        'aty' => $matches[12],
                        'ra' => $matches[13],
                        'Content-Type' => $matches[14],
                    ];

                default:
                    throw new \Exception('Unknown service type');
            }
        } catch (\Exception $e) {
            Log::driver('log_parse')->info('type' . $serviceType . ' logEntry:' . $logEntry);
        }
    }
}
