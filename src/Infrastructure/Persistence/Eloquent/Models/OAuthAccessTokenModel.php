<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de access tokens (hashes, nunca planos).
 */
final class OAuthAccessTokenModel extends Model
{
    protected $table = 'oauth_access_tokens';

    protected $fillable = [
        'access_token',
        'client_id',
        'user_id',
        'scopes',
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
