<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AccessControlGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_gates_allow_only_users_with_the_matching_permission(): void
    {
        $authorizedUser = User::factory()->create();
        $unauthorizedUser = User::factory()->create();
        $this->grantPermission($authorizedUser, 'clients.view');

        $this->assertTrue(Gate::forUser($authorizedUser)->allows('clients.view'));
        $this->assertFalse(Gate::forUser($unauthorizedUser)->allows('clients.view'));
    }
}
