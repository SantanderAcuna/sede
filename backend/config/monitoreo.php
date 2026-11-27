<?php

declare(strict_types=1);

/*
| Vigilancia interna de Redis (DISP-01, ADR-0009). Lo lee `php artisan monitoreo:redis`.
|
| `correo`: destinatario de los avisos. Vacío = solo registro (el comando igual termina con error).
| Con `MAIL_MAILER=log` el «correo» también queda en el registro: hace falta un SMTP real para que llegue.
|
| `umbral_sitio`: fracción de `maxmemory` que pueden ocupar las claves `sitio:*` antes de avisar.
| 10 % es decisión de José (2026-11-25) y una estimación sin datos de la Entidad.
*/
return [
    'correo' => (string) env('MONITOREO_NOTIFICACION_CORREO', ''),
    'umbral_sitio' => (float) env('MONITOREO_UMBRAL_SITIO', 0.10),
    'usuario_acl' => 'sitio',
];
