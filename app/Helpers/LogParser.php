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
                        '/^(?P<ip>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) (?P<uident>\S+) (?P<uname>\S+) \[(?P<datetime>[^\]]+)\] "(?P<method>[A-Z]+) (?P<url>[^\s]+) (?P<http_version>HTTP\/\d\.\d)" (?P<status>\d{1,3}) (?P<size>\d+) "(?P<referer>[^"]*)" "(?P<user_agent>.+)"$/',
                        '/^(?P<ip>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) (?P<uident>\S+) (?P<uname>\S+) \[(?P<datetime>[^\]]+)\] "(?P<method>[A-Z]+) (?P<url>[^\s]+) (?P<http_version>HTTP\/\d\.\d)" (?P<status>\d{1,3}) (?P<size>\d+) (?P<cache_status>[A-Z_]+)\s+(?P<cache_code>\d+|-)\s+(?P<cache_size>\d+|-)\s+(?P<response_bytes>\d+|-)\s+"(?P<referer>[^"]*)" "(?P<user_agent>.+)"$/'
                    ];

                    foreach ($patterns as $pattern) {
                        preg_match($pattern, $logEntry, $matches);
                        if (empty($matches) == true) {
                            continue;
                        }
                        return [
                            'hostname'         => parse_url($matches['url'], PHP_URL_HOST),
                            'servicegroup' => '',
                            'timestamp' => $matches['datetime'],
                            'clientIP' => $matches['ip'],
                            'uident'       => $matches['uident'],
                            'uname'        => $matches['uname'],
                            'method'       => $matches['method'],
                            'url'          => $matches['url'],
                            'version' => $matches['http_version'],
                            'code'         => $matches['status'],
                            'referer'      => $matches['referer'],
                            'useragent'           => $matches['user_agent'],
                            'cache'        => $matches['cache_status'] ?? '',
                            'origincode' => $matches['cache_code'] ?? '',
                            'attack-type' => '',
                            'firewall-action' => '',
                            'content-type' => '',
                            'size'         => $matches['size'],
                            'origin-responsetime' => '',
                            'origin-turnaroundtime' => '',
                        ];
                    }
                    break;
                case '1028':
                    // %host %uident %uname [%rt] "%method %url %rp" %code %size %cache %pic_bhs %pic_bt %tru "%referer" "%ua"
                    $pattern = '/^(?P<ip>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) (?P<uident>\S+) (?P<uname>\S+) \[(?P<datetime>[^\]]+)\] "(?P<method>[A-Z]+) (?P<url>[^\s]+) (?P<http_version>HTTP\/\d\.\d)" (?P<status>\d{1,3}) (?P<size>\d+) (?P<cache>[A-Z_]+)\s+(?P<pic_bhs>\d+|-)\s+(?P<pic_bt>\d+|-)\s+(?P<tru>\d+|-)\s+"(?P<referer>[^"]*)" "(?P<user_agent>.+)"$/';
                    preg_match($pattern, $logEntry, $matches);

                    return [
                        'hostname'  => parse_url($matches['url'], PHP_URL_HOST),
                        'timestamp' => $matches['datetime'],
                        'servicegroup' => '',
                        'clientIP' => $matches['ip'],
                        'uident'       => $matches['uident'],
                        'uname'        => $matches['uname'],
                        'method'       => $matches['method'],
                        'url'          => $matches['url'],
                        'version' => $matches['http_version'],
                        'code'         => $matches['status'],
                        'referer'      => $matches['referer'],
                        'useragent'           => $matches['user_agent'],
                        'cache'        => $matches['cache'] ?? '',
                        'origincode' => $matches['pic_bhs'],
                        'attack-type' => '',
                        'firewall-action' => '',
                        'content-type' => '',
                        'size'         => $matches['size'],
                        'origin-responsetime' => $matches['pic_bt'],
                        'origin-turnaroundtime' => $matches['tru']
                    ];

                    break;
                case '1551':
                    // %host %uident %uname [%rt] "%method %url %rp" %code %size "%referer" "%ua" "%aty" "%ra" "%Content-Type"
                    $pattern = '/^(?P<ip>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) (?P<uident>\S+) (?P<uname>\S+) \[(?P<datetime>[^\]]+)\] "(?P<method>[A-Z]+) (?P<url>[^\s]+) (?P<http_version>[^"]+)" (?P<code>\d{1,3}) (?P<size>\d+) "(?P<referer>[^"]*)" "(?P<user_agent>[^"]*)" "(?P<aty>[^"]*)" "(?P<ra>[^"]*)" "(?P<Content_Type>.+)"$/';
                    preg_match($pattern, $logEntry, $matches);
                    return [
                        'hostname'         => parse_url($matches['url'], PHP_URL_HOST),
                        'servicegroup' => '',
                        'timestamp' => $matches['datetime'],
                        'clientIP' => $matches['ip'],
                        'uident'       => $matches['uident'],
                        'uname'        => $matches['uname'],
                        'method'       => $matches['method'],
                        'url'          => $matches['url'],
                        'version' => $matches['http_version'],
                        'code'         => $matches['code'],
                        'referer'      => $matches['referer'],
                        'useragent'           => $matches['user_agent'],
                        'cache'        => '',
                        'origincode' => '',
                        'attack-type' => $matches['aty'],
                        'firewall-action' => $matches['ra'],
                        'content-type' => $matches['Content_Type'],
                        'size'         => $matches['size'],
                        'origin-responsetime' => '',
                        'origin-turnaroundtime' => '',
                    ];

                    break;
                default:
                    throw new \Exception('Unknown service type');
                    break;
            }

            throw new \Exception('match error');
        } catch (\Exception $e) {
            Log::driver('log_parse')->info('pid:' . getmypid() . ' type:' . $serviceType . ' logEntry:' . $logEntry);
            Log::driver('log_parse')->info($e->getMessage());
        }
        return [];
    }
}
