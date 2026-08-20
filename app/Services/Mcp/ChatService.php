<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response as McpResponse;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Tool;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ChatService
{
    public function __construct(
        private readonly ChatToolSchemaProvider $schemaProvider,
    ) {}

    /**
     * Stream the assistant reply as Server-Sent Events. The callback echoes
     * `meta`, `token`, and `done` events; $onComplete receives the final text
     * so the caller can persist the assistant message.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function streamAsk(
        string $message,
        array $history = [],
        ?callable $onComplete = null,
        ?int $conversationId = null,
    ): StreamedResponse {
        ['tools' => $resourceTools, 'map' => $resourceMap] = $this->schemaProvider->readOnlyResourcesForCurrentUser();
        ['tools' => $writeTools, 'map' => $writeMap] = $this->schemaProvider->writableToolsForCurrentUser();

        $tools = array_merge($resourceTools, $writeTools);
        $map = $resourceMap + $writeMap;

        $messages = $this->buildInitialMessages($history, $message);

        $config = config('mcp_chat');
        $maxIterations = (int) $config['max_tool_iterations'];
        $providers = $this->orderedProviders($config);

        return response()->stream(function () use ($providers, $config, $tools, $messages, $maxIterations, $onComplete, $conversationId): void {
            if ($conversationId !== null) {
                echo $this->sseEvent(['type' => 'meta', 'conversation_id' => $conversationId]);
            }

            $activeIndex = 0;
            $full = '';

            for ($iteration = 0; $iteration <= $maxIterations; $iteration++) {
                $body = null;

                for ($i = $activeIndex; $i < count($providers); $i++) {
                    $body = $this->streamProvider($providers[$i], $config, $tools, $messages);

                    if ($body !== null) {
                        $activeIndex = $i;
                        break;
                    }
                }

                if ($body === null) {
                    $failure = 'Falha ao chamar os provedores de LLM.';
                    echo $this->sseEvent(['type' => 'done', 'content' => $failure]);

                    if ($onComplete !== null) {
                        ($onComplete)($failure);
                    }

                    return;
                }

                $result = $this->streamOneCompletion($body, function (string $token): void {
                    echo $this->sseEvent(['type' => 'token', 'content' => $token]);
                });

                $assistantMessage = ['role' => 'assistant', 'content' => $result['content']];

                if ($result['tool_calls'] !== []) {
                    $assistantMessage['tool_calls'] = array_values($result['tool_calls']);
                }

                $messages[] = $assistantMessage;
                $full = $result['content'];

                if ($result['tool_calls'] === []) {
                    break;
                }

                foreach ($result['tool_calls'] as $toolCall) {
                    $function = $toolCall['function'] ?? [];
                    $name = (string) ($function['name'] ?? '');
                    $arguments = json_decode((string) ($function['arguments'] ?? '{}'), true) ?? [];

                    $resultText = $this->executeToolCall($name, $arguments, $map);

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => (string) ($toolCall['id'] ?? ''),
                        'content' => $resultText,
                    ];
                }
            }

            if ($full === '') {
                $full = 'Não foi possível concluir a resposta a tempo. Tente novamente.';
            }

            echo $this->sseEvent(['type' => 'done', 'content' => $full]);

            if ($onComplete !== null) {
                ($onComplete)($full);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Send a message to the LLM, executing MCP resource reads when the model
     * requests them, and return the final assistant text.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function ask(string $message, array $history = []): string
    {
        ['tools' => $resourceTools, 'map' => $resourceMap] = $this->schemaProvider->readOnlyResourcesForCurrentUser();
        ['tools' => $writeTools, 'map' => $writeMap] = $this->schemaProvider->writableToolsForCurrentUser();

        $tools = array_merge($resourceTools, $writeTools);
        $map = $resourceMap + $writeMap;

        $messages = $this->buildInitialMessages($history, $message);

        $config = config('mcp_chat');
        $maxIterations = (int) $config['max_tool_iterations'];
        $providers = $this->orderedProviders($config);
        $activeIndex = 0;

        for ($iteration = 0; $iteration <= $maxIterations; $iteration++) {
            $response = $this->completeChat($providers, $activeIndex, $config, $tools, $messages);

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

                $resultText = $this->executeToolCall($name, $arguments, $map);

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
     * Try each provider starting at $activeIndex and return the first
     * successful completion, keeping the chosen provider for later calls.
     *
     * @param  array<int, array{base_url: string, api_key: string, model: string}>  $providers
     * @param  array<string, mixed>  $config
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function completeChat(array $providers, int &$activeIndex, array $config, array $tools, array $messages): Response
    {
        $lastError = null;

        for ($i = $activeIndex; $i < count($providers); $i++) {
            $response = $this->callProvider($providers[$i], $config, $tools, $messages);

            if ($response->successful()) {
                $activeIndex = $i;

                return $response;
            }

            $lastError = $response->body();
        }

        throw new \RuntimeException('Falha ao chamar os provedores de LLM: '.$lastError);
    }

    /**
     * @param  array{base_url: string, api_key: string, model: string}  $provider
     * @param  array<string, mixed>  $config
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function callProvider(array $provider, array $config, array $tools, array $messages): Response
    {
        $payload = [
            'model' => $provider['model'],
            'messages' => $messages,
            'temperature' => (float) $config['temperature'],
            'max_tokens' => (int) $config['max_tokens'],
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        return Http::withToken((string) $provider['api_key'])
            ->timeout((int) $config['request_timeout'])
            ->post((string) $provider['base_url'], $payload);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, array{base_url: string, api_key: string, model: string}>
     */
    private function orderedProviders(array $config): array
    {
        $baseUrl = (string) ($config['base_url'] ?? '');
        $apiKey = (string) ($config['api_key'] ?? '');
        $models = $config['providers'] ?? [];

        if ($models === [] && isset($config['model'])) {
            $models = [$config['model']];
        }

        return collect($models)
            ->filter(fn ($model): bool => is_string($model) && $model !== '')
            ->map(fn (string $model): array => [
                'base_url' => $baseUrl,
                'api_key' => $apiKey,
                'model' => $model,
            ])
            ->values()
            ->all();
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

    /**
     * Execute a writable MCP tool for the current user and return its result
     * as text for the LLM. Validation or runtime failures are returned as an
     * error payload so the model can recover instead of aborting the turn.
     *
     * @param  class-string<Tool>  $class
     */
    private function executeTool(string $class, array $arguments): string
    {
        try {
            $tool = app($class);
            $result = $tool->handle(new Request($arguments));
        } catch (ValidationException $exception) {
            return json_encode(
                ['error' => 'Validação falhou: '.implode('; ', Arr::flatten($exception->errors()))],
                JSON_UNESCAPED_UNICODE,
            );
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

        if ($result instanceof McpResponse) {
            return (string) $result->content();
        }

        return (string) $result;
    }

    /**
     * Resolve and execute a resource read or writable tool by name for the
     * current user, returning the result as text (errors are returned as
     * payloads so the model can recover).
     *
     * @param  array<string, array{resource: \Laravel\Mcp\Server\Resource, params: array<int, string>, tool: class-string<Tool>}|null>  $map
     */
    private function executeToolCall(string $name, array $arguments, array $map): string
    {
        $entry = $map[$name] ?? null;

        if ($entry === null) {
            return json_encode(
                ['error' => "Ferramenta {$name} não disponível para este usuário."],
                JSON_UNESCAPED_UNICODE,
            );
        }

        if (isset($entry['tool'])) {
            return $this->executeTool($entry['tool'], $arguments);
        }

        return $this->executeResource($map, $name, $arguments);
    }

    /**
     * Open a streaming completion against one provider and return its raw body
     * stream, or null when the provider fails.
     *
     * @param  array{base_url: string, api_key: string, model: string}  $provider
     * @param  array<string, mixed>  $config
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function streamProvider(array $provider, array $config, array $tools, array $messages): ?StreamInterface
    {
        $payload = [
            'model' => $provider['model'],
            'messages' => $messages,
            'temperature' => (float) $config['temperature'],
            'max_tokens' => (int) $config['max_tokens'],
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = Http::withToken((string) $provider['api_key'])
            ->withOptions(['stream' => true])
            ->timeout((int) $config['request_timeout'])
            ->post((string) $provider['base_url'], $payload);

        if (! $response->successful()) {
            return null;
        }

        return $response->toPsrResponse()->getBody();
    }

    /**
     * Read an SSE stream, invoking $onToken for each text delta, and return the
     * accumulated text and reconstructed tool_calls.
     *
     * @return array{content: string, tool_calls: array<int, array{id: string, type: string, function: array{name: string, arguments: string}}>}
     */
    private function streamOneCompletion(StreamInterface $body, callable $onToken): array
    {
        $content = '';
        $toolCalls = [];
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(8192);

            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $eventBlock = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                $data = '';
                foreach (explode("\n", $eventBlock) as $line) {
                    if (str_starts_with($line, 'data:')) {
                        $data .= trim(substr($line, 5));
                    }
                }

                if ($data === '') {
                    continue;
                }

                if ($data === '[DONE]') {
                    return ['content' => $content, 'tool_calls' => $toolCalls];
                }

                $json = json_decode($data, true);

                if (! is_array($json)) {
                    continue;
                }

                $delta = $json['choices'][0]['delta'] ?? [];

                if (isset($delta['content']) && $delta['content'] !== '') {
                    $content .= (string) $delta['content'];
                    $onToken((string) $delta['content']);
                }

                if (isset($delta['tool_calls']) && is_array($delta['tool_calls'])) {
                    foreach ($delta['tool_calls'] as $tc) {
                        $index = (int) ($tc['index'] ?? 0);

                        if (! isset($toolCalls[$index])) {
                            $toolCalls[$index] = [
                                'id' => '',
                                'type' => 'function',
                                'function' => ['name' => '', 'arguments' => ''],
                            ];
                        }

                        if (isset($tc['id'])) {
                            $toolCalls[$index]['id'] = (string) $tc['id'];
                        }

                        if (isset($tc['function']['name'])) {
                            $toolCalls[$index]['function']['name'] .= (string) $tc['function']['name'];
                        }

                        if (isset($tc['function']['arguments'])) {
                            $toolCalls[$index]['function']['arguments'] .= (string) $tc['function']['arguments'];
                        }
                    }
                }
            }
        }

        return ['content' => $content, 'tool_calls' => $toolCalls];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sseEvent(array $payload): string
    {
        return 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
    }
}
