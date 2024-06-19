<?php

namespace App\Models\CdnNetworks\Services;

use App\Helpers\Elasticsearch;

class ElasticsearchService
{
    private $client;

    public function __construct($hosts, $token, $index)
    {
        $this->client = new Elasticsearch($hosts, $token, $index);
    }

    public function insertLogs($logs)
    {
        // 批次 5000 筆
        $logChunks = array_chunk($logs, config('elasticsearch.insertCount'));
        foreach ($logChunks as $logs) {
            // 執行批量插入
            $this->client->createBatch($logs);
        }
    }
}
