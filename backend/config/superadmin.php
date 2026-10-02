<?php

declare(strict_types=1);

/*
 * La cuenta de super-admin que siembra `SuperAdminSeeder`.
 *
 * Vive en `config` y no en `env()` dentro del sembrador por una razón concreta:
 * con `php artisan config:cache` —lo que hace el despliegue— `env()` deja de
 * leer el archivo y devuelve nulo fuera de los archivos de configuración. Un
 * sembrador que leyera `env()` directamente crearía el super-admin con la clave
 * equivocada el día que alguien cachee la configuración, y ese fallo no se nota
 * hasta que alguien intenta entrar.
 *
 * La clave viaja en texto plano a propósito: el cast `hashed` del modelo `User`
 * la cifra al guardarla. Guardarla ya cifrada aquí obligaría a recalcular el
 * hash a mano cada vez que se rote, que es como se acaba con hashes viejos.
 */
return [
    'email' => env('SUPER_ADMIN_EMAIL', 'jose.acuna@santamarta.gov.co'),
    'name' => env('SUPER_ADMIN_NAME', 'Jose Acuña'),

    /*
     * **Sin valor por defecto, y ahora sí.**
     *
     * Aquí hubo un literal —la clave real— y ese literal viajaba en un
     * repositorio **público**: cualquiera podía leer la contraseña del
     * super-admin de la Sede y entrar al panel. El comentario de arriba ya decía
     * «sin valor por defecto»; el código decía otra cosa.
     *
     * Sin `SUPER_ADMIN_PASSWORD`, el sembrador cae en su clave de desarrollo
     * declarada y **se niega a sembrar en producción**, que es el
     * comportamiento que se documentó desde el principio: fallar el sembrado es
     * preferible a dejar la puerta abierta en silencio.
     *
     * El valor se define donde no se publica: en `backend/.env` para desarrollo
     * —que no se versiona— y en el registro de secretos del despliegue para los
     * entornos publicados.
     */
    'password' => env('SUPER_ADMIN_PASSWORD'),
];
