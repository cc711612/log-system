<?php

namespace App\Helpers;

use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\WriteApi;
use InfluxDB2\Point;
use InfluxDB2\QueryApi; // Add this line to import the QueryApi class


class InfluxDB
{
    private $client;

    public function __construct(string $url, string $token, string $org, string $bucket)
    {
        /**
         * @var Client
         */
        $this->client = new Client([
            "url" => $url,
            "token" => $token,
            "org" => $org,
            "bucket" => $bucket
        ]);
    }

    /**
     * Create a new data point in the InfluxDB database.
     *
     * @param string $measurement The name of the measurement.
     * @param array $fields The fields of the data point.
     * @param array $tags The tags associated with the data point. (optional)
     * @param int|null $timestamp The timestamp of the data point. (optional)
     * @return void
     */
    public function create(string $measurement, array $fields, array $tags = [], int $timestamp = null): void
    {
        /**
         * @var WriteApi
         */
        $writeApi = $this->client->createWriteApi();
        /**
         * @var Point
         */
        $point = new Point($measurement, $fields, $tags, $timestamp);
        $writeApi->write([$point], WritePrecision::NS); // 將 $precision 參數設置為 WritePrecision::NS
        $writeApi->close();
    }
    
    /**
     * Reads data from InfluxDB using the given query.
     *
     * @param string $query The query to execute.
     * @return array The result of the query.
     */
    public function read(string $query): array
    {
        /**
         * @var QueryApi
         */
        $queryApi = $this->client->createQueryApi();

        return $queryApi->query($query);
    }

    /**
     * Update a measurement in the InfluxDB database.
     *
     * @param string $measurement The name of the measurement.
     * @param string $field The field to update.
     * @param mixed $value The new value for the field.
     * @param string $where The condition to specify which data to update.
     * @return void
     */
    public function update(string $measurement, string $field, $value, string $where): void
    {
        /**
         * @var WriteApi
         */
        $writeApi = $this->client->createWriteApi();

        $point = new Point($measurement, null, null, null, [$field => $value]);

        $writeApi->write([$point], WritePrecision::NS, null, $where);
        $writeApi->close();
    }

    /**
     * Check if the InfluxDB service is running.
     *
     * @return bool Returns true if the service is running, false otherwise.
     */
    public function isServiceRunning(): bool
    {
        $response = $this->client->ping();
        return isset($response['X-Influxdb-Version']) && isset($response['X-Influxdb-Build']);
    }
}
