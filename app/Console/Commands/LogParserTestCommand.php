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
                "data" => '40.77.167.50 - - [25/Jun/2024:22:08:39 +0800] "GET https://u022ob.hrv0968.net/(S(xideqseTF7K3n3i5n3u1yu54f4oyajgjrdfyi39kj2fqMP2gkNmF9qWPk4QZZ))/newindex HTTP/2.0" 200 1727 TCP_MISS 200 109 109 "-" "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) Chrome/116.0.1938.76 Safari/537.36" "NONE" "NONE" "text/html; charset=utf-8"'
            ]
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
