<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;


class DesignController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'config'          => 'required|string',
            'rendered_image'  => 'required|file|mimes:png|max:5120', // 5MB
            'original_image'  => 'nullable|file|mimes:png,jpg,jpeg,webp|max:10240',
            // 'design_id'     => 'sometimes|string' // اگر آپدیت هست
        ]);

        $config = json_decode($request->input('config'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid config JSON'], 422);
        }

        $designId = Str::uuid()->toString(); // یا اگر design_id اومده، همون

        // مسیر ذخیره‌سازی (storage/app/public/…)
        $basePath = "designs/{$designId}";
        if (!Storage::disk('public')->exists($basePath)) {
            Storage::disk('public')->makeDirectory($basePath);
        }

        $renderedPath = null;
        if ($request->hasFile('rendered_image')) {
            $renderedPath = $request->file('rendered_image')->storeAs($basePath, 'rendered.png', 'public');
        }

        $originalPath = null;
        if ($request->hasFile('original_image')) {
            $ext = $request->file('original_image')->getClientOriginalExtension();
            $originalPath = $request->file('original_image')->storeAs($basePath, "original.$ext", 'public');
        }

        // TODO: در DB ذخیره کن (designs table) با فیلدهای design_id, config(json), paths …

        return response()->json([
            'design_id'     => $designId,
            'rendered_url'  => $renderedPath ? Storage::disk('public')->url($renderedPath) : null,
            'original_url'  => $originalPath ? Storage::disk('public')->url($originalPath) : null,
            'config'        => $config,
        ]);
    }
}
