<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use App\Mcp\Servers\GymnamiteServer;
use Laravel\Mcp\Server\Resource;
use ReflectionClass;

class ChatToolSchemaProvider
{
    /**
     * Build OpenAI-compatible tool definitions from the read-only MCP resources
     * that the current user is allowed to access.
     *
     * @return array{tools: array<int, array>, map: array<string, array{resource: Resource, params: array<int, string>}>}
     */
    public function readOnlyResourcesForCurrentUser(): array
    {
        $tools = [];
        $map = [];

        foreach ($this->resourceClassList() as $class) {
            if (! class_exists($class)) {
                continue;
            }

            /** @var Resource $resource */
            $resource = app($class);

            if (! $resource->eligibleForRegistration()) {
                continue;
            }

            $params = $this->uriVariableNames($resource->uri());
            $toolName = $this->toolName($resource->name());

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $toolName,
                    'description' => $resource->description(),
                    'parameters' => $this->buildParameters($params),
                ],
            ];

            $map[$toolName] = [
                'resource' => $resource,
                'params' => $params,
            ];
        }

        return ['tools' => $tools, 'map' => $map];
    }

    /**
     * @return array<int, string>
     */
    private function resourceClassList(): array
    {
        $defaults = (new ReflectionClass(GymnamiteServer::class))
            ->getDefaultProperties();

        return $defaults['resources'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    private function uriVariableNames(string $uri): array
    {
        preg_match_all('/{([^}]+)}/', $uri, $matches);

        return $matches[1] ?? [];
    }

    private function toolName(string $resourceName): string
    {
        $sanitized = (string) preg_replace('/[^a-zA-Z0-9_]/', '_', $resourceName);

        return 'read_'.$sanitized;
    }

    /**
     * @param  array<int, string>  $params
     * @return array<string, mixed>
     */
    private function buildParameters(array $params): array
    {
        $properties = [];

        foreach ($params as $param) {
            $properties[$param] = [
                'type' => 'string',
                'description' => 'Valor do parâmetro '.$param,
            ];
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $params,
        ];
    }
}
