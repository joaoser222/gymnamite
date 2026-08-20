<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatAccessTest extends TestCase
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

    public function test_chat_page_requires_view_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/chat')->assertForbidden();
    }

    public function test_chat_page_loads_with_view_permission(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $this->actingAs($user)->get('/chat')->assertOk();
    }
}
