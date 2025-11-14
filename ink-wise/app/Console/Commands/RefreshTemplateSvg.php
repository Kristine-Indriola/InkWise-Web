<?php

namespace App\Console\Commands;

use App\Models\Template;
use App\Services\SvgAutoParser;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RefreshTemplateSvg extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'templates:refresh-svg
        {templateIds? : Optional comma-separated list of template IDs to process}
        {--dry-run : Analyse SVGs without writing any changes}
        {--only= : Limit to front or back side (front|back)}';

    /**
     * The console command description.
     */
    protected $description = 'Reprocess and store cleaned SVG previews for templates';

    public function handle(SvgAutoParser $parser): int
    {
        $idsInput = $this->argument('templateIds');
        $desiredSide = $this->option('only');
        $dryRun = (bool) $this->option('dry-run');

        if ($desiredSide && ! in_array($desiredSide, ['front', 'back'], true)) {
            $this->error('Invalid value for --only. Expected "front" or "back".');
            return self::FAILURE;
        }

        $query = Template::query();

        if ($idsInput) {
            $ids = collect(explode(',', $idsInput))
                ->map(fn ($value) => trim($value))
                ->filter();

            if ($ids->isEmpty()) {
                $this->warn('No valid template identifiers provided.');
                return self::INVALID;
            }

            $query->whereIn('id', $ids);
        }

        $templates = $query->get();

        if ($templates->isEmpty()) {
            $this->warn('No templates matched the supplied filters.');
            return self::INVALID;
        }

        $processedCount = 0;

        foreach ($templates as $template) {
            $this->info("Processing template #{$template->id} ({$template->name})");

            $paths = [
                'front' => $template->svg_path,
                'back' => $template->back_svg_path,
            ];

            foreach ($paths as $side => $relativePath) {
                if ($desiredSide && $desiredSide !== $side) {
                    continue;
                }

                if (! $relativePath) {
                    $this->line("  - {$side}: no SVG stored");
                    continue;
                }

                $disk = Storage::disk('public');

                if (! $disk->exists($relativePath)) {
                    $this->warn("  - {$side}: missing file at {$relativePath}");
                    continue;
                }

                try {
                    $original = $disk->get($relativePath);
                } catch (\Throwable $e) {
                    $this->warn("  - {$side}: failed to read SVG ({$e->getMessage()})");
                    continue;
                }

                try {
                    $isFigmaAsset = $this->isFigmaSvg($template, $relativePath);
                    $result = $parser->processSvgContent($original, $isFigmaAsset);
                } catch (\Throwable $e) {
                    $this->warn("  - {$side}: parser error ({$e->getMessage()})");
                    Log::warning('Template SVG reprocess failed', [
                        'template_id' => $template->id,
                        'side' => $side,
                        'path' => $relativePath,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }

                $summary = sprintf(
                    '  - %s: %d text / %d image / %d changeable elements',
                    $side,
                    Arr::get($result, 'metadata.text_count', 0),
                    Arr::get($result, 'metadata.image_count', 0),
                    Arr::get($result, 'metadata.changeable_count', 0)
                );
                $this->line($summary);

                if ($dryRun) {
                    continue;
                }

                try {
                    $disk->put($relativePath, Arr::get($result, 'content', $original));
                    $processedCount++;
                } catch (\Throwable $e) {
                    $this->warn("  - {$side}: failed to write updated SVG ({$e->getMessage()})");
                }
            }

            if (! $dryRun) {
                $template->forceFill([
                    'processed_at' => Carbon::now(),
                ])->save();
            }
        }

        if ($dryRun) {
            $this->info('Dry run complete. No files were modified.');
        } else {
            $this->info("Reprocessed {$processedCount} SVG file(s).");
        }

        return self::SUCCESS;
    }

    private function isFigmaSvg(Template $template, string $path): bool
    {
        if (! empty($template->figma_file_key)) {
            return true;
        }

        return Str::contains(Str::lower($path), 'figma');
    }
}
