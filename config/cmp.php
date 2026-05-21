<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Token de synchronisation des données de base
    |--------------------------------------------------------------------------
    |
    | Si défini, active GET /system/sync-production-base/{token}
    | (réservé aux déploiements / scripts — ne pas exposer publiquement).
    |
    */
    'production_base_sync_token' => env('PRODUCTION_BASE_SYNC_TOKEN'),

];
