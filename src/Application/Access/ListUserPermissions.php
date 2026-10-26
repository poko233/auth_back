<?php

declare(strict_types=1);

namespace Lab\Application\Access;

use Lab\Domain\Access\Role;

/**
 * Calcula los permisos efectivos del usuario a partir de sus roles vigentes.
 *
 * Regla de negocio: el conjunto de permisos del usuario es la UNIÓN de los
 * permisos de todos sus roles. Sin deduplicar, el cliente recibiría
 * duplicados cuando dos roles comparten un permiso (situación posible: la
 * pivot `role_permission` es única por par, pero dos roles distintos pueden
 * apuntar al mismo permiso).
 *
 * Contrato de entrada: recibe `list<Role>` YA resuelta por ListUserRoles.
 * Motivo: `RoleRepository::currentRolesOf()` hace el eager load
 * `roles.permissions` en una sola query; volver a llamarlo desde aquí
 * duplicaría la query en /me. Este caso de uso es PURO: no recibe puertos,
 * no consulta repositorios, no conoce Laravel.
 *
 * Se preserva la autoridad de `AuthorizeAction` para decidir SI una acción
 * está permitida: este use case solo INFORMA al cliente qué permisos
 * efectivos tiene hoy, no autoriza nada.
 */
final class ListUserPermissions
{
    /**
     * @param list<Role> $roles Roles del usuario con permisos ya cargados.
     * @return list<string> Nombres de permiso únicos, ordenados ascendentemente.
     */
    public function execute(array $roles): array
    {
        $unique = [];

        foreach ($roles as $role) {
            foreach ($role->permissions() as $permission) {
                // Las claves de un array PHP colapsan por valor; los nombres
                // pasan por PermissionName::__construct (regex ^[A-Z]...) así
                // que no pueden ser numéricos y array_keys los devuelve string.
                $unique[$permission->name()->value()] = true;
            }
        }

        $names = array_keys($unique);
        sort($names);

        return $names;
    }
}