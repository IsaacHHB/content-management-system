<?php

return [
    'allowed_domains' => array_values(array_filter(array_map(
        static fn (string $domain): string => strtolower(trim($domain)),
        explode(',', (string) env('ADMIN_ALLOWED_DOMAINS', 'nativedadsnetwork.org')),
    ))),
    'invite_expiry_days' => (int) env('ADMIN_INVITE_EXPIRY_DAYS', 7),
    'settings_media_keys' => ['logo', 'partner_banner'],
    'seed_superadmin' => [
        'name' => env('SEED_SUPERADMIN_NAME'),
        'email' => env('SEED_SUPERADMIN_EMAIL'),
        'password' => env('SEED_SUPERADMIN_PASSWORD'),
    ],
    'seed_admin' => [
        'name' => env('SEED_ADMIN_NAME'),
        'email' => env('SEED_ADMIN_EMAIL'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],
];
