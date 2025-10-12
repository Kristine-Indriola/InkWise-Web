<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Support\ImageResolver;
use Illuminate\Support\Str;

$p = Product::with(['uploads','images','template'])->where('product_type','Giveaway')->first();
if (!$p) {
    echo "No giveaway product found\n";
    exit(1);
}

echo "Product ID: {$p->id}\nName: {$p->name}\n";
$uploads = $p->uploads ?? collect();
if (!($uploads instanceof \Illuminate\Support\Collection)) {
    $uploads = collect($uploads);
}
$imageUpload = $uploads->first(fn($upload)=> Str::startsWith($upload->mime_type ?? '', 'image/'));

echo "Found upload: ".json_encode($imageUpload?->toArray() ?? null)."\n";

$previewSrc = null;
if ($imageUpload) {
    $uploadPath = 'uploads/products/' . $p->id . '/' . $imageUpload->filename;
    $previewSrc = ImageResolver::url($uploadPath);
    echo "From uploads -> $uploadPath -> $previewSrc\n";
}

if (!$previewSrc && $p->images) {
    $imageRecord = $p->images;
    $candidate = $imageRecord->front ?? $imageRecord->preview ?? $imageRecord->back ?? null;
    echo "Candidate from product images: $candidate\n";
    if ($candidate) {
        $previewSrc = ImageResolver::url($candidate);
        echo "From images -> $previewSrc\n";
    }
}

if (!$previewSrc && $p->image) {
    $blacklist = ['ink.png', 'logo.png', 'favicon.ico', 'inkwise.png', 'logo.svg', 'default.png'];
    $imgCandidate = $p->image;
    $basename = strtolower(trim(basename((string) $imgCandidate)));
    $isBlacklisted = in_array($basename, $blacklist, true) || strpos((string)$imgCandidate, 'adminimage') !== false;
    echo "Product image candidate: $imgCandidate (basename: $basename) blacklist? ".($isBlacklisted? 'yes':'no')."\n";
    if (!$isBlacklisted) {
        $previewSrc = ImageResolver::url($imgCandidate);
        echo "From product->image -> $previewSrc\n";
    }
}

if (!$previewSrc && $p->template) {
    $templatePreview = $p->template->preview_front
        ?? $p->template->front_image
        ?? $p->template->preview
        ?? $p->template->image
        ?? null;
    echo "Template preview candidate: $templatePreview\n";
    if ($templatePreview) {
        if (preg_match('/^(https?:)?\/\//i', $templatePreview) || Str::startsWith($templatePreview, '/')) {
            $previewSrc = $templatePreview;
        } else {
            $previewSrc = \Illuminate\Support\Facades\Storage::url($templatePreview);
        }
        echo "From template -> $previewSrc\n";
    }
}

if (!$previewSrc) {
    $previewSrc = asset('images/no-image.png');
    echo "Fell back to placeholder -> $previewSrc\n";
}

echo "Final previewSrc: $previewSrc\n";

