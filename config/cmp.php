<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Token de synchronisation des données de base
    |--------------------------------------------------------------------------
    */
    'production_base_sync_token' => env('PRODUCTION_BASE_SYNC_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Disque d'upload / lecture des fichiers publics
    |--------------------------------------------------------------------------
    |
    | Aligné sur FILESYSTEM_DISK (ex. s3). Utilisé par Filament, API et médiathèque.
    |
    */
    'upload_disk' => env('FILESYSTEM_DISK', 'local') === 's3' ? 's3' : 'public',

    /*
    |--------------------------------------------------------------------------
    | Conversions médiathèque (thumb / preview)
    |--------------------------------------------------------------------------
    |
    | false = upload immédiat (original uniquement). true = miniatures en file
    | d’attente (nécessite php artisan queue:work).
    |
    */
    'media_generate_conversions' => env('MEDIA_GENERATE_CONVERSIONS', false),

    /*
    | Durée des URLs signées S3 pour les aperçus (médiathèque, MediaPicker).
    */
    'media_preview_signed_url_ttl_minutes' => (int) env('MEDIA_PREVIEW_SIGNED_URL_TTL_MINUTES', 360),

];
