<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Transactions;

use Illuminate\Support\Facades\DB;
use Lab\Domain\Shared\TransactionManager;

/**
 * Transacciones con el driver de Laravel.
 */
final class LaravelTransactionManager implements TransactionManager
{
    public function transaction(callable $work): mixed
    {
        return DB::transaction($work);
    }
}
