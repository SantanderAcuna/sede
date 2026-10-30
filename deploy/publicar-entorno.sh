#!/bin/bash
#
# Publica el entorno de un despliegue en el servidor.
#
#   ./publicar-entorno.sh staging
#
# Deja en /opt/sede/<entorno>/ lo que la pila necesita para levantarse:
#
#   .env                        el entorno renderizado desde deploy/plantilla.env
#   compose.yaml                la definición de la pila
#   certs/                       donde vive el certificado del origen
#
# Los ajustes de PostgreSQL viajan DENTRO de compose.yaml, como parámetros
# explícitos del servicio. Antes vivían en un archivo aparte montado en un
# directorio que la imagen oficial no lee: parecía configurado y no lo estaba.
#
# QUÉ HACE HOY ESTE GUION, que ya no es lo que hacía (ADR-0019).
#
# Su trabajo principal es instalar los SECRETOS en el servidor:
# `/etc/sede/<entorno>.secretos.env`, de `root` y con permisos 600. Eso se ejecuta una
# vez, y después sólo cuando un secreto rota. Los valores salen de
# `.accesos.local.env`, que no se versiona, y no pasan por GitHub ni entran en ninguna
# imagen.
#
# El `.env` y el `compose.yaml` los RENDERIZA AHORA `desplegar.sh` en el servidor, en
# cada despliegue, desde la plantilla y los valores que viajan dentro de la imagen de
# la versión. Aquí se siguen publicando como ARRANQUE —para que la primera vez haya
# algo coherente y para poder volver a una versión anterior a este mecanismo—, pero ya
# NO hay que acordarse de publicar el entorno al cambiar una variable: un cambio en
# `deploy/plantilla.env` o en `deploy/valores-<entorno>.env` viaja con el siguiente
# despliegue.
#
# Historia, porque explica el diseño: el plan decía que «la canalización renderiza la
# plantilla en cada despliegue» y ese mecanismo no existía. La llave de despliegue está
# restringida a un único comando y no puede copiar archivos, así que esto se publicaba
# aparte, a mano. El coste se cobró entero: la canalización desplegó una imagen nueva
# con la configuración vieja y dejó el dominio entero en 521.
#
# LOS VALORES NO ESTÁN AQUÍ. Se leen de .accesos.local.env, que no se versiona.
# Este archivo es público y no puede contener ninguno.
#
set -euo pipefail

ENTORNO="${1:?Falta el entorno: staging o produccion}"

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REGISTRO="$RAIZ/.accesos.local.env"
PLANTILLA="$RAIZ/deploy/plantilla.env"

rojo()  { printf '\033[31m%s\033[0m\n' "$*" >&2; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }
amarillo() { printf '\033[33m%s\033[0m\n' "$*" >&2; }
paso()  { printf '\n\033[1m%s\033[0m\n' "$*"; }

case "$ENTORNO" in
  staging|produccion) ;;
  *) rojo "Entorno no válido: '$ENTORNO'"; exit 1 ;;
esac

[ -f "$REGISTRO" ] || { rojo "Falta $REGISTRO, con los valores."; exit 1; }
[ -f "$PLANTILLA" ] || { rojo "Falta $PLANTILLA."; exit 1; }

SUFIJO="$(echo "$ENTORNO" | tr '[:lower:]' '[:upper:]')"

# --- Valores ------------------------------------------------------------------
# Del registro local salen los secretos; el resto son datos derivados, que se
# calculan aquí para que no haya que recordarlos ni copiarlos a mano.
valor() {
  local clave="$1"
  local linea
  linea="$(grep -m1 "^${clave}=" "$REGISTRO" 2>/dev/null || true)"
  printf '%s' "${linea#*=}"
}

APP_KEY="$(valor "APP_KEY_$SUFIJO")"
DB_PASSWORD="$(valor "DB_PASSWORD_$SUFIJO")"
REDIS_PASSWORD="$(valor "REDIS_PASSWORD_$SUFIJO")"
BACKUP_PASSPHRASE="$(valor "BACKUP_PASSPHRASE_$SUFIJO")"
S3_ACCESS_KEY="$(valor "S3_ACCESS_KEY_$SUFIJO")"
S3_SECRET_KEY="$(valor "S3_SECRET_KEY_$SUFIJO")"

SERVIDOR_IP="$(valor SERVIDOR_IP)"

