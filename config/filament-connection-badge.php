<?php

declare(strict_types=1);

return [
    'enabled' => env('FILAMENT_CONNECTION_BADGE_ENABLED', true),

    'render_hook' => env(
        'FILAMENT_CONNECTION_BADGE_RENDER_HOOK',
        'panels::user-menu.before'
    ),

    'permission' => env('FILAMENT_CONNECTION_BADGE_PERMISSION'),

    'show_label' => true,

    'show_overlay' => false,

    'route' => [
        'prefix' => '_filament-connection-badge',
        'middleware' => ['web'],
        'throttle' => env('FILAMENT_CONNECTION_BADGE_THROTTLE'),
    ],

    'ping_url' => env('FILAMENT_CONNECTION_BADGE_PING_URL'),

    'ping_interval' => 5000,

    'thresholds' => [
        'full' => 200,
        'medium' => 600,
    ],

    'max_samples' => 30,
];
