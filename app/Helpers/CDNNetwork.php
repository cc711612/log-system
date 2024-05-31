<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class CDNNetwork
 *
 * @Author  : steatng
 *
 * @DateTime: 2024/5/23 下午2:36
 */
class CDNNetwork
{
    private $cdnNetworkApiDomain = 'https://api.cdnetworks.com';

    private $dateTime;

    private $username;

    private $apiKey;

    public function __construct()
    {
        date_default_timezone_set('PRC');
    }

    /**
     * @return mixed
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午10:47
     */
    private function getUsername()
    {
        return $this->username;
    }

    /**
     * @return $this
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午10:50
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return mixed
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午10:47
     */
    private function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return $this
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午10:50
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return $this
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午11:51
     */
    public function setDateTime()
    {
        $this->dateTime = gmdate('D, d M Y H:i:s') . ' GMT';

        return $this;
    }

    /**
     * @return mixed
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午10:51
     */
    private function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @return string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午10:51
     */
    private function getAuth()
    {
        $str = hash_hmac('SHA1', $this->getDateTime(), $this->getApiKey(), true);
        $str = base64_encode($str);
        //generate account base
        $auth = sprintf('%s:%s', $this->getUsername(), $str);
        $auth = base64_encode($auth);

        return $auth;
    }

    /**
     * @return array
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午11:17
     */
    private function createHeader(string $method)
    {
        return [
            'method' => $method,
            'header' => "Accept: application/json\r\n" .
                'Date: ' . $this->getDateTime() . "\r\n" .
                'Authorization: Basic ' . $this->getAuth(),
        ];
    }

    /**
     * @return string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午11:37
     */
    private function getCdnNetworkApiDomain()
    {
        return $this->cdnNetworkApiDomain;
    }

    /**
     * @return string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 下午12:47
     */
    private function getFullUrl(string $path, array $params = [])
    {
        $href = sprintf('%s%s', $this->getCdnNetworkApiDomain(), $path);

        if (!empty($params)) {
            $queryString = '';

            foreach ($params as $key => $value) {
                if ($queryString == '') {
                    $queryString = sprintf('%s=%s', $key, urlencode($value));
                } else {
                    $queryString = sprintf('%s&%s=%s', $queryString, $key, urlencode($value));
                }
            }

            $href = sprintf('%s?%s', $href, $queryString);
        }

        return $href;
    }

    /**
     * @return string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 下午1:00
     */
    private function handleDateFormat($dateTime)
    {
        $dateTime = Carbon::parse($dateTime);

        $date = $dateTime->format('Y-m-d');
        $time = $dateTime->format('H:i:s');

        $timezoneOffset = $dateTime->format('P');

        return "{$date}T{$time}{$timezoneOffset}";
    }

    /**
     * @return bool|string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午11:19
     */
    public function sendPost($fullHttpUrl, $body)
    {
        $ch = curl_init();
        $encode_body = json_encode($body);
        $header = $this->createHeader('POST');
        $start_at = now()->toDateTimeString();
        $status = 'True';

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encode_body);
        curl_setopt($ch, CURLOPT_URL, $fullHttpUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        try {
            $result = curl_exec($ch);
        } catch (\Exception $exception) {
            $result = 'false';
        }

        if ($result === false) {
            $status = 'False';
            $result = json_encode([]);
        }

        $end_at = now()->toDateTimeString();

        Log::channel('cdn_network')->info(str_replace(
            ['{start_at}', '{end_at}', '{status}', '{full_http_url}', '{header}', '{body}'],
            [$start_at, $end_at, $status, $fullHttpUrl, json_encode($header), $encode_body],
            '開始時間 {start_at}, 結束時間 {end_at}, 回傳狀態 {status} 網址 {full_http_url}, Header {header}, Body {body}'
        ));

        return $result;
    }

    /**
     * @return false|string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午11:30
     */
    public function sendGet(string $url)
    {
        $ch = curl_init();
        $header = $this->createHeader('GET');
        $start_at = now()->toDateTimeString();
        $status = 'True';

        try {
            $result = file_get_contents($url, false, stream_context_create(['http' => $header]));
        } catch (\Exception $exception) {
            $status = 'False';
            $result = json_encode([]);
        }

        $end_at = now()->toDateTimeString();

        Log::channel('cdn_network')->info(str_replace(
            ['{start_at}', '{end_at}', '{status}', '{full_http_url}', '{header}'],
            [$start_at, $end_at, $status, $url, json_encode($header)],
            '開始時間 {start_at}, 結束時間 {end_at}, 回傳狀態 {status} 網址 {full_http_url}, Header {header}'
        ));

        return $result;
    }

    /**
     * @return false|string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 上午11:40
     */
    public function getDomainList()
    {
        $url = $this->getFullUrl('/api/domain');

        return $this->sendGet($url);
    }

    /**
     * @param  string  $logType
     * @return bool|string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 下午2:33
     */
    public function getReportDownLoadLink($startAt, $endAt, array $domainList, $logType = 'cdn')
    {
        $params = [
            'datefrom' => $this->handleDateFormat($startAt),
            'dateto' => $this->handleDateFormat($endAt),
            'logtype' => $logType,
        ];

        $url = $this->getFullUrl('/api/report/log/downloadLink', $params);

        $body = [
            'domain-list' => [
                'domain-name' => $domainList,
            ],
        ];

        return $this->sendPost($url, $body);
    }

    /**
     * @return bool|string
     *
     * @Author  : steatng
     *
     * @DateTime: 2024/5/23 下午2:28
     */
    public function getCGDomainList(array $controlGroupCode = [])
    {
        $url = $this->getFullUrl('/user/cgdomainlist');

        $body = [
            'controlGroupCode' => $controlGroupCode,
        ];

        return $this->sendPost($url, $body);
    }
}