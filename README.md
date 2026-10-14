# Autenticación Lab — Backend

Laboratorio telemático para la **evaluación experimental de mecanismos de
autenticación ante cambios dinámicos de privilegios**.

El sistema implementa y compara cuatro mecanismos —**API Key**, **Basic
Authentication**, **JWT** y **OAuth 2.0**— bajo un escenario común de usuarios,
roles, permisos y recursos protegidos, permitiendo observar cómo responde cada
uno cuando la autoridad de una identidad cambia mientras ya posee una
credencial válida.

> **Proyecto académico.** No es un sistema empresarial. El objetivo es
> experimental: comparar mecanismos bajo condiciones controladas y registrar
> las diferencias observables.

---

## Tabla de contenidos

- [Stack técnico](#stack-técnico)
- [Arquitectura](#arquitectura)
    - [Clean Architecture en una frase](#clean-architecture-en-una-frase)
    - [Las cuatro capas](#las-cuatro-capas)
    - [Reglas de dependencia](#reglas-de-dependencia)
    - [División `app/` vs `src/`](#división-app-vs-src)
    - [El namespace `Lab\`](#el-namespace-lab)
- [Estructura de carpetas](#estructura-de-carpetas)
- [Cómo implementar un módulo nuevo](#cómo-implementar-un-módulo-nuevo)
- [Testing](#testing)
- [Instalación y ejecución](#instalación-y-ejecución)
- [Roadmap](#roadmap-por-fases)
- [Convenciones de código](#convenciones-de-código)

---

## Stack técnico

| Componente | Versión | Uso en el proyecto                                      |
| ---------- | ------- | ------------------------------------------------------- |
| PHP        | 8.5     | `readonly`, promoted properties, enums, tipos estrictos |
| Laravel    | 12.x    | Framework HTTP, contenedor de dependencias, migraciones |
| MySQL      | 8.x     | Base de datos (aun no implementada)                     |
| Pest       | 4.x     | Framework de tests (incluye tests de arquitectura)      |
| Composer   | 2.x     | Gestión de dependencias y autoload                      |

**Paquetes intencionalmente NO utilizados** (porque el proyecto los implementa
nativamente como parte del experimento):

- `laravel/passport` → se implementa un servidor OAuth 2.0 propio.
- `spatie/laravel-permission` → se implementa RBAp por fases [Roadmap](#roadmap-por-fases) propio.
- `firebase/php-jwt` → se instalará sólo en la fase de JWT, como primitiva
  criptográfica, no como la lógica del flujo.

---

## Arquitectura

### Clean Architecture en una frase

> El código de negocio no debe saber que existe un framework, una base de datos
> o HTTP.

Esta idea se materializa en **una regla de dependencias**: las capas externas
pueden conocer a las internas, nunca al revés. Los detalles técnicos
(Laravel, MySQL, HTTP) viven **fuera** del dominio y se inyectan desde fuera.

### Las cuatro capas

```
┌─────────────────────────────────────────────────────────────┐
│  FRAMEWORKS & DRIVERS                                       │
│  Laravel · MySQL · HTTP · Eloquent                          │
│                                                             │
│   ┌─────────────────────────────────────────────────────┐   │
│   │  INTERFACE ADAPTERS                                 │   │
│   │  Controllers · Middleware · Eloquent Repositories   │   │
│   │  Mappers · Requests · ApiResources                  │   │
│   │                                                     │   │
│   │   ┌─────────────────────────────────────────────┐   │   │
│   │   │  USE CASES (Application)                    │   │   │
│   │   │  Orquestan entidades y puertos              │   │   │
│   │   │                                             │   │   │
│   │   │   ┌─────────────────────────────────────┐   │   │   │
│   │   │   │  ENTITIES (Domain)                  │   │   │   │
│   │   │   │  Reglas de negocio puras            │   │   │   │
│   │   │   │  Sin Laravel, sin Eloquent, sin     │   │   │   │
│   │   │   │  HTTP. Sólo PHP.                    │   │   │   │
│   │   │   └─────────────────────────────────────┘   │   │   │
│   │   └─────────────────────────────────────────────┘   │   │
│   └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘

Dirección de dependencia:  ───────────────►  hacia adentro
```

| Capa                          | Qué contiene                                                           | Qué puede importar                                  |
| ----------------------------- | ---------------------------------------------------------------------- | --------------------------------------------------- |
| **Domain** (Entities)         | Entidades, Value Objects, Puertos (interfaces), excepciones de negocio | Sólo `Lab\Domain\*` y PHP estándar                  |
| **Application** (Use Cases)   | Casos de uso. Orquestan entidades y llaman a puertos                   | `Lab\Domain\*`                                      |
| **Infrastructure** (Adapters) | Implementaciones concretas: Eloquent, HTTP, JWT, etc.                  | `Lab\Domain\*`, `Lab\Application\*`, `Illuminate\*` |
| **Frameworks & Drivers**      | Laravel y su arranque                                                  | Todo                                                |

### Reglas de dependencia

Estas reglas **no son aspiracionales**, están codificadas como tests
(`tests/Architecture/CleanArchitectureTest.php`) y fallan el CI si alguien
las rompe:

- `Lab\Domain\*` **NO** puede importar `Illuminate\*`, `App\*`,
  `Lab\Application\*` ni `Lab\Infrastructure\*`.
- `Lab\Application\*` **NO** puede importar `Illuminate\*`, `App\*` ni
  `Lab\Infrastructure\*`.
- `Lab\Infrastructure\*` **SÍ** puede importar `Lab\Domain\*`,
  `Lab\Application\*`, `Illuminate\*` y `App\*`.
- Todo el código bajo el namespace `Lab\` declara `declare(strict_types=1);`.
- Las clases de `Lab\Domain`, `Lab\Application` y `Lab\Infrastructure` son
  `final` por defecto (excepto interfaces, traits y clases abstractas).

### División `app/` vs `src/`

| Carpeta | Namespace | Contiene                                                                                            |
| ------- | --------- | --------------------------------------------------------------------------------------------------- |
| `app/`  | `App\`    | Sólo lo que Laravel exige encontrar aquí: Service Providers, Http Kernel, Console, Controllers base |
| `src/`  | `Lab\`    | Todo el código de negocio: Domain, Application, Infrastructure                                      |

**Razón de la separación:** que sea visualmente imposible confundir código de
framework con código propio. Cualquier archivo bajo `src/` obedece reglas
estrictas de dependencia; cualquier archivo bajo `app/` es el código de Laravel.

### El namespace `Lab\`

Se configuró un autoload PSR-4 en `composer.json`:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/",
        "Lab\\": "src/"
    }
}
```

Esto significa que `Lab\Domain\Identity\UserId` se resuelve al archivo
`src/Domain/Identity/UserId.php`, siguiendo la convención PSR-4
(namespace ↔ carpeta, nombre de clase ↔ archivo).

---

## Estructura de carpetas

```
auth_back/
├── app/                              → Laravel (Frameworks & Drivers)
│   ├── Http/Controllers/Controller.php
│   ├── Models/User.php                (Modelo Eloquent por defecto de Laravel)
│   └── Providers/
│       ├── AppServiceProvider.php
│       ├── RepositoryServiceProvider.php       (composition root de persistencia)
│       └── AuthenticationServiceProvider.php   (composition root de auth)
│
├── bootstrap/                        → Arranque de Laravel
│   ├── app.php
│   └── providers.php                 (registro de Service Providers)
│
├── config/                           → Configuración de Laravel
├── database/
│   ├── migrations/                   → Migraciones (fuente de verdad del esquema)
│   ├── factories/
│   └── seeders/
│
├── routes/
│   ├── web.php                       → Sólo healthcheck; no sirve HTML
│   └── console.php                   → Comandos artisan (por defecto)
│
├── src/                              → CÓDIGO PROPIO (namespace Lab\)
│   ├── Domain/
│   │   ├── Identity/
│   │   │   └── UserId.php            (Value Object)
│   │   └── Shared/
│   │       └── Clock.php             (Puerto: interfaz para obtener la hora)
│   │
│   ├── Application/
│   │   └── Authentication/
│   │       └── AuthenticatedPrincipal.php      (DTO común de identidad autenticada)
│   │
│   └── Infrastructure/
│       └── Clock/
│           └── SystemClock.php       (Implementación de Clock)
│
├── tests/
│   ├── TestCase.php
│   ├── Architecture/
│   │   ├── AutoloadSmokeTest.php     (verifica que Lab\ se carga)
│   │   └── CleanArchitectureTest.php (reglas de dependencia)
│   ├── Feature/                       (tests end-to-end HTTP)
│   └── Unit/                          (tests de clases aisladas)
│
├── composer.json
├── phpunit.xml
└── README.md
```

**Notas sobre archivos que aún no existen** (se crearán en fases posteriores):

- `routes/api.php` — Laravel 12 no lo crea por defecto. Se generará con
  `php artisan install:api` cuando exista el primer endpoint.
- `routes/modules/*.php` — patrón de un archivo de rutas por módulo.
- `src/*/Infrastructure/` — los adaptadores concretos por módulo.

---

## Cómo implementar un módulo nuevo

Ejemplo: módulo **`Identity`** (usuarios). Aplica el mismo patrón a cualquier
otro módulo (`Access`, `Authentication`, `Experiment`, `Metrics`, `Reporting`).

### 1. Domain — entidades, VOs, puertos

```
src/Domain/Identity/
├── User.php                     ← entidad (tiene identidad + reglas)
├── UserId.php                   ← Value Object (ya existe)
├── Email.php                    ← Value Object
├── UserRepository.php           ← puerto (interface)
└── Exception/
    └── UserNotFound.php         ← excepción de dominio
```

**Reglas:**

- Sólo PHP puro. Nada de `Illuminate\*`, nada de `App\*`.
- La entidad implementa las reglas de negocio, no el caso de uso.
- El repositorio es una **interface**, no una clase.

### 2. Application — casos de uso

```
src/Application/Identity/
├── RegisterUser.php
├── RegisterUserCommand.php       ← DTO de entrada
└── FindUserById.php
```

**Reglas:**

- Un caso de uso = una clase con un método `execute()`.
- Sólo depende de puertos del Domain (inyectados por constructor).
- No conoce HTTP, ni Eloquent, ni `Request`/`Response`.

### 3. Infrastructure — adaptadores concretos

```
src/Infrastructure/Persistence/Eloquent/
├── Models/
│   └── UserModel.php            ← Modelo Eloquent
├── Mappers/
│   └── UserMapper.php           ← Model ↔ Entity
└── Repositories/
    └── EloquentUserRepository.php   ← implementa UserRepository
```

**Reglas:**

- Aquí sí vive Eloquent.
- El mapper traduce entre `UserModel` (Eloquent) y `User` (Domain).
- El repositorio concreto implementa el puerto del Domain.

### 4. HTTP — Controllers, Requests, Resources

```
src/Infrastructure/Http/
├── Controllers/
│   └── UserController.php
├── Requests/
│   └── StoreUserRequest.php
└── ApiResources/
    └── UserResource.php
```

**Reglas:**

- El controller **no tiene lógica de negocio**. Sólo:
    1. Recibe el Request validado.
    2. Construye el Command (DTO de entrada).
    3. Invoca el caso de uso.
    4. Formatea la respuesta (Resource).
- Toda la lógica de autorización va en el caso de uso, no en el controller.

### 5. Rutas

Crea o edita `routes/modules/identity.php`:

```php
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('{id}', [UserController::class, 'show']);
});
```

Y en `routes/api.php` (cuando exista), carga todos los archivos de módulo:

```php
foreach (glob(__DIR__ . '/modules/*.php') as $moduleFile) {
    require $moduleFile;
}
```

### 6. Binding puerto → implementación

En `app/Providers/RepositoryServiceProvider.php`, registra el binding:

```php
public function register(): void
{
    $this->app->bind(
        \Lab\Domain\Identity\UserRepository::class,
        \Lab\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository::class,
    );
}
```

**Este archivo es el único lugar del proyecto donde se conectan puertos del
Domain con implementaciones concretas.** Si mañana cambias MySQL por otra
cosa, sólo tocas este archivo.

### 7. Migración

```bash
php artisan make:migration create_users_table
```

Sigue las reglas de **zero-downtime**:

- Columnas nuevas: nullable o con default.
- Nunca `RENAME COLUMN` directo (add-and-migrate-then-drop).
- `down()` siempre funcional.
- Índices compuestos si aplica scoping.

### 8. Tests

- `tests/Unit/Application/RegisterUserTest.php` → usa **fakes** de los puertos,
  sin Laravel, sin MySQL.
- `tests/Feature/UserEndpointTest.php` → end-to-end con `RefreshDatabase`.

### Resumen del flujo al agregar un módulo

| Capa           | ¿Carpeta nueva?                              | Qué añades                          |
| -------------- | -------------------------------------------- | ----------------------------------- |
| Domain         | Sí (`src/Domain/MiModulo/`)                  | Entidades, VOs, puertos             |
| Application    | Sí (`src/Application/MiModulo/`)             | Casos de uso                        |
| Infrastructure | No, se añaden archivos a carpetas existentes | Models, Mappers, Repos, Controllers |
| Rutas          | Sí (`routes/modules/mi-modulo.php`)          | Endpoints                           |
| Provider       | No, se añaden bindings                       | `bind(puerto, implementación)`      |

---

## Testing

### Comandos

```bash
# Todos los tests
vendor\bin\pest

# Equivalente vía artisan
php artisan test

# Sólo una suite
vendor\bin\pest --testsuite=Architecture
vendor\bin\pest --testsuite=Unit
vendor\bin\pest --testsuite=Feature

# Un archivo concreto
vendor\bin\pest tests/Architecture/CleanArchitectureTest.php

# Filtrar por nombre de test
vendor\bin\pest --filter="UserId"
```

En Linux/macOS sustituye `vendor\bin\pest` por `./vendor/bin/pest`.

### Tests actuales

| Archivo                                        | Propósito                                                                                                                                                                     |
| ---------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `tests/Architecture/AutoloadSmokeTest.php`     | Verifica que el autoload PSR-4 de `Lab\` funciona correctamente, y prueba el comportamiento de `UserId` (rechaza vacíos, compara por valor)                                   |
| `tests/Architecture/CleanArchitectureTest.php` | Hace cumplir las reglas de dependencia: `Domain` no importa Laravel, `Application` no importa `Infrastructure`, todo el código declara `strict_types`, las clases son `final` |
| `tests/Feature/ExampleTest.php`                | Test de ejemplo de Laravel (se eliminará cuando exista el primer endpoint real)                                                                                               |
| `tests/Unit/ExampleTest.php`                   | Test de ejemplo de Laravel (se eliminará cuando exista el primer test unitario real)                                                                                          |

### Los tests de arquitectura

Estos tests son la **garantía ejecutable** de que la Clean Architecture se
respeta. No son documentación decorativa: si alguien importa Laravel desde
`Domain`, el CI falla y el PR no se puede mergear.

Ejemplo del tipo de aserción:

```php
arch('Domain no depende de Laravel ni de App')
    ->expect('Lab\Domain')
    ->not->toUse(['Illuminate', 'App']);

arch('Application no depende de Infrastructure')
    ->expect('Lab\Application')
    ->not->toUse('Lab\Infrastructure');
```

Cuando agregues módulos nuevos, los tests existentes **ya cubren las reglas**
para los nuevos namespaces (`Lab\Domain\Reporting\*` cae automáticamente bajo
la regla `Lab\Domain`). No hay que tocar nada PERO SI EJECUTARLOS PARA TESTEAR QUE SE CUMPLA LA ARQUITECTURA.

---

## Instalación y ejecución

### Requisitos

- PHP 8.5+ con extensiones: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`,
  `xml`, `ctype`, `json`, `bcmath`
- Composer 2.x
- MySQL 8.x (o MariaDB 10.x)

### Setup

```bash
# 1. Clonar e instalar dependencias
git clone <repo-url> auth_back
cd auth_back
composer install

# 2. Configurar entorno
cp .env.example .env
php artisan key:generate

# 3. Editar .env con credenciales de MySQL
#    DB_CONNECTION=mysql
#    DB_DATABASE=autenticacion_lab
#    DB_USERNAME=...
#    DB_PASSWORD=...

# 4. Migrar
php artisan migrate

# 5. Verificar que todo funciona
vendor\bin\pest
```

### Servidor de desarrollo

```bash
php artisan serve
# API disponible en http://localhost:8000
```

---

## Roadmap por fases

El proyecto se construye incrementalmente. Cada fase se valida antes de pasar
a la siguiente.

| Fase | Contenido                                                                      | Estado        |
| ---- | ------------------------------------------------------------------------------ | ------------- |
| 1    | Diseño arquitectónico (Clean Architecture + 2 repos separados)                 | ✅ Completada |
| 2    | Scaffold Laravel + autoload `Lab\` + Service Providers + tests de arquitectura | ✅ Completada |
| 3    | Base de datos y entidades fundamentales (users, tickets neutros)               | ✅ Completada |
| 4    | RBAC propio + tickets ricos + CRUD de roles/permisos                           | ✅ Completada |
| 5    | (integrada en Fase 4: modelo de datos extendido)                               | ✅ Completada |
| 6    | API Key (X-API-Key, hash sha256, expiración, revocación)                       | ✅ Completada |
| 7    | Basic Authentication (RFC 7617, anti-timing)                                   | ✅ Completada |
| 8    | JWT (HS256, denylist por jti, rotación con reuso-detection)                    | ✅ Completada |
| 9    | Servidor OAuth 2.0 propio (grant ROPC, X-Auth-Mechanism)                       | ✅ Completada |
| 10   | Experimentos y métricas (mediana/p95, pérdidas, smoke)                         | ✅ Completada |
| 11   | Polish (OpenAPI, smoke, seeders demo, docs)                                    | ✅ Completada |

**Variables experimentales a medir en las fases 9 y 10:**

1. Comportamiento ante cambio de rol/permisos después de emitir la credencial.
2. Expiración de credenciales.
3. Revocación (cuando el mecanismo lo permita).
4. Número de round-trips para autenticarse.
5. Latencia de operaciones.
6. Tamaño de credenciales.
7. Diferencia entre autenticación y autorización.
8. Comportamiento de cada mecanismo ante el mismo escenario.

---

## Convenciones de código

### Nombres

- **Clases, métodos, variables, nombres de archivo:** inglés (`User`,
  `AuthenticateWithApiKey`, `$userId`).
- **Comentarios, PHPDoc, mensajes de error, README:** español.
- **Nombres de dominio (roles, permisos, etc.):** mayúsculas sostenidas para
  roles y permisos (`VIEWER`, `EDITOR`, `ADMIN`, `READ`, `WRITE`, `DELETE`).

### Estilo

- **PSR-12** para formato.
- **`declare(strict_types=1);`** en todo archivo de `src/`.
- **Clases `final` por defecto** salvo que haya razón para extender.
- **`readonly`** en Value Objects y DTOs.
- **Constructor property promotion** siempre que sea posible.
- **PHPDoc** en métodos de servicio, repositorio y lógica no trivial.

### Prohibiciones

- No `SELECT *` en producción.
- No queries dentro de loops (N+1).
- No aceptar identificadores de scoping desde el body (sólo de contexto validado).
- No `new SomeClass()` dentro de controllers/services (inyectar por constructor).
- No acceder a la BD desde `Application` ni desde `Domain`.

### Antes de hacer commit

```bash
vendor\bin\pest
```

Todo verde. Si algún test de arquitectura falla, es una violación de las reglas
y hay que corregirla antes de continuar.

---

## Experimentos end-to-end

### Secuencia experimental

```
VIEWER → ESCALADO → EDITOR → ESCALADO → ADMIN → REDUCCIÓN → EDITOR → REVOCACIÓN
```

Después de cada cambio se ejecutan las mismas operaciones sobre tickets y se
registran métricas (latencia total y de auth, mecanismo, tamaño de credencial,
status HTTP y capa de fallo).

### Smoke test (requiere `php artisan serve` activo)

```bash
php artisan lab:smoke-experiment api_key
php artisan lab:smoke-experiment basic
php artisan lab:smoke-experiment jwt
php artisan lab:smoke-experiment oauth
```

Cada corrida crea un experimento, ejecuta la secuencia y muestra la comparación
(mediana/p95/desviación + pérdidas; con pérdidas > 0 la comparación no vale).

### Interpretación de resultados

- **N ≥ 30 iteraciones** por combinación mecanismo×operación; baseline de red
  ~150-250 ms contra Supabase, así que importan **mediana y p95**, nunca el
  promedio solo.
- **`auth_ms`**: solo verificación (lookup/hash/cripto). **`latency_ms`**:
  request completo. Misma semántica en los 4 mecanismos (1 query por verify).
- **`failed_layer`**: `rbac` (el rol no autoriza) o `ticket_rule` (el estado
  del ticket rechaza). Segmenta los 403 sin ambigüedad.

### Los 4 mecanismos

| Mecanismo | Transporte                                                  | Emisión                                             |
| --------- | ----------------------------------------------------------- | --------------------------------------------------- |
| API Key   | `X-API-Key: <plano>`                                        | `POST /api/auth/api-key/issue` (plano una sola vez) |
| Basic     | `Authorization: Basic <base64(email:pass)>`                 | verifica contra users                               |
| JWT       | `Authorization: Bearer <token>`                             | `POST /api/auth/jwt/login` (email+password)         |
| OAuth     | `Authorization: Bearer <token>` + `X-Auth-Mechanism: oauth` | `POST /api/auth/oauth/token` (grant password)       |

- 401 siempre bare: `{"error": "unauthenticated"}`.
- 403 siempre con capa: `{"error": "forbidden", "layer": "rbac"|"ticket_rule"}`.
- Contrato completo en `docs/openapi.yaml`.

---

## Esquema de base de datos

UNA sola migración: `database/migrations/2026_10_01_000000_create_full_schema.php`
con las 17 tablas en orden de FKs (users, roles, permissions, pivotes,
tickets + tracking, api*keys, jwt_revocations, oauth*\*, experiments + métricas).
La historia previa está en `git log`. Cambios futuros van en migraciones nuevas.

## Reset de base de datos

```bash
php artisan migrate:fresh --seed   # canónico: borra todo y reconstruye
php artisan lab:reset              # atajo con confirmación previa
```

`migrate:fresh --seed` corre las 11 migraciones en orden y todos los seeders
(roles, permisos, usuarios demo, tickets demo, cliente OAuth, experimento
demo). El sistema queda completamente funcional en ~30 segundos.

**Las migraciones NO se consolidan**: son historia inmutable (AGENTS.md §4.5).
Borrarlas y reemplazarlas por un archivo único rompería la tabla `migrations`
de cualquier base ya migrada y a cualquiera que clone el repo con una BD viva.

---

## Referencias

- **Clean Architecture** — Robert C. Martin, _Clean Architecture: A Craftsman's
  Guide to Software Structure and Design_ (2017).
- **RFC 6749** — The OAuth 2.0 Authorization Framework.
- **RFC 7519** — JSON Web Token (JWT).
- **RFC 7617** — The 'Basic' HTTP Authentication Scheme.

---

## Licencia

Proyecto académico. Uso educativo.
