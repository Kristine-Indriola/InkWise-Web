<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Figma API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Figma API integration including API key and endpoints.
    |
    */

    'api_key' => env('FIGMA_API_KEY'),

    'base_url' => 'https://api.figma.com/v1',

    /*
    |--------------------------------------------------------------------------
    | Frame Types to Extract
    |--------------------------------------------------------------------------
    |
    | The types of frames to extract from Figma files for template creation.
    |
    */

    'frame_types' => ['Template', 'Invitation', 'Giveaway', 'Envelope'],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for Figma API responses to reduce API calls.
    |
    */

    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour in seconds
        'prefix' => 'figma_',
    ],
];