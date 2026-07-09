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

        'openai' => [
            'api_key'  => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout'  => 60,
            // Per-role models: strong model for the analysis call (accuracy),
            // cheap model for the mechanical planning call (cost).
            'model'         => env('SOURCING_LLM_MODEL', 'gpt-4.1'),
            'planner_model' => env('SOURCING_LLM_PLANNER_MODEL', 'gpt-4o-mini'),
        ],
    ],

    'search' => [
        'provider' => env('SOURCING_SEARCH_PROVIDER', 'tavily'),

        'tavily' => [
            'api_key'      => env('TAVILY_API_KEY'),
            'base_url'     => 'https://api.tavily.com',
            'timeout'      => 30,
            'search_depth' => 'basic',   // basic = 1 credit, advanced = 2 credits
            'max_results'  => 5,
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

    'agent' => [
        'output_language'    => env('SOURCING_OUTPUT_LANGUAGE', 'fa'),  // 'fa' | 'en'
        'max_search_queries' => 4,   // hard cap on planned queries per run (= Tavily credits/run)

        // Page-extraction stage: pull full page content for the top-N merged
        // results so analysis can read real contact/price data, not snippets.
        'extract_top'             => 5,     // pages fetched via Tavily /extract (5 basic = 1 credit)
        'extract_chars_per_page'  => 4000,  // cap per page fed to analysis (keeps the prompt bounded)
    ],

    'queue' => [
        'connection' => env('SOURCING_QUEUE_CONNECTION', 'database'),
        'queue_name' => env('SOURCING_QUEUE_NAME', 'sourcing'),
    ],

];
