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
    'email' => env('SUPER_ADMIN_EMAIL', 'super-admin@santamarta.gov.co'),
    'name' => env('SUPER_ADMIN_NAME', 'Super Administrador'),

    // Sin valor por defecto: el sembrador decide qué hacer cuando falta y se
    // niega a sembrar la clave de desarrollo en producción.
    'password' => env('SUPER_ADMIN_PASSWORD'),
];
