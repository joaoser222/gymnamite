<?php

namespace Tests\Feature\Actions;

use App\Actions\Receivables\MarkReceivablePaidAction;
use App\DTOs\Receivables\MarkReceivablePaidDTO;
use App\Enums\BillableStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkReceivablePaidActionTest extends TestCase
{
    use RefreshDatabase;

    private function makeReceivable(array $overrides = []): Invoice
    {
        $client = Client::factory()->create();
        $sale = Sale::query()->create([
            'gross_value' => 200,
            'discount_value' => 0,
            'total' => 200,
            'status' => BillableStatus::OPEN,
            'payment_method' => PaymentMethod::PIX,
            'installments' => 1,
            'visibility' => 'visible',
            'client_id' => $client->id,
        ]);

        return Invoice::query()->create(array_merge([
            'invoice_type' => 'standard',
            'due_date' => '2026-09-01',
            'payment_method' => PaymentMethod::PIX,
            'gross_value' => 200,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => InvoiceStatus::PENDING,
            'operation_type' => OperationType::RECEIVABLE,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'billable_id' => $sale->id,
            'billable_type' => 'sale',
        ], $overrides));
    }

    public function test_marks_receivable_as_paid_with_cash_creates_internal_movement(): void
    {
        $receivable = $this->makeReceivable(['payment_method' => PaymentMethod::CASH]);

        $action = app(MarkReceivablePaidAction::class);
        $dto = new MarkReceivablePaidDTO(
            id: $receivable->id,
            payment_date: '2026-09-05',
        );

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertSame('Recebimento baixado com sucesso.', $result->message);

        $this->assertDatabaseHas('invoices', [
            'id' => $receivable->id,
            'status' => InvoiceStatus::PAID->value,
            'payment_date' => '2026-09-05',
            'paid_value' => 200,
        ]);

        $this->assertDatabaseHas('movements', [
            'operation_type' => OperationType::RECEIVABLE->value,
            'movement_type' => MovementType::INTERNAL->value,
            'value' => 200,
            'invoice_id' => $receivable->id,
        ]);
    }

    public function test_marks_receivable_as_paid_with_pix_creates_external_movement(): void
    {
        $receivable = $this->makeReceivable(['payment_method' => PaymentMethod::PIX]);

        $action = app(MarkReceivablePaidAction::class);
        $dto = new MarkReceivablePaidDTO(
            id: $receivable->id,
            payment_date: '2026-09-05',
        );

        $result = $action->execute($dto);

        $this->assertTrue($result->success);

        $this->assertDatabaseHas('movements', [
            'operation_type' => OperationType::RECEIVABLE->value,
            'movement_type' => MovementType::EXTERNAL->value,
            'value' => 200,
            'invoice_id' => $receivable->id,
        ]);
    }

    public function test_returns_failure_when_already_paid(): void
    {
        $receivable = $this->makeReceivable(['status' => InvoiceStatus::PAID]);

        $action = app(MarkReceivablePaidAction::class);
        $dto = new MarkReceivablePaidDTO(
            id: $receivable->id,
            payment_date: '2026-09-05',
        );

        $result = $action->execute($dto);

        $this->assertFalse($result->success);
        $this->assertSame('Este recebimento já foi baixado.', $result->message);
    }

    public function test_marks_boleto_receivable_as_paid(): void
    {
        $receivable = $this->makeReceivable(['payment_method' => PaymentMethod::BOLETO]);

        $action = app(MarkReceivablePaidAction::class);
        $dto = new MarkReceivablePaidDTO(
            id: $receivable->id,
            payment_date: '2026-09-10',
        );

        $result = $action->execute($dto);

        $this->assertTrue($result->success);

        $this->assertDatabaseHas('movements', [
            'movement_type' => MovementType::EXTERNAL->value,
            'invoice_id' => $receivable->id,
        ]);
    }
}
