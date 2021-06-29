<?php

return [
    'default_cache_ttl' => 24*60*60, // 24 hours
    'http_statuses' => [
        'forbidden' => 403,
    ],
    'log_groups' => [
        'auth' => 'auth',
        'core' => 'core',
    ],
    'user_model' => \RushApp\Core\Models\User::class,
    'default_language' => 'en',
];