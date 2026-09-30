# syntax=docker/dockerfile:1.7
#
# Imagen de la aplicación: Laravel 13 sobre PHP-FPM 8.5.
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
FROM php:8.5-fpm-alpine@sha256:fa01fb1645cd0fc566a5f146b099adace33b906571f972f71f2182a7c12d1cd7 AS base

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
        $PHPIZE_DEPS postgresql-dev icu-dev libzip-dev libpng-dev freetype-dev jpeg-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql intl zip gd bcmath exif pcntl; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del .construccion; \
    reintentar apk add --no-cache libpq icu-libs libzip freetype libpng libjpeg-turbo; \
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
    for m in pdo_pgsql pgsql intl zip gd bcmath exif pcntl redis; do \
      php -m | grep -qix "$m" || faltan="$faltan $m"; \
    done; \
    if [ -n "$faltan" ]; then \
      echo "Estas extensiones no cargan:$faltan"; \
      echo "Suele faltar su biblioteca de ejecución tras retirar las de compilación."; \
      exit 1; \
    fi; \
    echo "Las nueve extensiones cargan."

# Lo único que hace falta en ejecución. `fcgi` aporta el cliente con el que la
# sonda de salud habla con PHP-FPM, que no entiende HTTP.
RUN apk add --no-cache ca-certificates tzdata curl fcgi \
    && rm -rf /var/cache/apk/*

# ---------------------------------------------------------------------------
# Dependencias de PHP, resueltas sobre la misma base
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac \
     /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY backend/composer.json backend/composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

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

# Se declaran para que la pila sepa que ahí se escribe. Es lo que permite montar
# volúmenes y, aun así, ejecutar el contenedor con el sistema de archivos de sólo
# lectura.
VOLUME ["/app/storage", "/app/bootstrap/cache"]

USER sede

EXPOSE 9000

# La sonda habla FastCGI, no HTTP: PHP-FPM no atiende peticiones HTTP.
HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
    CMD SCRIPT_NAME=/health REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 > /dev/null 2>&1 || exit 1

CMD ["php-fpm", "--nodaemonize"]
