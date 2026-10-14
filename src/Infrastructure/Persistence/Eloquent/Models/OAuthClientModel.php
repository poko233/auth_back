<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de clientes OAuth.
 */
final class OAuthClientModel extends Model
{
    protected $table = 'oauth_clients';

    protected $fillable = [
        'client_id',
        'client_secret',
        'name',
    ];
}
