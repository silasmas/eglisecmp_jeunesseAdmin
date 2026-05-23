<?php

$defaultDisk = env('FILESYSTEM_DISK', 'local');
$usesS3 = $defaultDisk === 's3';

$s3Disk = [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => true,
    'report' => true,
    // Bucket « Bucket owner enforced » : pas d’ACL par objet (accès via politique IAM / bucket).
    'options' => [
        'ACL' => '',
    ],
];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => $defaultDisk,

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => $usesS3
            ? array_merge($s3Disk, ['root' => ''])
            : [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],

        's3' => array_merge($s3Disk, ['root' => '']),

        'media' => $usesS3
            ? array_merge($s3Disk, ['root' => 'mediatheque'])
            : [
                'driver' => 'local',
                'root' => storage_path('app/public/mediatheque'),
                'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage/mediatheque',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
