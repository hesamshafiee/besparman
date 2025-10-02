<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\V1\Image\Image;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageController extends Controller
{
    /**
     * @param string $group
     * @param int $id
     * @return JsonResponse
     * @group Image
     */
    public function imageList(string $group, int $id) : JsonResponse
    {
        $class = 'App\Models\\' . $group;
        if (class_exists($class)) {
            $model = $class::findOrFail($id);
            return Image::imageList($model, Image::DRIVER_PUBLIC);
        }

        abort(404);
    }

    /**
     * @param string $group
     * @param int $id
     * @return JsonResponse
     * @group Image
     */
    public function imageListPrivate(string $group, int $id) : JsonResponse
    {
        $class = 'App\Models\\' . $group;
        if (class_exists($class)) {
            $model = $class::findOrFail($id);
            return Image::imageList($model, Image::DRIVER_LOCAL);
        }

        abort(404);
    }

    /**
     * @param string|null $name
     * @return JsonResponse|StreamedResponse
     * @group Image
     */
    public function getPublicImage(string $name = null) : JsonResponse|StreamedResponse
    {
        if (empty($name)) {
            abort(404);
        }

        return Image::getImage($name, Image::DRIVER_PUBLIC, 'model');
    }

    /**
     * @param string|null $name
     * @return JsonResponse|StreamedResponse
     * @group Image
     */
    public function getImage(string $name = null) : JsonResponse|StreamedResponse
    {
        if (empty($name)) {
            abort(404);
        }

        return Image::getImage($name, Image::DRIVER_LOCAL, 'model');
    }

    /**
     * @param string|null $name
     * @param string $driver
     * @param string $type
     * @return JsonResponse
     * @group Image
     */
    public function deleteSingleImage(string $name = null, string $driver = Image::DRIVER_PUBLIC, string $type = 'model') : JsonResponse
    {
        if (empty($name)) {
            abort(404);
        }

        return Image::deleteSingleImage($name, $type, $driver);
    }
}
