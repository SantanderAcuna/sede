<?php

declare(strict_types=1);

namespace App\Support\Cors;

/**
 * La lista de orígenes que `config/cors.php` deja pasar con credenciales.
 *
 * Vive en una clase y no en el archivo de configuración por una razón práctica:
 * `config/cors.php` se evalúa una vez al arrancar y lee `env()`, así que no hay
 * forma limpia de probar «en producción no hay `localhost`» sin reiniciar la
 * aplicación. Con la lógica aquí, la prueba pasa el entorno como argumento.
 *
 * Los orígenes de desarrollo (`localhost`, `127.0.0.1` y hasta `0.0.0.0`) solo se
 * añaden en `local` y `testing`. En producción quedaban fijos, y con
 * `supports_credentials` en `true` eso autoriza a una página servida desde la
 * máquina de cualquier visitante a leer respuestas con la sesión (H-15).
 */
final class OrigenesPermitidos
{
    /**
     * Orígenes de las herramientas de desarrollo: el panel (Vite) y el sitio (Nuxt).
     *
     * @var list<string>
     */
    private const DESARROLLO = [
        'http://localhost:5190',
        'http://127.0.0.1:5190',
        'http://localhost:3001',
        'http://127.0.0.1:3001',
        // El navegador puede resolver el sitio a `0.0.0.0` por la configuración de
        // proxy de la red local. Solo tiene sentido en la máquina de quien desarrolla.
        'http://0.0.0.0:3001',
    ];

    /**
     * @param  string  $entorno  `APP_ENV`.
     * @param  string|null  $lista  `SEDE_ORIGENES_PERMITIDOS`: orígenes separados por comas.
     * @param  string|null  $frontend  `FRONTEND_URL`: el origen del cliente, si es uno solo.
     * @return list<string>
     */
    public static function para(string $entorno, ?string $lista, ?string $frontend): array
    {
        $origenes = [];

        foreach (explode(',', (string) $lista) as $origen) {
            $origenes[] = $origen;
        }

        $origenes[] = (string) $frontend;

        if (in_array($entorno, ['local', 'testing'], true)) {
            array_push($origenes, ...self::DESARROLLO);
        }

        $limpios = [];
        foreach ($origenes as $origen) {
            $origen = rtrim(trim($origen), '/');
            // Un comodín con credenciales lo prohíbe la especificación y, si el
            // navegador lo aceptara, abriría la API a cualquier origen.
            if ($origen === '' || $origen === '*') {
                continue;
            }
            $limpios[$origen] = true;
        }

        return array_keys($limpios);
    }
}
