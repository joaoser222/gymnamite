<?php

namespace Tests\Feature;

use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_seeder_creates_the_expected_records_and_is_idempotent(): void
    {
        $this->seed(SettingSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->assertDatabaseCount('settings', 4);

        $this->assertDatabaseHas('settings', [
            'name' => 'contract_default_category',
            'label' => 'Categoria de Contratos',
            'content' => '',
            'object_type' => 'string',
        ]);

        $this->assertDatabaseHas('settings', [
            'name' => 'purchase_default_category',
            'label' => 'Categoria de Compras',
            'content' => '',
            'object_type' => 'string',
        ]);

        $this->assertDatabaseHas('settings', [
            'name' => 'sale_default_category',
            'label' => 'Categoria de Vendas',
            'content' => '',
            'object_type' => 'string',
        ]);

        $this->assertDatabaseHas('settings', [
            'name' => 'direct_lesson_default_category',
            'label' => 'Categoria de Aula Avulsa',
            'content' => '',
            'object_type' => 'string',
        ]);

        $this->assertSame(1, Setting::query()->where('name', 'contract_default_category')->count());
    }
}
