<?php

declare(strict_types=1);

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
|--------------------------------------------------------------------------
| Copias de seguridad (spatie/laravel-backup)
|--------------------------------------------------------------------------
|
| Este archivo no existía: el paquete estaba instalado, pero sin configuración
| propia ni nadie que lo agendara, así que `backup:run` usaba los valores por
| defecto del paquete (disco `local`, sin cifrado porque buscaba una variable que
| el proyecto no define) y no se ejecutaba nunca. Lo documentado vive en
| docs/copias-de-seguridad.md.
|
| Tres decisiones que no son obvias al leerlo:
|
|  1. LA FRASE DE CIFRADO es BACKUP_PASSPHRASE, la variable que ya declaran el
|     despliegue y la lista de secretos. El paquete espera BACKUP_ARCHIVE_PASSWORD;
|     en vez de añadir un segundo secreto (y que alguien rote uno y olvide el otro),
|     se lee aquí el que ya existe. Si queda vacía, el paquete NO cifra y no avisa:
|     por eso el despliegue la exige no vacía (secretos-requeridos.txt).
|
|  2. EL DESTINO es el disco que indique BACKUP_DISK. Hoy `copias`, un volumen
|     propio en el mismo servidor; cuando exista un depósito de objetos será `s3`.
|     El cambio es una línea de deploy/valores-<entorno>.env, no código.
|
|  3. SÓLO LA BASE por defecto. La agenda (routes/console.php) invoca
|     `backup:run --only-db`. Los archivos subidos se declaran abajo para que
|     incluirlos sea quitar esa bandera, pero esa decisión pertenece a T-12.
*/

$disco = (string) env('BACKUP_DISK', 'copias');

/*
| Los avisos por correo se encienden SÓLO si hay un destinatario. Con un correo
| vacío el paquete intentaría enviar a nadie y el fallo se tragaría el aviso del
| fallo. Y sólo se avisa de lo que importa (fallo y copia no sana): un correo diario
| de «todo bien» se aprende a ignorar, y entonces deja de servir el día que falla.
*/
$correo = (string) env('BACKUP_NOTIFICACION_CORREO', '');
$canales = $correo === '' ? [] : ['mail'];

/*
| El paquete valida `notifications.mail.to` AL ARRANCAR, haya o no canales de correo
| encendidos: con una cadena vacía lanza «is not a valid email address» y la aplicación
| entera deja de arrancar (`artisan` incluido). Se descubrió porque `composer install`
| falló en CI al ejecutar `package:discover`. Por eso, sin destinatario, se pone una
| dirección sintácticamente válida que no existe (el dominio `.invalid` está reservado
| por la RFC 2606 y nunca resuelve). No recibe nada: los canales quedan vacíos arriba.
*/
$destinatario = $correo === '' ? 'sin-destinatario@example.invalid' : $correo;

return [

    'backup' => [

        'name' => env('APP_NAME', 'sede'),

        'source' => [
            'files' => [
                // Sólo cuentan cuando se ejecuta SIN --only-db (ver arriba, punto 3).
                'include' => [
                    storage_path('app/private'),
                    storage_path('app/public'),
                ],

                'exclude' => [],

                'follow_links' => false,

                'ignore_unreadable_directories' => false,

                'relative_path' => base_path(),
            ],

            // Conexión explícita y no `DB_CONNECTION`: si alguien cambiara esa
            // variable en un entorno, la copia dejaría de apuntar a la base de la
            // sede sin que nada fallara.
            'databases' => [
                'pgsql',
            ],
        ],

        'database_dump_compressor' => null,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,

            'compression_level' => 9,

            'filename_prefix' => '',

            'disks' => [
                $disco,
            ],

            // Una copia a medias que «se da por buena» es peor que ninguna: si el
            // destino falla, la ejecución falla.
            'continue_on_failure' => false,
        ],

        // Dentro del volumen de almacenamiento, que es escribible. El contenedor
        // corre con el sistema de archivos de sólo lectura (compose.yaml).
        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_PASSPHRASE'),

        // AES-256 cuando el sistema lo soporta (lo documenta el paquete).
        'encryption' => 'default',

        // Tras crear el zip, comprobar que se abre y que tiene contenido. Cuesta
        // poco y es lo que separa «se generó un archivo» de «el archivo sirve».
        'verify_backup' => true,

        'tries' => 1,

        'retry_delay' => 0,
    ],

    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => $canales,
            UnhealthyBackupWasFoundNotification::class => $canales,
            CleanupHasFailedNotification::class => $canales,
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => $destinatario,

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'no-responder@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Sede Electrónica'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    'log_channel' => null,

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'sede'),
            'disks' => [$disco],
            'health_checks' => [
                // La copia más reciente no puede tener más de un día.
                MaximumAgeInDays::class => 1,
                // Techo del almacenamiento de copias, en MB. Es un valor de partida
                // del paquete, no un dato medido: hay que ajustarlo al tamaño real
                // de la base (docs/copias-de-seguridad.md).
                MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        // Retención de partida: los valores por defecto del paquete. El plazo que
        // corresponde a la Entidad (tabla de retención documental) sigue pendiente
        // y se fija aquí cuando exista.
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],

        'tries' => 1,

        'retry_delay' => 0,
    ],

];
