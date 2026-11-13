#!/bin/sh
#
# Arranque de PostgreSQL con TLS opcional (Fase 0A, T5).
#
#   POSTGRES_SSL=off (por defecto)  no se activa TLS.
#   POSTGRES_SSL=on                 exige /tls/server.crt y /tls/server.key, activa TLS y
#                                   RECHAZA las conexiones de red sin TLS (hostnossl reject).
#
# Por qué un guion y no `-c ssl_key_file=/tls/server.key` directo: PostgreSQL rechaza una
# clave que no sea del usuario `postgres` (o de root con permisos <= 0640 y grupo
# adecuado), y un montaje de sólo lectura desde el anfitrión llega con otro dueño. Se
# copia a un sitio propio con el dueño y los permisos que exige.
#
# Si se pide TLS y faltan los archivos, el contenedor NO arranca: una base que se creía
# cifrada y no lo está es peor que una que no arranca.
set -eu

if [ "${POSTGRES_SSL:-off}" = "on" ]; then
  if [ ! -s /tls/server.crt ] || [ ! -s /tls/server.key ]; then
    echo "POSTGRES_SSL=on pero faltan /tls/server.crt o /tls/server.key." >&2
    exit 1
  fi
  install -d -m 700 -o postgres -g postgres /var/lib/postgresql/tls
  install -m 644 -o postgres -g postgres /tls/server.crt /var/lib/postgresql/tls/server.crt
  install -m 600 -o postgres -g postgres /tls/server.key /var/lib/postgresql/tls/server.key
  # El servidor EXIGE TLS a los clientes de red: sin esto la imagen acepta también
  # conexiones sin cifrar y bastaría un cliente mal configurado para saltarse el TLS.
  # Los sockets locales (arranque de la imagen, `pg_isready`, `docker exec`) no pasan
  # por la red y se dejan como los trae la imagen.
  cat > /var/lib/postgresql/tls/pg_hba.conf <<'HBA'
local   all all                 trust
hostssl all all all             scram-sha-256
hostnossl all all all           reject
HBA
  chown postgres:postgres /var/lib/postgresql/tls/pg_hba.conf
  chmod 600 /var/lib/postgresql/tls/pg_hba.conf
  set -- "$@" \
    -c hba_file=/var/lib/postgresql/tls/pg_hba.conf \
    -c ssl=on \
    -c ssl_cert_file=/var/lib/postgresql/tls/server.crt \
    -c ssl_key_file=/var/lib/postgresql/tls/server.key \
    -c ssl_min_protocol_version=TLSv1.2
else
  set -- "$@" -c ssl=off
fi

exec docker-entrypoint.sh "$@"
