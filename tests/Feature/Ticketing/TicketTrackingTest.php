<?php

declare(strict_types=1);

namespace Tests\Feature\Ticketing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lab\Application\Ticketing\EscalateTicket;
use Lab\Application\Ticketing\RecordTicketView;
use Lab\Domain\Ticketing\TicketId;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

/**
 * Tracking de tickets: vistas, historial y escalamientos (con API Key).
 */
final class TicketTrackingTest extends TestCase
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

    public function test_cada_vista_graba_una_fila_sin_dedupe(): void
    {
        $headers = $this->keyHeaders($this->adminKey);
        $id = $this->postJson('/api/tickets', ['title' => 'Falla'], $headers)->assertCreated()->json('id');

        app(RecordTicketView::class)->execute(TicketId::fromInt($id), $this->adminId);
        app(RecordTicketView::class)->execute(TicketId::fromInt($id), $this->adminId);

        $this->getJson("/api/tickets/{$id}/views", $headers)
            ->assertOk()->assertJsonCount(2);
    }

    public function test_escalamientos_quedan_registrados(): void
    {
        $headers = $this->keyHeaders($this->adminKey);
        $id = $this->postJson('/api/tickets', ['title' => 'Falla'], $headers)->assertCreated()->json('id');

        app(EscalateTicket::class)->execute(TicketId::fromInt($id), 'ADMIN', $this->adminId, 'Urgente');

        $this->getJson("/api/tickets/{$id}/escalations", $headers)
            ->assertOk()
            ->assertJsonFragment(['to_role' => 'ADMIN', 'note' => 'Urgente']);
    }

    public function test_ver_ticket_registra_vista_con_principal_del_key(): void
    {
        $headers = $this->keyHeaders($this->adminKey);
        $id = $this->postJson('/api/tickets', ['title' => 'Falla'], $headers)->assertCreated()->json('id');

        $this->getJson("/api/tickets/{$id}", $headers)->assertOk();

        $this->getJson("/api/tickets/{$id}/views", $headers)
            ->assertOk()->assertJsonFragment(['user_id' => $this->adminId]);
    }
}
