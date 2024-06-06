<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class LogParser
{
    /**
     * Parses a log entry.
     *
     * @param string      $logEntry    The log entry to parse.
     * @param string|null $serviceType The service type associated with the log entry (optional).
     *
     * @return array
     */
    public function parseLogEntry($logEntry, $serviceType = null)
    {
        $logEntry = trim($logEntry);
        try {
            switch ($serviceType) {
                case '1115':
                    $patterns = [
                        '/^(?P<ip>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) (?P<uident>\S+) (?P<uname>\S+) \[(?P<datetime>[^\]]+)\] "(?P<method>[A-Z]+) (?P<url>[^\s]+) HTTP\/(?P<http_version>\d\.\d)" (?P<status>\d{1,3}) (?P<size>\d+)(?: (?P<cache_status>[A-Z_]+) (?P<cache_code>\d+|-) (?P<cache_size>\d+|-) -)? "(?P<referer>[^"]*)" "(?P<user_agent>[^"]+)"/',
                        '/^(?P<ip>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) (?P<uident>\S+) (?P<uname>\S+) \[(?P<datetime>[^\]]+)\] "(?P<method>[A-Z]+) (?P<url>[^\s]+) HTTP\/(?P<http_version>\d\.\d)" (?P<status>\d{1,3}) (?P<size>\d+) (?P<cache_status>[A-Z_]+)\s+(?P<cache_code>\d+|-)\s+(?P<cache_size>\d+|-)\s+(?P<response_bytes>\d+|-)\s+"(?P<referer>[^"]*)" "(?P<user_agent>[^"]+)"/'
                    ];

                    foreach ($patterns as $pattern){
                        preg_match($pattern, $logEntry, $matches);
                        if(empty($matches) == true){
                            continue;
                        }
                        return [
                            'host'              => $matches['ip'],
                            'uident'            => $matches['uident'],
                            'uname'             => $matches['uname'],
                            'rt'                => $matches['datetime'],
                            'method'            => $matches['method'],
                            'url'               => $matches['url'],
                            'http_version'      => $matches['http_version'],
                            'code'              => $matches['status'],
                            'size'              => $matches['size'],
                            'cache_status'      => $matches['cache_status'] ?? '',
                            'cache_status_code' => $matches['cache_status_code'] ?? '',
                            'cache_size'        => $matches['cache_size'] ?? '',
                            'referer'           => $matches['referer'],
                            'ua'                => $matches['user_agent'],
                        ];
                    }
                    break;
                case '1028':
                    // %host %uident %uname [%rt] "%method %url %rp" %code %size %cache %pic_bhs %pic_bt %tru "%referer" "%ua"
                    $pattern = '/^(\S+) (\S+) (\S+) \[([^]]+)\] "(\S+) (\S+) (\S+)" (\d+) (\d+) (\S+) (\S+) (\S+) (\S+) "([^"]*)" "([^"]*)"$/';
                    preg_match($pattern, $logEntry, $matches);
                    return [
                        'host'         => $matches[1],
                        'uident'       => $matches[2],
                        'uname'        => $matches[3],
                        'rt'           => $matches[4],
                        'method'       => $matches[5],
                        'url'          => $matches[6],
                        'http_version' => $matches[7],
                        'code'         => $matches[8],
                        'size'         => $matches[9],
                        'cache'        => $matches[10],
                        'pic_bhs'      => $matches[11],
                        'pic_bt'       => $matches[12],
                        'tru'          => $matches[13],
                        'referer'      => $matches[14],
                        'ua'           => $matches[15],
                    ];

                    break;
                case '1551':
                    // %host %uident %uname [%rt] "%method %url %rp" %code %size "%referer" "%ua" "%aty" "%ra" "%Content-Type"
                    $pattern = '/(\S+) (\S+) (\S+) \[(.+?)\] "(\S+) (\S+) (\S+)" (\d+) (\d+) "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)"/';
                    preg_match($pattern, $logEntry, $matches);
                    return [
                        'host'         => $matches[1],
                        'uident'       => $matches[2],
                        'uname'        => $matches[3],
                        'rt'           => $matches[4],
                        'method'       => $matches[5],
                        'url'          => $matches[6],
                        'http_version' => $matches[7],
                        'code'         => $matches[8],
                        'size'         => $matches[9],
                        'referer'      => $matches[10],
                        'ua'           => $matches[11],
                        'aty'          => $matches[12],
                        'ra'           => $matches[13],
                        'Content-Type' => $matches[14],
                    ];

                    break;
                default:
                    throw new \Exception('Unknown service type');
                    break;
            }

            throw new \Exception('match error');
        } catch (\Exception $e) {
            Log::driver('log_parse')->info('type:' . $serviceType . ' logEntry:' . $logEntry);
            Log::driver('log_parse')->info($e->getMessage());
        }
        return [];
    }
}
