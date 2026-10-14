# PROJECT.md — Guía completa de Autenticación Lab

> Documento maestro del proyecto. Orden de lectura: de arriba hacia abajo.
> Tiempo total estimado: ~30 minutos para las secciones 1–2, resto por consulta.

## 0. Cómo leer este documento

- **Secciones 1–2**: el proyecto en 5 minutos (para cualquiera).
- **Secciones 3–9**: el sistema en detalle (para el defensor y revisores).
- **Secciones 10–14**: operación, tests, glosario y FAQ (referencia).
- **Sección 12**: orden recomendado para leer el código por primera vez.

## 1. El proyecto en 5 minutos

**Qué es**: laboratorio académico que compara cuatro mecanismos de
autenticación — API Key, Basic, JWT y OAuth 2.0 (ROPC) — bajo un escenario
común de usuarios, roles y tickets, observando cómo responde cada uno cuando
cambian los privilegios de una identidad con credencial válida.

**Qué problema resuelve**: responder con datos si el mecanismo de auth afecta
el comportamiento ante cambios dinámicos de autorización.

**Qué se mide**: latencia (total y de auth), tamaño de credencial, round-trips,
expiración, revocación, y capa de cada rechazo (`rbac` vs `ticket_rule`).

**Qué NO es**: no es un producto ni un sistema empresarial. No hay frontend
conectado todavía (`auth_front` es repo separado, sin integración).

## 2. Arquitectura general

Dos repos independientes que solo hablan por HTTP/REST+JSON. El backend usa
Clean Architecture en 4 capas con dependencias hacia adentro:

```
Frameworks & Drivers → Interface Adapters → Use Cases → Entities
(Laravel, MySQL→Postgres) (src/Infrastructure) (src/Application) (src/Domain)
```

Reglas verificadas por tests (`tests/Architecture/`): Domain solo PHP puro;
Application solo Domain; Infrastructure puede todo menos ser importada.
`declare(strict_types=1)` y clases `final` en todo `src/`.

## 3. Estructura de carpetas

- `app/` → solo Laravel (providers, console). `User.php` por defecto eliminado.
- `src/Domain/` → `Identity/`, `Access/`, `Ticketing/`, `Credential/`,
  `Experiment/`, `Shared/` (puertos `Clock`, `TransactionManager`,
  `AuthorizationResult`).
- `src/Application/` → casos de uso por módulo (uno por clase, método `execute()`).
- `src/Infrastructure/` → Eloquent (Models/Mappers/Repositories), `Security/`
  (hashers, signers), `Http/` (Controllers/Requests/ApiResources/Middleware),
  `Clock/`.
- `routes/` → `api.php` carga `modules/*.php` (users, roles, permissions,
  tickets, ticket*history, auth*\*, experiments, metrics).
- `tests/` → Architecture, Unit (fakes, sin Laravel), Feature (HTTP + BD).
- `database/` → UNA migración (`create_full_schema`) + seeders idempotentes.
- `docs/openapi.yaml` → contrato de la API.
- `app/Console/Commands/` → `lab:reset`, `lab:smoke-experiment`,
  `lab:prune-jwt-revocations`.

## 4. El modelo de dominio

- **Identity**: `User` (identidad pura, sin roles), `Email` (VO con
  normalización), `UserId` (VO string), `PasswordHasher` (puerto).
  El `User` transporta `passwordHash` ya calculado (Opción 1).
- **Access**: `Role` (nombre = identidad, sin RoleId), `Permission` (entidad
  CRUD con `is_system`), `PermissionName` (VO con formato), `RoleRepository`
  (`currentRolesOf` con eager, assign/revoke por nombre).
- **Ticketing**: `Ticket` rico (status/priority/asignaciones/soft delete),
  enums `TicketStatus`/`TicketPriority`, `TicketView`/`TicketEscalation`/
  `TicketHistoryEntry` + sus repositorios.
- **Credential**: `ApiKey` (sha256), `JwtClaims`+`JwtSigner`+denylist,
  OAuth (`OAuthClient`, `AccessToken`/`RefreshToken` aleatorios).
- **Experiment**: `Experiment`, `ExperimentEvent`, `Metric`.

## 5. Los cuatro mecanismos

| Mecanismo | Transporte                           | Emisión (verifica identidad) | Revocación                                  |
| --------- | ------------------------------------ | ---------------------------- | ------------------------------------------- |
| API Key   | `X-API-Key`                          | `issue` (bootstrap)          | `revoked_at` (soft)                         |
| Basic     | `Authorization: Basic` (RFC 7617)    | verifica users               | n/a (password)                              |
| JWT       | `Bearer` (+ nada o `jwt`)            | `login` email+password       | denylist jti + rotación con reuso-detection |
| OAuth     | `Bearer` + `X-Auth-Mechanism: oauth` | grant password (ROPC)        | `revoked_at` + rotación con family-kill     |

Cada verify hace exactamente 1 query. 401 siempre bare. `mechanism` en
minúsculas en el principal; metadata solo trazabilidad.

## 6. Flujo de una petición

```
Cliente → MetricsRecorder → FlexibleAuth → Controller → AuthorizeAction
→ Use Case → Repository → Supabase Postgres
```

