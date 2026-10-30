<?php

declare(strict_types=1);

namespace Tests\Feature\Experiments;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SmokeExperimentCompatibilityTest extends TestCase
{
    public function test_smoke_crea_inicia_y_conserva_su_secuencia_una_sola_vez(): void
    {
        Http::preventStrayRequests();
        $role = '';
        Http::fake(function (Request $request) use (&$role) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            if ($path === '/api/users') {
                return Http::response([['id' => '42', 'email' => 'smoke-basic@lab.test']]);
            }

            if ($path === '/api/experiments') {
                return Http::response(['id' => 1, 'status' => 'PENDING'], 201);
            }

            if ($path === '/api/experiments/1/privileges') {
                $role = $request['add_roles'][0] ?? '';

                return Http::response(null, 204);
            }

            if ($path === '/api/tickets' && $request->method() === 'POST') {
                return in_array($role, ['EDITOR', 'ADMIN'], true)
                    ? Http::response(['id' => 10], 201)
                    : Http::response(['error' => 'forbidden'], 403);
            }

            if ($path === '/api/experiments/1/metrics/compare') {
                return Http::response(['rows' => [], 'losses' => 0, 'valid' => true]);
            }

            return Http::response([]);
        });

        $this->artisan('lab:smoke-experiment', ['mechanism' => 'basic', '--admin-password' => 'fixture-admin'])->assertExitCode(0);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://localhost:8000/api/experiments'
            && $request['test_user_id'] === 42 && $request['resources'] === ['tickets.list'] && $request->hasHeader('Authorization'));
        $this->assertCount(1, Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/experiments/1/start')));
        $this->assertCount(5, Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/tickets') && $request->method() === 'GET'));
        $this->assertCount(5, Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/tickets') && $request->method() === 'POST'));
        $this->assertCount(3, Http::recorded(fn (Request $request): bool => $request->method() === 'DELETE'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/experiments/1/finish') && $request->hasHeader('Authorization'));
    }
}
