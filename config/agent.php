<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API key used to authenticate against the LLM provider.
    |--------------------------------------------------------------------------
    */
    'api_key' => env('AGENT_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Base URL of the OpenAI-compatible API. Do not include a trailing slash.
    |--------------------------------------------------------------------------
    */
    'base_url' => env('AGENT_BASE_URL', 'https://api.openai.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Model identifier understood by the provider.
    |--------------------------------------------------------------------------
    */
    'model' => env('AGENT_MODEL', 'gpt-4o-mini'),

    /*
    |--------------------------------------------------------------------------
    | Sampling temperature. Lower values make the interviewer more consistent.
    |--------------------------------------------------------------------------
    */
    'temperature' => (float) env('AGENT_TEMPERATURE', 0.3),

    /*
    |--------------------------------------------------------------------------
    | Hard cap on the number of tokens the model may generate per reply.
    |--------------------------------------------------------------------------
    */
    'max_tokens' => (int) env('AGENT_MAX_TOKENS', 2000),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout in seconds for each request to the LLM provider.
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('AGENT_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Natural language the agent must speak and write its output in.
    | The system prompt injects this value at runtime.
    |--------------------------------------------------------------------------
    */
    'language' => env('AGENT_LANGUAGE', 'persian'),
];