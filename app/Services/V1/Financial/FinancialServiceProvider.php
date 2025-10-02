<?php


namespace App\Services\V1\Financial;

use Illuminate\Support\ServiceProvider;

class FinancialServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('financial' , function() {
            return new FinancialService();
        });
    }
}
