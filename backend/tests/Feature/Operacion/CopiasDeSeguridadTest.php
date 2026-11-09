<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

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

    public function test_la_agenda_limpia_copia_y_vigila_en_ese_orden(): void
    {
        // `schedule:list` alinea las columnas de la expresión cron con espacios de
        // relleno («0  1 * * *»), así que los campos se separan con `\s+`.
        Artisan::call('schedule:list');
        $salida = Artisan::output();

        $this->assertMatchesRegularExpression('/^\s*0\s+1\s+\*\s+\*\s+\*\s+.*backup:clean/m', $salida, 'Falta backup:clean a la 01:00.');
        $this->assertMatchesRegularExpression('/^\s*30\s+1\s+\*\s+\*\s+\*\s+.*backup:run --only-db/m', $salida, 'Falta backup:run --only-db a la 01:30.');
        $this->assertMatchesRegularExpression('/^\s*0\s+6\s+\*\s+\*\s+\*\s+.*backup:monitor/m', $salida, 'Falta backup:monitor a las 06:00.');
    }
}
