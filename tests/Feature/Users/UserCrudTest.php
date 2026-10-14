<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRUD de usuarios end-to-end.
 */
final class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_lista_muestra_actualiza_y_desactiva(): void
    {
        $created = $this->postJson('/api/users', [
            'name' => 'Ana',
            'email' => 'ana@lab.test',
            'password' => 'secreta123',
        ])->assertCreated()->json();

        $id = $created['id'];

        $this->getJson('/api/users')->assertOk()->assertJsonFragment(['email' => 'ana@lab.test']);
        $this->getJson("/api/users/{$id}")->assertOk()->assertJsonFragment(['name' => 'Ana']);

        $this->putJson("/api/users/{$id}", ['name' => 'Ana María'])
            ->assertOk()->assertJsonFragment(['name' => 'Ana María']);

        $this->postJson("/api/users/{$id}/deactivate")
            ->assertOk()->assertJsonFragment(['is_active' => false]);
    }

    public function test_rechaza_email_duplicado_y_datos_invalidos(): void
    {
        $payload = ['name' => 'Beto', 'email' => 'beto@lab.test', 'password' => 'secreta123'];
        $this->postJson('/api/users', $payload)->assertCreated();
        $this->postJson('/api/users', $payload)->assertStatus(422);
        $this->postJson('/api/users', ['name' => '', 'email' => 'x', 'password' => 'corta'])
            ->assertStatus(422);
    }

    public function test_usuario_inexistente_devuelve_404(): void
    {
        $this->getJson('/api/users/999999')->assertNotFound();
    }
}
