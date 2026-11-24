# syntax=docker/dockerfile:1.7
#
# Imagen del punto de entrada.
#
# nginx sirve la SPA del panel desde disco —sin tocar PHP— y hace de proxy al
# servidor del sitio. Es el único contenedor que publica puertos y el único
# lugar donde se aplican las cabeceras de seguridad.

# El panel NO se compila aquí. Llega como CONTEXTO DE CONSTRUCCIÓN con nombre
# —«panel»—, que es la imagen `sede-panel` ya construida y publicada:
#
#     docker buildx build --build-context panel=docker-image://…/sede-panel:<versión>
#
# Se compilaba en una etapa de este mismo archivo, y funcionaba, pero tenía dos
# consecuencias que no se ven hasta que hacen falta: el artefacto del panel no
# existía por separado —así que no se podía publicar, versionar ni servir desde
# otro sitio—, y este archivo tenía que saber de Node, de npm y del contrato,
# cuando lo único que le importa es tener los archivos en su sitio.
#
# ---------------------------------------------------------------------------
# Imagen final
# ---------------------------------------------------------------------------
FROM nginx:1.31.9-alpine@sha256:fb4658584bd9a916ffb6f6e365f97c25cfdb2923801f551ea08356e695f14ff7 AS final

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
    reintentar apk add --no-cache curl tzdata; \
    rm -rf /var/cache/apk/*

# La configuración vive en el repositorio y se versiona, no se copia a mano en
# el servidor.
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/conf.d/ /etc/nginx/conf.d/
COPY docker/nginx/snippets/ /etc/nginx/snippets/

# Modo de la CSP (informe por defecto; «aplicar» solo si SEDE_CSP_MODO=aplicar). Corre antes
# de nginx gracias al mecanismo /docker-entrypoint.d/ de la imagen oficial.
COPY --chmod=0755 docker/nginx/entrypoint/40-csp-modo.sh /docker-entrypoint.d/40-csp-modo.sh

# La SPA compilada se sirve desde disco.
# La carpeta en disco acompaña a la ruta pública `/admin`: así `root` y la URI
# siguen cuadrando sin `alias`, cuyo `try_files` es una fuente clásica de sorpresas.
COPY --from=panel /srv/panel /usr/share/nginx/admin

# nginx necesita escuchar en 80 y 443 y escribir en su caché. Nada más: el
# contenedor corre con todas las capacidades retiradas salvo ésa.
EXPOSE 80 443

# Se pide por HTTPS (con -k: el certificado es del dominio, no de 127.0.0.1) y no por el
# puerto 80: ese puerto solo atiende el desafío de certbot y responde 301 a todo lo demás, y
# `curl -f` no toma un 301 por error. Con la dirección http:// el chequeo daba «sano» siempre
# que nginx contestara, aunque PHP-FPM estuviera caído. Así recorre el servidor TLS y llega a
# PHP por FastCGI (502 si no contesta). El despliegue no espera esta salud (`up -d` sin
# `--wait`): solo hace que el estado diga la verdad.
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsSk https://127.0.0.1/health > /dev/null || exit 1

CMD ["nginx", "-g", "daemon off;"]
