<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Lab\Application\Access\AuthorizeAction;
use Lab\Application\Authentication\AuthenticatedPrincipal;
use Lab\Application\Ticketing\ShowTicket;
use Lab\Domain\Access\PermissionName;
use Lab\Domain\Identity\UserId;
use Lab\Domain\Ticketing\Ticket;
use Lab\Domain\Ticketing\TicketId;
use Lab\Domain\Ticketing\TicketStatus;
use Tests\TestCase;

/**
 * AuthorizeAction contra BD real: distingue capa RBAC de capa ticket_rule.
 */
final class AuthorizeActionTest extends TestCase
{
    use RefreshDatabase;

    private int $viewerId;

    private int $adminId;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['READ', 'WRITE', 'DELETE', 'ADMIN'] as $name) {
            DB::table('permissions')->insert(['name' => $name, 'is_system' => true]);
        }

        $viewer = DB::table('roles')->insertGetId(['name' => 'VIEWER', 'is_system' => true]);
        $admin = DB::table('roles')->insertGetId(['name' => 'ADMIN', 'is_system' => true]);

        $read = DB::table('permissions')->where('name', 'READ')->value('id');
        DB::table('role_permission')->insert(['role_id' => $viewer, 'permission_id' => $read]);

        foreach (DB::table('permissions')->pluck('id') as $permissionId) {
            DB::table('role_permission')->insert(['role_id' => $admin, 'permission_id' => $permissionId]);
        }

        $this->viewerId = DB::table('users')->insertGetId([
            'name' => 'Viewer', 'email' => 'viewer@lab.test', 'password' => 'x', 'is_active' => true,
        ]);
        $this->adminId = DB::table('users')->insertGetId([
            'name' => 'Admin', 'email' => 'admin@lab.test', 'password' => 'x', 'is_active' => true,
        ]);

        DB::table('role_user')->insert(['user_id' => $this->viewerId, 'role_id' => $viewer]);
        DB::table('role_user')->insert(['user_id' => $this->adminId, 'role_id' => $admin]);
    }

    private function principal(int $userId): AuthenticatedPrincipal
    {
        return new AuthenticatedPrincipal(UserId::fromString((string) $userId), 'TEST');
    }

    public function test_rechazo_rbac_para_viewer_sin_delete(): void
    {
        $result = app(AuthorizeAction::class)->execute(
            $this->principal($this->viewerId),
            PermissionName::fromString('DELETE'),
        );

        $this->assertFalse($result->allowed);
        $this->assertSame('rbac', $result->failedLayer);
    }

    public function test_rechazo_ticket_rule_para_admin_sobre_closed(): void
    {
        $ticketId = DB::table('tickets')->insertGetId([
            'title' => 'Falla', 'status' => 'CLOSED', 'priority' => 'MEDIUM',
        ]);
        $ticket = app(ShowTicket::class)->execute(TicketId::fromInt($ticketId));

        $this->assertSame(TicketStatus::CLOSED, $ticket->status());

        $result = app(AuthorizeAction::class)->execute(
            $this->principal($this->adminId),
            PermissionName::fromString('DELETE'),
            $ticket,
        );

        $this->assertFalse($result->allowed);
        $this->assertSame('ticket_rule', $result->failedLayer);
    }

    public function test_permite_admin_sobre_ticket_abierto(): void
    {
        $ticket = Ticket::create('Falla', null, $this->adminId);

        $result = app(AuthorizeAction::class)->execute(
            $this->principal($this->adminId),
            PermissionName::fromString('WRITE'),
            $ticket,
        );

        $this->assertTrue($result->allowed);
    }

    public function test_usuario_inactivo_siempre_denegado_por_rbac(): void
    {
        DB::table('users')->where('id', $this->viewerId)->update(['is_active' => false]);

        $result = app(AuthorizeAction::class)->execute(
            $this->principal($this->viewerId),
            PermissionName::fromString('READ'),
        );

        $this->assertFalse($result->allowed);
        $this->assertSame('rbac', $result->failedLayer);
    }
}
