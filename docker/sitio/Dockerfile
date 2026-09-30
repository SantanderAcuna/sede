# syntax=docker/dockerfile:1.7
#
# Imagen del sitio público: Nuxt 4 con renderizado en servidor.
#
# El servidor de Nuxt no es un adorno: existe porque el contenido debe ser
# rastreable y auditable sin ejecutar JavaScript. Por eso hay un proceso de Node
# en producción y no sólo archivos estáticos.

# ---------------------------------------------------------------------------
# Etapa 1 — compilación
# ---------------------------------------------------------------------------
FROM node:24-alpine@sha256:ebfe2f90462722a7a4de65e91990e97fe0d401c70e0e762c5b53302f905ec1c1 AS construccion

WORKDIR /app

COPY sitio/package.json sitio/package-lock.json ./
RUN npm ci --ignore-scripts

COPY contract/ /contract/
COPY sitio/ ./

# Los tipos se generan desde el contrato: no se escriben a mano ni se versionan.
RUN npx openapi-typescript /contract/openapi.yaml -o types/openapi.d.ts --read-write-markers \
    && npm run build

# Se retiran las dependencias de desarrollo del árbol que va a producción.
RUN npm prune --omit=dev

# ---------------------------------------------------------------------------
# Etapa 2 — imagen final
# ---------------------------------------------------------------------------
FROM node:24-alpine@sha256:ebfe2f90462722a7a4de65e91990e97fe0d401c70e0e762c5b53302f905ec1c1 AS final

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
    reintentar apk add --no-cache ca-certificates tzdata curl; \
    rm -rf /var/cache/apk/*

# npm y corepack NO viajan en la imagen de ejecución.
#
# Este contenedor arranca con `node .output/server/index.mjs`: no instala nada ni
# compila nada. Y sin embargo npm arrastraba SIETE vulnerabilidades de severidad
# ALTA en sus propias dependencias internas —`brace-expansion`, `ip-address` y
# `undici`—, que se publicaban con la imagen y bloqueaban el despliegue.
#
# No era deriva de las dependencias del proyecto: `app/node_modules` salía
# limpio. Era una HERRAMIENTA DE CONSTRUCCIÓN dentro de una imagen de EJECUCIÓN.
# Actualizar npm lo habría tapado hasta la siguiente vulnerabilidad; quitarlo
# elimina la superficie entera, y de paso la imagen encoge.
RUN rm -rf /usr/local/lib/node_modules/npm /usr/local/bin/npm /usr/local/bin/npx \
    && rm -rf /usr/local/lib/node_modules/corepack /usr/local/bin/corepack \
    && rm -f /usr/local/bin/yarn /usr/local/bin/yarnpkg /usr/local/bin/pnpm /usr/local/bin/pnpx

RUN addgroup -g 10001 -S sede \
    && adduser -u 10001 -S sede -G sede

WORKDIR /app

COPY --from=construccion --chown=sede:sede /app/.output ./.output
COPY --from=construccion --chown=sede:sede /app/node_modules ./node_modules
COPY --from=construccion --chown=sede:sede /app/package.json ./

ENV NODE_ENV=production \
    NITRO_PORT=3000 \
    NITRO_HOST=0.0.0.0

USER sede

EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1:3000/ > /dev/null || exit 1

CMD ["node", ".output/server/index.mjs"]
