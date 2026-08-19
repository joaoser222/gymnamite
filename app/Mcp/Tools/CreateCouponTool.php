<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Coupon\CreateCouponAction;
use App\DTOs\Coupon\CreateCouponDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-coupon')]
#[Description('Cria um novo cupom de desconto no sistema')]
#[IsIdempotent(false)]
class CreateCouponTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateCouponAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'percent' => 'required|numeric|min:0|max:100',
            'discount_limit' => 'nullable|numeric|min:0',
            'duration' => 'nullable|string|max:50',
            'expiration_date' => 'nullable|date',
        ]);

        $dto = CreateCouponDTO::from($validated);
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
        return auth()->user()?->can('coupons.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Código do cupom (ex: DESCONTO10)')->required(),
            'percent' => $schema->number()->description('Percentual de desconto (0-100)')->required(),
            'discount_limit' => $schema->number()->description('Limite máximo de desconto em reais')->nullable(),
            'duration' => $schema->string()->description('Duração do cupom (ex: unlimited, once, first_month)')->nullable(),
            'expiration_date' => $schema->string()->description('Data de expiração (Y-m-d)')->nullable(),
        ];
    }
}
