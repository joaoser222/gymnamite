<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\DB;
use Throwable;

trait HandlesTransactions
{
    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    protected function transactionWithRetry(callable $callback, int $maxAttempts = 3, int $sleepMs = 100): mixed
    {
        $attempt = 0;

        while (true) {
            try {
                return DB::transaction($callback);
            } catch (Throwable $e) {
                $attempt++;

                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                usleep($sleepMs * 1000);
            }
        }
    }
}
