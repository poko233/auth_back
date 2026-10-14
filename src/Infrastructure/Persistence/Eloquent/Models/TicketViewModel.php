<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de vistas de tickets (sin updated_at: solo viewed_at).
 */
final class TicketViewModel extends Model
{
    protected $table = 'ticket_views';

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'viewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }
}
