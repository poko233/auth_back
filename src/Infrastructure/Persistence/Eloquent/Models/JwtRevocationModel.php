<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de revocaciones JWT (denylist por jti).
 */
final class JwtRevocationModel extends Model
{
    protected $table = 'jwt_revocations';

    public $timestamps = false;

    protected $fillable = [
        'jti',
        'expires_at',
        'revoked_at',
        'replaced_by',
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
