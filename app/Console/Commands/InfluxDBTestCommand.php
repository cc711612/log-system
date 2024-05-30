<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\InfluxDB;
use Illuminate\Support\Arr;

class InfluxDBTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:influxdb-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'influxdb test';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $json = '[{"host":"15.177.50.2","uident":"-","uname":"-","[rt]":"24/May/2024:12:55:18 +0800","method":"GET","url":"http://www01.nova88.in/speedtest.png","rp":"HTTP/1.1","code":"302","size":"667","cache":"TCP_MISS","pic_bhs":"302","pic_bt":"65","tru":"-","referer":"-","ua":"Amazon-Route53-Health-Check-Service (ref 05b24055-5961-480e-b1ed-5ad3afbe7c2b; report http://amzn.to/1vsZADi)"},{"host":"15.177.50.2","uident":"-","uname":"-","[rt]":"24/May/2024:12:55:48 +0800","method":"GET","url":"http://www01.nova88.in/speedtest.png","rp":"HTTP/1.1","code":"302","size":"666","cache":"TCP_MISS","pic_bhs":"302","pic_bt":"65","tru":"-","referer":"-","ua":"Amazon-Route53-Health-Check-Service (ref 05b24055-5961-480e-b1ed-5ad3afbe7c2b; report http://amzn.to/1vsZADi)"},{"host":"15.177.50.2","uident":"-","uname":"-","[rt]":"24/May/2024:12:56:18 +0800","method":"GET","url":"http://www01.nova88.in/speedtest.png","rp":"HTTP/1.1","code":"302","size":"617","cache":"TCP_MISS","pic_bhs":"302","pic_bt":"57","tru":"-","referer":"-","ua":"Amazon-Route53-Health-Check-Service (ref 05b24055-5961-480e-b1ed-5ad3afbe7c2b; report http://amzn.to/1vsZADi)"},{"host":"15.177.58.117","uident":"-","uname":"-","[rt]":"24/May/2024:12:55:12 +0800","method":"GET","url":"http://www01.nova88.in/speedtest.png","rp":"HTTP/1.1","code":"302","size":"688","cache":"TCP_MISS","pic_bhs":"302","pic_bt":"163","tru":"-","referer":"-","ua":"Amazon-Route53-Health-Check-Service (ref 05b24055-5961-480e-b1ed-5ad3afbe7c2b; report http://amzn.to/1vsZADi)"},{"host":"15.177.58.117","uident":"-","uname":"-","[rt]":"24/May/2024:12:55:42 +0800","method":"GET","url":"http://www01.nova88.in/speedtest.png","rp":"HTTP/1.1","code":"302","size":"687","cache":"TCP_MISS","pic_bhs":"302","pic_bt":"163","tru":"-","referer":"-","ua":"Amazon-Route53-Health-Check-Service (ref 05b24055-5961-480e-b1ed-5ad3afbe7c2b; report http://amzn.to/1vsZADi)"},{"host":"15.177.58.117","uident":"-","uname":"-","[rt]":"24/May/2024:12:56:12 +0800","method":"GET","url":"http://www01.nova88.in/speedtest.png","rp":"HTTP/1.1","code":"302","size":"688","cache":"TCP_MISS","pic_bhs":"302","pic_bt":"164","tru":"-","referer":"-","ua":"Amazon-Route53-Health-Check-Service (ref 05b24055-5961-480e-b1ed-5ad3afbe7c2b; report http://amzn.to/1vsZADi)"},{"host":"15.177.62.178","uident":"-","uname":"-","[rt]":"24/May/2024:12:56:20 +0800","method":"GET","url":"http://www01.nova88.in/speedtest.png","rp":"HTTP/1.1","code":"302","size":"687","cache":"TCP_MISS","pic_bhs":"302","pic_bt":"162","tru":"-","referer":"-","ua":"Amazon-Route53-Health-Check-Service (ref 05b24055-5961-480e-b1ed-5ad3afbe7c2b; report http://amzn.to/1vsZADi)"},{"host":"15.177.42.4","uident":"-","uname":"-","[rt]":"24/May/2024:12:55:02 +0800","method":"GET","url":"http://www01.nova88.in/speedtest.png","rp":"HTTP/1.1","code":"302","size":"636","cache":"TCP_MISS","pic_bhs":"302","pic_bt":"33","tru":"-","referer":"-","ua":"Amazon-Route53-Health-Check-Service (ref 05b24055-5961-480e-b1ed-5ad3afbe7c2b; report http://amzn.to/1vsZADi)"},{"host":"15.177.42.4","uident":"-","uname":"-","[rt]":"24/May/2024:12:55:32 +0800","method":"GET","url":"http://www01.nova88.in/speedtest.png","rp":"HTTP/1.1","code":"302","size":"636","cache":"TCP_MISS","pic_bhs":"302","pic_bt":"34","tru":"-","referer":"-","ua":"Amazon-Route53-Health-Check-Service (ref 05b24055-5961-480e-b1ed-5ad3afbe7c2b; report http://amzn.to/1vsZADi)"},{"host":"15.177.42.4","uident":"-","uname":"-","[rt]":"24/May/2024:12:57:02 +0800","method":"GET","url":"http://www01.nova88.in/speedtest.png","rp":"HTTP/1.1","code":"302","size":"636","cache":"TCP_MISS","pic_bhs":"302","pic_bt":"33","tru":"-","referer":"-","ua":"Amazon-Route53-Health-Check-Service (ref 05b24055-5961-480e-b1ed-5ad3afbe7c2b; report http://amzn.to/1vsZADi)"}]';
        $data = json_decode($json, true);
        /**
         * @var InfluxDB
         */
        $influxDB = new InfluxDB(
            config('influxdb.host'),
            config('influxdb.token'),
            config('influxdb.org'),
            config('influxdb.bucket')
        );

        if ($influxDB->isServiceRunning()) {
            $this->info('InfluxDB is running.');
        } else {
            $this->error('InfluxDB is not running.');
            exit;
        }

        // 創建數據
        $measurement = 'WebLogs';
        $fields = Arr::only($data[0], ['size']);
        foreach ($data as $log) {
            $timestamp = time() * 1000000000; // 轉換為納秒
            // IP、Method、URL、StatusCode、UserAgent
            $log = Arr::only($log, ['host', 'method', 'url', 'code', 'ua']);
            $influxDB->create($measurement, $fields, $log, $timestamp);
        }

        // 讀取數據
        $query = 'from(bucket: "' . config('influxdb.bucket') . '") |> range(start: -1h)';
        $result = $influxDB->read($query);
        dump($result);
        $this->info('InfluxDB test completed.');
    }
}
