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

    'ga' => [
        'property_id' => env('GOOGLE_ANALYTICS_PROPERTY_ID', '')
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => \App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'flickr' => [
        'api_key' => env('FLICKR_API_KEY'),
        'gallery_uid' => env('FLICKR_GALLERY_USER')
    ],

    'mapbox' => [
        'token' => env('MAPBOX_API_TOKEN')
    ],

    'qrz' => [
        'key' => env('QRZ_API_KEY'),
    ],

    'discord' => [
        'webhook_uri' => env('DISCORD_WEBHOOK_URI')
    ],
    'geoapify' => [
        'key' => env('GEOAPIFY_API_KEY')
    ],
    'digitalocean' => [
        'key' => env('DO_ACCESS_KEY'),
        'cdn' => [
            'id' => env('DO_CDN_ID'),
        ]
    ],
    'spaces' => [
        'key' => env('DO_SPACES_KEY_ID'),
        'secret' => env('DO_SPACES_SECRET_ACCESS_KEY'),
        'region' => env('DO_SPACES_DEFAULT_REGION'),
        'bucket' => env('DO_SPACES_BUCKET'),
    ]
];
