<?php

return [
    'seed_admin' => [
        'email' => env('LINGUAPATH_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('LINGUAPATH_ADMIN_PASSWORD', 'password'),
    ],

    'audio_storage_disk' => env('AUDIO_STORAGE_DISK', 'public'),

    'demo_audio_import_path' => env('DEMO_AUDIO_IMPORT_PATH', 'storage/app/imports/listening-audio/manifest.json'),
];
