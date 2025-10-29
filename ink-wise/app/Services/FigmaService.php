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

        Log::debug('Extracting template frames', [
            'frame_types' => $frameTypes,
            'has_document' => isset($fileData['document']),
            'has_children' => isset($fileData['document']['children']),
            'children_count' => isset($fileData['document']['children']) ? count($fileData['document']['children']) : 0
        ]);

        if (!isset($fileData['document']['children'])) {
            Log::warning('No document children found in Figma file data');
            return $frames;
        }

        // First try to find frames with strict matching
        $this->traverseNodes($fileData['document']['children'], $frameTypes, $frames);

        // If no frames found with strict matching, try a more lenient approach
        if (empty($frames)) {
            Log::debug('No frames found with strict matching, trying lenient approach');
            $this->traverseNodesLenient($fileData['document']['children'], $frames);
        }

        Log::debug('Template frames extraction complete', [
            'found_frames' => count($frames),
            'frames' => array_map(function($frame) {
                return ['name' => $frame['name'], 'type' => $frame['type']];
            }, $frames)
        ]);

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
            // Log all nodes for debugging
            if (isset($node['type']) && isset($node['name'])) {
                Log::debug('Figma node found', [
                    'type' => $node['type'], 
                    'name' => $node['name']
                ]);
            }

            // Check if this is a frame with matching name
            if (isset($node['type']) && $node['type'] === 'FRAME') {
                $nodeName = $node['name'] ?? '';

                Log::debug('Checking frame for templates', [
                    'frame_name' => $nodeName,
                    'expected_types' => $frameTypes
                ]);

                $matched = false;
                foreach ($frameTypes as $type) {
                    $lowerName = strtolower($nodeName);
                    $lowerType = strtolower($type);
                    
                    // Check for exact match, contains match, or starts/ends with match
                    if ($lowerName === $lowerType || 
                        strpos($lowerName, $lowerType) !== false ||
                        strpos($lowerType, $lowerName) !== false) {
                        
                        Log::debug('Found matching frame', [
                            'frame_name' => $nodeName,
                            'matched_type' => $type,
                            'lower_name' => $lowerName,
                            'lower_type' => $lowerType
                        ]);

                        $frames[] = [
                            'id' => $node['id'],
                            'name' => $nodeName,
                            'type' => $this->determineCategory($nodeName),
                            'bounds' => $node['absoluteBoundingBox'] ?? null,
                        ];
                        $matched = true;
                        break;
                    }
                }
                
                if (!$matched) {
                    Log::debug('Frame did not match any type', [
                        'frame_name' => $nodeName,
                        'frame_types' => $frameTypes
                    ]);
                }
            }

            // Recursively check children
            if (isset($node['children']) && is_array($node['children'])) {
                $this->traverseNodes($node['children'], $frameTypes, $frames);
            }
        }
    }

    /**
     * More lenient traversal for finding any frames that could be templates
     */
    protected function traverseNodesLenient(array $nodes, array &$frames): void
    {
        foreach ($nodes as $node) {
            // Check if this is a frame
            if (isset($node['type']) && $node['type'] === 'FRAME') {
                $nodeName = $node['name'] ?? '';
                
                // Include frames that might be templates based on common patterns
                $include = false;
                $lowerName = strtolower($nodeName);
                
                // Check for common template keywords
                $templateKeywords = [
                    'template', 'invitation', 'giveaway', 'envelope', 
                    'card', 'design', 'layout', 'front', 'back',
                    'cover', 'page', 'artboard'
                ];
                
                foreach ($templateKeywords as $keyword) {
                    if (strpos($lowerName, $keyword) !== false) {
                        $include = true;
                        break;
                    }
                }
                
                // Also include frames with dimensions that look like templates
                if (!$include && isset($node['absoluteBoundingBox'])) {
                    $width = $node['absoluteBoundingBox']['width'] ?? 0;
                    $height = $node['absoluteBoundingBox']['height'] ?? 0;
                    
                    // Common template sizes (rough ranges)
                    if (($width >= 200 && $width <= 2000) && ($height >= 200 && $height <= 2000)) {
                        $include = true;
                        Log::debug('Including frame based on size', [
                            'name' => $nodeName,
                            'width' => $width,
                            'height' => $height
                        ]);
                    }
                }

                if ($include) {
                    Log::debug('Including frame with lenient matching', [
                        'frame_name' => $nodeName
                    ]);

                    $frames[] = [
                        'id' => $node['id'],
                        'name' => $nodeName,
                        'type' => $this->determineCategory($nodeName),
                        'bounds' => $node['absoluteBoundingBox'] ?? null,
                    ];
                }
            }

            // Recursively check children
            if (isset($node['children']) && is_array($node['children'])) {
                $this->traverseNodesLenient($node['children'], $frames);
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