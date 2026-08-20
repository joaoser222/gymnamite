<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Throwable;

class ChatService
{
    public function __construct(
        private readonly ChatToolSchemaProvider $schemaProvider,
    ) {}

    /**
     * Send a message to the LLM, executing MCP resource reads when the model
     * requests them, and return the final assistant text.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function ask(string $message, array $history = []): string
    {
        ['tools' => $tools, 'map' => $map] = $this->schemaProvider->readOnlyResourcesForCurrentUser();

        $messages = $this->buildInitialMessages($history, $message);

        $config = config('mcp_chat');
        $maxIterations = (int) $config['max_tool_iterations'];

        for ($iteration = 0; $iteration <= $maxIterations; $iteration++) {
            $payload = [
                'model' => $config['model'],
                'messages' => $messages,
                'temperature' => (float) $config['temperature'],
                'max_tokens' => (int) $config['max_tokens'],
            ];

            if ($tools !== []) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = 'auto';
            }

            $response = Http::withToken((string) $config['api_key'])
                ->timeout((int) $config['request_timeout'])
                ->post((string) $config['base_url'], $payload);

            if ($response->failed()) {
                throw new \RuntimeException('Falha ao chamar o provedor de LLM: '.$response->body());
            }

            $data = $response->json();
            $assistantMessage = $data['choices'][0]['message'] ?? null;

            if ($assistantMessage === null) {
                throw new \RuntimeException('Resposta inesperada do provedor de LLM.');
            }

            $messages[] = $assistantMessage;

            $toolCalls = $assistantMessage['tool_calls'] ?? [];

            if ($toolCalls === []) {
                return (string) ($assistantMessage['content'] ?? '');
            }

            foreach ($toolCalls as $toolCall) {
                $function = $toolCall['function'] ?? [];
                $name = (string) ($function['name'] ?? '');
                $arguments = json_decode((string) ($function['arguments'] ?? '{}'), true) ?? [];

                $resultText = $this->executeResource($map, $name, $arguments);

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) ($toolCall['id'] ?? ''),
                    'content' => $resultText,
                ];
            }
        }

        return 'Não foi possível concluir a resposta a tempo. Tente novamente.';
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array<string, mixed>>
     */
    private function buildInitialMessages(array $history, string $message): array
    {
        $messages = [];

        foreach ($history as $entry) {
            $messages[] = [
                'role' => $entry['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $entry['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        return $messages;
    }

    /**
     * @param  array<string, array{resource: \Laravel\Mcp\Server\Resource, params: array<int, string>}>  $map
     */
    private function executeResource(array $map, string $name, array $arguments): string
    {
        $entry = $map[$name] ?? null;

        if ($entry === null) {
            return json_encode(
                ['error' => "Recurso {$name} não disponível para este usuário."],
                JSON_UNESCAPED_UNICODE,
            );
        }

        /** @var \Laravel\Mcp\Server\Resource $resource */
        $resource = $entry['resource'];
        $params = $entry['params'];

        $requestArguments = [];
        foreach ($params as $param) {
            $requestArguments[$param] = $arguments[$param] ?? null;
        }

        try {
            $request = new Request($requestArguments);
            $result = $resource->handle($request);
        } catch (Throwable $exception) {
            return json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        return $this->responseToText($result);
    }

    private function responseToText(mixed $result): string
    {
        if ($result instanceof ResponseFactory) {
            $structured = $result->getStructuredContent();

            if ($structured !== null) {
                return json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            $text = '';
            foreach ($result->responses() as $response) {
                $text .= (string) $response->content();
            }

            return $text;
        }

        if ($result instanceof Response) {
            return (string) $result->content();
        }

        return (string) $result;
    }
}
