<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Lab\Domain\Credential\ApiKey\ApiKeyRepository;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

final class ApiKeyManagementTest extends TestCase
{
    use IssuesApiKeys;
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $adminHeaders;

    private int $targetId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();
        $this->makeUserWithKey('manager@lab.test', 'ADMIN');
        $this->adminHeaders = ['Authorization' => 'Basic '.base64_encode('manager@lab.test:secreta123')];
        $this->targetId = $this->makeUserWithKey('target@lab.test', 'VIEWER')['id'];
    }

    public function test_emite_lista_y_revoca_sin_exponer_el_secreto_nuevamente(): void
    {
        $issued = $this->postJson('/api/auth/api-key/issue', [
            'user_id' => $this->targetId,
            'label' => 'Mi experimento',
            'expires_at' => now()->addDay()->toIso8601String(),
        ], $this->adminHeaders)->assertCreated()->assertHeader('Cache-Control', 'no-store, private')->json();

        $id = $issued['key']['id'];
        $plain = $issued['plain'];
        $this->assertNotEmpty($issued['key']['created_at']);
        $this->assertArrayNotHasKey('key_hash', $issued['key']);
        $this->assertDatabaseHas('api_keys', ['id' => $id, 'key_hash' => hash('sha256', $plain)]);
        $stored = (array) DB::table('api_keys')->where('id', $id)->first();
        $this->assertNotContains($plain, array_values($stored));
        $found = app(ApiKeyRepository::class)->findByHash(hash('sha256', $plain));
        $this->assertSame($id, $found?->id()?->value());

        $listed = $this->getJson("/api/auth/api-key/users/{$this->targetId}/keys", $this->adminHeaders)->assertOk();
        $listed->assertJsonFragment(['label' => 'Mi experimento', 'id' => $id]);
        $this->assertStringNotContainsString($plain, $listed->getContent());
        $this->assertStringNotContainsString('key_hash', $listed->getContent());
        $this->getJson('/api/tickets', $this->keyHeaders($plain))->assertOk();

        $this->postJson("/api/auth/api-key/revoke/{$id}", [], $this->adminHeaders)->assertNoContent();
        $firstRevokedAt = DB::table('api_keys')->where('id', $id)->value('revoked_at');
        $this->travel(1)->hours();
        $this->postJson("/api/auth/api-key/revoke/{$id}", [], $this->adminHeaders)
            ->assertStatus(409)->assertJsonPath('detail', 'Esta API Key ya está revocada');
        $this->assertSame($firstRevokedAt, DB::table('api_keys')->where('id', $id)->value('revoked_at'));
        $this->getJson('/api/tickets', $this->keyHeaders($plain))->assertUnauthorized()->assertJsonPath('error', 'unauthenticated');
        $keys = $this->getJson("/api/auth/api-key/users/{$this->targetId}/keys", $this->adminHeaders)->assertOk()->json();
        $revoked = array_values(array_filter($keys, static fn (array $key): bool => $key['id'] === $id));
        $this->assertNotNull($revoked[0]['revoked_at']);
    }

    public function test_valida_etiqueta_y_expiracion_futura_y_permite_omitir_expiracion(): void
    {
        foreach (['', '   '] as $label) {
            $this->postJson('/api/auth/api-key/issue', ['user_id' => $this->targetId, 'label' => $label], $this->adminHeaders)
                ->assertUnprocessable()->assertJsonPath('errors.label.0', 'La etiqueta es obligatoria');
        }
        $this->postJson('/api/auth/api-key/issue', [
            'user_id' => $this->targetId, 'label' => 'Pasada', 'expires_at' => now()->subMinute()->toIso8601String(),
        ], $this->adminHeaders)->assertUnprocessable()
            ->assertJsonPath('errors.expires_at.0', 'La fecha de expiración debe ser posterior a la fecha actual');
        $this->postJson('/api/auth/api-key/issue', ['user_id' => $this->targetId, 'label' => str_repeat('x', 101)], $this->adminHeaders)->assertUnprocessable();
        $this->postJson('/api/auth/api-key/issue', ['user_id' => $this->targetId, 'label' => 'Sin caducidad'], $this->adminHeaders)
            ->assertCreated()->assertJsonPath('key.expires_at', null);
    }

    public function test_la_expiracion_con_zona_horaria_representa_el_mismo_instante(): void
    {
        $expiry = (new \DateTimeImmutable('+1 day'))->setTimezone(new \DateTimeZone('-04:00'));
        $issued = $this->postJson('/api/auth/api-key/issue', [
            'user_id' => $this->targetId, 'label' => 'Zona horaria', 'expires_at' => $expiry->format(DATE_ATOM),
        ], $this->adminHeaders)->assertCreated()->json();
        $this->assertSame($expiry->getTimestamp(), (new \DateTimeImmutable($issued['key']['expires_at']))->getTimestamp());
    }

    public function test_solo_administradores_pueden_gestionar_claves(): void
    {
        $id = DB::table('api_keys')->where('user_id', $this->targetId)->value('id');
        $payload = ['user_id' => $this->targetId, 'label' => 'Prueba'];
        $this->postJson('/api/auth/api-key/issue', $payload)->assertUnauthorized();
        $this->getJson("/api/auth/api-key/users/{$this->targetId}/keys")->assertUnauthorized();
        $this->postJson("/api/auth/api-key/revoke/{$id}")->assertUnauthorized();
        $viewerHeaders = ['Authorization' => 'Basic '.base64_encode('target@lab.test:secreta123')];
        $this->postJson('/api/auth/api-key/issue', $payload, $viewerHeaders)->assertForbidden();
        $this->getJson("/api/auth/api-key/users/{$this->targetId}/keys", $viewerHeaders)->assertForbidden();
        $this->postJson("/api/auth/api-key/revoke/{$id}", [], $viewerHeaders)->assertForbidden();
    }

    public function test_rechaza_usuarios_inexistentes_e_inactivos_y_claves_inexistentes(): void
    {
        $this->getJson('/api/auth/api-key/users/999999/keys', $this->adminHeaders)->assertNotFound();
        $this->postJson('/api/auth/api-key/issue', ['user_id' => 999999, 'label' => 'Prueba'], $this->adminHeaders)->assertNotFound();
        $this->postJson('/api/auth/api-key/revoke/999999', [], $this->adminHeaders)->assertNotFound();
        DB::table('users')->where('id', $this->targetId)->update(['is_active' => false]);
        $this->postJson('/api/auth/api-key/issue', ['user_id' => $this->targetId, 'label' => 'Prueba'], $this->adminHeaders)->assertUnprocessable();
    }
}