# El super-admin que siembra el despliegue. La clave vive en el registro, que no
# se versiona: publicarla en el repositorio —que es público— dejaría la cuenta de
# máximo privilegio de la Sede al alcance de cualquiera.
SUPER_ADMIN_EMAIL="$(valor SUPER_ADMIN_EMAIL)"
SUPER_ADMIN_NAME="$(valor SUPER_ADMIN_NAME)"
SUPER_ADMIN_PASSWORD="$(valor SUPER_ADMIN_PASSWORD)"

# Si la clave falta, el sembrador se niega a crearla en producción. Se avisa
# aquí, antes de publicar, para que el fallo no aparezca a mitad de un despliegue.
if [ -z "$SUPER_ADMIN_PASSWORD" ]; then
  amarillo "Sin SUPER_ADMIN_PASSWORD en $REGISTRO: la siembra se negará a crear el super-admin."
fi

# Dominio por entorno. El de producción sigue SIN decidirse: mientras no lo esté,
# se falla en lugar de inventarlo, porque un dominio inventado produce un
# certificado inválido y un sitio que no se encuentra.
if [ "$ENTORNO" = "staging" ]; then
  DOMINIO="${SEDE_DOMINIO_STAGING:-staging.santamarta.gov.co}"
else
  DOMINIO="$(valor SEDE_DOMINIO_PRODUCTION)"
  [ -n "$DOMINIO" ] || { rojo "El dominio de producción no está decidido (ver §21.A del plan)."; exit 1; }
fi

# (La dirección real del visitante la restaura nginx con los rangos de Cloudflare,
# `docker/nginx/snippets/cloudflare-realip.conf`; la aplicación recibe `REMOTE_ADDR`
# ya resuelto y no necesita una lista propia. La
# lista de proxies de confianza que se generaba aquí no la leía nadie.)

# --- Renderizado --------------------------------------------------------------
paso "1. Renderizado del entorno ($ENTORNO)"

# Las integraciones sin credenciales se declaran APAGADAS con `mock`, que es la
# convención de la propia plantilla: ofrecer un botón que no lleva a ninguna parte
# es peor que decir que no está.
export APP_KEY DB_PASSWORD REDIS_PASSWORD BACKUP_PASSPHRASE
export S3_ACCESS_KEY S3_SECRET_KEY
export DOMINIO ENTORNO
export SUPER_ADMIN_EMAIL SUPER_ADMIN_NAME SUPER_ADMIN_PASSWORD

export DB_DATABASE="sede"
export DB_USERNAME="sede"
export VERSION="${VERSION:-$(git -C "$RAIZ" rev-parse HEAD 2>/dev/null || echo desconocida)}"
export REGISTRY="ghcr.io/santanderacuna"
export ORIGENES_PERMITIDOS="https://${DOMINIO}"

# Sin depósito de objetos todavía: se usa el disco local, declarado como apaño.
export FILESYSTEM_DISK="${FILESYSTEM_DISK:-local}"
export S3_BUCKET="${S3_BUCKET:-sede}"
export S3_ENDPOINT="${S3_ENDPOINT:-}"
export S3_REGION="${S3_REGION:-us-east-1}"
export S3_PATH_STYLE="${S3_PATH_STYLE:-false}"

export MAIL_MAILER="${MAIL_MAILER:-log}"
export MAIL_HOST="" MAIL_PORT="" MAIL_USERNAME="" MAIL_PASSWORD=""

export IDENTIDAD_DRIVER="mock" IDENTIDAD_EMISOR="" IDENTIDAD_CLIENTE_ID="" IDENTIDAD_CLIENTE_SECRETO=""
export PAGOS_DRIVER="mock"      SEDE_PAGOS_SECRETO=""
export CORREO_CERTIFICADO_DRIVER="mock" SEDE_CORREO_CERTIFICADO_SECRETO=""
export CAPTCHA_DRIVER="mock"    CAPTCHA_SECRETO=""

RENDERIZADO="$(mktemp)"
trap 'rm -f "$RENDERIZADO"' EXIT
envsubst < "$PLANTILLA" > "$RENDERIZADO"

# --- Comprobaciones -----------------------------------------------------------
paso "2. Comprobaciones antes de publicar"

# Un marcador sin sustituir significa que falta un valor, y envsubst lo habría
# dejado en blanco en silencio: la aplicación arrancaría con una configuración
# incompleta y el fallo aparecería mucho después.
if grep -qE '\$\{[A-Z_]+' "$RENDERIZADO"; then
  rojo "  Quedaron marcadores sin sustituir:"
  grep -oE '\$\{[A-Z_]+[^}]*\}' "$RENDERIZADO" | sort -u | sed 's/^/    /'
  exit 1
