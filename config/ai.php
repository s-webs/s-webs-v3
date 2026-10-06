<?php

return [
    'enabled' => env('OPENAI_AI_ENABLED', false),
    'key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-6.1-sol'),
    'max_output_tokens' => (int) env('OPENAI_AI_MAX_OUTPUT_TOKENS', 1600),
    'daily_request_limit' => (int) env('OPENAI_AI_DAILY_REQUEST_LIMIT', 30),
    'daily_token_limit' => (int) env('OPENAI_AI_DAILY_TOKEN_LIMIT', 100000),
];
