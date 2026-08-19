<?php

namespace Tests\Feature\Actions;

use App\Actions\Coupon\CreateCouponAction;
use App\DTOs\Coupon\CreateCouponDTO;
use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCouponActionTest extends TestCase
{
    use RefreshDatabase;

    private function validData(): array
    {
        return [
            'code' => 'DESCONTO10',
            'percent' => 10.0,
            'discount_limit' => 50.0,
            'duration' => 30,
            'expiration_date' => '2026-12-31',
        ];
    }

    public function test_creates_coupon_with_valid_data(): void
    {
        $action = app(CreateCouponAction::class);
        $dto = CreateCouponDTO::from($this->validData());

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('coupons', [
            'code' => 'DESCONTO10',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $action = app(CreateCouponAction::class);
        $dto = CreateCouponDTO::from($this->validData());

        $result = $action->execute($dto);

        $this->assertSame('Cupom criado com sucesso.', $result->message);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreateCouponAction::class);
        $action->execute('not-a-dto');
    }
}
