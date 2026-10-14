<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

/**
 * Ayudas para tests Feature con API Key.
 *
 * Crea usuario + rol + key y devuelve headers listos para usar.
 */
trait IssuesApiKeys
{
    /**
     * @return array{id: int, key: string}
     */
    protected function makeUserWithKey(string $email, string $role): array
    {
        $userId = (int) $this->postJson('/api/users', [
            'name' => $email,
            'email' => $email,
            'password' => 'secreta123',
        ])->assertCreated()->json('id');

        $this->postJson("/api/users/{$userId}/roles", ['role' => $role])->assertNoContent();

        $plain = (string) $this->postJson('/api/auth/api-key/issue', [
            'user_id' => $userId,
        ])->assertCreated()->json('plain');

        return ['id' => $userId, 'key' => $plain];
    }

    /**
     * @return array<string, string>
     */
    protected function keyHeaders(string $plainKey): array
    {
        return ['X-API-Key' => $plainKey];
    }

    protected function seedLabRbac(): void
    {
        foreach (['READ', 'WRITE', 'DELETE', 'ADMIN'] as $name) {
            DB::table('permissions')->insert(['name' => $name, 'is_system' => true]);
        }

        foreach (['VIEWER' => ['READ'], 'EDITOR' => ['READ', 'WRITE'], 'ADMIN' => ['READ', 'WRITE', 'DELETE', 'ADMIN']] as $role => $permissions) {
            $roleId = DB::table('roles')->insertGetId(['name' => $role, 'is_system' => true]);

            foreach ($permissions as $permission) {
                $permissionId = DB::table('permissions')->where('name', $permission)->value('id');
                DB::table('role_permission')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }
}
