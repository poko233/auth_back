<?php

declare(strict_types=1);

namespace Tests\Feature\Ticketing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lab\Application\Ticketing\EscalateTicket;
use Lab\Domain\Ticketing\TicketId;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

/**
 * CRUD de tickets + estados + historial end-to-end (con API Key).
 */
final class TicketCrudTest extends TestCase
{
    use IssuesApiKeys;
    use RefreshDatabase;

    private string $adminKey;

    private int $adminId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLabRbac();

        $admin = $this->makeUserWithKey('admin@lab.test', 'ADMIN');
        $this->adminKey = $admin['key'];
        $this->adminId = $admin['id'];
    }

    public function test_ciclo_completo_de_ticket_con_historial(): void
    {
        $headers = $this->keyHeaders($this->adminKey);

        $created = $this->postJson('/api/tickets', [
            'title' => 'Caída de red',
            'description' => 'Sin conexión',
            'priority' => 'HIGH',
        ], $headers)->assertCreated()->assertJsonFragment(['status' => 'OPEN'])->json();

        $id = $created['id'];
        $this->assertSame($this->adminId, $created['created_by']);

        $this->getJson('/api/tickets', $headers)->assertOk()->assertJsonFragment(['title' => 'Caída de red']);
        $this->getJson("/api/tickets/{$id}", $headers)->assertOk();

        $this->putJson("/api/tickets/{$id}", ['title' => 'Caída de red total'], $headers)
            ->assertOk()->assertJsonFragment(['title' => 'Caída de red total']);

        $this->postJson("/api/tickets/{$id}/status", ['status' => 'IN_PROGRESS'], $headers)
            ->assertOk()->assertJsonFragment(['status' => 'IN_PROGRESS']);

        // Transición inválida: IN_PROGRESS→OPEN no existe.
        $this->postJson("/api/tickets/{$id}/status", ['status' => 'OPEN'], $headers)->assertStatus(422);

        $escalated = app(EscalateTicket::class)->execute(TicketId::fromInt($id), 'ADMIN', $this->adminId, 'Urgente');

        $this->assertSame('ESCALATED', $escalated->status()->value);

        $this->getJson("/api/tickets/{$id}/history", $headers)->assertOk()->assertJsonCount(4);

        $this->deleteJson("/api/tickets/{$id}", [], $headers)->assertNoContent();
        $this->getJson("/api/tickets/{$id}", $headers)->assertNotFound();
    }

    public function test_ticket_inexistente_devuelve_404_antes_que_403(): void
    {
        $headers = $this->keyHeaders($this->adminKey);

        // Orden: existencia (404) siempre antes que autorización (403).
        $this->getJson('/api/tickets/999999', $headers)->assertNotFound();
        $this->deleteJson('/api/tickets/999999', [], $headers)->assertNotFound();
    }

    public function test_sin_key_responde_401(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
        $this->postJson('/api/tickets', ['title' => 'X'])->assertUnauthorized();
    }
}
