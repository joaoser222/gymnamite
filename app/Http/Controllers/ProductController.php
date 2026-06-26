<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\ProductType;
use App\Models\Product;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;

class ProductController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'sale_price', 'quantity', 'product_type', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::PRODUCT;
    }

    protected function modelClass(): string
    {
        return Product::class;
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'productTypes' => $this->enumOptions(ProductType::class),
            ],
        ];
    }
}
