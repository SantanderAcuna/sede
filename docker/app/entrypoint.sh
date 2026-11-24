#!/bin/sh
# Punto de entrada de la imagen de la aplicación.
#
# Genera las cachés de Laravel (configuración, rutas y eventos) AL ARRANCAR cada contenedor, en
# su propio `/tmp` (tmpfs), y después cede el control al punto de entrada oficial de PHP.
#
# Por qué aquí y no al construir la imagen: la configuración depende del entorno de ESTE
# contenedor (`APP_KEY`, credenciales de la base, `PGOPTIONS`...), que Docker inyecta al arrancar
# y que es distinto en `app`, `horizon` y `scheduler`. Cachearla al construir hornearía valores
# de construcción en la imagen.
#
# Por qué no en `/app/bootstrap/cache`: ese volumen lo comparten varios servicios y sobrevive
# a los despliegues. Un `config.php` guardado ahí lo leerían contenedores con otro entorno y
# seguiría vigente tras desplegar una configuración nueva. El tmpfs de cada contenedor nace
# vacío y muere con él: cada arranque regenera la caché con SU entorno, y recrear el contenedor
# (lo que hace cualquier despliegue) la renueva.
#
# Solo actúa si `SEDE_CACHE_MARCO=1` (compose.yaml lo activa en `app`, `horizon` y `scheduler`;
# el desarrollo local lo apaga). Las rutas de las cachés las fija el `ENV` del Dockerfile
# (`APP_CONFIG_CACHE`, `APP_ROUTES_CACHE`, `APP_EVENTS_CACHE`), así que también las ven
# `docker exec ... php artisan ...` y los procesos hijos (`schedule:run`, trabajadores de colas).
#
# Si la generación falla NO se impide el arranque: se avisa en voz alta y el contenedor sigue
# sin cachés, que es exactamente el comportamiento anterior (más lento, no distinto). Un
# despliegue no debe caerse por una optimización.
set -eu

if [ "${SEDE_CACHE_MARCO:-0}" = "1" ]; then
    # `config.php` contiene la configuración ya resuelta, con APP_KEY y las claves de la base:
    # solo el usuario del proceso debe poder leerla (el tmpfs ya es propio de este contenedor).
    umask 077
    if mkdir -p "$(dirname "${APP_CONFIG_CACHE:?}")" \
        && php artisan config:cache --no-interaction > /dev/null \
        && php artisan route:cache --no-interaction > /dev/null \
        && php artisan event:cache --no-interaction > /dev/null; then
        echo "sede: cachés de configuración, rutas y eventos generadas en $(dirname "$APP_CONFIG_CACHE")"
    else
        echo "sede: AVISO: no se pudieron generar las cachés de Laravel; el contenedor arranca SIN ellas." >&2
        rm -f "${APP_CONFIG_CACHE:-}" "${APP_ROUTES_CACHE:-}" "${APP_EVENTS_CACHE:-}"
    fi
fi

umask 022
exec docker-php-entrypoint "$@"
