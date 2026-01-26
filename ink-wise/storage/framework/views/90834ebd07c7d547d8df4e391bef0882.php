<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($product->name ?? optional($template)->name ?? 'InkWise Studio'); ?> &mdash; InkWise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Lora:wght@400;500;600;700&family=Raleway:wght@300;400;500;600;700&family=Pacifico&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Open+Sans:wght@300;400;500;600;700&family=Bebas+Neue&family=Allura&family=Alex+Brush&family=Dancing+Script&family=Cinzel:wght@400;500;600;700&family=Abril+Fatface&family=Cormorant+SC:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&family=Crimson+Text:wght@400;600;700&family=Josefin+Sans:wght@300;400;500;600;700&family=Tangerine&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-rounded/css/uicons-solid-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-straight/css/uicons-solid-straight.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <?php if(app()->environment('local')): ?>
        <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
    <?php endif; ?>
    <?php echo app('Illuminate\Foundation\Vite')([
        'resources/css/customer/studio.css',
        'resources/js/customer/studio/main.jsx',
    ]); ?>
</head>
 <?php /**PATH C:\xampp\htdocs\InkWise-Web\ink-wise\resources\views/customer/studio/_head.blade.php ENDPATH**/ ?>