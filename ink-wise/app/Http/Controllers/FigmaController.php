<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Services\FigmaService;
use App\Services\SvgAutoParser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FigmaController extends Controller
{
    protected $figmaService;
    protected $svgParser;

    public function __construct(FigmaService $figmaService, SvgAutoParser $svgParser)
    {
        $this->figmaService = $figmaService;
        $this->svgParser = $svgParser;
    }

    /**
     * Show the Figma import interface
     */
    public function index()
    {
        return view('staff.figma-import');
    }

    /**
     * Analyze Figma file and return available frames
     */
    public function analyze(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'figma_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Figma URL provided.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $figmaUrl = $request->input('figma_url');
        $fileKey = $this->figmaService->extractFileKey($figmaUrl);

        if (!$fileKey) {
            return response()->json([
                'success' => false,
                'message' => 'Could not extract file key from Figma URL. Please ensure the URL is in the correct format.',
            ], 400);
        }

        $fileData = $this->figmaService->fetchFile($fileKey);

        if (!$fileData) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Figma file. Please check the URL and ensure the file is publicly accessible.',
            ], 400);
        }

        $frames = $this->figmaService->extractTemplateFrames($fileData);

        Log::debug('Figma analyze result', [
            'file_key' => $fileKey,
            'frames_found' => count($frames),
            'frames' => $frames
        ]);

        if (empty($frames)) {
            Log::warning('No template frames found', [
                'file_key' => $fileKey,
                'figma_url' => $figmaUrl
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No template frames found in the Figma file. Frames should be named with keywords like "Template", "Invitation", "Giveaway", "Envelope", "Card", "Design", "Layout", etc. Frame names are case-insensitive.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'file_key' => $fileKey,
            'frames' => $frames,
        ]);
    }

    /**
     * Import selected frames as templates
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_key' => 'required|string',
            'frames' => 'required|array',
            'frames.*.id' => 'required|string',
            'frames.*.name' => 'required|string',
            'frames.*.type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid import data provided.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $fileKey = $request->input('file_key');
        $selectedFrames = $request->input('frames');

        // Group frames by base name to detect front/back pairs
        $frameGroups = $this->groupFramesByDesign($selectedFrames);

        // Fetch SVG images for all selected frames
        $nodeIds = array_column($selectedFrames, 'id');
        $imagesData = $this->figmaService->fetchSvgs($fileKey, $nodeIds);

        if (empty($imagesData) || !isset($imagesData['images'])) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SVG images from Figma.',
            ], 400);
        }

        $importedTemplates = [];
        $errors = [];

        foreach ($frameGroups as $baseName => $frames) {
            try {
                $frontFrame = $frames['front'] ?? null;
                $backFrame = $frames['back'] ?? null;

                if (!$frontFrame) {
                    $errors[] = "No front design found for: {$baseName}";
                    continue;
                }

                // Process front design
                $frontSvgPath = $this->processFrameSvg($frontFrame, $imagesData['images'], $fileKey);
                if (!$frontSvgPath) {
                    $errors[] = "Failed to process front SVG for: {$baseName}";
                    continue;
                }

                $backSvgPath = null;
                if ($backFrame) {
                    $backSvgPath = $this->processFrameSvg($backFrame, $imagesData['images'], $fileKey);
                }

                // Create template record
                $template = Template::create([
                    'name' => $baseName,
                    'event_type' => $this->mapEventType($frontFrame['type']),
                    'product_type' => $frontFrame['type'],
                    'svg_path' => $frontSvgPath['processed_path'],
                    'back_svg_path' => $backSvgPath ? $backSvgPath['processed_path'] : null,
                    'status' => 'active',
                    'figma_file_key' => $fileKey,
                    'figma_node_id' => $frontFrame['id'],
                    'figma_url' => $request->input('figma_url'),
                    'figma_metadata' => json_encode([
                        'front_frame' => $frontFrame,
                        'back_frame' => $backFrame,
                        'original_front_svg' => $frontSvgPath['original_path'],
                        'original_back_svg' => $backSvgPath ? $backSvgPath['original_path'] : null,
                        'processed_at' => now(),
                    ]),
                    'figma_synced_at' => now(),
                    'has_back_design' => !is_null($backFrame),
                ]);

                $importedTemplates[] = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'type' => $template->product_type,
                    'has_back' => !is_null($backFrame),
                ];

                Log::info('Figma template imported', [
                    'template_id' => $template->id,
                    'base_name' => $baseName,
                    'has_back' => !is_null($backFrame),
                    'file_key' => $fileKey,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to import Figma design', [
                    'base_name' => $baseName,
                    'frames' => $frames,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = "Failed to import design '{$baseName}': {$e->getMessage()}";
            }
        }

        return response()->json([
            'success' => !empty($importedTemplates),
            'imported' => $importedTemplates,
            'errors' => $errors,
            'message' => count($importedTemplates) . ' templates imported successfully.' . (!empty($errors) ? ' Some designs failed to import.' : ''),
        ]);
    }

    /**
     * Sync existing Figma template
     */
    public function sync(Request $request, Template $template): JsonResponse
    {
        if (!$template->figma_file_key || !$template->figma_node_id) {
            return response()->json([
                'success' => false,
                'message' => 'Template is not linked to a Figma file.',
            ], 400);
        }

        try {
            // Clear cache for this file
            $this->figmaService->clearCache($template->figma_file_key);

            // Get metadata to check for back frame
            $metadata = json_decode($template->figma_metadata ?? '{}', true) ?: [];

            // Sync front design
            $frontSvgData = $this->syncFrameSvg($template->figma_file_key, $template->figma_node_id, $template->name . ' Front');
            if (!$frontSvgData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync front design.',
                ], 400);
            }

            $updateData = [
                'svg_path' => $frontSvgData['processed_path'],
                'figma_synced_at' => now(),
            ];

            // Sync back design if it exists
            if (isset($metadata['back_frame']) && $template->has_back_design) {
                $backFrame = $metadata['back_frame'];
                $backSvgData = $this->syncFrameSvg($template->figma_file_key, $backFrame['id'], $template->name . ' Back');

                if ($backSvgData) {
                    $updateData['back_svg_path'] = $backSvgData['processed_path'];
                }
            }

            // Update metadata
            $metadata['synced_at'] = now();
            $metadata['original_front_svg'] = $frontSvgData['original_path'];
            if (isset($backSvgData)) {
                $metadata['original_back_svg'] = $backSvgData['original_path'];
            }

            $updateData['figma_metadata'] = json_encode($metadata);

            // Update template
            $template->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Template synced successfully.',
                'template' => $template,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync Figma template', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync template: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Map frame type to event type
     */
    protected function mapEventType(string $frameType): string
    {
        $mapping = [
            'invitation' => 'Wedding',
            'giveaway' => 'Giveaway',
            'envelope' => 'Wedding',
            'template' => 'General',
        ];

        return $mapping[strtolower($frameType)] ?? 'General';
    }

    /**
     * Group frames by design (detecting front/back pairs)
     */
    protected function groupFramesByDesign(array $frames): array
    {
        $groups = [];

        foreach ($frames as $frame) {
            $name = strtolower($frame['name']);

            // Check for front/back indicators
            $isFront = strpos($name, 'front') !== false || strpos($name, 'face') !== false;
            $isBack = strpos($name, 'back') !== false || strpos($name, 'reverse') !== false;

            // Extract base name (remove front/back indicators)
            $baseName = preg_replace('/\s+(front|back|face|reverse)$/i', '', $frame['name']);
            $baseName = trim($baseName);

            if (!isset($groups[$baseName])) {
                $groups[$baseName] = ['front' => null, 'back' => null];
            }

            if ($isFront) {
                $groups[$baseName]['front'] = $frame;
            } elseif ($isBack) {
                $groups[$baseName]['back'] = $frame;
            } else {
                // If no front/back indicator, assume it's front
                if (!$groups[$baseName]['front']) {
                    $groups[$baseName]['front'] = $frame;
                }
            }
        }

        return $groups;
    }

    /**
     * Process SVG for a single frame
     */
    protected function processFrameSvg(array $frame, array $imagesData, string $fileKey): ?array
    {
        $nodeId = $frame['id'];
        $svgUrl = $imagesData[$nodeId] ?? null;

        if (!$svgUrl) {
            return null;
        }

        // Download and store SVG
        $filename = Str::slug($frame['name']) . '_' . $nodeId;
        $svgPath = $this->figmaService->downloadAndStoreSvg($svgUrl, $filename);

        if (!$svgPath) {
            return null;
        }

        // Process SVG through auto-parser to add editable attributes
        $processedSvgData = $this->svgParser->parseSvg($svgPath);

        return [
            'original_path' => $svgPath,
            'processed_path' => $processedSvgData['processed_path'],
            'metadata' => $processedSvgData['metadata'],
        ];
    }

    /**
     * Sync SVG for a single frame
     */
    protected function syncFrameSvg(string $fileKey, string $nodeId, string $frameName): ?array
    {
        // Fetch updated SVG
        $imagesData = $this->figmaService->fetchSvgs($fileKey, [$nodeId]);

        if (empty($imagesData) || !isset($imagesData['images'][$nodeId])) {
            return null;
        }

        $svgUrl = $imagesData['images'][$nodeId];
        $filename = Str::slug($frameName) . '_' . $nodeId;
        $svgPath = $this->figmaService->downloadAndStoreSvg($svgUrl, $filename);

        if (!$svgPath) {
            return null;
        }

        // Process SVG through auto-parser
        $processedSvgData = $this->svgParser->parseSvg($svgPath);

        return [
            'original_path' => $svgPath,
            'processed_path' => $processedSvgData['processed_path'],
            'metadata' => $processedSvgData['metadata'],
        ];
    }
}