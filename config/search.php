<?php

return [
    'base_url' => env('SEARCH_BASE_URL', 'https://search.services.abrbit.com'),
    'index'    => env('SEARCH_INDEX', 'my-index'),
    'api_key'  => env('SEARCH_API_KEY'),
    // تنظیمات اختیاری
    'timeout'  => 10,
    'retries'  => 2,
    'retry_ms' => 200,
];