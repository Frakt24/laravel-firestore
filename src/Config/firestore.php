<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firestore Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Firestore service.
    | You can get these values from your Google Cloud Console.
    |
    */

    'project_id' => env('FIRESTORE_PROJECT_ID'),
    'private_key_id' => env('FIRESTORE_PRIVATE_KEY_ID'),
    'private_key' => env('FIRESTORE_PRIVATE_KEY'),
    'client_email' => env('FIRESTORE_CLIENT_EMAIL'),
    'client_id' => env('FIRESTORE_CLIENT_ID'),
    'client_x509_cert_url' => env('FIRESTORE_CLIENT_CERT_URL'),

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the database ID and other settings.
    |
    */

    'database' => env('FIRESTORE_DATABASE', '(default)'),
    'retry' => env('FIRESTORE_RETRY', true),
    'timeout' => env('FIRESTORE_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for Firestore queries and documents.
    |
    */

    'cache' => env('FIRESTORE_CACHE', true),
    'cache_ttl' => env('FIRESTORE_CACHE_TTL', 3600),
    'cache_prefix' => env('FIRESTORE_CACHE_PREFIX', 'firestore'),

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default settings for Firestore models.
    |
    */

    'models' => [
        'timestamps' => true,
        'soft_deletes' => true,
        'created_at' => 'createdAt',
        'updated_at' => 'updatedAt',
        'deleted_at' => 'deletedAt',
    ],
];
