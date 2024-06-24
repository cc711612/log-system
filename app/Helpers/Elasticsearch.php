<?php

namespace App\Helpers;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class Elasticsearch
{
    private $client;
    private $index;

    public function __construct(array $hosts, string $token, string $index)
    {
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->setApiKey($token)
            ->setSSLVerification(false)
            ->build();
        $this->index = $index;
    }

    /**
     * 在 Elasticsearch 中創建新的數據點。
     *
     * @param  array  $data  數據點的數據。
     */
    public function create(array $data): void
    {
        $params = [
            'index' => $this->index,
            'body'  => $data
        ];

        $this->client->index($params);
    }

    /**
     * 在 Elasticsearch 中批量創建數據點。
     *
     * @param  array  $dataPoints  數據點數組。
     */
    public function createBatch(array $dataPoints): void
    {
        $params = ['body' => []];

        foreach ($dataPoints as $data) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->index
                ]
            ];
            $params['body'][] = $data;
        }

        $responses = $this->client->bulk($params);

        if (isset($responses['errors']) && $responses['errors']) {
            // 處理批量插入失敗的情況
            foreach ($responses['items'] as $item) {
                if (isset($item['index']['error'])) {
                    // 可以記錄錯誤或其他處理
                    Log::channel('elasticsearch')->error($item['index']['error']);
                }
            }
        }
    }

    /**
     * 使用給定的查詢從 Elasticsearch 中讀取數據。
     *
     * @param  array  $query  要執行的查詢。
     * @return array  查詢結果。
     */
    public function read(array $query): array
    {
        $params = [
            'index' => $this->index,
            'body'  => $query
        ];

        $response = $this->client->search($params);

        return $response['hits']['hits'];
    }

    /**
     * 更新 Elasticsearch 中的文檔。
     *
     * @param  string  $id  文檔 ID。
     * @param  array  $data  要更新的數據。
     */
    public function update(string $id, array $data): void
    {
        $params = [
            'index' => $this->index,
            'id'    => $id,
            'body'  => [
                'doc' => $data
            ]
        ];

        $this->client->update($params);
    }

    /**
     * 檢查 Elasticsearch 服務是否正在運行。
     *
     * @return bool  返回 true 如果服務正在運行，否則返回 false。
     */
    public function isServiceRunning(): bool
    {
        try {
            $this->client->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
