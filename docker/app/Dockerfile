# syntax=docker/dockerfile:1.7
#
# Imagen de la aplicación: Laravel 13 sobre PHP-FPM 8.6.
#
# Todas las bases están fijadas por resumen y no por etiqueta: una etiqueta
# puede reapuntarse y, con ella, cambiar todo el contenido de la imagen sin que
# nadie toque este archivo.
#
# La etapa `base` es la clave: tanto la resolución de dependencias como la
# imagen final parten de la MISMA base, con las MISMAS extensiones. La primera
# versión resolvía las dependencias sobre la imagen oficial de Composer, que no
# las tiene, y la comprobación de plataforma fallaba: Composer se negaba a
# instalar paquetes que necesitan extensiones que no veía.

# ---------------------------------------------------------------------------
# Base común: PHP con las extensiones que la aplicación necesita
# ---------------------------------------------------------------------------
FROM php:8.6-fpm-alpine@sha256:3892978a38794b348f73d6926fb63e352b5338f8b302cdd2cf0ed968f63b50c5 AS base

# Los paquetes de la imagen base se actualizan en la construcción.
#
# La base está fijada por resumen, así que no cambia sola — pero las correcciones
# de Alpine llegan como versiones nuevas de paquete, no como una imagen nueva. Sin
# esto, la imagen arrastraba `libexpat` 2.8.4-r0 con una vulnerabilidad ALTA ya
# corregida en 2.8.5-r0, y la publicación se detenía con razón.
#
# Actualizar aquí renuncia a que dos construcciones del mismo código den bytes
# idénticos. Se acepta a cambio de no publicar una imagen vulnerable, y lo que lo
# hace admisible es que la comprobación no es una promesa: el escáner corre en
# cada construcción y falla si queda algo corregible de severidad alta o crítica.
# Es decir, la falta de reproducibilidad la vigila una puerta, no la confianza.
# `opcache` NO aparece en la lista de extensiones, y esa ausencia es deliberada.
# La imagen base de PHP ya lo trae compilado y habilitado —aparece en `php -m`
# como «Zend OPcache»—, así que pedirlo otra vez intenta construir como módulo
# COMPARTIDO algo que ya está enlazado de forma estática: no se genera
# `modules/*.so` y la construcción muere con «cp: can't stat 'modules/*'», un
# mensaje que habla de copiar y no dice nada del motivo real.
#
# Costó encontrarlo. Se aisló instalando extensión por extensión:
#   OK pdo_pgsql · OK pgsql · OK intl · FALLA opcache · OK zip · OK gd · OK bcmath · OK exif · OK pcntl
# Las bibliotecas de EJECUCIÓN se instalan DESPUÉS de retirar las de compilación,
# y no es un detalle: `apk del .construccion` se lleva consigo `libzip`, `libpq`,
# `icu-libs`, `freetype`, `libpng` y `libjpeg-turbo`, que es de lo que dependen los
# módulos ya compilados. Sin reponerlas, `zip`, `intl`, `gd` y `pdo_pgsql` no
# cargan — y el síntoma aparece en otro sitio, como un error de Composer.
# Los paquetes se instalan CON REINTENTOS, y no es una precaución teórica: la
# construcción se cayó una vez con «TLS: unspecified error» al buscar el índice de
# Alpine. Un fallo transitorio de la red del ejecutor NO debe bloquear un
# despliegue: el código estaba bien y el despliegue no llegó por un parpadeo de la
# red. Reintentar con espera creciente cuesta unos segundos cuando pasa, y evita
# una hora de diagnóstico equivocado cuando pasa.
# `mbstring` se instala a propósito: la imagen php:8.5 lo traía compilado, la php:8.6 ya no
# (medido el 2026-11-24 con `php -m`). Sin él, el despliegue a staging falló al sembrar la
# base con «Call to undefined function mb_strimwidth()» y el servidor volvió a la versión
# anterior. Necesita `oniguruma-dev` para compilar y `oniguruma` en ejecución.
# phpredis se compila desde el tarball de PECL con su huella comprobada, y no con
# `pecl install`, por dos motivos medidos el 2026-11-23 con la imagen php:8.6:
#   - la imagen ya no trae `pecl` («pecl: not found»);
#   - phpredis 6.3.0, la última estable, NO compila en PHP 8.6 (usa `zval_dtor` y
#     `ZEND_WRONG_PARAM_COUNT`, retiradas). La 6.4.0RC1, publicada ese mismo día, es la
#     primera que declara compatibilidad con 8.6 («Fix PHP 8.6 compatibility»).
# Es una versión candidata (beta): al salir la 6.4.0 estable, se cambia la versión y la
# huella aquí. Verificado con Redis real: conecta, autentica, SET con TTL, GET y DEL.
RUN set -eu; \
    reintentar() { \
      n=0; \
      until "$@"; do \
        n=$((n + 1)); \
        if [ "$n" -ge 5 ]; then echo "Se agotaron los reintentos: $*"; return 1; fi; \
        echo "Fallo transitorio; reintento $n de 5 en $((n * 5))s: $*"; \
        sleep $((n * 5)); \
      done; \
    }; \
    reintentar apk --no-cache upgrade; \
    reintentar apk add --no-cache --virtual .construccion \
        $PHPIZE_DEPS postgresql-dev icu-dev libzip-dev libpng-dev freetype-dev jpeg-dev oniguruma-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql intl zip gd bcmath exif pcntl mbstring; \
    REDIS_VERSION=6.4.0RC1; \
    REDIS_SHA256=876434dff29603c83e33cb69bbcfdcff1f7846d968a396436b6c026cc15fc492; \
    reintentar curl -fsSL -o /tmp/redis.tgz "https://pecl.php.net/get/redis-${REDIS_VERSION}.tgz"; \
    echo "${REDIS_SHA256}  /tmp/redis.tgz" | sha256sum -c -; \
    mkdir /tmp/redis-src; \
    tar -xzf /tmp/redis.tgz -C /tmp/redis-src --strip-components=1; \
    ( cd /tmp/redis-src && phpize && ./configure --enable-redis && make -j"$(nproc)" && make install ); \
    docker-php-ext-enable redis; \
    rm -rf /tmp/redis-src /tmp/redis.tgz; \
    apk del .construccion; \
    reintentar apk add --no-cache libpq icu-libs libzip freetype libpng libjpeg-turbo oniguruma; \
    rm -rf /tmp/pear /var/cache/apk/*

# Las extensiones se COMPRUEBAN, no se dan por hechas.
#
# `apk del .construccion` retira los paquetes de compilación, y con ellos se lleva
# las bibliotecas COMPARTIDAS que los módulos ya compilados necesitan en
# ejecución. Quedaban sin cargar cinco extensiones —`pdo_pgsql`, `pgsql`, `intl`,
# `zip` y `gd`—, es decir, la imagen no habría podido ni conectarse a PostgreSQL.
#
# Y no se notaba aquí: aparecía mucho después, como un error de Composer que decía
# que faltaba `ext-zip` y hablaba de actualizar el archivo de bloqueo, que no era
# el problema. Fallar en el sitio donde está la causa es la mitad de arreglarlo.
RUN faltan=""; \
    for m in pdo_pgsql pgsql intl zip gd bcmath exif pcntl mbstring redis; do \
      php -m | grep -qix "$m" || faltan="$faltan $m"; \
    done; \
    if [ -n "$faltan" ]; then \
      echo "Estas extensiones no cargan:$faltan"; \
      echo "Suele faltar su biblioteca de ejecución tras retirar las de compilación."; \
      exit 1; \
    fi; \
    echo "Las diez extensiones cargan."

# Lo único que hace falta en ejecución. `fcgi` aporta el cliente con el que la
# sonda de salud habla con PHP-FPM, que no entiende HTTP.
#
# `postgresql18-client` aporta `pg_dump`, que es lo que usa `backup:run` para volcar
# la base. Hasta ahora la imagen sólo llevaba `libpq` (la biblioteca de conexión, que
# es lo que necesita `pdo_pgsql`) y NO el binario: cualquier copia habría fallado con
# «pg_dump: not found» la primera vez que alguien la ejecutara.
#
# La versión no es un detalle. `pg_dump` se niega a volcar un servidor MÁS NUEVO que
# él («aborting because of server version mismatch»), y el servidor de la pila es
# `postgres:18` (compose.yaml). Por eso el paquete es el 18 y no el genérico, y la
# comprobación de abajo falla la construcción si algún día cambia el cliente sin
# cambiar el servidor, o al revés.
RUN apk add --no-cache ca-certificates tzdata curl fcgi postgresql18-client \
    && rm -rf /var/cache/apk/*

RUN pg_dump --version | grep -q 'pg_dump (PostgreSQL) 18\.' \
    || { echo "pg_dump no es de la serie 18: $(pg_dump --version 2>&1)"; \
         echo "Debe coincidir con el servidor (postgres:18 en compose.yaml)."; exit 1; }

# ---------------------------------------------------------------------------
# Dependencias de PHP, resueltas sobre la misma base
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac \
     /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY backend/composer.json backend/composer.lock ./

# `--ignore-platform-req=php`: mpdf/mpdf 8.3.1 declara PHP ~8.5.0 como máximo y aún no admite 8.6.
# Verificado en la CI el 2026-11-23 con PHP 8.6.0 (suite completa y PDF real de una política).
# Retirar la opción al actualizar mpdf a una versión que declare 8.6.
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --ignore-platform-req=php

# ---------------------------------------------------------------------------
# Imagen final
# ---------------------------------------------------------------------------
FROM base AS final

# Usuario sin privilegios, con identificador fijo para que los permisos y los
# volúmenes sean reproducibles entre entornos.
RUN addgroup -g 10001 -S sede \
    && adduser -u 10001 -S sede -G sede

WORKDIR /app

COPY docker/app/php.ini /usr/local/etc/php/conf.d/99-sede.ini

# Punto de entrada: genera las cachés de configuración, rutas y eventos de Laravel al arrancar
# (solo si `SEDE_CACHE_MARCO=1`, que compose.yaml activa en app, horizon y scheduler) y cede
# el control al punto de entrada oficial de PHP. Ver el encabezado del script.
COPY --chmod=0755 docker/app/entrypoint.sh /usr/local/bin/sede-entrypoint

# Dónde guarda Laravel esas cachés: en `/tmp`, que en cada contenedor es un tmpfs propio. NO en
# `/app/bootstrap/cache`, que es un volumen compartido entre servicios con distinto entorno y
# que sobrevive a los despliegues. Que las rutas estén en la imagen (y no solo en el script) hace
# que `docker exec ... php artisan ...` y los procesos hijos lean las mismas cachés.
ENV APP_CONFIG_CACHE=/tmp/laravel-cache/config.php \
    APP_ROUTES_CACHE=/tmp/laravel-cache/routes-v7.php \
    APP_EVENTS_CACHE=/tmp/laravel-cache/events.php

COPY --from=vendor /app/vendor ./vendor
COPY backend/ ./

# El CARGADOR AUTOMÁTICO se genera aquí, con el código ya copiado, y se COMPRUEBA
# ejecutando la aplicación.
#
# La etapa de dependencias instala con `--no-autoloader`, que es deliberado: el
# cargador debe generarse cuando el código está presente, para que el mapa de
# clases incluya lo que de verdad se va a ejecutar. Pero nadie lo generaba en la
# etapa final, así que la imagen quedaba SIN cargador automático:
#
#     PHP Fatal error: Failed opening required '/app/vendor/autoload.php'
#
# Y eso no se notaba antes de desplegar: la imagen se construía, se escaneaba y se
# publicaba sin problema. Una imagen que no arranca pasaba todas las puertas menos
# la única que importa. Por eso, además de generarlo, se ejecuta la aplicación: si
# no arranca, la construcción falla AQUÍ, y no en el servidor con el despliegue a
# medias.
COPY --from=composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac \
     /usr/bin/composer /usr/bin/composer

RUN composer dump-autoload --optimize --classmap-authoritative --no-scripts \
    && php artisan package:discover --ansi \
    && php artisan --version \
    && rm -f /usr/bin/composer

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R sede:sede storage bootstrap/cache \
    && chmod -R a+rX /app

# Destino de las copias de seguridad (disco `copias`, config/filesystems.php).
#
# Está FUERA de `/app/storage` a propósito: es otro volumen, con otro ciclo de vida.
# Quien borre o recree el almacenamiento de la aplicación no debe llevarse con él las
# copias que existen precisamente para recuperarse de eso. Se crea aquí, con el dueño
# `sede`, para que el volumen nuevo herede el dueño correcto: el contenedor corre sin
# privilegios y un volumen que Docker crea vacío pertenece a `root`.
RUN mkdir -p /copias && chown sede:sede /copias && chmod 700 /copias
# `/copias` NO se declara en el `VOLUME` de más abajo, y no es un olvido. Declarar un VOLUME
# hace que Docker cree un volumen ANÓNIMO en ese camino en cada contenedor que no lo monte
# explícitamente: `app` y `horizon` lo recibían vacío y escribible, y cada recreación en un
# despliegue dejaba otro volumen huérfano. Se vio en staging: `ls /copias` en `app` y
# `horizon` no daba error. El montaje del volumen NOMBRADO en `scheduler` y `copia` funciona
# igual sin la declaración, y la carpeta de la imagen sólo aporta el dueño correcto.

# EL ENTORNO DEL DESPLIEGUE VIAJA CON LA VERSIÓN (ADR-0019).
#
# Estos cuatro archivos no los usa la aplicación: los extrae `desplegar.sh` en el
# servidor —con `docker create` + `docker cp`, sin ejecutar nada de la imagen— para
# renderizar el `.env` de ESTA versión con los secretos que viven allí. Que la
# configuración viaje por el registro, atada a la etiqueta del commit, es lo que hace
# que volver a una versión anterior devuelva también SU configuración.
#
# AQUÍ NO HAY NI UN SECRETO, y no es una casualidad: los valores de
# `valores-*.env` son públicos (dominio, nombre de la base, integraciones apagadas) y
# los secretos sólo existen en `/etc/sede/<entorno>.secretos.env`, en el servidor.
# `.dockerignore` excluye `**/.env` y `**/*.local.env` justamente para que un archivo
# de valores reales no pueda entrar aquí por descuido, y una guarda de la canalización
# comprueba que ningún nombre de secreto esté declarado en estos archivos.
#
# Van como `root` y legibles: es configuración, no código, y el proceso de la
# aplicación corre como `sede` y no necesita tocarlos.
COPY compose.yaml /opt/sede-entorno/compose.yaml
COPY deploy/plantilla.env deploy/valores-staging.env deploy/valores-produccion.env \
     /opt/sede-entorno/
COPY deploy/despliegue-remoto.sha256 /opt/sede-entorno/despliegue-remoto.sha256

# Se declaran para que la pila sepa que ahí se escribe. Es lo que permite montar
# volúmenes y, aun así, ejecutar el contenedor con el sistema de archivos de sólo
# lectura.
VOLUME ["/app/storage", "/app/bootstrap/cache"]

USER sede

EXPOSE 9000

# La sonda habla FastCGI, no HTTP: PHP-FPM no atiende peticiones HTTP.
HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
    CMD SCRIPT_NAME=/health REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 > /dev/null 2>&1 || exit 1

ENTRYPOINT ["sede-entrypoint"]
CMD ["php-fpm", "--nodaemonize"]
