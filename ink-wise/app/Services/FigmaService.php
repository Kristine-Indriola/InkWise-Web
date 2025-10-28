<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FigmaService
{
    protected $apiKey;
    protected $baseUrl;
    protected $cacheEnabled;
    protected $cacheTtl;
    protected $cachePrefix;

    public function __construct()
    {
        $this->apiKey = config('figma.api_key');
        $this->baseUrl = config('figma.base_url');
        $this->cacheEnabled = config('figma.cache.enabled');
        $this->cacheTtl = config('figma.cache.ttl');
        $this->cachePrefix = config('figma.cache.prefix');
    }

    /**
     * Extract file key from Figma URL
     *
     * @param string $url
     * @return string|null
     */
    public function extractFileKey(string $url): ?string
    {
        // Match patterns like:
        // https://www.figma.com/file/AbCdEfGhIjKlMnOpQrSt/Template
        // https://www.figma.com/design/AbCdEfGhIjKlMnOpQrSt/Template
        $patterns = [
            '/figma\.com\/(?:file|design)\/([A-Za-z0-9]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Fetch Figma file data
     *
     * @param string $fileKey
     * @return array|null
     */
    public function fetchFile(string $fileKey): ?array
    {
        $cacheKey = $this->cachePrefix . 'file_' . $fileKey;

        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withHeaders([
                'X-Figma-Token' => $this->apiKey,
            ])->get("{$this->baseUrl}/files/{$fileKey}");

            if ($response->successful()) {
                $data = $response->json();

                if ($this->cacheEnabled) {
                    Cache::put($cacheKey, $data, $this->cacheTtl);
                }

                return $data;
            }

            Log::error('Figma API error', [
                'file_key' => $fileKey,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Figma API exception', [
                'file_key' => $fileKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract template frames from Figma file
     *
     * @param array $fileData
     * @return array
     */
    public function extractTemplateFrames(array $fileData): array
    {
        $frames = [];
        $frameTypes = config('figma.frame_types');

        if (!isset($fileData['document']['children'])) {
            return $frames;
        }

        $this->traverseNodes($fileData['document']['children'], $frameTypes, $frames);

        return $frames;
    }

    /**
     * Recursively traverse nodes to find template frames
     *
     * @param array $nodes
     * @param array $frameTypes
     * @param array &$frames
     */
    protected function traverseNodes(array $nodes, array $frameTypes, array &$frames): void
    {
        foreach ($nodes as $node) {
            // Check if this is a frame with matching name
            if (isset($node['type']) && $node['type'] === 'FRAME') {
                $nodeName = $node['name'] ?? '';

                foreach ($frameTypes as $type) {
                    if (stripos($nodeName, $type) !== false) {
                        $frames[] = [
                            'id' => $node['id'],
                            'name' => $nodeName,
                            'type' => $this->determineCategory($nodeName),
                            'bounds' => $node['absoluteBoundingBox'] ?? null,
                        ];
                        break;
                    }
                }
            }

            // Recursively check children
            if (isset($node['children']) && is_array($node['children'])) {
                $this->traverseNodes($node['children'], $frameTypes, $frames);
            }
        }
    }

    /**
     * Determine category from frame name
     *
     * @param string $name
     * @return string
     */
    protected function determineCategory(string $name): string
    {
        $name = strtolower($name);

        if (strpos($name, 'invitation') !== false) {
            return 'invitation';
        }

        if (strpos($name, 'giveaway') !== false) {
            return 'giveaway';
        }

        if (strpos($name, 'envelope') !== false) {
            return 'envelope';
        }

        return 'template';
    }

    /**
     * Fetch SVG images for frames
     *
     * @param string $fileKey
     * @param array $nodeIds
     * @return array
     */
    public function fetchSvgs(string $fileKey, array $nodeIds): array
    {
        $cacheKey = $this->cachePrefix . 'images_' . $fileKey . '_' . md5(implode(',', $nodeIds));

        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withHeaders([
                'X-Figma-Token' => $this->apiKey,
            ])->get("{$this->baseUrl}/images/{$fileKey}", [
                'ids' => implode(',', $nodeIds),
                'format' => 'svg',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($this->cacheEnabled) {
                    Cache::put($cacheKey, $data, $this->cacheTtl);
                }

                return $data;
            }

            Log::error('Figma images API error', [
                'file_key' => $fileKey,
                'node_ids' => $nodeIds,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Figma images API exception', [
                'file_key' => $fileKey,
                'node_ids' => $nodeIds,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Download and store SVG file
     *
     * @param string $svgUrl
     * @param string $filename
     * @return string|null
     */
    public function downloadAndStoreSvg(string $svgUrl, string $filename): ?string
    {
        try {
            $response = Http::get($svgUrl);

            if ($response->successful()) {
                $path = "templates/{$filename}.svg";
                Storage::disk('public')->put($path, $response->body());

                return $path;
            }

            Log::error('Failed to download SVG', [
                'url' => $svgUrl,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SVG download exception', [
                'url' => $svgUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if SVG has front and back frames
     *
     * @param string $svgContent
     * @return array
     */
    public function analyzeSvgStructure(string $svgContent): array
    {
        $hasFront = strpos($svgContent, 'front') !== false || strpos($svgContent, 'Front') !== false;
        $hasBack = strpos($svgContent, 'back') !== false || strpos($svgContent, 'Back') !== false;

        return [
            'has_front' => $hasFront,
            'has_back' => $hasBack,
            'is_dual_sided' => $hasFront && $hasBack,
        ];
    }

    /**
     * Clear cache for a specific file
     *
     * @param string $fileKey
     */
    public function clearCache(string $fileKey): void
    {
        if ($this->cacheEnabled) {
            Cache::forget($this->cachePrefix . 'file_' . $fileKey);

            // Clear all image caches for this file (this is a broad clear)
            $cacheKeys = Cache::getStore()->getMemcached()->getAllKeys();
            foreach ($cacheKeys as $key) {
                if (strpos($key, $this->cachePrefix . 'images_' . $fileKey) === 0) {
                    Cache::forget(str_replace(':', '', $key));
                }
            }
        }
    }
}