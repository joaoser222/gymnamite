<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Models\ProductUnity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogActionPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_permission_can_create_a_modality(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $this->actingAs($user)
            ->postJson(route('modalities.store'), ['name' => 'Pilates'])
            ->assertCreated();

        $this->assertDatabaseHas('modalities', ['name' => 'Pilates']);
    }

    public function test_users_with_permission_can_create_a_product(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'products.create');

        ProductUnity::query()->create([
            'name' => 'Unidade',
            'code' => 'UN',
        ]);

        $this->actingAs($user)
            ->postJson(route('products.store'), [
                'name' => 'Garrafa',
                'purchase_price' => 8.5,
                'sale_price' => 15,
                'quantity' => 12,
                'product_type' => ProductType::MERCHANDISE->value,
                'product_unity' => 'UN',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('products', [
            'name' => 'Garrafa',
            'product_unity' => 'UN',
            'quantity' => 12,
        ]);
    }
}
