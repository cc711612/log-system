<?php

namespace App\Jobs;

use App\Models\CdnNetworks\Services\CdnNetworkService;
use App\Models\Downloads\Entities\DownloadEntity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $download_id;

    public $timeout = 300;

    /**
     * HandleDownloadJob constructor.
     *
     * @param $download_id
     *
     * @Author  : steatng
     * @DateTime: 2024/5/31 上午11:54
     */
    public function __construct($download_id)
    {
        $this->onQueue('download');

        $this->download_id = $download_id;
    }

    /**
     * @throws \App\Exceptions\LogFileExtensionException
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $DownloadEntity =
            app(DownloadEntity::class)
                ->find($this->download_id);

        $DownloadEntity->status = "in progress";
        $DownloadEntity->save();

        # 取得 DownloadEntity
        app(CdnNetworkService::class)
            ->processLogByDownload($DownloadEntity);
    }


    /**
     * @param $name
     *
     * @return $this
     * @Author  : steatng
     * @DateTime: 2024/5/31 下午7:29
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
     * @DateTime: 2024/5/31 下午7:29
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

    /**
     * @param $name
     *
     * @Author  : steatng
     * @DateTime: 2024/5/31 下午7:29
     */
    private function errorInfo($name)
    {
        $this->error(str_replace(
            ['{class}', '{date_time}'],
            [$name, now()->toDateTimeString()],
            '{class} 執行結束 {date_time}'
        ));
    }
}
