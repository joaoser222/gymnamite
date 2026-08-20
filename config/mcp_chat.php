<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

return [
    'driver' => 'openai-compatible',

    'base_url' => env('MCP_CHAT_BASE_URL', 'https://api.minimax.io/v1/chat/completions'),

    'api_key' => env('MCP_CHAT_API_KEY', ''),

    'model' => env('MCP_CHAT_MODEL', 'MiniMax-Text-01'),

    'temperature' => (float) env('MCP_CHAT_TEMPERATURE', 0.3),

    'max_tokens' => (int) env('MCP_CHAT_MAX_TOKENS', 1024),

    'max_tool_iterations' => (int) env('MCP_CHAT_MAX_TOOL_ITERATIONS', 5),

    'request_timeout' => (int) env('MCP_CHAT_REQUEST_TIMEOUT', 60),
];
