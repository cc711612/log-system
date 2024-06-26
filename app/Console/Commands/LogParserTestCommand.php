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
                "data" => '103.212.118.173 - - [26/Jun/2024:13:56:54 +0800] "GET https://onebop.mpulsefusion.com/ HTTP/2.0" 0 0 TCP_MISS - - - "-" "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36" "NONE" "NONE" "-"'
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
