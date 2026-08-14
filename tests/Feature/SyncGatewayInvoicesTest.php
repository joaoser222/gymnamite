<?php

namespace Tests\Feature;

use App\Services\Gateway\GatewayBillingOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncGatewayInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_succeeds_when_there_are_no_eligible_invoices(): void
    {
        $this->mock(GatewayBillingOrchestrator::class);

        $this->artisan('gateway:sync-invoices')
            ->assertSuccessful();
    }
}
