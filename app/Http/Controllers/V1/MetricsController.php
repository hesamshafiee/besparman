<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class MetricsController extends Controller
{
    /**
     *
     * @group Metrics
     */
    public function metrics()
    {
        $adapter = new \Prometheus\Storage\Redis([
            'host' => env('REDIS_HOST', 'redis'),
            'port' => env('REDIS_PORT', 6379),
            'timeout' => 0.1,
            'read_timeout' => 10,
            'persistent_connections' => false,
            'password' => empty(env('REDIS_PASSWORD')) ? null : env('REDIS_PASSWORD'),
            'database' => 1,
        ]);

        $registry = new CollectorRegistry($adapter);
        $renderer = new RenderTextFormat();

        $metrics = $registry->getMetricFamilySamples();
        $result = $renderer->render($metrics);

        return response($result, 200)
            ->header('Content-Type', RenderTextFormat::MIME_TYPE);
    }
}
