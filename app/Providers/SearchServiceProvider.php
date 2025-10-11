<?php

namespace App\Providers;

use App\Services\V1\ElasticSearch\SearchClient;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SearchClient::class, function () {
            $cfg = config('search');
            return new SearchClient(
                $cfg['base_url'],
                $cfg['api_key'],
                $cfg['index'],
                $cfg['timeout'],
                $cfg['retries'],
                $cfg['retry_ms'],
            );
        });
    }
}
