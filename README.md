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
- [Roadma
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
- `spatie/laravel-permission` → se implementa RBAp por fases](#roadmap-por-fases)C propio.
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
| 3    | Base de datos y entidades fundamentales (users, roles, permissions, resources) | ⏳ Pendiente  |
| 4    | RBAC propio (asignación, revocación, verificación)                             | ⏳ Pendiente  |
| 5    | API Key (primer mecanismo de autenticación)                                    | ⏳ Pendiente  |
| 6    | Basic Authentication                                                           | ⏳ Pendiente  |
| 7    | JWT (con expiración controlada por `Clock`)                                    | ⏳ Pendiente  |
| 8    | Servidor OAuth 2.0 propio (flujo a definir según variables experimentales)     | ⏳ Pendiente  |
| 9    | Experimentos y métricas                                                        | ⏳ Pendiente  |
| 10   | Frontend React + Vite (repo separado)                                          | ⏳ Pendiente  |

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

## Referencias

- **Clean Architecture** — Robert C. Martin, _Clean Architecture: A Craftsman's
  Guide to Software Structure and Design_ (2017).
- **RFC 6749** — The OAuth 2.0 Authorization Framework.
- **RFC 7519** — JSON Web Token (JWT).
- **RFC 7617** — The 'Basic' HTTP Authentication Scheme.

---

## Licencia

Proyecto académico. Uso educativo.
