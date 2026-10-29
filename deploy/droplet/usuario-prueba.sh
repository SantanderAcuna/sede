#!/bin/bash
#
# Crea o borra el usuario de prueba de las verificaciones de la auditoría
# (Módulo 01: AC-05.5 y AC-12.5). SOLO para staging.
#
#   usuario-prueba.sh crear           sin roles ni permisos      → AC-05.5
#   usuario-prueba.sh crear archivos  con archivos.ver/.crear    → AC-12.5
#   usuario-prueba.sh borrar
#
# Se ejecuta en el servidor con una cuenta que pueda usar Docker (administración,
# NO la cuenta de despliegue: su llave sólo puede ejecutar `desplegar.sh`).
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

rojo()  { printf '\033[31m%s\033[0m\n' "$*" >&2; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }

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

[ -f "$BASE/.env" ] && [ -f "$BASE/compose.yaml" ] \
  || { rojo "Faltan $BASE/.env o $BASE/compose.yaml. ¿Es este el servidor de staging?"; exit 1; }

COMPOSE="docker compose -f $BASE/compose.yaml --env-file $BASE/.env"

# Contraseña de 24 caracteres alfanuméricos. Se pasa por variable de entorno al
# contenedor, no por la línea de comandos, para que no aparezca en `ps`.
CLAVE=""
if [ "$ACCION" = "crear" ]; then
  CLAVE="$(LC_ALL=C tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 24)"
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
    // `PermissionSeeder` NO siembra `archivos.*` (las rutas de archivos los exigen
    // con `can:`), así que se crean aquí si faltan. Sólo se conceden al usuario de
    // prueba; ningún rol los recibe.
    $usuario->givePermissionTo(
        Permission::findOrCreate('archivos.ver', 'web'),
        Permission::findOrCreate('archivos.crear', 'web'),
    );
}

echo "Usuario creado: $correo\n";
echo 'Roles: '.($usuario->getRoleNames()->implode(', ') ?: 'ninguno')."\n";
echo 'Permisos: '.($usuario->getAllPermissions()->pluck('name')->implode(', ') ?: 'ninguno')."\n";
PHP

if [ "$ACCION" = "crear" ]; then
  verde "Contraseña (se muestra solo ahora): $CLAVE"
fi
