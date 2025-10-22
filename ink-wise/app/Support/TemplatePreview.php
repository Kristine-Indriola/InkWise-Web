<?php
namespace App\Support;

use Illuminate\Support\Facades\Storage;

class TemplatePreview
{
    /**
     * Resolve a preview input into a browser-friendly URL.
     * Attempts to accept absolute URLs, public paths, storage keys, and filesystem paths.
     * Returns $fallback when unable to resolve.
     *
     * @param string|null $raw
     * @param string $fallback
     * @return string|null
     */
    public static function resolvePreview($raw, $fallback)
    {
        $raw = $raw ?? null;
        if (empty($raw)) return $fallback;
        $raw = trim($raw);
        $raw = str_replace('\\', '/', $raw);
        $pathOnly = parse_url($raw, PHP_URL_PATH) ?: $raw;

        if (preg_match('#^https?://#i', $raw)) return $raw;

        if (stripos($raw, 'public/storage') !== false || stripos($raw, '/storage/templates/') !== false || stripos($raw, 'storage/templates') !== false) {
            $after = preg_split('#public#i', $raw);
            $candidate = end($after);
            $candidate = ltrim($candidate, '/\\');
            $base = basename($pathOnly);
            if ($base && file_exists(public_path('templates/' . $base))) {
                return asset('templates/' . $base);
            }
            if ($candidate) {
                if (stripos($candidate, 'storage/') === 0) {
                    return asset($candidate);
                }
                return asset('storage/' . ltrim($candidate, '/'));
            }
        }

        if (str_contains($pathOnly, '/storage/templates/')) {
            $base = basename($pathOnly);
            if ($base && file_exists(public_path('templates/' . $base))) {
                return asset('templates/' . $base);
            }
            return asset('storage/templates/' . $base);
        }

        if (str_starts_with($raw, '/')) {
            if (str_starts_with($pathOnly, '/storage/templates/')) {
                $base = basename($pathOnly);
                if ($base && file_exists(public_path('templates/' . $base))) {
                    return asset('templates/' . $base);
                }
            }
            return asset(ltrim($raw, '/'));
        }

        if (str_starts_with($raw, 'storage/')) {
            if (str_starts_with($raw, 'storage/templates/')) {
                $base = basename($pathOnly);
                if ($base && file_exists(public_path('templates/' . $base))) {
                    return asset('templates/' . $base);
                }
            }
            return asset($raw);
        }

        if (str_starts_with($raw, 'templates/')) {
            $base = basename($pathOnly);
            if ($base && file_exists(public_path('templates/' . $base))) {
                return asset('templates/' . $base);
            }
            return asset('storage/' . ltrim($raw, '/'));
        }

        try {
            if (Storage::disk('public')->exists(ltrim($raw, '/'))) {
                return asset('storage/' . ltrim($raw, '/'));
            }
        } catch (\Throwable $e) {
            // ignore
        }

        if (preg_match('#^[A-Za-z]:/#', $raw) || preg_match('#^/#', $raw)) {
            $base = basename($pathOnly);
            if ($base) {
                try {
                    if (Storage::disk('public')->exists('templates/' . $base)) {
                        if (file_exists(public_path('templates/' . $base))) {
                            return asset('templates/' . $base);
                        }
                        return asset('storage/templates/' . $base);
                    }
                } catch (\Throwable $e) {}
                if (file_exists(public_path('storage/templates/' . $base))) {
                    return asset('storage/templates/' . $base);
                }
                if (file_exists(public_path('templates/' . $base))) {
                    return asset('templates/' . $base);
                }
            }
        }

        if (file_exists(public_path($raw))) return asset($raw);
        return $fallback;
    }

    /**
     * Normalize an output URL to a web-friendly path when possible.
     * If input is already root-relative or absolute URL, returns input.
     *
     * @param string|null $u
     * @return string|null
     */
    public static function normalizeToWebUrl($u)
    {
        if (empty($u)) return $u;
        $u = str_replace('\\', '/', $u);
        if (preg_match('#^https?://#i', $u) || str_starts_with($u, '/')) return $u;
        if (stripos($u, '/public/') !== false) {
            $parts = preg_split('#/public/#i', $u);
            $after = end($parts);
            return '/' . ltrim(str_replace('\\', '/', $after), '/');
        }
        if (stripos($u, 'public/') !== false) {
            $parts = preg_split('#public/#i', $u);
            $after = end($parts);
            return '/' . ltrim(str_replace('\\', '/', $after), '/');
        }
        if (stripos($u, 'storage/templates/') !== false) {
            $idx = stripos($u, 'storage/templates/');
            return '/' . ltrim(substr($u, $idx), '/\\');
        }
        if (stripos($u, 'templates/') !== false) {
            $base = basename($u);
            if ($base) {
                if (file_exists(public_path('storage/templates/' . $base))) {
                    return '/storage/templates/' . $base;
                }
                if (file_exists(public_path('templates/' . $base))) {
                    return '/templates/' . $base;
                }
            }
        }
        return $u;
    }
}
