<?php

declare(strict_types=1);

namespace Tests\Concerns;

use RuntimeException;

/**
 * Para las pruebas de migraciones anteriores a D10 (ver `MediatecaUsoMigracionTest` y
 * `MediatecaContraerMigracionTest`): siembran filas «de antes», con dueño polimórfico y `collection`,
 * y necesitan el esquema de entonces.
 */
trait RevierteUsoDeArchivos
{
    /** Deshace D10b y después D10a, en ese orden: queda la forma de D9. */
    private function volverAAntesDeUso(): void
    {
        $this->volverAAntesDeContraer();
        $this->ejecutarMigracionDeMediateca('*_mediateca_uso.php', 'down');
    }

    /** Deshace solo D10b: queda la forma de D10a (con `uso` y el disparador). */
    private function volverAAntesDeContraer(): void
    {
        $this->ejecutarMigracionDeMediateca('*_mediateca_contraer.php', 'down');
    }

    private function ejecutarMigracionDeMediateca(string $patron, string $metodo): void
    {
        $rutas = glob(database_path('migrations/'.$patron)) ?: [];
        if (count($rutas) !== 1) {
            throw new RuntimeException("Falta la migración {$patron}");
        }

        $migracion = require $rutas[0];
        $migracion->{$metodo}();
    }
}
