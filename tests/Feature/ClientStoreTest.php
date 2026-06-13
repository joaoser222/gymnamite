<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientStoreTest extends TestCase
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
            'name' => 'Cliente Teste',
            'email' => 'cliente@teste.com',
            'phone' => '11999999999',
            'document' => '12345678901',
            'gender' => 'M',
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

    public function test_authenticated_users_can_create_clients(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.create');

        $response = $this->actingAs($user)->post(route('clients.store'), $this->validPayload());

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('clients', [
            'email' => 'cliente@teste.com',
            'document' => '12345678901',
        ]);
    }

    public function test_client_creation_requires_valid_data(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.create');

        $response = $this->actingAs($user)->post(route('clients.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'phone', 'document', 'gender', 'birth_date']);
    }
}
