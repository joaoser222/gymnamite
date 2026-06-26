<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CostCenter;
use App\Models\FinancialCategory;
use App\Models\Modality;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductUnity;
use App\Models\Supplier;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SelectBoxController extends Controller
{
    public function __invoke(Request $request, string $objectName): JsonResponse
    {
        $method = 'select'.str_replace('-', '', ucwords($objectName, '-'));

        if (! method_exists($this, $method)) {
            abort(404, "Select para '{$objectName}' não encontrado.");
        }

        return $this->{$method}($request);
    }

    private function selectClient(Request $request): JsonResponse
    {
        return $this->search(Client::class, $request, fn (Builder $q) => $q->where('status', 'active'));
    }

    private function selectFinancialCategory(Request $request): JsonResponse
    {
        return $this->search(FinancialCategory::class, $request, fn (Builder $q) => $q->where('visibility', 'visible'));
    }

    private function selectCostCenter(Request $request): JsonResponse
    {
        return $this->search(CostCenter::class, $request, fn (Builder $q) => $q->where('visibility', 'visible'));
    }

    private function selectModality(Request $request): JsonResponse
    {
        return $this->search(Modality::class, $request);
    }

    private function selectPlan(Request $request): JsonResponse
    {
        return $this->search(Plan::class, $request);
    }

    private function selectProduct(Request $request): JsonResponse
    {
        return $this->search(Product::class, $request);
    }

    private function selectProductUnity(Request $request): JsonResponse
    {
        return $this->search(ProductUnity::class, $request, null, 'name', 'code');
    }

    private function selectSupplier(Request $request): JsonResponse
    {
        return $this->search(Supplier::class, $request);
    }

    private function selectTrainer(Request $request): JsonResponse
    {
        return $this->search(Trainer::class, $request);
    }

    /**
     * Busca genérica para selectboxes.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function search(
        string $modelClass,
        Request $request,
        ?\Closure $extraFilters = null,
        string $optionName = 'name',
        string $optionValue = 'id'
    ): JsonResponse {
        $search = $request->input('search', '');
        $limit = min((int) $request->input('limit', 15), 50);

        $query = $modelClass::query()
            ->select([$optionValue, $optionName])
            ->orderBy($optionName);

        if ($extraFilters !== null) {
            $query = $extraFilters($query);
        }

        if ($search !== '') {
            $query->where($optionName, 'like', '%'.$search.'%');
        }

        $options = $query->limit($limit)
            ->get()
            ->map(fn (mixed $model): array => [
                'value' => (string) $model->getAttribute($optionValue),
                'label' => (string) $model->getAttribute($optionName),
            ])
            ->all();

        return response()->json($options);
    }
}
