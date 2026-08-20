<?php

declare(strict_types=1);

return [
    'driver' => 'openai-compatible',

    'base_url' => env('MCP_CHAT_BASE_URL', 'https://api.groq.com/openai/v1/chat/completions'),

    'api_key' => env('MCP_CHAT_API_KEY', ''),

    'temperature' => (float) env('MCP_CHAT_TEMPERATURE', 0.3),

    'max_tokens' => (int) env('MCP_CHAT_MAX_TOKENS', 1024),

    'max_tool_iterations' => (int) env('MCP_CHAT_MAX_TOOL_ITERATIONS', 5),

    'request_timeout' => (int) env('MCP_CHAT_REQUEST_TIMEOUT', 60),

    // Ordered list of models used as fallback chain. All entries share the
    // same base_url/api_key (e.g. Groq free models). The first model that
    // answers successfully is kept for the whole conversation; on failure the
    // next model is tried.
    'providers' => [
        env('MCP_CHAT_MODEL', 'llama-3.3-70b-versatile'),
        env('MCP_CHAT_MODEL_2', 'llama-3.1-8b-instant'),
        env('MCP_CHAT_MODEL_3', 'qwen/qwen3-32b'),
    ],
];
