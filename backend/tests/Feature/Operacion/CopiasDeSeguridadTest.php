<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Las copias de seguridad de la base (docs/copias-de-seguridad.md).
 *
 * Antes de esta configuración el paquete estaba instalado y NADA fallaba: no existía
 * `config/backup.php`, no había agenda, el destino era el disco local por defecto y la
 * variable de cifrado que el paquete busca no es la que define el proyecto. Una copia
 * que no se hace no produce ningún error, y por eso hace falta una prueba que lo
 * convierta en un fallo ruidoso.
 *
 * Las comprobaciones de configuración leen la configuración en un PROCESO APARTE con
 * las variables de entorno que se quieren probar. Cambiarlas dentro de la propia prueba
 * no es fiable: `env()` lee de un repositorio inmutable que ya cargó el entorno de las
 * pruebas, y una variable definida «fuera» del archivo `.env` no se puede sobrescribir
 * desde dentro.
 *
 * Lo que estas pruebas NO demuestran, y se verifica a mano en el servidor: que
 * `pg_dump` exista en la imagen y coincida con el servidor, que el volumen `/copias`
 * sea escribible por el usuario sin privilegios y que una copia se pueda restaurar.
 */
final class CopiasDeSeguridadTest extends TestCase
{
    /**
     * Lee una clave de configuración arrancando la aplicación en otro proceso con
     * `$entorno` añadido al entorno heredado. Una variable con valor `null` se QUITA
     * del entorno heredado, para probar el caso «no definida».
     *
     * @param  array<string, string|null>  $entorno
     */
    private function configuracion(string $clave, array $entorno = []): mixed
    {
        $guion = sprintf(
            'require %s; $app = require %s; '
            .'$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); '
            .'echo json_encode(config(%s));',
            var_export(base_path('vendor/autoload.php'), true),
            var_export(base_path('bootstrap/app.php'), true),
            var_export($clave, true),
        );

        $proceso = new Process([PHP_BINARY, '-r', $guion], base_path(), $this->entornoHeredado($entorno));
        $proceso->mustRun();

        return json_decode($proceso->getOutput(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, string|null>  $cambios
     * @return array<string, string|false>
     */
    private function entornoHeredado(array $cambios): array
    {
        // `false` le pide a Symfony Process que QUITE la variable del entorno del hijo.
        $entorno = [];
        foreach ($cambios as $nombre => $valor) {
            $entorno[$nombre] = $valor ?? false;
        }

        return $entorno;
    }

    public function test_por_defecto_la_copia_va_al_disco_copias_y_la_monitorizacion_mira_el_mismo(): void
    {
        $this->assertSame(['copias'], $this->configuracion('backup.backup.destination.disks', ['BACKUP_DISK' => null]));
        $this->assertSame(['copias'], $this->configuracion('backup.monitor_backups.0.disks', ['BACKUP_DISK' => null]));
    }

    public function test_cambiar_el_destino_es_una_sola_variable_y_mueve_copia_y_vigilancia_juntas(): void
    {
        // Si la copia fuera a un sitio y la vigilancia mirara otro, el día que el destino
        // cambie la vigilancia daría «sana» o «caducada» sobre el sitio equivocado.
        $this->assertSame(['s3'], $this->configuracion('backup.backup.destination.disks', ['BACKUP_DISK' => 's3']));
        $this->assertSame(['s3'], $this->configuracion('backup.monitor_backups.0.disks', ['BACKUP_DISK' => 's3']));
    }

    public function test_el_disco_copias_es_privado_y_falla_en_voz_alta(): void
    {
        $disco = config('filesystems.disks.copias');

        $this->assertIsArray($disco);
        $this->assertSame('local', $disco['driver']);
        $this->assertFalse($disco['serve'], 'Las copias no deben poder servirse por HTTP.');
        $this->assertTrue($disco['throw'], 'Un fallo al escribir la copia debe ser una excepción, no un `false` silencioso.');
        $this->assertArrayNotHasKey('url', $disco);
    }

    public function test_la_copia_se_cifra_con_la_frase_que_ya_define_el_despliegue(): void
    {
        $this->assertSame(
            'frase-de-prueba',
            $this->configuracion('backup.backup.password', ['BACKUP_PASSPHRASE' => 'frase-de-prueba']),
            'La frase debe salir de BACKUP_PASSPHRASE (la que declara el despliegue), no de BACKUP_ARCHIVE_PASSWORD.',
        );
        $this->assertSame('default', $this->configuracion('backup.backup.encryption'));
    }

    public function test_la_variable_que_el_paquete_busca_por_defecto_no_cifra_nada(): void
    {
        // Documenta POR QUÉ no se usa BACKUP_ARCHIVE_PASSWORD: dejarla como única fuente
        // dejaba copias sin cifrar mientras el despliegue creía haber puesto una frase.
        $frase = $this->configuracion('backup.backup.password', [
            'BACKUP_PASSPHRASE' => null,
            'BACKUP_ARCHIVE_PASSWORD' => 'otra-frase',
        ]);

        $this->assertTrue($frase === null || $frase === '');
    }

    public function test_solo_se_copia_la_base_de_la_sede_y_se_verifica_el_archivo(): void
    {
        $this->assertSame(['pgsql'], config('backup.backup.source.databases'));
        $this->assertTrue(config('backup.backup.verify_backup'));
        $this->assertFalse(config('backup.backup.destination.continue_on_failure'));
    }

    public function test_sin_destinatario_no_se_intenta_enviar_correo(): void
    {
        $avisos = $this->configuracion('backup.notifications.notifications', ['BACKUP_NOTIFICACION_CORREO' => '']);

        foreach ($avisos as $clase => $canales) {
            $this->assertSame([], $canales, "{$clase} no debe usar ningún canal sin destinatario.");
        }
    }

    public function test_sin_destinatario_la_aplicacion_arranca_igualmente(): void
    {
        // El paquete valida `mail.to` al arrancar aunque no haya canales de correo: con
        // una cadena vacía la aplicación entera dejaba de arrancar y `composer install`
        // fallaba en `package:discover`. Aquí `configuracion()` arranca el núcleo en otro
        // proceso (`mustRun`): si el arranque lanzara, la prueba fallaría.
        $para = $this->configuracion('backup.notifications.mail.to', ['BACKUP_NOTIFICACION_CORREO' => '']);

        $this->assertIsString($para);
        $this->assertNotFalse(filter_var($para, FILTER_VALIDATE_EMAIL), 'La dirección de relleno debe ser sintácticamente válida.');
        $this->assertStringEndsWith('.invalid', $para, 'El relleno debe estar en un dominio reservado que nunca resuelve.');
    }

    public function test_con_destinatario_solo_avisa_de_fallos_y_de_copia_no_sana(): void
    {
        $entorno = ['BACKUP_NOTIFICACION_CORREO' => 'ops@example.test'];
        $avisos = $this->configuracion('backup.notifications.notifications', $entorno);

        $this->assertSame('ops@example.test', $this->configuracion('backup.notifications.mail.to', $entorno));

        $conCorreo = array_filter($avisos, static fn (array $canales): bool => $canales !== []);
        $this->assertEqualsCanonicalizing(
            ['BackupHasFailedNotification', 'UnhealthyBackupWasFoundNotification', 'CleanupHasFailedNotification'],
            array_map(static fn (string $clase): string => class_basename($clase), array_keys($conCorreo)),
            'Sólo deben avisar el fallo de copia, la copia no sana y el fallo de limpieza: un aviso diario de «todo bien» se aprende a ignorar.',
        );
        foreach ($conCorreo as $canales) {
            $this->assertSame(['mail'], $canales);
        }
    }

    /**
     * Las tareas de copia de la agenda, por orden de definición.
     *
     * @return Collection<int, Event>
     */
    private function tareasDeCopia(): Collection
    {
        // `schedule:list` fuerza el arranque de las rutas de consola, donde está la agenda.
        Artisan::call('schedule:list');

        return collect(app(Schedule::class)->events())
            ->filter(static fn ($evento): bool => str_contains((string) $evento->command, 'backup:'))
            ->values();
    }

    public function test_la_agenda_limpia_copia_y_vigila_en_ese_orden_y_a_esas_horas(): void
    {
        // Se leen la expresión y la zona de cada tarea, NO la salida de `schedule:list`: esa
        // salida convierte la hora a la zona de la aplicación (UTC), de modo que «01:30 de
        // Bogotá» se imprime como «06:30» y una comprobación sobre el texto confunde las dos.
        $horas = $this->tareasDeCopia()->mapWithKeys(
            static fn ($evento): array => [(string) $evento->command => $evento->expression],
        );

        $comando = static fn (string $final): string => (string) $horas->keys()->first(
            static fn (string $c): bool => str_ends_with($c, $final),
        );

        $this->assertSame('0 1 * * *', $horas[$comando('backup:clean')], 'backup:clean debe correr a la 01:00.');
        $this->assertSame('30 1 * * *', $horas[$comando('backup:run')], 'backup:run debe correr a la 01:30.');
        $this->assertSame('0 6 * * *', $horas[$comando('backup:monitor')], 'backup:monitor debe correr a las 06:00.');
    }

    public function test_ninguna_tarea_de_copia_deja_fuera_los_archivos_subidos(): void
    {
        // `--only-db` hace una copia parcial y `backup:monitor` no la distingue de una
        // completa (lo advierte la documentación del paquete): la agenda daría «sana» una
        // copia sin los archivos. T-12: la copia es completa.
        foreach ($this->tareasDeCopia() as $evento) {
            $this->assertStringNotContainsString('--only-db', (string) $evento->command, 'La agenda no debe usar --only-db.');
        }
    }

    public function test_la_copia_incluye_los_archivos_subidos_y_los_cifra(): void
    {
        // Prueba de punta a punta de la mitad «archivos» (`--only-files` no necesita
        // `pg_dump`, que sólo existe en la imagen): un archivo en el disco `local` termina
        // dentro del zip cifrado, y sólo se abre con la frase. La mitad «base» la cubre la
        // verificación del servidor (docs/copias-de-seguridad.md §7).
        $marca = 'prueba-copias-'.bin2hex(random_bytes(4));
        $carpeta = storage_path('app/private/'.$marca);
        $destino = sys_get_temp_dir().'/'.$marca;
        $extraido = $destino.'-extraido';

        mkdir($carpeta, 0775, true);
        file_put_contents($carpeta.'/documento.pdf', 'contenido-de-prueba');

        config([
            'filesystems.disks.copias_prueba' => ['driver' => 'local', 'root' => $destino, 'throw' => true],
            'backup.backup.destination.disks' => ['copias_prueba'],
            'backup.backup.password' => 'frase-de-prueba',
            'backup.backup.encryption' => 'default',
        ]);

        try {
            $codigo = Artisan::call('backup:run', ['--only-files' => true, '--disable-notifications' => true]);
            $this->assertSame(0, $codigo, Artisan::output());

            $zips = glob($destino.'/*/*.zip') ?: [];
            $this->assertCount(1, $zips, 'Debe haberse escrito una copia en el disco de destino.');

            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($zips[0]) === true);
            $zip->setPassword('frase-de-prueba');
            $this->assertTrue($zip->extractTo($extraido), 'Con la frase correcta el zip debe abrirse.');

            $this->assertSame(
                'contenido-de-prueba',
                file_get_contents($extraido.'/storage/app/private/'.$marca.'/documento.pdf'),
                'El archivo subido debe estar dentro de la copia, con su contenido.',
            );

            $otro = new \ZipArchive;
            $otro->open($zips[0]);
            $otro->setPassword('frase-equivocada');
            $this->assertFalse(@$otro->extractTo($extraido.'-mal'), 'Con otra frase el zip no debe abrirse.');
        } finally {
            (new Filesystem)->deleteDirectories($carpeta);
            (new Filesystem)->deleteDirectory($carpeta);
            (new Filesystem)->deleteDirectory($destino);
            (new Filesystem)->deleteDirectory($extraido);
            (new Filesystem)->deleteDirectory($extraido.'-mal');
        }
    }

    public function test_la_agenda_de_copias_corre_en_hora_de_bogota_aunque_la_aplicacion_use_utc(): void
    {
        // La zona de la aplicación es UTC (config/app.php la fija y no lee APP_TIMEZONE).
        // Sin fijar la zona en cada tarea, «01:30» era 01:30 UTC = 20:30 en Bogotá.
        $copias = $this->tareasDeCopia();

        $this->assertCount(3, $copias, 'Deben existir backup:clean, backup:run y backup:monitor.');
        foreach ($copias as $evento) {
            $this->assertSame('America/Bogota', $evento->timezone, "{$evento->command} debe fijar su zona horaria.");
        }
    }

    public function test_la_agenda_no_deja_que_una_copia_lenta_se_pise_con_la_siguiente(): void
    {
        $conBloqueo = $this->tareasDeCopia()->filter(
            static fn ($evento): bool => str_contains((string) $evento->command, 'backup:run')
                || str_contains((string) $evento->command, 'backup:clean'),
        );

        $this->assertCount(2, $conBloqueo);
        foreach ($conBloqueo as $evento) {
            $this->assertTrue($evento->withoutOverlapping, "{$evento->command} debe usar withoutOverlapping.");
        }
    }
}
