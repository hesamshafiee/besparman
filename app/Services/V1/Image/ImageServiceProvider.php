<?php


namespace App\Services\V1\Image;


use Illuminate\Support\ServiceProvider;

/**
 * Class CartServiceProvider
 * @package App\Helpers\Cart
 * in this class we create our cart service
 */

class ImageServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('image' , function() {
            return new ImageService();
        });
    }
}
