<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de eventos de experimento.
 */
final class ExperimentEventModel extends Model
{
    protected $table = 'experiment_events';

    public $timestamps = false;

    protected $fillable = [
        'experiment_id',
        'event_type',
        'payload',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
