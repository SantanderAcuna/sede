#!/bin/sh
# Modo de la Content-Security-Policy: informe (por defecto) o aplicación.
#
# La política es UNA sola, la de snippets/csp-informe.conf. Este script solo decide el
# NOMBRE de la cabecera con la que sale:
#
#   SEDE_CSP_MODO sin definir, vacío o «informe» → Content-Security-Policy-Report-Only
#                                                    (observa y avisa; no bloquea nada)
#   SEDE_CSP_MODO=aplicar                         → Content-Security-Policy
#                                                    (el navegador bloquea lo que incumpla)
#
# Cualquier otro valor se trata como «informe» y se avisa: una errata en una variable no
# debe poder romper el sitio ni, peor, aplicar una política sin que nadie lo haya decidido.
# El paso a «aplicar» es una decisión humana, tras 7 días de informes limpios (AC-08.3);
# staging y producción arrancan en «informe» mientras la variable no se defina.
#
# El contenedor corre con el sistema de archivos en SOLO LECTURA (compose.yaml), así que
# no se edita ningún archivo de la imagen: se GENERA, al arrancar, /var/run/sede-csp.conf
# (tmpfs) a partir de snippets/csp-informe.conf, y default.conf incluye el generado.
# Se ejecuta antes que nginx (/docker-entrypoint.d/) y es idempotente. Los dos caminos se
# pueden cambiar con SEDE_CSP_ORIGEN y SEDE_CSP_DESTINO (solo lo usa la prueba).
set -eu

ORIGEN="${SEDE_CSP_ORIGEN:-/etc/nginx/snippets/csp-informe.conf}"
ARCHIVO="${SEDE_CSP_DESTINO:-/var/run/sede-csp.conf}"
MODO="${SEDE_CSP_MODO:-informe}"

[ -f "$ORIGEN" ] || { echo "csp-modo: no existe $ORIGEN" >&2; exit 1; }
cp "$ORIGEN" "$ARCHIVO"

case "$MODO" in
  aplicar)
    sed -i 's/^add_header Content-Security-Policy-Report-Only /add_header Content-Security-Policy /' "$ARCHIVO"
    grep -q '^add_header Content-Security-Policy ' "$ARCHIVO" \
      || { echo "csp-modo: no se pudo pasar a modo de aplicación" >&2; exit 1; }
    echo "csp-modo: APLICAR (Content-Security-Policy)"
    ;;
  informe)
    echo "csp-modo: informe (Content-Security-Policy-Report-Only)"
    ;;
  *)
    echo "csp-modo: valor «$MODO» no reconocido en SEDE_CSP_MODO; se queda en informe" >&2
    ;;
esac
