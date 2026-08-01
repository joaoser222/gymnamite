<?php

namespace App\Console\Commands;

use App\Services\GatewayFiscalSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gateway:sync-fiscal-invoices {--account=* : Gateway account IDs to sync} {--status=* : Filter gateway invoice statuses} {--force : Rewrite unchanged gateway invoices}')]
#[Description('Reconcile issued fiscal invoices (NFS-e) with the payment gateway via polling fallback')]
class SyncFiscalInvoices extends Command
{
    public function handle(GatewayFiscalSyncService $service): int
    {
        $accountIds = array_map('intval', (array) $this->option('account'));
        $statuses = array_map('strval', (array) $this->option('status'));

        $results = $service->syncAll($accountIds, $statuses, (bool) $this->option('force'));

        if ($results === []) {
            $this->components->info('No eligible gateway accounts found for fiscal synchronization.');

            return self::SUCCESS;
        }

        $failedAccounts = 0;

        foreach ($results as $accountId => $result) {
            $this->components->twoColumnDetail("Account #{$accountId}", '');
            $this->components->twoColumnDetail('  Notes found', (string) $result['found']);
            $this->components->twoColumnDetail('  Notes updated', (string) $result['updated']);
            $this->components->twoColumnDetail('  Notes unchanged', (string) $result['unchanged']);
            $this->components->twoColumnDetail('  Notes failed', (string) $result['failed']);

            if ($result['failed'] > 0) {
                $failedAccounts++;
            }
        }

        return $failedAccounts > 0 ? self::FAILURE : self::SUCCESS;
    }
}
