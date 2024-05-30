<?php

namespace App\Console\Commands;

use App\Models\CdnNetworks\Services\CdnNetworkService;
use App\Models\Users\Entities\UserEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class LogDownloadParseCommand extends Command
{
    /**
     * @var string
     */
    public $driver = 'local';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:log-download-parse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '下載log & 解析log';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * @var CdnNetworkService
         */
        $cdnNetworkService = app(CdnNetworkService::class);

        $users =
            app(UserEntity::class)
                ->get()
                ->toArray();

        $chunkCount = config('services.tswd.chunk_count');

        foreach ($users as $user) {
            $domainLists =
                $cdnNetworkService
                    ->setAccount(Arr::get($user, 'tswd_account'))
                    ->setToken(Arr::get($user, 'tswd_token'))
                    ->getDomainList();

            // 測試 start
            $downloadLinks =
                $cdnNetworkService->getDownloadLinkByDomains(
                    ['.nova88.in'],
                    [
                        '2024-05-24 12:00:00',
                        '2024-05-24 13:00:00',
                    ]
                );

            if (!empty($downloadLinks['logs'])) {
                $cdnNetworkService
                    ->setAccount(Arr::get($user, 'TSWD_Account'))
                    ->setToken(Arr::get($user, 'TSWD_Token'))
                    ->processLogByDownloadLinks($downloadLinks['logs']);
            }
            // 測試 end
            exit();

            $domainChunks = $domainLists ? array_chunk($domainLists, $chunkCount) : [];

            foreach ($domainChunks as $domainLists) {
                $domains = array_values(
                    array_filter(
                        array_column($domainLists, 'domain-name')
                    )
                );
                // 取得下載連結
                $downloadLinks = $cdnNetworkService
                    ->getDownloadLinkByDomains($domains);
                if (!empty($downloadLinks['logs'])) {
                    $cdnNetworkService
                        ->processLogByDownloadLinks($downloadLinks['logs']);
                }
            }
            $this->info('Done');
        }
    }
}
