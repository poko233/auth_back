<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de escalamientos de tickets.
 */
final class TicketEscalationModel extends Model
{
    protected $table = 'ticket_escalations';

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'from_role_id',
        'to_role_id',
        'escalated_by',
        'escalated_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'escalated_at' => 'datetime',
        ];
    }
}
