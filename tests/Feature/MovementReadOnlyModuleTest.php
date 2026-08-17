<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovementReadOnlyModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_permission_can_browse_movements_but_cannot_create_them(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'movements.view');

        $this->actingAs($user)
            ->get(route('movements.index'))
            ->assertOk();

        $this->actingAs($user)
            ->post('/movements')
            ->assertMethodNotAllowed();
    }
}
