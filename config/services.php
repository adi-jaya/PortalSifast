<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram-bot-api' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'username' => env('TELEGRAM_BOT_USERNAME'),
        'base_uri' => env('TELEGRAM_BOT_BASE_URI', 'https://api.telegram.org'),
        'tickets_group_chat_id' => env('TELEGRAM_TICKETS_GROUP_CHAT_ID'),
    ],

    'firebase' => [
        'credentials' => storage_path('app/firebase-credentials.json'),
    ],

    'sikat' => [
        'base_url' => env('SIKAT_BASE_URL', 'https://sikat.rsaisyiyahsitifatimah.com'),
        'sso_secret' => env('PORTALSIFAST_SIKAT_SSO_SECRET'),
    ],

    'simrs' => [
        'pegawai_photo_base_url' => env(
            'SIMRS_PEGAWAI_PHOTO_BASE_URL',
            'http://'.env('DB_HOST_2', '127.0.0.1').'/webapps2/penggajian',
        ),
    ],

    'instagram' => [
        'enabled' => filter_var(env('INSTAGRAM_FEED_ENABLED', false), FILTER_VALIDATE_BOOL),
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
        'user_id' => env('INSTAGRAM_USER_ID'),
        'profile_url' => env('INSTAGRAM_PROFILE_URL', 'https://instagram.com/rsasitifatimah'),
        'default_limit' => (int) env('INSTAGRAM_FEED_LIMIT', 12),
        'cache_minutes' => (int) env('INSTAGRAM_FEED_CACHE_MINUTES', 60),
        'graph_api_version' => env('INSTAGRAM_GRAPH_API_VERSION', 'v21.0'),
    ],

    'external_rss' => [
        'enabled' => filter_var(env('EXTERNAL_RSS_ENABLED', true), FILTER_VALIDATE_BOOL),
        'default_limit' => (int) env('EXTERNAL_RSS_DEFAULT_LIMIT', 20),
        'cache_minutes' => (int) env('EXTERNAL_RSS_CACHE_MINUTES', 60),
        'sources' => [
            [
                'id' => 'suara-muhammadiyah',
                'name' => 'Suara Muhammadiyah',
                'url' => env('EXTERNAL_RSS_SUARA_URL', 'https://web.suaramuhammadiyah.id/feed/'),
                'websiteUrl' => env('EXTERNAL_RSS_SUARA_WEBSITE', 'https://suaramuhammadiyah.id'),
            ],
            [
                'id' => 'muhammadiyah',
                'name' => 'Muhammadiyah.or.id',
                'url' => env('EXTERNAL_RSS_MUHAMMADIYAH_URL', 'https://muhammadiyah.or.id/feed/'),
                'websiteUrl' => env('EXTERNAL_RSS_MUHAMMADIYAH_WEBSITE', 'https://muhammadiyah.or.id'),
            ],
        ],
    ],

    'tianji' => [
        'base_url' => env('TIANJI_BASE_URL'),
        'api_key' => env('TIANJI_API_KEY'),
        'workspace_id' => env('TIANJI_WORKSPACE_ID'),
        'timeout' => (int) env('TIANJI_TIMEOUT', 30),
    ],

    'stirling_pdf' => [
        'url' => env('STIRLING_PDF_URL'),
        'api_key' => env('STIRLING_PDF_API_KEY'),
        'timeout' => (int) env('STIRLING_PDF_TIMEOUT', 120),
        'cert' => [
            'type' => env('STIRLING_PDF_CERT_TYPE', 'custom'),
            'p12_path' => env('STIRLING_PDF_CERT_P12_PATH'),
            'password' => env('STIRLING_PDF_CERT_PASSWORD'),
        ],
        'signature' => [
            'enable_cert_sign' => filter_var(env('STIRLING_PDF_ENABLE_CERT_SIGN', false), FILTER_VALIDATE_BOOL),
            'enable_timestamp' => filter_var(env('STIRLING_PDF_ENABLE_TIMESTAMP', true), FILTER_VALIDATE_BOOL),
            'image_path' => env('STIRLING_PDF_SIGN_IMAGE_PATH'),
            'page' => env('STIRLING_PDF_SIGN_PAGE', 'last'),
            'x_percent' => (int) env('STIRLING_PDF_SIGN_X_PERCENT', 65),
            'y_percent' => (int) env('STIRLING_PDF_SIGN_Y_PERCENT', 82),
            'show_signature' => filter_var(env('STIRLING_PDF_SIGN_SHOW_BLOCK', true), FILTER_VALIDATE_BOOL),
            'reason' => env('STIRLING_PDF_SIGN_REASON', 'Disetujui melalui Portal SIFAST'),
            'location' => env('STIRLING_PDF_SIGN_LOCATION', "RSU 'Aisyiyah Siti Fatimah"),
        ],
    ],

];
