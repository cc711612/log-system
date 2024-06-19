<?php

namespace App\Console\Commands;

use App\Helpers\LogParser;
use Illuminate\Console\Command;

class LogParserTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:log_parser_test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '正規測試';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->startInfo(self::class);

        $LogParser = app(LogParser::class);

        $TestData = [
            [
                "type" => 1551,
                "data" => '171.244.212.228 - - [19/Jun/2024:17:00:37 +0800] "GET https://onebop.mpulsefusion.com/ HTTP/2.0" 200 349 "-" "Mozilla/5.'
            ],
            [
                "type" => 1115,
                "data" => '49.149.108.135 - - [19/Jun/2024:11:22:43 +0000] "GET https://y2j9ma.gon1836.com/api/Config/GetSiteConfigWithSession HTTP/2.0" 200 492 TCP_MISS 200 60 60 "https://y2j9gp.gon1836.com/" "Mozilla/5.0 (Linux; Android 13; en; TECNO CK7n Build/SP'
            ],
        ];

        foreach ($TestData as $Data){
            $Parser = $LogParser->parseLogEntry($Data['data'], $Data['type']);
            dump($Parser);
        }

        $this->endInfo(self::class);
    }

    /**
     * @param $name
     *
     * @return $this
     * @Author  : steatng
     * @DateTime: 2024/6/19 下午8:50
     */
    private function startInfo($name)
    {
        $this->info(str_replace(
            ['{class}', '{date_time}'],
            [$name, now()->toDateTimeString()],
            '{class} 開始執行 {date_time}'
        ));

        return $this;
    }

    /**
     * @param $name
     *
     * @return $this
     * @Author  : steatng
     * @DateTime: 2024/6/19 下午8:50
     */
    private function endInfo($name)
    {
        $this->info(str_replace(
            ['{class}', '{date_time}'],
            [$name, now()->toDateTimeString()],
            '{class} 執行結束 {date_time}'
        ));

        return $this;
    }
}
