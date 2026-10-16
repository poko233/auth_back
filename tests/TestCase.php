<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Lección del incidente idle-in-transaction (Fase 6): con Postgres
        // remoto, una transacción colgada bloquea el migrate:fresh. Postgres
        // mata conexiones problemáticas automáticamente con estos timeouts.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('SET statement_timeout = 60000');
            DB::statement('SET idle_in_transaction_session_timeout = 15000');
        }
    }
}
