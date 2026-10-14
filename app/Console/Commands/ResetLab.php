<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Resetea la base del laboratorio: migrate:fresh --seed con confirmación.
 *
 * Las migraciones NO se consolidan (AGENTS.md §4.5): son historia inmutable y
 * se corren en orden. El seeder reconstruye todo el estado inicial.
 */
final class ResetLab extends Command
{
    protected $signature = 'lab:reset {--force : Omitir la confirmación}';

    protected $description = 'Borra TODA la base y la reconstruye (migrate:fresh --seed).';

    public function handle(): int
    {
        if (! (bool) $this->option('force') && ! $this->confirm('Esto borra TODA la base. ¿Continuar?')) {
            $this->info('Cancelado.');

            return self::SUCCESS;
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->info('Base reseteada. Sistema listo.');

        return self::SUCCESS;
    }
}
