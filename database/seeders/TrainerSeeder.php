<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Trainer;
use App\Enums\GenderType;
use App\Enums\Visibility;

class TrainerSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Trainer::upsert([[
            'id' => 1,
            'name' => 'TREINADOR PADRÃO',
            'document' => '000000000000',
            'phone' => '99999999999',
            'gender' => GenderType::MALE->value,
            'visibility' => Visibility::VISIBLE->value
        ]],['id'],['name','document','phone']);
    }
}
