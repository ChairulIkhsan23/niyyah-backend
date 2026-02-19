<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Ramadhan API Documentation',
            ],
            'routes' => [
                'api' => 'api/documentation',
            ],
            'paths' => [
                'use_absolute_path' => true,
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => 'json',
                'annotations' => [
                    base_path('app/Http/Controllers/Api'),
                ],
            ],
        ],
    ],
    'routes' => [
        'api' => [
            'middleware' => [
                'api',
            ],
        ],
        'docs' => [
            'middleware' => [
                'web',
            ],
        ],
    ],
    'paths' => [
        'docs' => storage_path('api-docs'),
        'views' => base_path('resources/views/vendor/l5-swagger'),
        'base' => env('L5_SWAGGER_BASE_PATH', null),
        'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
    ],
];