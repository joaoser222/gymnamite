<?php

namespace Tests\Feature\AccessControl;

use App\AccessControl\AccessRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator_with_synced_permissions(): void
    {
        $this->artisan('user:create-admin', [
            '--name' => 'Admin User',
            '--email' => 'admin@example.com',
            '--password' => 'password123',
            '--force' => true,
        ])->assertSuccessful();

        $user = User::query()->where('email', 'admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Admin User', $user->name);
        $this->assertTrue(Hash::check('password123', $user->password));

        $role = Role::query()->where('name', AccessRole::ADMINISTRATOR->value)->firstOrFail();

        $this->assertSame($role->id, $user->role_id);
        $this->assertEqualsCanonicalizing(
            $role->permissions()->pluck('permissions.id')->all(),
            $user->permissions()->pluck('permissions.id')->all(),
        );
    }

    public function test_it_updates_existing_user_to_administrator_with_synced_permissions(): void
    {
        $user = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role_id' => null,
        ]);

        $this->artisan('user:create-admin', [
            '--name' => 'Updated Admin',
            '--email' => 'admin@example.com',
            '--password' => 'new-password123',
            '--force' => true,
        ])->assertSuccessful();

        $user->refresh();

        $role = Role::query()->where('name', AccessRole::ADMINISTRATOR->value)->firstOrFail();

        $this->assertSame('Updated Admin', $user->name);
        $this->assertSame($role->id, $user->role_id);
        $this->assertTrue(Hash::check('new-password123', $user->password));
        $this->assertEqualsCanonicalizing(
            $role->permissions()->pluck('permissions.id')->all(),
            $user->permissions()->pluck('permissions.id')->all(),
        );
    }
}
