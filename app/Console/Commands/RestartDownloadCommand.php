<?php

namespace App\Console\Commands;

use App\Helpers\Enums\StatusEnum;
use App\Jobs\HandleDownloadJob;
use App\Models\Downloads\Entities\DownloadEntity;
use Illuminate\Console\Command;

class RestartDownloadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:restart_download {--downloadIds= : downloadIds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重新啟動 download jobs';

    public function handle()
    {
        if ($this->option('downloadIds')) {
            $downloadIds = explode(',', $this->option('downloadIds'));
            $downloadIds = $this->getDownloadIdsByIds($downloadIds);
        } else {
            $downloadIds = $this->getDownloadIds();
        }

        $downloadChunkIds = array_chunk($downloadIds, 500);

        foreach ($downloadChunkIds as $downloadIds) {
            foreach ($downloadIds as $downloadId) {
                HandleDownloadJob::dispatch($downloadId)
                    ->onQueue('retry_download');
            }
            $this->updateDownloadByIds($downloadIds);
        }

        $this->info('已重新啟動 download jobs : ' . implode(',', $downloadIds));
    }

    private function getDownloadIds()
    {
        $status = [
            StatusEnum::PROCESSING->value,
            StatusEnum::FAILURE->value
        ];

        return DownloadEntity::whereIn('status', $status)
            ->select(['id'])
            ->get()
            ->pluck('id')
            ->toArray();
    }

    private function getDownloadIdsByIds(array $downloadIds)
    {
        return DownloadEntity::whereIn('id', $downloadIds)
            ->select(['id'])
            ->get()
            ->pluck('id')
            ->toArray();
    }

    private function updateDownloadByIds($downloadIds)
    {
        return DownloadEntity::whereIn('id', $downloadIds)
            ->update(['status' => StatusEnum::INITIAL->value]);
    }
}
