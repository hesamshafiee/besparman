<?php

namespace App\Services\V1\Search;

use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('search' , function() {
            return new SearchService();
        });
    }
}
