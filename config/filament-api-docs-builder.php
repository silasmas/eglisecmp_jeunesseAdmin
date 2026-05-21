<?php

use Filament\Support\Icons\Heroicon;
use ZPMLabs\FilamentApiDocsBuilder\Enums\PredefinedCodeBuilders;
use ZPMLabs\FilamentApiDocsBuilder\Filament\Resources\ApiDocsResource\ApiDocsResource;
use ZPMLabs\FilamentApiDocsBuilder\Models\ApiDocs;

return [
    // Array of custom code builders that can be used for generating API code examples
    'code_builders' => [],

    // Predefined parameters that will be included in API requests by default
    'predefined_params' => [
        [
            'location' => 'header',         // Parameter location (e.g., header, query, body)
            'type' => 'string',            // Data type of the parameter
            'name' => 'Authorization',     // Name of the parameter
            'value' => 'Bearer $TOKEN',    // Default value of the parameter
            'description' => '',           // Optional description of the parameter
            'required' => true,             // Indicates whether the parameter is required
        ],
        [
            'location' => 'header',
            'type' => 'string',
            'name' => 'Content-Type',
            'value' => 'application/json',
            'description' => '',
            'required' => true,
        ],
        [
            'location' => 'header',
            'type' => 'string',
            'name' => 'Accept',
            'value' => 'application/json',
            'description' => '',
            'required' => true,
        ],
    ],

    // Resource class used for managing API documentation within Filament
    'resource' => ApiDocsResource::class,
    'resource_icon' => Heroicon::OutlinedCodeBracket,

    // Model class representing API documentation
    'model' => ApiDocs::class,

    // Configuration for the importer, including predefined code builders
    'importer' => [
        'predefined_codes' => [
            PredefinedCodeBuilders::cURL, // Default predefined code builder
        ],
    ],

    // Flag to indicate whether the current user should be saved during operations
    'save_current_user' => false,

    // Tenant model (if applicable), set to null by default
    'tenant' => null,
];
