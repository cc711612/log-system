<?php

namespace App\Jobs;

use App\Helpers\Enums\StatusEnum;
use App\Models\CdnNetworks\Services\CdnNetworkService;
use App\Models\Downloads\Entities\DownloadEntity;
use App\Models\ExecuteSchedules\Entities\ExecuteScheduleEntity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $download_id;

    public $timeout = 3600;

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
        ini_set('max_execution_time', $this->timeout);

        $downloadEntity = $this->getDownloadEntity();
        /**
         * @var CdnNetworkService $cdnNetworkService
         */
        $cdnNetworkService  = app(CdnNetworkService::class);

        if (is_null($downloadEntity) == false) {
            $downloadEntity->status = StatusEnum::PROCESSING->value;
            $downloadEntity->save();

            # 取得 DownloadEntity
            $cdnNetworkService
                ->processLogByDownload($downloadEntity);
        }
    }


    public function failed(?Throwable $exception): void
    {
        $downloadEntity = $this->getDownloadEntity();
        // update
        if ($downloadEntity) {
            $downloadEntity->status = StatusEnum::FAILURE->value;
            $downloadEntity->error_message = $exception->getMessage();
            $downloadEntity->save();
            ExecuteScheduleEntity::where('id', $downloadEntity->execute_schedule_id)
                ->update([
                    'status' => StatusEnum::FAILURE->value,
                    'error_message' => $exception->getMessage()
                ]);
        }

        Log::error($exception->getMessage());
    }

    /**
     * find download entity
     *
     * @param int $download_id
     * @return DownloadEntity
     */
    private function getDownloadEntity()
    {
        return app(DownloadEntity::class)
            ->find($this->download_id);
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
