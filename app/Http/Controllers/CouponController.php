<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Coupon\CreateCouponAction;
use App\Actions\Coupon\UpdateCouponAction;
use App\DTOs\Coupon\CreateCouponDTO;
use App\DTOs\Coupon\UpdateCouponDTO;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CouponController extends CrudModuleController
{
    public function __construct(
        private readonly CreateCouponAction $createCoupon,
        private readonly UpdateCouponAction $updateCoupon,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'code', 'percent', 'discount_limit', 'duration', 'expiration_date', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['code'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'code', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::COUPON;
    }

    protected function modelClass(): string
    {
        return Coupon::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createCoupon->execute(
            CreateCouponDTO::from($request->validate([
                'code' => ['required', 'string', 'max:50'],
                'percent' => ['required', 'numeric', 'min:0'],
                'discount_limit' => ['nullable', 'numeric', 'min:0'],
                'duration' => ['nullable', 'string', 'max:50'],
                'expiration_date' => ['nullable', 'date'],
            ]))
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
        }

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        /** @var Coupon $coupon */
        $coupon = $this->modelFromRoute($request);

        $result = $this->updateCoupon->execute(
            UpdateCouponDTO::from([
                ...$request->validate([
                    'code' => ['nullable', 'string', 'max:50'],
                    'percent' => ['nullable', 'numeric', 'min:0'],
                    'discount_limit' => ['nullable', 'numeric', 'min:0'],
                    'duration' => ['nullable', 'string', 'max:50'],
                    'expiration_date' => ['nullable', 'date'],
                ]),
                'id' => $coupon->getKey(),
            ])
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data);
        }

        return redirect()->route($this->routePrefix().'.index');
    }

    private function actionFailureResponse(Request $request, ?array $errors, ?string $message): RedirectResponse|JsonResponse
    {
        $message ??= 'Não foi possível concluir a operação.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        return back()->withErrors($errors ?? ['action' => $message])->withInput();
    }
}
