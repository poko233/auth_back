<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo Eloquent de permisos.
 */
final class PermissionModel extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'description',
        'is_system',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<RoleModel, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'role_permission', 'permission_id', 'role_id');
    }
}
