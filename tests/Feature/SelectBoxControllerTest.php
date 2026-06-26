<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectBoxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_load_initial_client_options_without_search(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 18) as $index) {
            Client::factory()->create([
                'name' => sprintf('Cliente %02d', $index),
            ]);
        }

        Client::factory()->create([
            'name' => 'Cliente Inativo',
            'status' => ClientStatus::INACTIVE->value,
        ]);

        $response = $this->actingAs($user)->getJson(route('select-box', ['objectName' => 'client']));

        $response
            ->assertOk()
            ->assertJsonCount(15)
            ->assertJsonPath('0.label', 'Cliente 01')
            ->assertJsonPath('14.label', 'Cliente 15');
    }

    public function test_authenticated_users_can_filter_client_options_by_search(): void
    {
        $user = User::factory()->create();

        Client::factory()->create(['name' => 'Ana Maria']);
        Client::factory()->create(['name' => 'Bruno Silva']);

        $response = $this->actingAs($user)->getJson(route('select-box', [
            'objectName' => 'client',
            'search' => 'Ana',
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.label', 'Ana Maria');
    }

    public function test_authenticated_users_can_load_selected_client_outside_the_initial_limit(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 18) as $index) {
            Client::factory()->create([
                'name' => sprintf('Cliente %02d', $index),
            ]);
        }

        $selectedClient = Client::query()->where('name', 'Cliente 18')->firstOrFail();

        $response = $this->actingAs($user)->getJson(route('select-box', [
            'objectName' => 'client',
            'selected' => $selectedClient->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(16)
            ->assertJsonPath('0.value', (string) $selectedClient->id)
            ->assertJsonPath('0.label', 'Cliente 18');
    }
}
