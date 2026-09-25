<?php

declare(strict_types=1);

namespace Lab\Application\Authentication;

use Lab\Domain\Identity\UserId;

/**
 * Contrato común de identidad autenticada.
 *
 * Es el punto de unión entre los cuatro mecanismos bajo estudio:
 * cada uno (API Key, Basic, JWT, OAuth) tiene su propia lógica interna de
 * extracción y validación de credenciales, pero todos entregan al resto
 * del sistema una instancia de este DTO.
 *
 * Decisión de diseño (Fase 1, cerrada):
 *   Credencial → autenticación específica → AuthenticatedPrincipal → RBAC
 *
 * El RBAC consume exclusivamente este DTO. Nunca Auth::user() de Laravel,
 * nunca el formato crudo de la credencial.
 *
 * ¿Por qué Application y no Domain?
 * Porque no representa una regla de negocio: representa el resultado de un
 * caso de uso de autenticación. El Domain sólo conoce UserId (regla pura de
 * identidad). Este DTO añade metadatos del mecanismo que sólo interesan a la
 * capa de aplicación y a la experimentación.
 */
final readonly class AuthenticatedPrincipal
{
    /**
     * @param array<string, mixed> $metadata
     *        Metadatos específicos del mecanismo (p. ej. jti de JWT,
     *        scopes de OAuth, exp de token). Nunca usados para autorización;
     *        sí usados para métricas y trazabilidad experimental.
     */
    public function __construct(
        public UserId $userId,
        public string $mechanism,
        public array $metadata = [],
    ) {
    }
}