<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de refresh tokens.
 */
final class OAuthRefreshTokenModel extends Model
{
    protected $table = 'oauth_refresh_tokens';

    protected $fillable = [
        'refresh_token',
        'access_token_id',
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
