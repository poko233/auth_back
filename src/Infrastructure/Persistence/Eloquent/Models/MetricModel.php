<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de métricas.
 */
final class MetricModel extends Model
{
    protected $table = 'metrics';

    public $timestamps = false;

    protected $fillable = [
        'experiment_id',
        'mechanism',
        'operation',
        'http_status',
        'latency_ms',
        'auth_ms',
        'credential_size',
        'round_trips',
        'failed_layer',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
