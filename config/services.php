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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
        // Comma-separated models tried in order when the main one is overloaded or times out.
        'fallback_models' => env('GEMINI_FALLBACK_MODELS', 'gemini-3.1-flash-lite,gemini-3.8-flash'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 45),
        // The chat assistant answers short questions, so a slow model is abandoned sooner for the next one.
        'chat_timeout' => (int) env('GEMINI_CHAT_TIMEOUT', 12),
        // Text-to-speech models for the mock interviewer's voice, tried in order. Each model has
        // its own rate limit, so a busy (429) model hands over to the next one.
        'tts_models' => env('GEMINI_TTS_MODELS', 'gemini-3.8-flash-tts,gemini-3.8-flash-lite-tts,gemini-3.1-flash-tts-preview,gemini-2.5-flash-preview-tts,gemini-2.5-pro-preview-tts'),
        'tts_timeout' => (int) env('GEMINI_TTS_TIMEOUT', 30),
    ],

];
