<?php

namespace Tests\Feature\Actions;

use App\Actions\Coupon\UpdateCouponAction;
use App\DTOs\Coupon\UpdateCouponDTO;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCouponActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_coupon_with_valid_data(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'OLD10',
            'percent' => 10.0,
        ]);

        $action = app(UpdateCouponAction::class);
        $dto = UpdateCouponDTO::from([
            'id' => $coupon->id,
            'code' => 'NEW20',
            'percent' => 20.0,
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'NEW20',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'UPD',
            'percent' => 10.0,
        ]);

        $action = app(UpdateCouponAction::class);
        $dto = UpdateCouponDTO::from([
            'id' => $coupon->id,
            'code' => 'UPD2',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Cupom atualizado com sucesso.', $result->message);
    }

    public function test_throws_when_coupon_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $action = app(UpdateCouponAction::class);
        $dto = UpdateCouponDTO::from([
            'id' => 999999,
            'code' => 'X',
        ]);
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateCouponAction::class);
        $action->execute('not-a-dto');
    }
}
