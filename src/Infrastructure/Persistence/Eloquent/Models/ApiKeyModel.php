<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de API Keys (solo hashes, nunca planos).
 */
final class ApiKeyModel extends Model
{
    protected $table = 'api_keys';

    protected $fillable = [
        'user_id',
        'key_hash',
        'label',
        'expires_at',
        'revoked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
