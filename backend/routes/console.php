<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Retención del registro de consentimientos de cookies (T-26). Corre una vez al día
// siempre que el servidor ejecute `schedule:run` cada minuto.
//
// A las 02:15 de Bogotá (0D, D7). Con `->daily()` y la zona de la aplicación en UTC corría a las
// 19:00 de Bogotá, en plena tarde, borrando de una vez lo que pasó la retención mientras la sede
// tiene tráfico. Va DESPUÉS de limpiar (01:00) y de copiar (01:30, que puede tardar) para no
// competir con ellas, y antes del hash (03:00).
Schedule::command('consentimientos:depurar')->dailyAt('02:15')->timezone('America/Bogota')->withoutOverlapping();

// Copias de seguridad de la base (docs/copias-de-seguridad.md). Las ejecuta el servicio
// `scheduler`, que es el único proceso con la agenda y con el volumen de copias montado.
//
// El ORDEN importa y las horas no son casuales: primero se LIMPIA (01:00) y después se
// copia (01:30), así la limpieza nunca borra una copia recién hecha para cumplir un
// límite de espacio; y se VIGILA (06:00) cuando la de la noche ya debió terminar.
// America/Bogota no tiene horario de verano, así que no hay una hora que se repita o
// desaparezca.
//
// LA HORA SE FIJA EN CADA TAREA porque la zona de la aplicación es UTC
// (`config/app.php` tiene 'timezone' => 'UTC' fijo y NO lee APP_TIMEZONE). Sin esto la
// copia «de la 01:30» corría a las 01:30 UTC, es decir, a las 20:30 de Bogotá: se vio en
// staging porque `schedule:list` anunciaba «Next Due: en 8 horas» a las 12:12 locales.
// Cambiar la zona de toda la aplicación no es opción aquí: movería cómo se leen todas
// las marcas de tiempo ya guardadas.
//
// `withoutOverlapping` evita que una copia lenta se pise con la siguiente. La copia es
// COMPLETA (base y archivos subidos, T-12): sin `--only-db`, que dejaba los archivos fuera
// y hacía que `backup:monitor` diera por «sana» una copia parcial (el paquete no distingue
// copias parciales de completas).
Schedule::command('backup:clean')->dailyAt('01:00')->timezone('America/Bogota')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('01:30')->timezone('America/Bogota')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('06:00')->timezone('America/Bogota');

// Verificación de huellas (0D, D10c): compara el SHA-256 guardado de cada archivo con su contenido en disco y avisa
// (falla el comando) si alguno cambió o desapareció. Solo lee; no corrige nada. Desde D10b la huella es obligatoria,
// así que ya no hay nada que completar y `archivos:calcular-hash` salió de la agenda (el despliegue aún lo corre,
// sin efecto). 03:00 de Bogotá, entre la copia (01:30) y su vigilancia (06:00), para no competir con ella.
Schedule::command('archivos:verificar')->dailyAt('03:00')->timezone('America/Bogota')->withoutOverlapping();

// Imágenes de contenido sin uso (0D, D9): solo las de la colección «contenido» que ninguna relación usa y llevan
// más de 7 días sin tocarse. A las 03:30 de Bogotá, después de la verificación de huellas (03:00) y mucho antes de la vigilancia de
// la copia (06:00); la copia (01:30) ya terminó, así que no borra nada que ella esté leyendo.
Schedule::command('archivos:limpiar-sin-uso')->dailyAt('03:30')->timezone('America/Bogota')->withoutOverlapping();

// Vigilancia interna de Redis (DISP-01, ADR-0009): memoria de `sitio:*`, `evicted_keys` y rechazos ACL del usuario
// `sitio`. Cada 15 minutos; solo lee. Lo que la sonda de fuera (`sonda.yml`) no puede ver.
Schedule::command('monitoreo:redis')->everyFifteenMinutes()->withoutOverlapping();
