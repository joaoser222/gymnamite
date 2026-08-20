<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Mcp\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    public function message(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:8000'],
        ]);

        $reply = $this->chatService->ask($data['message'], $data['history'] ?? []);

        return response()->json(['reply' => $reply]);
    }
}
