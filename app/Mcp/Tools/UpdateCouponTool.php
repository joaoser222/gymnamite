<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Coupon\UpdateCouponAction;
use App\DTOs\Coupon\UpdateCouponDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use App\Models\Coupon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-coupon')]
#[Description('Atualiza um cupom de desconto existente')]
#[IsIdempotent(true)]
class UpdateCouponTool extends Tool
{
    use HasMcpToolName;
    public function __construct(
        protected UpdateCouponAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'code' => 'nullable|string|max:50',
            'percent' => 'nullable|numeric|min:0|max:100',
            'discount_limit' => 'nullable|numeric|min:0',
            'duration' => 'nullable|string|max:50',
            'expiration_date' => 'nullable|date',
        ]);

        $coupon = Coupon::find($validated['id']);

        if (! $coupon) {
            return Response::error('Cupom não encontrado.');
        }

        $dto = UpdateCouponDTO::from(array_merge(
            ['id' => $coupon->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'code' => $result->data->code,
            'percent' => $result->data->percent,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('coupons.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do cupom')->required(),
            'code' => $schema->string()->description('Novo código do cupom')->nullable(),
            'percent' => $schema->number()->description('Novo percentual de desconto (0-100)')->nullable(),
            'discount_limit' => $schema->number()->description('Novo limite máximo de desconto em reais')->nullable(),
            'duration' => $schema->string()->description('Nova duração do cupom')->nullable(),
            'expiration_date' => $schema->string()->description('Nova data de expiração (Y-m-d)')->nullable(),
        ];
    }
}
