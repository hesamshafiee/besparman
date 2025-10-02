<?php


namespace App\Services\V1\Image;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @method static bool modelImages(Model $model, array $images, string $driver)
 * @method static bool deletingModelImages(Model $model, string $driver)
 * @method static JsonResponse imageList(Model $model, string $driver)
 * @method static StreamedResponse getImage(string $name, string $driver, string $type)
 * @method static JsonResponse deleteSingleImage(string $name, string $type, string $driver)
 */
class Image extends Facade
{
    const DRIVER_PUBLIC = 'public';
    const DRIVER_LOCAL = 'local';

    protected static function getFacadeAccessor(): string
    {
        return 'image';
    }
}
