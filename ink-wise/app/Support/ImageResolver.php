<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageResolver
{
    /**
     * Resolve an image path or URL to a usable public URL.
     * Accepts storage paths (public disk), public relative paths, or full URLs.
     */
    public static function url($path)
    {
        $placeholder = asset('images/no-image.png');
        if (empty($path)) {
            return $placeholder;
        }

        // If it's already a full URL, return as-is
        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        // Normalize common prefixes: remove leading 'storage/' or '/storage/' if present
        $path = preg_replace('#^/??storage/#i', '', $path);

        try {
            // check storage public disk (common Laravel setup uses public disk symlinked to public/storage)
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/' . ltrim($path, '/'));
            }
        } catch (\Throwable $e) {
            // ignore and fallback to public path
        }

        // if file exists in public/ directory
        if (file_exists(public_path($path))) {
            return asset($path);
        }

        // Common pattern: sometimes images are stored under storage/app/public and
        // the caller provided a path without 'storage/' prefix. Try with storage/ prefix.
        if (file_exists(public_path('storage/' . ltrim($path, '/')))) {
            return asset('storage/' . ltrim($path, '/'));
        }

        return $placeholder;
    }
}
