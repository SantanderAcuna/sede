#!/usr/bin/env bash
#
# Entorno local en Docker (Fase 0A, T1): la MISMA pila que se despliega, con sus roles
# de base de datos (`sede` dueño, `sede_app`, `sede_lectura`), para revisar cambios y
# correr pruebas antes de pasarlos a `develop`.
#
#   make local            levanta, migra con el dueño y siembra
#   make local-abajo      detiene la pila (conserva los datos)
#   make local-limpio     detiene y BORRA los datos locales
#
# Qué crea, una sola vez y sin pisar lo que ya exista:
#   .env                   secretos ALEATORIOS locales (está en .gitignore)
#   docker/nginx/certs/    certificado autofirmado para localhost (está en .gitignore)
#
# Nada de esto sale de la máquina ni sirve fuera de ella.
set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"

accion="${1:-arriba}"

aleatorio() { openssl rand -hex 24; }

crear_env() {
  [ -f .env ] && { echo "  .env ya existe: no se toca."; return; }
  local clave_app
  clave_app="base64:$(openssl rand -base64 32)"
  ( umask 077
  cat > .env <<ENV
# Generado por scripts/local.sh. Sólo para esta máquina. No se versiona.
APP_NAME=sede
APP_ENV=local
APP_DEBUG=true
APP_KEY=${clave_app}
APP_URL=https://localhost:8443
APP_TIMEZONE=America/Bogota
APP_LOCALE=es
APP_FALLBACK_LOCALE=es

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=sede
# TLS a la base también en local (Fase 0A, T5), con certificado de prueba.
DB_SSLMODE=require
POSTGRES_SSL=on
# Dueño de la base: sólo lo usa el servicio \`migrar\`. La aplicación usa sede_app.
DB_USERNAME=sede
DB_PASSWORD=$(aleatorio)
DB_APP_PASSWORD=$(aleatorio)
DB_LECTURA_PASSWORD=$(aleatorio)

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=$(aleatorio)

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=false
CACHE_STORE=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=correo
MAIL_PORT=1025

BACKUP_PASSPHRASE=$(aleatorio)
ENV
  )
  echo "  .env creado con secretos aleatorios locales."
}

crear_certificado() {
  mkdir -p docker/nginx/certs
  if [ -f docker/nginx/certs/sede.crt ] && [ -f docker/nginx/certs/sede.key ]; then
    echo "  certificado ya existe: no se toca."
    return
  fi
  openssl req -x509 -newkey rsa:2048 -nodes -days 365 \
    -keyout docker/nginx/certs/sede.key -out docker/nginx/certs/sede.crt \
    -subj "/CN=localhost" -addext "subjectAltName=DNS:localhost,IP:127.0.0.1" 2>/dev/null
  # 0644 a propósito: nginx corre sin DAC_OVERRIDE y su usuario no es el tuyo, así que una clave
  # 0600 tuya no se puede leer. Es un certificado de PRUEBA de esta máquina; en servidores no se usa.
  chmod 644 docker/nginx/certs/sede.crt docker/nginx/certs/sede.key
  echo "  certificado autofirmado creado (el navegador pedirá aceptarlo)."
}

crear_certificado_bd() {
  mkdir -p docker/postgres/certs
  if [ -f docker/postgres/certs/server.crt ] && [ -f docker/postgres/certs/server.key ]; then
    echo "  certificado de la base ya existe: no se toca."
    return
  fi
  openssl req -x509 -newkey rsa:2048 -nodes -days 365 \
    -keyout docker/postgres/certs/server.key -out docker/postgres/certs/server.crt \
    -subj "/CN=postgres" -addext "subjectAltName=DNS:postgres,DNS:localhost,IP:127.0.0.1" 2>/dev/null
  # Se copia dentro del contenedor con el dueño que PostgreSQL exige; aquí basta con 0600.
  chmod 644 docker/postgres/certs/server.crt
  chmod 644 docker/postgres/certs/server.key
  echo "  certificado de prueba de la base creado."
}

case "$accion" in
  preparar)
    # Sólo el entorno y el certificado, sin tocar Docker (lo usa la prueba del propio guion).
    crear_env
    crear_certificado
    crear_certificado_bd
    ;;
  arriba)
    command -v docker >/dev/null || { echo "Falta docker." >&2; exit 1; }
    echo "1. Entorno"
    crear_env
    crear_certificado
    crear_certificado_bd
    echo "2. Imagen del panel (contexto con nombre de la imagen de nginx)"
    docker build -f docker/panel/Dockerfile -t sede-panel:dev .
    echo "   Imágenes y servicios"
    docker compose up -d --build postgres redis
    echo "3. Migraciones con el rol dueño (crea sede_app y sede_lectura)"
    docker compose --profile migrar run --rm --build migrar
    echo "4. Aplicación (rol sede_app)"
    docker compose up -d --build
    echo "5. Siembra"
    docker compose exec -T app php artisan db:seed --class=SedeSeeder --force
    echo
    echo "Listo. https://localhost:8443  ·  correo de prueba: http://localhost:8025"
    ;;
  abajo)
    docker compose down
    ;;
  limpio)
    docker compose down -v
    ;;
  *)
    echo "Uso: $0 [arriba|abajo|limpio|preparar]" >&2
    exit 2
    ;;
esac
