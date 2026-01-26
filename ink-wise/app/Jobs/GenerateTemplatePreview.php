<?php

namespace App\Jobs;

use App\Models\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateTemplatePreview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $templateId;
    public string $designPath;

    public function __construct(int $templateId, string $designPath)
    {
        $this->templateId = $templateId;
        $this->designPath = $designPath;
    }

    public function handle(): void
    {
        try {
            $template = Template::find($this->templateId);
            if (!$template) {
                Log::warning('GenerateTemplatePreview: template not found', ['id' => $this->templateId]);
                return;
            }

            // Attempt to read the design JSON from storage
            $disk = Storage::disk('public');
            $designJson = null;
            if ($this->designPath && $disk->exists($this->designPath)) {
                $designJson = $disk->get($this->designPath);
            }

            // If advanced renderer available (Browsershot), user can extend this job to use it.
            // For now, create a simple placeholder PNG so the template has a usable preview.
            $width = 1200;
            $height = 1200;
            $filename = 'templates/front/png/' . 'template_' . Str::uuid() . '.png';

            // Create a simple white PNG with GD if available
            if (function_exists('imagecreatetruecolor')) {
                $img = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($img, 255, 255, 255);
                $black = imagecolorallocate($img, 0, 0, 0);
                imagefilledrectangle($img, 0, 0, $width, $height, $white);
                $text = 'Preview pending';
                // Try to write centered text
                imagestring($img, 5, 20, intval($height / 2) - 10, $text, $black);
                ob_start();
                imagepng($img);
                $contents = ob_get_clean();
                imagedestroy($img);

                $disk->put($filename, $contents);
            } else {
                // Fallback: write a readable placeholder PNG (600x400) when GD is unavailable
                $dummy = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAlgAAAGQCAYAAAByNR6YAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAcmSURBVHhe7dahEQAgEMAw9t+XQz4eS2VETCfo2mcGAIDOegMAAH8MFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQMFgBAzGABAMQuGVdGC8fqbB8AAAAASUVORK5CYII=');
                $disk->put($filename, $dummy);
            }

            // Update template record
            $template->preview = $filename;
            $template->preview_front = $filename;

            $shouldUpdateFront = empty($template->front_image);
            if (!$shouldUpdateFront && $disk->exists($template->front_image)) {
                try {
                    if ($disk->size($template->front_image) < 1024) {
                        $shouldUpdateFront = true;
                    }
                } catch (\Throwable $sizeCheckError) {
                    $shouldUpdateFront = true;
                }
            } else {
                $shouldUpdateFront = true;
            }

            if ($shouldUpdateFront) {
                $template->front_image = $filename;
            }

            $metadata = $template->metadata ?? [];
            if (!is_array($metadata)) {
                $metadata = json_decode(json_encode($metadata), true) ?: [];
            }
            $metadata['preview_status'] = 'generated';
            $template->metadata = $metadata;
            $template->save();

            Log::info('GenerateTemplatePreview: generated placeholder preview', ['template_id' => $template->id, 'preview' => $filename]);
        } catch (\Throwable $e) {
            Log::error('GenerateTemplatePreview failed', ['error' => $e->getMessage(), 'template_id' => $this->templateId]);
        }
    }
}
