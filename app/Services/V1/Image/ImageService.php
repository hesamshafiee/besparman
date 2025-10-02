<?php

namespace App\Services\V1\Image;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageService
{
    /**
     * @param Model $model
     * @param array $images
     * @param string $driver
     * @return bool
     */
    public function modelImages(Model $model, array $images, string $driver) : bool
    {
        $modelName = $this->modelName($model);
        $main = true;
        $userId = Auth::check() ? Auth::id() : '';
        $adminId = 0;

        if ($modelName === 'Conversation') {
            $ticket = Ticket::findOrFail($model->id);
            $userId = $ticket->user_id;
            $adminId = Auth::check() ? Auth::id() : '';
        }

        foreach (Storage::disk($driver)->allFiles('models/' . $modelName) as $path) {
            if(str_contains($path, $modelName . '-' . $model->id . '-' . $userId . '-' . $adminId . '-' . 'main')) {
                $main = false;
            }
        }

        $directory = $this->directory($model);
        foreach($images as $image)
        {
            $mainFile = $main ? '-main' : '';
            $respond = Storage::disk($driver)->putFileAs($directory, $image, $modelName . '-' . $model->id . '-' . $userId . '-' . $adminId . $mainFile . '-' . mt_rand(1, 100000));

            if (!$respond) {
                return false;
            }

            $main = false;
        }

        return true;
    }

    /**
     * @param Model $model
     * @param string $driver
     * @return bool
     */
    public function deletingModelImages(Model $model, string $driver) : bool
    {
        $directory = $this->directory($model);
        return Storage::disk($driver)->deleteDirectory($directory);
    }

    /**
     * @param Model $model
     * @param string $driver
     * @return JsonResponse
     */
    public function imageList(Model $model, string $driver) : JsonResponse
    {
        $directory = $this->directory($model);
        if($driver === Image::DRIVER_LOCAL) {
            $files = Storage::disk(Image::DRIVER_LOCAL)->files($directory);

            if (!Auth::check()) {
                return abort(403);
            }

            if (!Auth::user()->can(strtolower($this->modelName($model)) . '.show')) {
                $userId = Auth::id();
                $allFiles = Storage::disk(Image::DRIVER_LOCAL)->files($directory);

                $files = array_filter($allFiles, function ($file) use ($userId) {
                    $filename = basename($file);
                    $parts = explode('-', $filename);

                    return isset($parts[2]) && $parts[2] == $userId;
                });
            }
        } else if ($driver === Image::DRIVER_PUBLIC) {
            $files = Storage::disk(Image::DRIVER_PUBLIC)->files($directory);
        }

        return response()->json([
            'paths' => $this->fileNames($files),
        ], 200);
    }

    /**
     * @param string $name
     * @param string $driver
     * @param string $type
     * @return StreamedResponse
     */
    public function getImage(string $name, string $driver = Image::DRIVER_LOCAL, string $type = 'model') : StreamedResponse
    {
        if ($driver === Image::DRIVER_LOCAL) {
            $parts = explode('-', $name);

            if (Auth::check() && (Auth::user()->can(strtolower($this->modelNameFromString($name)) . '.show') || isset($parts[2]) && $parts[2] === (string) Auth::id())) {
                return $this->fetchImage($name, $driver, $type);
            } else {
                return abort(403);
            }
        } else {
            return $this->fetchImage($name, $driver, $type);
        }
    }

    /**
     * @param string $name
     * @param string $type
     * @param string $driver
     * @return JsonResponse
     */
    public function deleteSingleImage(string $name, string $type, string $driver) : JsonResponse
    {

        if (Auth::user()->can(strtolower($this->modelNameFromString($name)) . '.image')) {
            $path = $this->path($name, $type);
            if ($driver === Image::DRIVER_LOCAL) {
                if (Storage::disk(Image::DRIVER_LOCAL)->exists($path)) {
                    if (Storage::disk(Image::DRIVER_LOCAL)->delete($path)) {
                        return Response()->ok(__('general.deletedSuccessfully', ['id' => $name]));
                    }
                }
            } else {
                if (Storage::disk(Image::DRIVER_PUBLIC)->exists($path)) {
                    if (Storage::disk(Image::DRIVER_PUBLIC)->delete($path)) {
                        return Response()->ok(__('general.deletedSuccessfully', ['id' => $name]));
                    }
                }
            }
            return abort(404);
        } else {
            return abort(403);
        }
    }

    /**
     * @param string $name
     * @param string $driver
     * @param string $type
     * @return StreamedResponse|null
     */
    private function fetchImage(string $name, string $driver, string $type) : StreamedResponse|null
    {
        $path = $this->path($name, $type);
        if (Storage::disk($driver)->exists($path)) {
            return Storage::disk($driver)->response($path);
        }

        return abort(404);
    }

    /**
     * @param string $name
     * @param string $type
     * @return string|bool
     */
    private function path(string $name, string $type) : string|bool
    {
        if ($type === 'model') {
            $explode = explode('-', $name);
            $modelName = $explode[0] ?? '';
            $id = $explode[1] ?? '';
            return 'models/' . $modelName . '/' . $modelName . $id . '/' . $name;
        }

        return false;
    }

    /**
     * @param Model $model
     * @return string
     */
    private function directory(Model $model) : string
    {
        $modelName = $this->modelName($model);
        return 'models/' . $modelName . '/' . $modelName . $model->id;
    }

    /**
     * @param Model $model
     * @return string
     */
    private function modelName(Model $model) : string
    {
        return class_basename($model);
    }

    /**
     * @param string $name
     * @return string
     */
    private function modelNameFromString(string $name) : string
    {
        $explode = explode('-', $name);
        return $explode[0] ?? '';
    }

    /**
     * @param array $files
     * @return array
     */
    private function fileNames(array $files = []) : array
    {
        $names = [];
        foreach ($files as $file) {
            $explode = explode('/', $file);
            $names[] = end($explode);
        }

        return $names;
    }
}
