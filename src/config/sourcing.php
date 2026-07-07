<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sourcing Agent — Provider Selection
    |--------------------------------------------------------------------------
    | Each provider is swappable via .env without touching consumer code.
    | Evaluation phase: gemini + brave + tesseract (free tier).
    | Production candidates: openai / anthropic (decided after evaluation).
    */

    'llm' => [
        'provider' => env('SOURCING_LLM_PROVIDER', 'gemini'),

        'gemini' => [
            'api_key'  => env('GEMINI_API_KEY'),
            'model'    => env('SOURCING_LLM_MODEL', 'gemini-2.5-flash'),
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'timeout'  => 60,
        ],
    ],

    'search' => [
        'provider' => env('SOURCING_SEARCH_PROVIDER', 'brave'),

        'brave' => [
            'api_key'  => env('BRAVE_API_KEY'),
            'base_url' => 'https://api.search.brave.com/res/v1',
            'timeout'  => 30,
        ],
    ],

    'ocr' => [
        'provider' => env('SOURCING_OCR_PROVIDER', 'tesseract'),

        'tesseract' => [
            'binary'    => env('TESSERACT_BINARY', '/usr/bin/tesseract'),
            'languages' => env('TESSERACT_LANGUAGES', 'eng+fas'),
            'timeout'   => 120,
        ],
    ],

    'queue' => [
        'connection' => env('SOURCING_QUEUE_CONNECTION', 'database'),
        'queue_name' => env('SOURCING_QUEUE_NAME', 'sourcing'),
    ],

];
