<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_clients_index(): void
    {
        $response = $this->get(route('clients.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_clients_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('clients.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('clients/Index')
            ->has('clients.data')
            ->has('filters')
        );
    }

    public function test_clients_index_filters_by_search(): void
    {
        $user = User::factory()->create();

        Client::factory()->create(['name' => 'João Silva']);
        Client::factory()->create(['name' => 'Maria Souza']);

        $response = $this->actingAs($user)->get(route('clients.index', [
            'search' => 'João',
            'searchField' => 'name',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('clients/Index')
            ->where('clients.total', 1)
            ->where('clients.data.0.name', 'João Silva')
        );
    }
}
