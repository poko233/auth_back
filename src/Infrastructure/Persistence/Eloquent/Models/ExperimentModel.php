<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de experimentos.
 */
final class ExperimentModel extends Model
{
    protected $table = 'experiments';

    protected $fillable = [
        'name',
        'mechanism',
        'test_user_id',
        'resources',
        'status',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resources' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
