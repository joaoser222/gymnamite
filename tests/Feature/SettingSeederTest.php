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
        Setting::query()->create([
            'name' => 'sale_default_category',
            'label' => 'Categoria antiga',
            'content' => '15',
            'object_type' => 'int',
        ]);

        $this->seed(SettingSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->assertDatabaseCount('settings', 5);

        $this->assertDatabaseHas('settings', [
            'name' => 'contract_default_category',
            'label' => 'Categoria de Contratos',
            'content' => '',
            'object_type' => 'select:financial-category',
        ]);

        $this->assertDatabaseHas('settings', [
            'name' => 'purchase_default_category',
            'label' => 'Categoria de Compras',
            'content' => '',
            'object_type' => 'select:financial-category',
        ]);

        $this->assertDatabaseHas('settings', [
            'name' => 'sale_default_category',
            'label' => 'Categoria de Vendas',
            'content' => '15',
            'object_type' => 'select:financial-category',
        ]);

        $this->assertDatabaseHas('settings', [
            'name' => 'direct_lesson_default_category',
            'label' => 'Categoria de Aula Avulsa',
            'content' => '',
            'object_type' => 'select:financial-category',
        ]);

        $this->assertDatabaseHas('settings', [
            'name' => 'default_financial_account',
            'label' => 'Conta Padrão',
            'content' => '',
            'object_type' => 'select:financial-account',
        ]);

        $this->assertSame(1, Setting::query()->where('name', 'contract_default_category')->count());
    }
}
