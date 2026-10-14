<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de la bitácora de tickets (solo created_at).
 */
final class TicketHistoryModel extends Model
{
    protected $table = 'ticket_history';

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'actor_id',
        'action',
        'from_value',
        'to_value',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
