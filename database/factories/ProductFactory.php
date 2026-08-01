<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Enums\Visibility;
use App\Models\Product;
use App\Models\ProductUnity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        ProductUnity::query()->firstOrCreate(['code' => 'UN'], ['name' => 'Unidade']);

        return [
            'name' => fake()->word(),
            'purchase_price' => 10,
            'sale_price' => 15,
            'quantity' => 0,
            'product_type' => ProductType::MERCHANDISE,
            'product_unity' => 'UN',
            'visibility' => Visibility::VISIBLE,
        ];
    }
}
