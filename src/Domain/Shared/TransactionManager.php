<?php

declare(strict_types=1);

namespace Lab\Domain\Shared;

/**
 * Puerto de transacciones.
 *
 * Los casos de uso que mutan un ticket y su historial necesitan atomicidad,
 * pero Application no puede importar Illuminate: este puerto lo resuelve y la
 * implementación Eloquent usa DB::transaction.
 */
interface TransactionManager
{
    /**
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public function transaction(callable $work): mixed;
}
