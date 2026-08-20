<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\Mcp\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    public function message(Request $request): JsonResponse|StreamedResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:8000'],
            'conversation_id' => ['nullable', 'integer', 'exists:chat_conversations,id'],
            'stream' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        if (! empty($data['conversation_id'])) {
            $conversation = Conversation::query()
                ->where('id', $data['conversation_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $history = $conversation->messages()
                ->orderBy('id')
                ->get(['role', 'content'])
                ->map(fn ($message) => ['role' => $message->role, 'content' => $message->content])
                ->all();
        } else {
            $history = $data['history'] ?? [];

            $conversation = Conversation::create([
                'user_id' => $user->id,
                'title' => mb_substr($data['message'], 0, 100),
            ]);
        }

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $data['message'],
        ]);

        if (! empty($data['stream'])) {
            return $this->chatService->streamAsk(
                $data['message'],
                $history,
                function (string $reply) use ($conversation): void {
                    $conversation->messages()->create([
                        'role' => 'assistant',
                        'content' => $reply,
                    ]);
                },
                $conversation->id,
            );
        }

        $reply = $this->chatService->ask($data['message'], $history);

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return response()->json([
            'reply' => $reply,
            'conversation_id' => $conversation->id,
        ]);
    }
}
