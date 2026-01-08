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
    // prefer root-relative placeholder so the browser uses the same host/port as the page
    $placeholder = '/' . ltrim('images/no-image.png', '/');

        if (is_array($path)) {
            $fallbackValue = null;
            foreach ($path as $value) {
                if ($value) {
                    $fallbackValue = $value;
                    break;
                }
            }
            $path = $path['path'] ?? $path['url'] ?? $fallbackValue;
        }

        if (is_string($path)) {
            $trimmedJson = trim($path);
            if ($trimmedJson !== '' && ($trimmedJson[0] === '{' || $trimmedJson[0] === '[')) {
                $decoded = json_decode($trimmedJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return self::url($decoded);
                }
            }
        }

        if (empty($path)) {
            return $placeholder;
        }

        $path = trim((string) $path);
        if ($path === '') {
            return $placeholder;
        }

        // If it's already a full URL, return as-is
        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        $normalized = preg_replace('#^/?storage/#i', '', str_replace('\\', '/', $path)) ?? '';
        if ($normalized === '') {
            return $placeholder;
        }

        $publicRoot = str_replace('\\', '/', public_path());
        $storageRoot = str_replace('\\', '/', storage_path('app/public'));

        if (Str::startsWith($normalized, $publicRoot)) {
            $normalized = ltrim(Str::after($normalized, $publicRoot), '/');
        }

        if (Str::startsWith($normalized, $storageRoot)) {
            $normalized = ltrim(Str::after($normalized, $storageRoot), '/');
        }

        // Remove leading public/ if still present
        $normalized = preg_replace('#^/?public/#i', '', $normalized) ?? $normalized;

        try {
            // check storage public disk (common Laravel setup uses public disk symlinked to public/storage)
            if (Storage::disk('public')->exists($normalized)) {
                // return root-relative path so the browser uses the same host/port as the page
                return '/' . ltrim('storage/' . ltrim($normalized, '/'), '/');
            }
            
            // Try alternate paths with 'previews' instead of 'preview'
            $alternateNormalized = str_replace('/preview/', '/previews/', $normalized);
            if ($alternateNormalized !== $normalized && Storage::disk('public')->exists($alternateNormalized)) {
                return '/' . ltrim('storage/' . ltrim($alternateNormalized, '/'), '/');
            }
            
            // Try with just checking if the parent directory has any similar files
            $pathInfo = pathinfo($normalized);
            if (isset($pathInfo['dirname']) && isset($pathInfo['filename'])) {
                $dir = $pathInfo['dirname'];
                $filename = $pathInfo['filename'];
                $ext = $pathInfo['extension'] ?? 'png';
                
                // Check if there's a file with similar name pattern (for renamed/regenerated files)
                try {
                    $files = Storage::disk('public')->files($dir);
                    foreach ($files as $file) {
                        if (basename($file) === basename($normalized)) {
                            return '/' . ltrim('storage/' . ltrim($file, '/'), '/');
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        } catch (\Throwable $e) {
            // ignore and fallback to public path
        }

        // Dedicated disk lookup ensures invites stored on invitation_templates show correctly.
        if (config('filesystems.disks.invitation_templates')) {
            $invitationPath = ltrim(preg_replace('#^invitation_templates/#i', '', $normalized) ?? $normalized, '/');
            if ($invitationPath !== '' && $invitationPath !== $normalized) {
                try {
                    if (Storage::disk('invitation_templates')->exists($invitationPath)) {
                        return '/' . ltrim('storage/invitation_templates/' . ltrim($invitationPath, '/'), '/');
                    }
                } catch (\Throwable $e) {
                    // ignore and continue
                }
            }
        }

        // if file exists in public/ directory
        if (file_exists(public_path($normalized))) {
            return '/' . ltrim($normalized, '/');
        }

        // Common pattern: sometimes images are stored under storage/app/public and
        // the caller provided a path without 'storage/' prefix. Try with storage/ prefix.
        if (file_exists(public_path('storage/' . ltrim($normalized, '/')))) {
            return '/' . ltrim('storage/' . ltrim($normalized, '/'), '/');
        }
        return $placeholder;
    }
}
