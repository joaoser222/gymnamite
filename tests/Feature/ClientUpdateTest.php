<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientUpdateTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Cliente Atualizado',
            'email' => 'atualizado@teste.com',
            'phone' => '11988887777',
            'document' => '12345678901',
            'gender' => 'F',
            'birth_date' => '1990-01-01',
            'legal_representative' => false,
            'address_postal_code' => '01001000',
            'address' => 'Rua Teste',
            'address_number' => '100',
            'address_complement' => '',
            'address_district' => 'Centro',
            'address_state' => 'SP',
            'address_city' => 'São Paulo',
        ];
    }

    public function test_authenticated_users_can_update_clients(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.update');
        $client = Client::factory()->create([
            'document' => '12345678901',
        ]);

        $response = $this->actingAs($user)->put(route('clients.update', $client), $this->validPayload());

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Cliente Atualizado',
            'email' => 'atualizado@teste.com',
        ]);
    }

    public function test_client_update_requires_valid_data(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.update');
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'phone', 'document', 'gender', 'birth_date']);
    }
}
