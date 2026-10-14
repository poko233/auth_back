<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Lab\Infrastructure\Persistence\Eloquent\Models\JwtRevocationModel;

/**
 * Poda la denylist de JTIs: las filas con expires_at pasado ya no protegen
 * nada (el token expiró) y solo degradan la verificación con el tiempo.
 */
final class PruneJwtRevocations extends Command
{
    protected $signature = 'lab:prune-jwt-revocations';

    protected $description = 'Borra JTIs revocados cuyo token ya expiró.';

    public function handle(): int
    {
        $deleted = JwtRevocationModel::query()->where('expires_at', '<', now())->delete();

        $this->info("JTIs podados: {$deleted}.");

        return self::SUCCESS;
    }
}
