<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = App\Models\Template::find(75);
$product = App\Models\Product::find(36);

echo "Template data:\n";
if ($template) {
    echo "ID: " . $template->id . "\n";
    echo "Name: " . $template->name . "\n";
    echo "front_image: '" . ($template->front_image ?? 'null') . "'\n";
    echo "back_image: '" . ($template->back_image ?? 'null') . "'\n";
    echo "preview: '" . ($template->preview ?? 'null') . "'\n";
    echo "svg_path: '" . ($template->svg_path ?? 'null') . "'\n";
}

echo "\nProduct data:\n";
if ($product) {
    echo "ID: " . $product->id . "\n";
    echo "Name: " . $product->name . "\n";
    echo "image: '" . ($product->image ?? 'null') . "'\n";
    if ($product->images) {
        echo "images->front: '" . ($product->images->front ?? 'null') . "'\n";
        echo "images->back: '" . ($product->images->back ?? 'null') . "'\n";
    }
}

echo "\nResolved URLs:\n";

// Simulate the resolveImage function from studio.blade.php
$resolveImage = function ($path, $fallback) {
    if (!$path) {
        return $fallback;
    }

    try {
        return \App\Support\ImageResolver::url($path);
    } catch (\Throwable $e) {
        return $fallback;
    }
};

$defaultFront = asset('Customerimages/invite/wedding2.png');
$defaultBack = asset('Customerimages/invite/wedding3.jpg');

echo "defaultFront: " . $defaultFront . "\n";
echo "defaultBack: " . $defaultBack . "\n";

$templateFront = $template->preview_front ?? $template->front_image ?? $template->preview ?? $template->image;
$templateBack = $template->preview_back ?? $template->back_image;

echo "templateFront: '" . $templateFront . "'\n";
echo "templateBack: '" . $templateBack . "'\n";

$frontRasterCandidates = [
    $templateFront,
    $product?->images?->front,
    $product?->images?->preview,
    $product?->image,
];

$backRasterCandidates = [
    $templateBack,
    $product?->images?->back,
    $product?->image,
];

$pickFirst = function (array $candidates) {
    foreach ($candidates as $candidate) {
        if (is_array($candidate)) {
            foreach ($candidate as $value) {
                if (!empty($value)) {
                    return $value;
                }
            }
            continue;
        }

        if (!empty($candidate)) {
            return $candidate;
        }
    }

    return null;
};

$frontSource = $pickFirst($frontRasterCandidates);
$backSource = $pickFirst($backRasterCandidates);

echo "frontSource: '" . $frontSource . "'\n";
echo "backSource: '" . $backSource . "'\n";

$frontImage = is_string($frontSource) && str_starts_with($frontSource, 'data:')
    ? $frontSource
    : $resolveImage($frontSource, $defaultFront);

$backImage = is_string($backSource) && str_starts_with($backSource, 'data:')
    ? $backSource
    : $resolveImage($backSource, $defaultBack);

$frontImage = $frontImage ?: $defaultFront;
$backImage = $backImage ?: ($frontImage ?: $defaultBack);

echo "final frontImage: " . $frontImage . "\n";
echo "final backImage: " . $backImage . "\n";

// Test ImageResolver on the template paths
if ($templateFront) {
    echo "ImageResolver for templateFront: " . \App\Support\ImageResolver::url($templateFront) . "\n";
}
if ($templateBack) {
    echo "ImageResolver for templateBack: " . \App\Support\ImageResolver::url($templateBack) . "\n";
}
