<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\ProductType;
use App\Models\Product;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'sale_price', 'quantity', 'product_type', 'product_unity_label', 'created_at'];

    protected array $joins = ['productUnity'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'sale_price', 'created_at'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'products.id',
        'name' => 'products.name',
        'sale_price' => 'products.sale_price',
        'quantity' => 'products.quantity',
        'product_type' => 'products.product_type',
        'product_unity_label' => 'product_unities.name',
        'created_at' => 'products.created_at',
    ];

    protected function accessModule(): AccessModule
    {
        return AccessModule::PRODUCT;
    }

    protected function modelClass(): string
    {
        return Product::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'productTypes' => $this->enumOptions(ProductType::class),
            ],
        ];
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
