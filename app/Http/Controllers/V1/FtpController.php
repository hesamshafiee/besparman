<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class FtpController extends Controller
{
    /**
     * @return JsonResponse
     * @group Ftp
     * 
     */
    public function listFiles(): JsonResponse
    {
        try {
            $disk = Storage::disk('ftp');
            $files = $disk->allFiles('/');

            $filesWithTimestamps = collect($files)->map(function ($file) use ($disk) {
                return [
                    'path' => $file,
                    'last_modified' => $disk->lastModified($file),
                ];
            });

            $sortedFiles = $filesWithTimestamps->sortByDesc('last_modified')->values();

            return response()->json($sortedFiles);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to connect or list files',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param $filename
     * @return ResponseFactory|Application|JsonResponse|Response|object
     * @group Ftp
     */
    public function downloadFile($filename)
    {
        try {
            $disk = Storage::disk('ftp');

            if (!$disk->exists($filename)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $fileContents = $disk->get($filename);

            return response($fileContents)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to download file',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
