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
#   docker/postgres/conf.d/      la configuración del gestor de datos
#   certs/                       donde vive el certificado del origen
#
# POR QUÉ EXISTE ESTE GUION. El plan dice que «la canalización renderiza la
# plantilla en cada despliegue», pero ese mecanismo no estaba implementado: la
# llave de despliegue está restringida a un único comando y no puede copiar
# archivos, así que el despliegue fallaba con «Falta /opt/sede/staging/.env».
# Mientras se decide por dónde viaja el entorno, esto lo publica de forma
# reproducible en lugar de a mano: un entorno escrito a mano no se puede
# reconstruir ni auditar.
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

# Dominio por entorno. El de producción sigue SIN decidirse: mientras no lo esté,
# se falla en lugar de inventarlo, porque un dominio inventado produce un
# certificado inválido y un sitio que no se encuentra.
if [ "$ENTORNO" = "staging" ]; then
  DOMINIO="${SEDE_DOMINIO_STAGING:-staging.santamarta.gov.co}"
else
  DOMINIO="$(valor SEDE_DOMINIO_PRODUCTION)"
  [ -n "$DOMINIO" ] || { rojo "El dominio de producción no está decidido (ver §21.A del plan)."; exit 1; }
fi

# Rangos oficiales de Cloudflare, NUNCA «*»: la aplicación sólo debe fiarse de
# quien de verdad está delante. Se descargan para no quedarse desactualizados.
TRUSTED_PROXIES="$(curl -fsS --max-time 20 https://www.cloudflare.com/ips-v4 2>/dev/null | tr '\n' ',' | sed 's/,$//')"
if [ -z "$TRUSTED_PROXIES" ]; then
  rojo "No se pudieron obtener los rangos de Cloudflare."
  rojo "No se publica un entorno fiándose de «*»: eso haría que la aplicación"
  rojo "creyera a cualquiera que enviara la cabecera de reenvío."
  exit 1
fi

# --- Renderizado --------------------------------------------------------------
paso "1. Renderizado del entorno ($ENTORNO)"

# Las integraciones sin credenciales se declaran APAGADAS con `mock`, que es la
# convención de la propia plantilla: ofrecer un botón que no lleva a ninguna parte
# es peor que decir que no está.
export APP_KEY DB_PASSWORD REDIS_PASSWORD BACKUP_PASSPHRASE
export S3_ACCESS_KEY S3_SECRET_KEY
export DOMINIO ENTORNO TRUSTED_PROXIES

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
tar -czf "$PAQUETE/postgres.tar.gz" -C "$RAIZ" docker/postgres/conf.d

B64_ENV="$(base64 -w0 "$PAQUETE/entorno.tar.gz")"
B64_PG="$(base64 -w0 "$PAQUETE/postgres.tar.gz")"
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
  printf %s "@@PG@@"  | base64 -d > /tmp/sede-entorno/postgres.tar.gz
  tar -xzf /tmp/sede-entorno/entorno.tar.gz -C "$DESTINO"
  install -d -m 755 "$DESTINO/docker/postgres"
  tar -xzf /tmp/sede-entorno/postgres.tar.gz -C "$DESTINO/docker/postgres" --strip-components=2
  install -d -m 750 "$DESTINO/certs"
  mv "$DESTINO/sede.env" "$DESTINO/.env"

  # PERTENENCIA Y PERMISOS: `desplegar.sh` se ejecuta como la cuenta de
  # despliegue, NO como la de administración. Si el entorno queda de `root` con
  # 600, el despliegue falla al leerlo con un error de permisos que parece del
  # guion y no del reparto. El grupo es el de despliegue, de modo que puede LEER
  # pero no MODIFICAR: si la llave de despliegue se comprometiera, no podría
  # reescribir el entorno que ejecuta.
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
GUION=${GUION//@@PG@@/$B64_PG}

printf '%s\n' "$GUION" | timeout 180 ssh -F /dev/null -i "$HOME/.ssh/id_ed25519" \
  -o BatchMode=yes -o StrictHostKeyChecking=no -o ConnectTimeout=25 \
  "ops@$SERVIDOR_IP" 'bash -s' 2>&1 \
  | grep -viE "Failed to add|store now|may need|pq.html"

# --- Comprobación en el servidor ----------------------------------------------
paso "4. La pila se resuelve EN EL SERVIDOR"

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
echo "Lo que sigue faltando, y NO lo resuelve este guion:"
echo "  · El certificado del origen (F0.11). Sin él, nginx no arranca y el"
echo "    despliegue seguirá fallando, ya con el entorno en su sitio."
echo "  · Decidir por dónde viaja el entorno en cada despliegue, para que esto"
echo "    deje de ser una publicación aparte."
