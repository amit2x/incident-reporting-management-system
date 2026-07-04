<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebP Conversion Settings
    |--------------------------------------------------------------------------
    */
    'webp' => [
        'quality' => env('IMAGE_WEBP_QUALITY', 82),
        'max_width' => env('IMAGE_MAX_WIDTH', 1920),
        'max_height' => env('IMAGE_MAX_HEIGHT', 1920),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Settings
    |--------------------------------------------------------------------------
    */
    'thumbnail' => [
        'width' => env('IMAGE_THUMBNAIL_WIDTH', 300),
        'height' => env('IMAGE_THUMBNAIL_HEIGHT', 300),
        'quality' => env('IMAGE_THUMBNAIL_QUALITY', 70),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Validation
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'max_size' => env('IMAGE_MAX_SIZE', 20 * 1024 * 1024), // 20MB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'disk' => env('IMAGE_STORAGE_DISK', 'public'),
        'path' => env('IMAGE_STORAGE_PATH', 'images'),
    ],
];
