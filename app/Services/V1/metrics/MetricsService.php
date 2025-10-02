<?php

namespace App\Services\V1\metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

class MetricsService
{
    public static function getRegistry(): CollectorRegistry
    {
        $redisConfig = config('database.redis.default');

        $adapter = new Redis([
            'host' => env('REDIS_HOST', 'redis'),
            'port' => env('REDIS_PORT', 6379),
            'timeout' => 0.1,
            'read_timeout' => 10,
            'persistent_connections' => false,
            'password' => empty(env('REDIS_PASSWORD')) ? null : env('REDIS_PASSWORD'),
            'database' => 1,
        ]);

        return new CollectorRegistry($adapter);
    }
}
