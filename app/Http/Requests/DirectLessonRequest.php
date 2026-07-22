<?php

namespace App\Http\Requests;

use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DirectLessonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'trainer_id' => ['required', 'integer', 'exists:trainers,id'],
            'lesson_date' => ['required', Rule::date()->todayOrBefore()],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(BillableStatus::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'generate_invoices' => ['boolean'],
        ];
    }
}
