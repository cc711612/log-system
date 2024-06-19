<?php

return [
    'driver' => 'elasticsearch',
    'host' => env('ELASTICSEARCH_HOST', 'localhost'),
    'port' => env('ELASTICSEARCH_PORT', '9200'),
    'index' => env('ELASTICSEARCH_INDEX', 'index'),
    'token' => env('ELASTICSEARCH_TOKEN', ''),
    'insertCount' => env('ELASTICSEARCH_INSERT_COUNT', 1000),
    'sleep' => env('ELASTICSEARCH_SLEEP', 60),
];
