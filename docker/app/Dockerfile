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

RUN apk add --no-cache --virtual .construccion \
        $PHPIZE_DEPS postgresql-dev icu-dev libzip-dev libpng-dev freetype-dev jpeg-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql intl opcache zip gd bcmath exif pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .construccion \
    && rm -rf /tmp/pear /var/cache/apk/*

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
