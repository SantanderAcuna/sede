# syntax=docker/dockerfile:1.7
#
# Imagen del panel: compila la SPA y publica SÓLO el resultado.
#
# Existe como imagen propia porque el plan la declara, y porque separa dos cosas
# que tienen ciclos distintos: el artefacto del panel (HTML y activos) se compila
# y se versiona por su cuenta, y el punto de entrada lo consume sin saber cómo se
# construyó. Si algún día el panel se sirve desde un CDN, o desde otro servidor,
# el artefacto ya está separado.
#
# NO se vuelve a compilar aquí lo que ya está compilado en otro sitio: esta imagen
# es la ÚNICA que compila el panel, y la de nginx la copia.

# ---------------------------------------------------------------------------
# Etapa 1 — compilación
# ---------------------------------------------------------------------------
FROM node:24-alpine@sha256:ebfe2f90462722a7a4de65e91990e97fe0d401c70e0e762c5b53302f905ec1c1 AS construccion

WORKDIR /app

# Las dependencias primero, para que la caché de capas no se invalide cada vez que
# cambia una línea de código del panel.
COPY panel/package.json panel/package-lock.json ./
RUN npm ci --ignore-scripts

COPY contract/ /contract/
COPY panel/ ./

# Los tipos se generan desde el contrato: no se escriben a mano ni se versionan.
RUN npx openapi-typescript /contract/openapi.yaml -o src/types/openapi.d.ts --read-write-markers \
    && npm run build

# ---------------------------------------------------------------------------
# Etapa 2 — el artefacto
# ---------------------------------------------------------------------------
# `scratch` a propósito: esta imagen no ejecuta nada, sólo CONTIENE archivos, y el
# punto de entrada los copia durante su construcción. Añadirle un sistema de
# archivos completo —con su intérprete de órdenes, sus bibliotecas y sus paquetes—
# sería cargar con superficie de ataque algo que nunca se ejecuta.
#
# Es lo contrario del error habitual en el otro sentido: aquí no se «ahorra» una
# etapa, se declara que este artefacto no tiene proceso. Como no hay nada que
# arrancar, tampoco hay sonda, ni usuario, ni límites que declarar.
FROM scratch AS final

COPY --from=construccion /app/dist /srv/panel
