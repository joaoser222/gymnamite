<?php

declare(strict_types=1);

return [
    'driver' => 'openai-compatible',

    'base_url' => env('MCP_CHAT_BASE_URL', 'https://opencode.ai/zen/v1/chat/completions'),

    'api_key' => env('MCP_CHAT_API_KEY', ''),

    'temperature' => (float) env('MCP_CHAT_TEMPERATURE', 0.3),

    'max_tokens' => (int) env('MCP_CHAT_MAX_TOKENS', 1024),

    'max_tool_iterations' => (int) env('MCP_CHAT_MAX_TOOL_ITERATIONS', 5),

    'request_timeout' => (int) env('MCP_CHAT_REQUEST_TIMEOUT', 60),

    // Ordered list of models used as fallback chain. All entries share the
    // same base_url/api_key (e.g. OpenCode Zen free models). The first model
    // that answers successfully is kept for the whole conversation; on failure
    // the next model is tried.
    'providers' => [
        env('MCP_CHAT_MODEL', 'hy3-free'),
        env('MCP_CHAT_MODEL_2', 'mimo-v2.5-free'),
        env('MCP_CHAT_MODEL_3', 'nemotron-3-ultra-free'),
    ],
];