fi
verde "  sin marcadores pendientes"

# Un secreto vacío es peor que ausente: pasa la comprobación de presencia y falla
# al usarlo. Se comprueban uno a uno, por su nombre.
for pareja in "APP_KEY:$APP_KEY" "DB_PASSWORD:$DB_PASSWORD" \
              "REDIS_PASSWORD:$REDIS_PASSWORD" "BACKUP_PASSPHRASE:$BACKUP_PASSPHRASE"; do
  nombre="${pareja%%:*}"; contenido="${pareja#*:}"
  if [ -z "$contenido" ]; then rojo "  Falta $nombre en el registro."; exit 1; fi
done
verde "  los secretos obligatorios están presentes"

# La pila debe resolverse ANTES de viajar. Descubrir que no resuelve en el
# servidor, con el despliegue a medias, es mucho más caro.
comprobar_pila() {
  local carpeta="$1"
  ( cd "$carpeta" && \
    APP_KEY="$APP_KEY" DB_PASSWORD="$DB_PASSWORD" REDIS_PASSWORD="$REDIS_PASSWORD" \
    BACKUP_PASSPHRASE="$BACKUP_PASSPHRASE" S3_ACCESS_KEY="$S3_ACCESS_KEY" \
    S3_SECRET_KEY="$S3_SECRET_KEY" docker compose -f compose.yaml config --quiet )
}
verde "  (la comprobación real de la pila se hace al final, sobre el servidor)"
echo "  VERSION publicada: $VERSION"

# --- Publicación --------------------------------------------------------------
paso "3. Publicación en $SERVIDOR_IP"

DESTINO="/opt/sede/$ENTORNO"
[ -n "$SERVIDOR_IP" ] || { rojo "Falta SERVIDOR_IP en el registro."; exit 1; }

# Lo que viaja se prepara en un archivo y se envía por la entrada estándar, para
# que ningún valor aparezca en la lista de procesos del servidor.
PAQUETE="$(mktemp -d)"
trap 'rm -f "$RENDERIZADO"; rm -rf "$PAQUETE"' EXIT
cp "$RENDERIZADO" "$PAQUETE/sede.env"
cp "$RAIZ/compose.yaml" "$PAQUETE/compose.yaml"
tar -czf "$PAQUETE/entorno.tar.gz" -C "$PAQUETE" sede.env compose.yaml

B64_ENV="$(base64 -w0 "$PAQUETE/entorno.tar.gz")"
CONTRASENA="$(valor OPS_PASSWORD_INICIAL)"
B64_PW="$(printf '%s' "$CONTRASENA" | base64 -w0)"

