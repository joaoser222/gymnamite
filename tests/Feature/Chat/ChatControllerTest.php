<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use App\Models\Client;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    private function givePermission(User $user, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            ['name' => $permissionName, 'description' => $permissionName],
        );

        $user->permissions()->attach($permission);
    }

    public function test_chat_executes_readonly_resource_via_llm_tool_call(): void
    {
        $client = Client::factory()->create(['name' => 'Cliente Teste MCP']);

        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        $this->givePermission($user, 'clients.view');

        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_1',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'read_client',
                                    'arguments' => (string) json_encode(['id' => (string) $client->id]),
                                ],
                            ]],
                        ],
                    ]],
                ])
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Aqui estão os dados do cliente.',
                        ],
                    ]],
                ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Mostre o cliente',
        ]);

        $response->assertOk();
        $response->assertJson(['reply' => 'Aqui estão os dados do cliente.']);

        $toolCalled = false;
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            foreach ($body['messages'] ?? [] as $message) {
                if (($message['role'] ?? null) === 'tool'
                    && str_contains((string) ($message['content'] ?? ''), 'Cliente Teste MCP')) {
                    $toolCalled = true;
                }
            }
        }

        $this->assertTrue($toolCalled, 'O recurso read_client não foi executado com os dados do cliente.');
    }

    public function test_chat_without_module_permission_exposes_no_resource_tool(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Resposta sem ferramentas.',
                    ],
                ]],
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Mostre algo',
        ]);

        $response->assertOk();
        $response->assertJson(['reply' => 'Resposta sem ferramentas.']);

        $sentTools = false;
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            if (! empty($body['tools'])) {
                $sentTools = true;
            }
        }

        $this->assertFalse($sentTools, 'Usuário sem permissão de módulo não deve receber tools.');
    }

    public function test_chat_falls_back_to_next_model_when_first_fails(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        Http::fake([
            '*' => Http::sequence()
                ->push(['choices' => [['message' => ['role' => 'assistant', 'content' => 'erro']]]], 500)
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Resposta do modelo de fallback.',
                        ],
                    ]],
                ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Olá',
        ]);

        $response->assertOk();
        $response->assertJson(['reply' => 'Resposta do modelo de fallback.']);

        // First request (primary model) failed, second (fallback) succeeded.
        $this->assertCount(2, Http::recorded());
    }

    public function test_chat_executes_writable_tool_when_user_has_permission(): void
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        $this->givePermission($user, 'clients.create');

        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_1',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'create-client',
                                    'arguments' => (string) json_encode([
                                        'name' => 'Cliente Via Tool',
                                        'email' => 'cliente@tool.com',
                                        'phone' => '11999999999',
                                        'document' => '12345678901',
                                        'gender' => 'male',
                                        'birth_date' => '1990-01-01',
                                    ]),
                                ],
                            ]],
                        ],
                    ]],
                ])
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Cliente criado com sucesso.',
                        ],
                    ]],
                ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Crie um cliente',
        ]);

        $response->assertOk();
        $response->assertJson(['reply' => 'Cliente criado com sucesso.']);

        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente Via Tool',
            'document' => '12345678901',
        ]);

        $toolCalled = false;
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            if (! empty($body['tools'])) {
                $names = array_column(array_column($body['tools'], 'function'), 'name');
                if (in_array('create-client', $names, true)) {
                    $toolCalled = true;
                }
            }
        }

        $this->assertTrue($toolCalled, 'A tool create-client não foi exposta ao LLM.');
    }

    public function test_chat_hides_writable_tool_without_permission(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        // Intentionally NOT granting clients.create.

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Sem ferramentas de escrita.',
                    ],
                ]],
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Crie um cliente',
        ]);

        $response->assertOk();

        $exposedToolNames = [];
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            if (! empty($body['tools'])) {
                $exposedToolNames = array_merge(
                    $exposedToolNames,
                    array_column(array_column($body['tools'], 'function'), 'name'),
                );
            }
        }

        $this->assertNotContains('create-client', $exposedToolNames, 'Usuário sem permissão não deve ver a tool de escrita.');
    }
}
