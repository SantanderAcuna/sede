<?php

declare(strict_types=1);

namespace Tests\Concerns;

use RuntimeException;

/** Para las pruebas de migraciones anteriores a D10a (ver `MediatecaUsoMigracionTest`). */
trait RevierteUsoDeArchivos
{
    /**
     * Deshace D10a (`uso`): las pruebas de migraciones anteriores siembran filas «de antes», con dueño
     * polimórfico y `collection`, y necesitan el esquema de entonces.
     */
    private function volverAAntesDeUso(): void
    {
        $rutas = glob(database_path('migrations/*_mediateca_uso.php')) ?: [];
        if (count($rutas) !== 1) {
            throw new RuntimeException('Falta la migración *_mediateca_uso.php');
        }

        $migracion = require $rutas[0];
        $migracion->down();
    }
}
