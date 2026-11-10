#!/bin/bash
#
# Crea o borra el usuario de prueba de las verificaciones de la auditoría
# (Módulo 01: AC-05.5 y AC-12.5). SOLO para staging.
#
#   usuario-prueba.sh crear           sin roles ni permisos      → AC-05.5
#   usuario-prueba.sh crear archivos  con archivos.ver/.crear    → AC-12.5
#   usuario-prueba.sh borrar
#
# AC-12.5 completo (subir, `curl -sI`, limpiar) lo hace `verificar-archivo-publico.sh`.
#
# Se ejecuta en el servidor con `sudo`, desde la cuenta de administración (NO la de
# despliegue: su llave sólo puede ejecutar `desplegar.sh`):
#
#   ssh -t ops@<servidor> 'sudo bash ~/usuario-prueba.sh crear archivos'
#
# La contraseña se genera al azar, se imprime UNA vez y no se guarda en ningún
# archivo ni en el historial del shell. Guárdala en el gestor de contraseñas
# mientras dure la prueba y ejecuta `borrar` al terminar.
#
# No crea código en la aplicación: arranca el framework con `php` y usa el modelo
# `User` tal cual, de modo que la contraseña pasa por el mismo cast `hashed` que en
# un alta real.
#
set -euo pipefail

# Color sólo si la salida es una terminal: `verificar-archivo-publico.sh` lee la
# contraseña de esta salida y los códigos de color se la estropeaban.
rojo()  { if [ -t 2 ]; then printf '\033[31m%s\033[0m\n' "$*" >&2; else printf '%s\n' "$*" >&2; fi; }
verde() { if [ -t 1 ]; then printf '\033[32m%s\033[0m\n' "$*"; else printf '%s\n' "$*"; fi; }

ENTORNO="staging"                 # fijo a propósito: no existe versión para producción
BASE="/opt/sede/$ENTORNO"
CORREO="prueba.auditoria@example.com"
ACCION="${1:-}"
CON_ARCHIVOS="${2:-}"

case "$ACCION" in
  crear|borrar) ;;
  *) rojo "Uso: $0 crear [archivos] | borrar"; exit 2 ;;
esac
if [ -n "$CON_ARCHIVOS" ] && [ "$CON_ARCHIVOS" != "archivos" ]; then
  rojo "El segundo argumento sólo puede ser «archivos»."; exit 2
fi

if ! { [ -f "$BASE/.env" ] && [ -f "$BASE/compose.yaml" ]; }; then
  if [ "$(id -u)" -ne 0 ]; then
    rojo "Con esta cuenta no veo $BASE/.env ni compose.yaml (en el servidor sólo root entra a esa carpeta). Ejecuta con sudo."
  else
    rojo "Faltan $BASE/.env o $BASE/compose.yaml. ¿Es este el servidor de staging?"
  fi
  exit 1
fi

COMPOSE="docker compose -f $BASE/compose.yaml --env-file $BASE/.env"

# Contraseña de 24 caracteres alfanuméricos. Se pasa por variable de entorno al
# contenedor, no por la línea de comandos, para que no aparezca en `ps`.
CLAVE=""
if [ "$ACCION" = "crear" ]; then
  # `head` cierra la tubería al leer sus 24 caracteres y `tr` muere por SIGPIPE
  # (estado 141). Con `pipefail` activo eso abortaba el guion en silencio, antes de
  # llegar a Docker; por eso `pipefail` se apaga sólo dentro de esta sustitución.
  CLAVE="$(set +o pipefail; LC_ALL=C tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 24)"
  [ "${#CLAVE}" -eq 24 ] || { rojo "No se pudo generar la contraseña."; exit 1; }
fi

$COMPOSE run --rm -T \
  -e "PRUEBA_ACCION=$ACCION" -e "PRUEBA_CORREO=$CORREO" \
  -e "PRUEBA_CLAVE=$CLAVE" -e "PRUEBA_ARCHIVOS=$CON_ARCHIVOS" \
  app php <<'PHP'
<?php
require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Permission;

// Laravel captura las excepciones en el arranque y las imprime SIN cambiar el código
// de salida de `php` (queda en 0), así que un fallo aquí parecería un éxito para el
// guion de shell. Se atrapan para salir con 1 y decir la causa por stderr.
try {
    $correo = getenv('PRUEBA_CORREO');
    $existente = User::where('email', $correo)->first();

    if (getenv('PRUEBA_ACCION') === 'borrar') {
        if ($existente) {
            $existente->tokens()->delete();
            $existente->delete();
            echo "Usuario de prueba borrado.\n";
        } else {
            echo "No había usuario de prueba.\n";
        }
        exit(0);
    }

    if ($existente) {
        fwrite(STDERR, "Ya existe $correo. Ejecuta primero «borrar».\n");
        exit(1);
    }

    $usuario = User::create([
        'name' => 'Prueba de auditoría',
        'email' => $correo,
        'password' => getenv('PRUEBA_CLAVE'),
        'estado' => 'activo',
        'mfa_habilitado' => false,
    ]);

    if (getenv('PRUEBA_ARCHIVOS') === 'archivos') {
        // `PermissionSeeder` siembra `archivos.*` (desde #337); `findOrCreate` sólo cubre
        // un servidor al que aún no se le haya corrido el seeder. Los permisos se conceden
        // directamente al usuario de prueba: ningún rol del panel los recibe.
        $usuario->givePermissionTo(
            Permission::findOrCreate('archivos.ver', 'web'),
            Permission::findOrCreate('archivos.crear', 'web'),
        );
    }

    echo "Usuario creado: $correo\n";
    echo 'Roles: '.($usuario->getRoleNames()->implode(', ') ?: 'ninguno')."\n";
    echo 'Permisos: '.($usuario->getAllPermissions()->pluck('name')->implode(', ') ?: 'ninguno')."\n";
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage()."\n");
    exit(1);
}
PHP

if [ "$ACCION" = "crear" ]; then
  verde "Contraseña (se muestra solo ahora): $CLAVE"
fi
