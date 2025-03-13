<?php

return [
    'project_id' => env('FIRESTORE_PROJECT_ID', 'your-project-id'),
    'api_key'    => env('FIRESTORE_API_KEY', 'your-api-key'),
    'options'    => [
        'database' => '(default)',
    ],
];
