<?php

namespace Tests\Unit\Services;

use App\Contracts\BillingInvoiceSource;
use App\Services\Billing\DiscountCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private DiscountCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(DiscountCalculator::class);
    }

    private function fakeSource(array $overrides = []): BillingInvoiceSource
    {
        $defaults = [
            'billingDiscountPercent' => null,
            'billingDiscountedInstallments' => null,
            'billingDiscountValue' => 0.0,
            'billingDiscountLimit' => null,
        ];

        $config = array_merge($defaults, $overrides);

        return new class ($config) implements BillingInvoiceSource
        {
            public function __construct(private readonly array $config) {}

            public function billingDiscountPercent(): ?float
            {
                return $this->config['billingDiscountPercent'];
            }

            public function billingDiscountedInstallments(): ?int
            {
                return $this->config['billingDiscountedInstallments'];
            }

            public function billingDiscountValue(): float
            {
                return $this->config['billingDiscountValue'];
            }

            public function billingDiscountLimit(): ?float
            {
                return $this->config['billingDiscountLimit'];
            }

            public function billingHolder(): \Illuminate\Database\Eloquent\Model
            {
                throw new \RuntimeException('Not used in DiscountCalculator.');
            }

            public function billingOperationType(): \App\Enums\OperationType
            {
                throw new \RuntimeException('Not used in DiscountCalculator.');
            }

            public function billingGrossValue(): float
            {
                throw new \RuntimeException('Not used in DiscountCalculator.');
            }

            public function billingTotalValue(): float
            {
                throw new \RuntimeException('Not used in DiscountCalculator.');
            }

            public function billingInstallments(): int
            {
                throw new \RuntimeException('Not used in DiscountCalculator.');
            }

            public function billingFirstDueDate(): ?\Carbon\CarbonInterface
            {
                throw new \RuntimeException('Not used in DiscountCalculator.');
            }

            public function billingPaymentMethod(): \App\Enums\PaymentMethod
            {
                throw new \RuntimeException('Not used in DiscountCalculator.');
            }

            public function billingAnnotations(): ?string
            {
                throw new \RuntimeException('Not used in DiscountCalculator.');
            }

            public function billingFinancialCategoryId(): ?int
            {
                throw new \RuntimeException('Not used in DiscountCalculator.');
            }

            public function billingFinancialAccountId(): ?int
            {
                throw new \RuntimeException('Not used in DiscountCalculator.');
            }
        };
    }

    public function test_returns_zero_discounts_when_no_discount_configured(): void
    {
        $source = $this->fakeSource();
        $installments = [100.0, 100.0, 100.0];

        $result = $this->calculator->calculate($source, $installments);

        $this->assertEquals([0, 0, 0], $result);
    }

    public function test_splits_fixed_discount_value_across_installments(): void
    {
        $source = $this->fakeSource(['billingDiscountValue' => 30.0]);
        $installments = [100.0, 100.0, 100.0];

        $result = $this->calculator->calculate($source, $installments);

        $this->assertCount(3, $result);
        $this->assertSame(30.0, round(array_sum($result), 4));
    }

    public function test_applies_percentage_discount_to_eligible_installments(): void
    {
        $source = $this->fakeSource([
            'billingDiscountPercent' => 10.0,
            'billingDiscountedInstallments' => 2,
        ]);
        $installments = [200.0, 200.0, 200.0];

        $result = $this->calculator->calculate($source, $installments);

        $this->assertCount(3, $result);
        $this->assertSame(20.0, $result[0]);
        $this->assertSame(20.0, $result[1]);
        $this->assertSame(0.0, $result[2]);
    }

    public function test_discount_limit_caps_total_discount(): void
    {
        $source = $this->fakeSource([
            'billingDiscountPercent' => 50.0,
            'billingDiscountedInstallments' => 3,
            'billingDiscountLimit' => 30.0,
        ]);
        $installments = [200.0, 200.0, 200.0];

        $result = $this->calculator->calculate($source, $installments);

        $this->assertCount(3, $result);
        $this->assertLessThanOrEqual(30.01, array_sum($result));
        $this->assertGreaterThan(29.0, array_sum($result));
    }

    public function test_returns_zero_when_discount_percent_is_zero(): void
    {
        $source = $this->fakeSource([
            'billingDiscountPercent' => 0.0,
            'billingDiscountedInstallments' => 3,
        ]);
        $installments = [100.0, 100.0];

        $result = $this->calculator->calculate($source, $installments);

        $this->assertEquals([0, 0], $result);
    }

    public function test_handles_single_installment(): void
    {
        $source = $this->fakeSource(['billingDiscountValue' => 15.0]);
        $installments = [100.0];

        $result = $this->calculator->calculate($source, $installments);

        $this->assertCount(1, $result);
        $this->assertEquals(15.0, $result[0]);
    }

    public function test_handles_cents_with_remainder(): void
    {
        $source = $this->fakeSource(['billingDiscountValue' => 10.0]);
        $installments = [100.0, 100.0, 100.0];

        $result = $this->calculator->calculate($source, $installments);

        $this->assertCount(3, $result);
        $this->assertSame(10.0, round(array_sum($result), 4));
    }

    public function test_eligible_installments_capped_by_actual_count(): void
    {
        $source = $this->fakeSource([
            'billingDiscountPercent' => 10.0,
            'billingDiscountedInstallments' => 10,
        ]);
        $installments = [100.0, 100.0];

        $result = $this->calculator->calculate($source, $installments);

        $this->assertCount(2, $result);
        $this->assertSame(10.0, $result[0]);
        $this->assertSame(10.0, $result[1]);
    }
}
