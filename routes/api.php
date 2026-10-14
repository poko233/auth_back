<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Índice de rutas API del laboratorio
|--------------------------------------------------------------------------
|
| Cada módulo expone su archivo en routes/modules/. Sin autenticación
| todavía (Fase 6+): endpoints accesibles directamente.
|
*/

foreach (glob(__DIR__.'/modules/*.php') ?: [] as $moduleFile) {
    require $moduleFile;
}
