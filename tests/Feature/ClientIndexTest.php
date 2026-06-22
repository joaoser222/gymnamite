<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientIndexTest extends TestCase
{
    use RefreshDatabase;

    private function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }

    public function test_guests_are_redirected_from_clients_index(): void
    {
        $response = $this->get(route('clients.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_client_show(): void
    {
        $client = Client::factory()->create();

        $response = $this->get(route('clients.show', $client));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_client_create(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.create');

        $response = $this->actingAs($user)->get(route('clients.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('client/Details')
            ->where('client', null)
            ->where('id', 'new')
            ->has('routes')
            ->where('options.genderTypes.0.value', 'M')
            ->where('options.genderTypes.0.label', 'Masculino')
        );
    }

    public function test_authenticated_users_can_visit_clients_index(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.view');

        $response = $this->actingAs($user)->get(route('clients.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('client/Index')
            ->has('clients.data')
            ->has('filters')
            ->has('options.clientStatus')
        );

        $this->assertNull($response->inertiaProps('enums'));
    }

    public function test_authenticated_users_can_fetch_client_for_editing(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.view');
        $client = Client::factory()->create([
            'name' => 'Cliente Completo',
            'email' => 'cliente-completo@example.com',
            'address' => 'Rua Completa',
            'legal_representative_name' => 'Responsável Legal',
        ]);

        $response = $this->actingAs($user)->getJson(route('clients.show', $client));

        $response
            ->assertOk()
            ->assertJsonPath('id', $client->id)
            ->assertJsonPath('name', 'Cliente Completo')
            ->assertJsonPath('email', 'cliente-completo@example.com')
            ->assertJsonPath('address', 'Rua Completa')
            ->assertJsonPath('legal_representative_name', 'Responsável Legal');
    }

    public function test_client_show_requires_view_permission(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->getJson(route('clients.show', $client));

        $response->assertForbidden();
    }

    public function test_clients_index_filters_by_search(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.view');

        Client::factory()->create(['name' => 'João Silva']);
        Client::factory()->create(['name' => 'Maria Souza']);

        $response = $this->actingAs($user)->get(route('clients.index', [
            'search' => 'João',
            'searchField' => 'name',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('client/Index')
            ->where('clients.total', 1)
            ->where('clients.data.0.name', 'João Silva')
        );
    }
}