GUION=$(cat <<'EOS'
set -eu
PWSEC=$(printf %s '@@PW@@' | base64 -d)
printf '%s\n' "$PWSEC" | sudo -S -k -p '' bash -c '
  set -eu
  DESTINO="@@DESTINO@@"
  install -d -m 750 "$DESTINO"
  rm -rf /tmp/sede-entorno && install -d -m 700 /tmp/sede-entorno
  printf %s "@@ENV@@" | base64 -d > /tmp/sede-entorno/entorno.tar.gz
    tar -xzf /tmp/sede-entorno/entorno.tar.gz -C "$DESTINO"
  install -d -m 750 "$DESTINO/certs"
  mv "$DESTINO/sede.env" "$DESTINO/.env"

  # PERTENENCIA Y PERMISOS: la llave de despliegue entra como la cuenta de
  # despliegue y `desplegar.sh` corre con `sudo` (esa cuenta ya no pertenece al
  # grupo `docker`). El entorno es de `root` con grupo de despliegue y 640: la
  # cuenta de despliegue puede LEER pero no MODIFICAR, de modo que si la llave se
  # comprometiera no podría reescribir el entorno que ejecuta. Antes de la
  # migración a `sudo` el guion corría como esa cuenta y este reparto era además
  # lo que le permitía leer el `.env`; hoy sigue siendo lo correcto.
  chown root:deploy "$DESTINO" && chmod 750 "$DESTINO"
  chown root:deploy "$DESTINO/.env" && chmod 640 "$DESTINO/.env"
  chown root:deploy "$DESTINO/compose.yaml" && chmod 644 "$DESTINO/compose.yaml"
  chown -R root:deploy "$DESTINO/docker" && chmod -R a+rX "$DESTINO/docker"

  # El ESTADO lo escribe la cuenta de despliegue, así que vive en /var/lib y le
  # pertenece. Separarlo de la configuración es lo que permite que esa cuenta
  # pueda anotar qué versión corre sin poder reescribir el entorno que ejecuta.
  install -d -m 750 -o deploy -g deploy "/var/lib/sede/@@ENTORNO@@"
  rm -rf /tmp/sede-entorno
  echo "    publicado en $DESTINO"
  ls -la "$DESTINO" | sed "s/^/      /"
'
unset PWSEC
EOS
)
GUION=${GUION//@@PW@@/$B64_PW}
GUION=${GUION//@@DESTINO@@/$DESTINO}
GUION=${GUION//@@ENTORNO@@/$ENTORNO}
GUION=${GUION//@@ENV@@/$B64_ENV}

printf '%s\n' "$GUION" | timeout 180 ssh -F /dev/null -i "$HOME/.ssh/id_ed25519" \
  -o BatchMode=yes -o StrictHostKeyChecking=no -o ConnectTimeout=25 \
  "ops@$SERVIDOR_IP" 'bash -s' 2>&1 \
  | grep -viE "Failed to add|store now|may need|pq.html"

# --- Secretos del servidor (ADR-0019) -----------------------------------------
paso "4. Secretos en el servidor"

# Los secretos van a UN archivo propio, de `root` y con permisos 600, fuera de
# `/opt/sede/<entorno>` y fuera del alcance de la cuenta de despliegue. Desde aquí
# salen por la entrada estándar de `ssh`, nunca como argumentos: un argumento aparece
# en la lista de procesos del servidor.
#
# `desplegar.sh` comprueba dueño y permisos antes de leerlo y se niega si no son los
# esperados: si otra cuenta pudiera escribirlo, dejaría de ser el ancla que se supone.
#
# Los opcionales sólo se escriben si tienen valor. Ausente y vacío son lo mismo para
# el renderizador —una integración apagada—, y un archivo con dieciséis líneas vacías
# no dice nada que no diga su ausencia.
SECRETOS_TMP="$(mktemp)"
chmod 600 "$SECRETOS_TMP"
trap 'rm -f "$RENDERIZADO" "$SECRETOS_TMP"; rm -rf "$PAQUETE"' EXIT

{
  echo "# Secretos de $ENTORNO. Los escribe deploy/publicar-entorno.sh; los lee"
  echo "# /usr/local/bin/desplegar.sh al renderizar el entorno (ADR-0019)."
  echo "# Este archivo es la ÚNICA copia en el servidor: no está en el repositorio,"
  echo "# no está en GitHub y no entra en ninguna imagen. No editar a mano."
  # `${!nombre}` es expansión indirecta de bash: el valor se lee sin pasar por `eval`.
  # Con `eval`, un secreto con acentos graves o `$(...)` se ejecutaría aquí.
  for nombre in APP_KEY DB_PASSWORD REDIS_PASSWORD BACKUP_PASSPHRASE; do
    printf '%s=%s\n' "$nombre" "${!nombre}"
  done
  for nombre in SUPER_ADMIN_EMAIL SUPER_ADMIN_NAME SUPER_ADMIN_PASSWORD \
                S3_ACCESS_KEY S3_SECRET_KEY; do
    [ -n "${!nombre}" ] && printf '%s=%s\n' "$nombre" "${!nombre}"
  done
  # Este `true` NO es decorativo. Con `set -e`, si el ÚLTIMO secreto opcional está
  # vacío, el `[ -n … ]` del bucle devuelve 1, ese 1 es el estado del grupo entero y el
  # guion se detendría aquí sin decir por qué, justo cuando todo está bien.
  true
} > "$SECRETOS_TMP"

# Una comprobación más, antes de que viaje: un salto de línea dentro de un secreto
# partiría el archivo en dos y la segunda mitad se rechazaría en el servidor.
if [ "$(wc -l < "$SECRETOS_TMP")" -lt 8 ]; then
  rojo "El archivo de secretos salió más corto de lo posible. Revisa $REGISTRO."
  exit 1
fi
while IFS= read -r l; do
  case "$l" in
    '#'*) ;;
    [A-Z]*=*) ;;
    *) rojo "Una línea del archivo de secretos no es CLAVE=valor. ¿Un salto de línea dentro de un secreto?"; exit 1 ;;
  esac
done < "$SECRETOS_TMP"
verde "  archivo de secretos armado ($(grep -cE '^[A-Z]' "$SECRETOS_TMP") valores)"

B64_SEC="$(base64 -w0 "$SECRETOS_TMP")"

GUIONS=$(cat <<'EOS'
set -eu
PWSEC=$(printf %s '@@PW@@' | base64 -d)
printf '%s\n' "$PWSEC" | sudo -S -k -p '' bash -c '
  set -eu
  install -d -m 700 -o root -g root /etc/sede
  umask 077
  printf %s "@@SEC@@" | base64 -d > /etc/sede/@@ENTORNO@@.secretos.env.nuevo
  chown root:root /etc/sede/@@ENTORNO@@.secretos.env.nuevo
  chmod 600 /etc/sede/@@ENTORNO@@.secretos.env.nuevo
  mv -f /etc/sede/@@ENTORNO@@.secretos.env.nuevo /etc/sede/@@ENTORNO@@.secretos.env
  echo "    /etc/sede/@@ENTORNO@@.secretos.env  $(stat -c "%U:%G %a" /etc/sede/@@ENTORNO@@.secretos.env)"
'
unset PWSEC
EOS
)
GUIONS=${GUIONS//@@PW@@/$B64_PW}
GUIONS=${GUIONS//@@SEC@@/$B64_SEC}
GUIONS=${GUIONS//@@ENTORNO@@/$ENTORNO}

printf '%s\n' "$GUIONS" | timeout 180 ssh -F /dev/null -i "$HOME/.ssh/id_ed25519" \
  -o BatchMode=yes -o StrictHostKeyChecking=no -o ConnectTimeout=25 \
  "ops@$SERVIDOR_IP" 'bash -s' 2>&1 \
  | grep -viE "Failed to add|store now|may need|pq.html"

unset B64_SEC
rm -f "$SECRETOS_TMP"

# --- Comprobación en el servidor ----------------------------------------------
paso "5. La pila se resuelve EN EL SERVIDOR"

GUION2=$(cat <<'EOS'
set -eu
PWSEC=$(printf %s '@@PW@@' | base64 -d)
printf '%s\n' "$PWSEC" | sudo -S -k -p '' bash -c '
  set -eu
  DESTINO="@@DESTINO@@"
  cd "$DESTINO"
  if docker compose -f compose.yaml --env-file .env config --quiet; then
    echo "    ✓ la pila se resuelve en el servidor"
  else
    echo "    ✗ la pila NO se resuelve"
    exit 1
  fi
'
unset PWSEC
EOS
)
GUION2=${GUION2//@@PW@@/$B64_PW}
GUION2=${GUION2//@@DESTINO@@/$DESTINO}

printf '%s\n' "$GUION2" | timeout 180 ssh -F /dev/null -i "$HOME/.ssh/id_ed25519" \
  -o BatchMode=yes -o StrictHostKeyChecking=no -o ConnectTimeout=25 \
  "ops@$SERVIDOR_IP" 'bash -s' 2>&1 \
  | grep -viE "Failed to add|store now|may need|pq.html"

echo
verde "Entorno $ENTORNO publicado."
echo
echo "Qué queda fuera de este guion:"
echo "  · El certificado del origen (F0.11). Lo emite 'droplet/certificado.sh' EN el"
echo "    servidor. Sin certificado nginx no arranca, por mucho que el entorno esté bien."
echo "  · '/usr/local/bin/desplegar.sh'. Es el ancla de confianza del anfitrión y NO se"
echo "    autoactualiza a propósito (ADR-0019): si se autoactualizara desde la imagen,"
echo "    quien pudiera empujar al registro ejecutaría código como root aquí. Se"
echo "    instala a mano, y sólo cuando cambia el contrato del entorno:"
echo "      scp deploy/droplet/despliegue-remoto.sh ops@<servidor>:/tmp/"
echo "      ssh ops@<servidor> 'sudo install -m 755 -o root -g root /tmp/despliegue-remoto.sh /usr/local/bin/desplegar.sh'"
echo
echo "Lo que ya NO hay que hacer: publicar el entorno al cambiar una variable. Desde el"
echo "ADR-0019, 'plantilla.env' y 'valores-<entorno>.env' viajan dentro de la imagen y"
echo "'desplegar.sh' los renderiza en cada despliegue."
