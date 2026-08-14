<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayTransferModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_transfer_routes_are_not_registered(): void
    {
        $this->get('/transfers')->assertNotFound();
    }

    public function test_users_with_permission_can_view_gateway_transfers(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'gateway_transfers.view');

        $this->actingAs($user)
            ->getJson(route('gateway-transfers.index'))
            ->assertOk();
    }
}
