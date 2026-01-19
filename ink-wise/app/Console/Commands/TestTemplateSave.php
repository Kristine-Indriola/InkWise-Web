<?php

namespace App\Console\Commands;

use App\Models\Template;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestTemplateSave extends Command
{
    protected $signature = 'template:test {id?}';
    protected $description = 'Test template save functionality and verify storage';

    public function handle()
    {
        $this->info('=== Template Save Test ===');
        $this->newLine();

        $templateId = $this->argument('id');

        if ($templateId) {
            $template = Template::find($templateId);
            if (!$template) {
                $this->error("Template #{$templateId} not found");
                return 1;
            }
        } else {
            $template = Template::where('status', 'draft')->first();
            if (!$template) {
                $this->warn('No draft templates found. Creating test template...');
                $template = $this->createTestTemplate();
            }
        }

        $this->info("Testing Template: {$template->name} (ID: {$template->id})");
        $this->newLine();

        // Check design
        $this->checkDesign($template);
        
        // Check storage files
        $this->checkStorage($template);
        
        // Summary
        $this->newLine();
        $this->info('--- Next Steps ---');
        $this->line("1. Open: http://localhost/staff/templates/{$template->id}/editor");
        $this->line('2. Press F12 to open Browser DevTools → Console');
        $this->line('3. Add some elements to the canvas');
        $this->line('4. Click Save Template');
        $this->line('5. Watch console for detailed save logs');
        $this->line('6. Run: php artisan template:test ' . $template->id);

        return 0;
    }

    private function createTestTemplate()
    {
        $template = Template::create([
            'name' => 'Test Template ' . now()->format('Y-m-d H:i:s'),
            'product_type' => 'Invitation',
            'event_type' => 'Birthday',
            'status' => 'draft',
            'width_inch' => 5,
            'height_inch' => 7,
            'design' => json_encode([
                'pages' => [
                    [
                        'id' => 'page-' . uniqid(),
                        'name' => 'Front',
                        'width' => 400,
                        'height' => 600,
                        'background' => '#ffffff',
                        'nodes' => [
                            [
                                'id' => 'text-' . uniqid(),
                                'type' => 'text',
                                'name' => 'Test Title',
                                'content' => 'Birthday Celebration',
                                'frame' => ['x' => 50, 'y' => 100, 'width' => 300, 'height' => 60],
                                'fontSize' => 32,
                                'fontFamily' => 'Inter',
                                'fontWeight' => '600',
                                'visible' => true,
                                'locked' => false,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->info("✓ Created: {$template->name}");
        return $template;
    }

    private function checkDesign($template)
    {
        $this->info('--- Design Check ---');
        
        $design = is_string($template->design) 
            ? json_decode($template->design, true) 
            : $template->design;

        $pageCount = isset($design['pages']) ? count($design['pages']) : 0;
        $nodeCount = isset($design['pages'][0]['nodes']) ? count($design['pages'][0]['nodes']) : 0;

        $this->line("Pages: {$pageCount}");
        $this->line("Nodes in first page: {$nodeCount}");

        if ($pageCount === 0) {
            $this->error('❌ No pages in design!');
        } elseif ($nodeCount === 0) {
            $this->warn('⚠ First page has no nodes (empty canvas)');
        } else {
            $this->info("✓ Design has content ({$nodeCount} nodes)");
        }

        $this->newLine();
    }

    private function checkStorage($template)
    {
        $this->info('--- Storage Check ---');

        // Check preview
        if ($template->preview) {
            $path = ltrim(str_replace('\\', '/', $template->preview), '/');
            if (Storage::disk('public')->exists($path)) {
                $size = Storage::disk('public')->size($path);
                $sizeKB = round($size / 1024, 1);
                
                if ($size < 5000) {
                    $this->warn("⚠ Preview: {$sizeKB} KB (too small - likely blank)");
                } else {
                    $this->info("✓ Preview: {$sizeKB} KB");
                }
            } else {
                $this->error("❌ Preview file not found: {$path}");
            }
        } else {
            $this->warn('⚠ No preview set');
        }

        // Check SVG
        if ($template->svg_path) {
            $path = ltrim(str_replace('\\', '/', $template->svg_path), '/');
            if (Storage::disk('public')->exists($path)) {
                $size = Storage::disk('public')->size($path);
                $this->info("✓ SVG: " . round($size / 1024, 1) . " KB");
            } else {
                $this->error("❌ SVG file not found");
            }
        } else {
            $this->warn('⚠ No SVG set');
        }

        // Check JSON
        $metadata = is_string($template->metadata) 
            ? json_decode($template->metadata, true) 
            : (is_array($template->metadata) ? $template->metadata : []);
            
        $jsonPath = $metadata['json_path'] ?? null;

        if ($jsonPath) {
            $path = ltrim(str_replace('\\', '/', $jsonPath), '/');
            if (Storage::disk('public')->exists($path)) {
                $size = Storage::disk('public')->size($path);
                $content = json_decode(Storage::disk('public')->get($path), true);
                $jsonNodes = isset($content['pages'][0]['nodes']) ? count($content['pages'][0]['nodes']) : 0;
                
                $this->info("✓ JSON: " . round($size / 1024, 1) . " KB ({$jsonNodes} nodes)");
                
                if ($jsonNodes === 0) {
                    $this->warn('⚠ Saved JSON has no nodes');
                }
            } else {
                $this->error("❌ JSON file not found");
            }
        } else {
            $this->warn('⚠ No JSON path in metadata');
        }

        $this->newLine();
    }
}
