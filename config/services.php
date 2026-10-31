<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // [MASUKKAN API KEY DI .ENV] - Google OAuth untuk Login Petugas & Admin
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    // [MASUKKAN API KEY DI .ENV] - Telegram Bot API untuk Siaran Kanal & Tiket Petugas
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'PyroGuardBromo_bot'),
        'public_channel_id' => env('TELEGRAM_PUBLIC_CHANNEL_ID', '@PyroGuard'),
        'officer_group_id' => env('TELEGRAM_OFFICER_GROUP_ID'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    // [MASUKKAN API KEY DI .ENV] - Open-Meteo API (Cuaca & Prakiraan Kawasan)
    'open_meteo' => [
        'base_url' => env('OPEN_METEO_BASE_URL', 'https://api.open-meteo.com/v1/forecast'),
    ],

    // [MASUKKAN API KEY DI .ENV] - NASA FIRMS Satelit Titik Panas (MODIS / VIIRS)
    'firms' => [
        'api_key' => env('NASA_FIRMS_API_KEY'),
        'base_url' => env('NASA_FIRMS_BASE_URL', 'https://firms.modaps.eosdis.nasa.gov/api/area/csv'),
    ],

    // [MASUKKAN API KEY DI .ENV] - VAPID Web-Push Notification Browser
    'vapid' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@pyroguard.id'),
    ],

];
