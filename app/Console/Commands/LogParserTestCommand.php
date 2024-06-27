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
                "type" => 1115,
                "data" => '212.18.122.92 - - [26/Jun/2024:23:27:03 +0000] "POST https://i1l7oa.plm0865.net/(S(xideqseTk2L3gsvh0cqtduxi5lgbzd1tlcvs1oW3aq5CdLPJ-zjU-Jt0v8gZZ))/login_checkin.aspx HTTP/2.0" 302 4226 "https://i1l7oa.plm0865.net/(S(xideqseTk2L3gsvh0cqtduxi5lgbzd1tlcvs1oW3aq5CdLPJ-zjU-Jt0v8gZZ))/NewIndex?lang=en&webskintype=3" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"'
            ],
            [
                "type" => 1115,
                "data" => '192.228.154.178 - - [26/Jun/2024:23:27:58 +0000] "POST https://q2v1gp.plm0865.net/zh-CN/live/1 HTTP/2.0" 200 4057 "https://q2v1gp.plm0865.net/zh-CN/live/1" "Mozilla/5.0 (Linux; Android 11; 2201117TG Build/RKQ1.211001.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/126.0.6478.71 Mobile Safari/537.36"'
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
