<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Reglas de dependencia — Clean Architecture
|--------------------------------------------------------------------------
|
| Estos tests son la garantía ejecutable de la arquitectura.
| Si alguien importa Laravel desde Domain, o Infrastructure desde Application,
| el CI falla. La arquitectura deja de ser documentación y pasa a ser
| un contrato verificable.
|
| Convención de namespace:
|   Lab\Domain\*         → Entities        (no depende de nada externo)
|   Lab\Application\*    → Use Cases       (depende de Domain)
|   Lab\Infrastructure\* → Adapters        (depende de Domain, Application y Laravel)
|   App\*                → Laravel         (puede depender de todo)
|
*/

arch('Domain no depende de Laravel ni de App')
    ->expect('Lab\Domain')
    ->not->toUse(['Illuminate', 'App']);

arch('Domain no depende de Application')
    ->expect('Lab\Domain')
    ->not->toUse('Lab\Application');

arch('Domain no depende de Infrastructure')
    ->expect('Lab\Domain')
    ->not->toUse('Lab\Infrastructure');

arch('Application no depende de Laravel ni de App')
    ->expect('Lab\Application')
    ->not->toUse(['Illuminate', 'App']);

arch('Application no depende de Infrastructure')
    ->expect('Lab\Application')
    ->not->toUse('Lab\Infrastructure');

arch('Todo el código propio declara strict_types')
    ->expect('Lab')
    ->toUseStrictTypes();

arch('Las clases de Domain son finales')
    ->expect('Lab\Domain')
    ->classes()
    ->toBeFinal();

arch('Las clases de Application son finales')
    ->expect('Lab\Application')
    ->classes()
    ->toBeFinal();

arch('Las clases de Infrastructure son finales')
    ->expect('Lab\Infrastructure')
    ->classes()
    ->toBeFinal();