<?php

namespace App\Http\Requests;

use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'status' => ['required', Rule::enum(BillableStatus::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'annotations' => ['nullable', 'string', 'max:500'],
            'disable_stock' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
