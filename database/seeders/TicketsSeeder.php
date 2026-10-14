<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Tickets demo en distintos estados (requiere UsersSeeder previo).
 *
 * Idempotente: updateOrInsert por título.
 */
class TicketsSeeder extends Seeder
{
    /**
     * @var list<array{title: string, description: string, status: string, priority: string}>
     */
    public const TICKETS = [
        ['title' => 'Caída de red en laboratorio', 'description' => 'Sin conexión desde las 08:00', 'status' => 'OPEN', 'priority' => 'HIGH'],
        ['title' => 'Impresora sin tóner', 'description' => 'Segundo piso', 'status' => 'OPEN', 'priority' => 'LOW'],
        ['title' => 'Error 500 en login', 'description' => 'Solo en Safari', 'status' => 'IN_PROGRESS', 'priority' => 'HIGH'],
        ['title' => 'Backup lento', 'description' => 'Tarda 6 horas', 'status' => 'IN_PROGRESS', 'priority' => 'MEDIUM'],
        ['title' => 'Acceso VPN caído', 'description' => 'Escalado a red', 'status' => 'ESCALATED', 'priority' => 'CRITICAL'],
        ['title' => 'Certificado por vencer', 'description' => 'Vence en 7 días', 'status' => 'ESCALATED', 'priority' => 'HIGH'],
        ['title' => 'Teclado roto aula 3', 'description' => 'Reemplazado', 'status' => 'RESOLVED', 'priority' => 'LOW'],
        ['title' => 'DNS intermitente', 'description' => 'Resuelto con flush', 'status' => 'RESOLVED', 'priority' => 'MEDIUM'],
        ['title' => 'Ticket de prueba 2024', 'description' => 'Histórico', 'status' => 'CLOSED', 'priority' => 'LOW'],
        ['title' => 'Migración completada', 'description' => 'Cierre de proyecto', 'status' => 'CLOSED', 'priority' => 'MEDIUM'],
    ];

    public function run(): void
    {
        $adminId = DB::table('users')->where('email', 'admin@lab.test')->value('id');

        foreach (self::TICKETS as $ticket) {
            DB::table('tickets')->updateOrInsert(
                ['title' => $ticket['title']],
                [
                    'description' => $ticket['description'],
                    'status' => $ticket['status'],
                    'priority' => $ticket['priority'],
                    'created_by' => $adminId,
                    'updated_at' => now(),
                ],
            );
        }
    }
}
