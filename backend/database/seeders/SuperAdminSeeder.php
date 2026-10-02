<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * El super-admin del sistema.
 *
 * Es la única cuenta que no depende de permisos para poder concederlos: el
 * `Gate::before` de `AppServiceProvider` la deja pasar siempre, así que tiene
 * que existir antes de que haya ningún otro rol, permiso o usuario. Sembrarla
 * aquí —y no en un comando aparte— hace que una base recién creada ya pueda
 * administrarse.
 *
 * El sembrador es idempotente: `updateOrCreate` para la cuenta, `findOrCreate`
 * para el rol y `syncRoles` para la relación. Volver a ejecutarlo deja la misma
 * fila y la misma contraseña, que es lo que permite repetirlo en cada despliegue
 * sin duplicar ni pisar la corrección de una persona.
 */
final class SuperAdminSeeder extends Seeder
{
    /**
     * La clave que se usa cuando `SUPER_ADMIN_PASSWORD` no está definida.
     *
     * Es una clave de desarrollo declarada —no un secreto— y el sembrador se
     * niega a usarla en producción: el repositorio es público y una credencial
     * literal aquí sería una credencial filtrada desde el primer commit.
     */
    private const CLAVE_DE_DESARROLLO = 'Sede.SuperAdmin.2026';

    public function run(): void
    {
        $clave = config('superadmin.password');
        $clave = is_string($clave) && $clave !== '' ? $clave : self::CLAVE_DE_DESARROLLO;

        // Sin esta guarda, desplegar sin definir la variable dejaría la cuenta de
        // máximo privilegio con una contraseña publicada en el repositorio. Fallar
        // el sembrado es preferible a dejar la puerta abierta en silencio.
        if ($clave === self::CLAVE_DE_DESARROLLO && app()->isProduction()) {
            throw new RuntimeException(
                'Falta SUPER_ADMIN_PASSWORD: el sembrador no crea el super-admin en producción con la clave de desarrollo.'
            );
        }

        // El guard `web` es el que usa el modelo `User`: declararlo explícito evita
        // que un cambio en los guards por defecto cree un rol que nadie puede
        // asignar.
        $rol = Role::findOrCreate('super-admin', 'web');

        $usuario = User::updateOrCreate(
            ['email' => (string) config('superadmin.email')],
            [
                'name' => (string) config('superadmin.name'),
                // Texto plano a propósito: el cast `hashed` de `User` lo cifra.
                'password' => $clave,
            ],
        );

        // `syncRoles` y no `assignRole`: repetir la siembra no debe acumular filas
        // en el pivote ni conservar un rol que ya no se declara aquí.
        $usuario->syncRoles([$rol]);
    }
}