1. Recorder valida `X-Experiment-Id` (RUNNING o 400) e inicia cronómetro.
2. Flexible autentica por orden (X-API-Key → Basic → Bearer+jwt|oauth),
   mide `auth.ms`, adjunta el principal o 401.
3. Controller carga el recurso (**404 primero**), autoriza (**403 con layer**).
4. Use case muta + historial en una transacción (`TransactionManager`).
5. Recorder persiste la métrica (best-effort con contador `metric_loss`).

## 7. Autorización (RBAC en dos capas)

`AuthorizeAction(principal, PermissionName, ?Ticket)` consulta roles ACTUALES
en BD (nunca del token). Sin ticket: solo RBAC. Con ticket: RBAC + reglas
(eliminado, CLOSED+DELETE). Devuelve `AuthorizationResult` con `failedLayer`
(`rbac`|`ticket_rule`); el HTTP lo expone vía `ForbiddenResource`.
Existencia (404) siempre antes que autorización (403).

## 8. Tickets ricos

Estados: `OPEN → IN_PROGRESS|ESCALATED|CLOSED`, `IN_PROGRESS → ESCALATED|RESOLVED|CLOSED`, `ESCALATED → IN_PROGRESS|RESOLVED|CLOSED`,
`RESOLVED → IN_PROGRESS|CLOSED`, `CLOSED` terminal. Inválida → 422.
Toda mutación graba `ticket_history` en la misma transacción; cada vista graba
`ticket_views` sin dedupe (best-effort en lectura); escalamientos en
`ticket_escalations`. Borrado = soft delete.

## 9. Experimentos

`POST /experiments` (RUNNING) → operar con `X-Experiment-Id` →
`POST /experiments/{id}/privileges` (role_change con evento) →
`POST .../finish` → `GET .../metrics/compare` (`rows` con n/mediana/p95/
stddev + `losses` + `valid`). N ≥ 30 por combinación; importan mediana y p95
(baseline de red ~150-250 ms). `lab:smoke-experiment <mecanismo>` corre la
secuencia completa (requiere `php artisan serve`).

## 10. Reset y reproducción

```bash
php artisan migrate:fresh --seed   # 1 migración + 8 seeders, ~30s
php artisan lab:reset              # lo mismo con confirmación
```

Hay UNA sola migración consolidada (`create_full_schema`, 17 tablas); la
historia previa vive en `git log`. Cambios futuros van en migraciones nuevas.

## 11. Tests

- **Architecture** (12): dependencias, strict_types, final.
- **Unit** (37): dominio con fakes en memoria (clonan al guardar/devolver).
- **Feature** (58): HTTP end-to-end contra Supabase con transacciones.
- `vendor\bin\pest` → 107/107. `phpunit.xml` apunta a pgsql (sqlite sin driver
  aquí); `TestCase` fija timeouts anti `idle in transaction`.

## 12. Dónde empezar a leer

1. Este archivo (30 min) 2. `README.md` (10 min)
2. `database/migrations/*_create_full_schema.php` (15 min)
3. `src/Domain/` (1 h) 5. `src/Application/Access/AuthorizeAction.php` (30 min)
4. `src/Application/Authentication/*` (1 h)
5. `src/Infrastructure/Http/Middleware/*` (30 min)
6. `src/Infrastructure/Persistence/Eloquent/` (1 h)
7. `tests/Architecture/*` (20 min) 10. `tests/Feature/Authentication/*` (30 min)

## 13. Glosario

Clean Architecture, Entity, Value Object, DTO, puerto/adaptador, caso de uso,
Service Provider, migración, seeder, RBAC, JWT, OAuth ROPC, API Key, Basic,
round-trip, latencia, mediana, p95, desviación estándar, denylist, soft delete.

## 14. FAQ

- **¿Por qué dos repos?** Para que frontend y backend evolucionen y se
  desplieguen por separado; contrato solo por HTTP.
- **¿Por qué Clean Architecture?** Para que el experimento (dominio) no dependa
  del framework y los 4 mecanismos sean intercambiables.
- **¿Por qué permisos dinámicos y no enum?** Porque el laboratorio necesita
  CRUD de roles/permisos como variable experimental; los base están protegidos.
- **¿Por qué una sola migración?** Proyecto cerrado: el schema completo en un
  archivo es legible para el revisor; la historia está en git.
- **¿Por qué soft delete?** Para medir quién borró qué y cuándo.
- **¿Por qué sha256 y no bcrypt en API Keys?** bcrypt no permite `findByHash`;
  con 256 bits de entropía, sha256 para lookup es el estándar (OWASP/Stripe).
- **¿Cómo agrego un quinto mecanismo?** Replicar §14 del AGENTS.md:
  use case + middleware + Bearer/discriminador + tests espejo.
- **¿Cómo agrego un recurso?** Módulo según AGENTS.md §4.4 + migración nueva.
- **¿401 vs 403?** 401 = sin identidad válida (bare); 403 = identidad ok sin
  permiso/regla (con `layer`).

## 15. Referencias

RFC 6749 (OAuth 2.0), RFC 7519 (JWT), RFC 7617 (Basic), RFC 7662
(introspección), Clean Architecture (R. C. Martin, 2017), docs de Laravel 12,
Pest 4, Postgres.
