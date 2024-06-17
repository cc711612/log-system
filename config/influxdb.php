<?php

return [
    'driver' => 'influxdb',
    'host' => env('INFLUXDB_HOST', 'localhost'),
    'port' => env('INFLUXDB_PORT', '8086'),
    'token' => env('INFLUXDB_TOKEN', ''),
    'bucket' => env('INFLUXDB_BUCKET', ''),
    'org' => env('INFLUXDB_ORG', 'myorg'),
    'insertCount' => env('INFLUXDB_INSERT_COUNT', 1000),
    'sleep' => env('INFLUXDB_SLEEP', 60),
];
