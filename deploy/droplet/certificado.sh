#!/bin/bash
#
# Emite y renueva el certificado del origen.
#
#   ./certificado.sh staging staging.santamarta.gov.co admin@entidad.gov.co
#   ./certificado.sh produccion sede.santamarta.gov.co admin@entidad.gov.co
#
# Se emite ANTES del primer despliegue, y no después: el punto de entrada no
# arranca sin certificado, porque su configuración TLS lo referencia. Un nginx
# que arranca sin certificado y luego se «arregla» es una ventana en la que la
# sede atiende sin cifrar.
#
# ---------------------------------------------------------------------------
# POR QUÉ SE VALIDA POR DNS Y NO POR HTTP
#
# El desafío por HTTP exige que la petición llegue al ORIGEN por el puerto 80. Con
# Cloudflare delante no llega: el borde la redirige a HTTPS antes de que salga de
# su red. Comprobado contra el borde real:
#
#     HTTP  → 301 Moved Permanently → https://…
#     HTTPS → 521 (origen caído, porque aún no hay certificado)
#
# Validando por DNS el desafío no depende del borde, ni del proxy, ni de que el
# origen esté levantado. Además no obliga a exponer la dirección del origen ni un
# instante, y no hay que detener el punto de entrada: la sede sigue sirviendo
# mientras se emite el certificado.
# ---------------------------------------------------------------------------
#
set -euo pipefail

ENTORNO="${1:?Falta el entorno: staging o produccion}"
DOMINIO="${2:?Falta el dominio}"
CORREO="${3:?Falta el correo de contacto que exige la autoridad certificadora}"

BASE="/opt/sede/$ENTORNO"
CERTS="$BASE/certs"

# El token vive aquí, con permisos 600, porque certbot lo necesita PARA RENOVAR
# sola todos los meses. Si la renovación fallara, el certificado caducaría sin que
# nadie lo note hasta que el navegador avise al ciudadano.
CREDENCIALES_CLOUDFLARE=/root/.secrets/cloudflare.ini

rojo()  { printf '\033[31m%s\033[0m\n' "$*"; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }

case "$ENTORNO" in
  staging|produccion) ;;
  *) rojo "Entorno no válido: '$ENTORNO'"; exit 1 ;;
esac

[ "$(id -u)" -eq 0 ] || { rojo "Se ejecuta como root."; exit 1; }

command -v certbot >/dev/null 2>&1 || {
  rojo "Falta certbot. Instálalo con: apt-get install -y certbot python3-certbot-dns-cloudflare"
  exit 1
}

# ---------------------------------------------------------------------------
# Las credenciales se comprueban ANTES de tocar nada.
#
# La primera versión de este guion detenía el punto de entrada y DESPUÉS
# descubría que no tenía token, dejando la sede sin servir por un desafío que no
# se iba a poder atender. Comprobar primero es la diferencia entre no hacer nada y
# deshacer algo.
# ---------------------------------------------------------------------------
if [ ! -f "$CREDENCIALES_CLOUDFLARE" ]; then
  rojo "Faltan las credenciales de Cloudflare en $CREDENCIALES_CLOUDFLARE."
  rojo "El desafío por HTTP no puede funcionar con Cloudflare delante: el borde"
  rojo "redirige antes de que la petición llegue al origen. Se crea así:"
  rojo ""
  rojo "  install -d -m 700 /root/.secrets"
  rojo "  printf 'dns_cloudflare_api_token = <token>\\n' > $CREDENCIALES_CLOUDFLARE"
  rojo "  chmod 600 $CREDENCIALES_CLOUDFLARE"
  rojo ""
  rojo "El token necesita Zone:DNS:Edit y Zone:Zone:Read sobre la zona."
  exit 1
fi

echo "Entorno : $ENTORNO"
echo "Dominio : $DOMINIO"
echo "Validación: por DNS, con las credenciales de Cloudflare."
echo

certbot certonly \
  --dns-cloudflare \
  --dns-cloudflare-credentials "$CREDENCIALES_CLOUDFLARE" \
  --dns-cloudflare-propagation-seconds "${ESPERA_DNS:-30}" \
  --non-interactive \
  --agree-tos \
  --email "$CORREO" \
  --domains "$DOMINIO" \
  --cert-name "$ENTORNO"

ORIGEN="/etc/letsencrypt/live/$ENTORNO"
install -d -m 750 "$CERTS"
install -m 640 "$ORIGEN/fullchain.pem" "$CERTS/sede.crt"
install -m 640 "$ORIGEN/privkey.pem"   "$CERTS/sede.key"
chown root:root "$CERTS"/*

verde "Certificado instalado en $CERTS"
openssl x509 -in "$CERTS/sede.crt" -noout -subject -dates | sed 's/^/  /'

# --- Renovación automática ---------------------------------------------------
# El gancho copia el certificado nuevo donde nginx lo lee y recarga el servicio.
# Sin esto, certbot renueva y la sede sigue sirviendo el certificado viejo hasta
# que alguien lo note —normalmente, cuando caduca.
install -d -m 755 /etc/letsencrypt/renewal-hooks/deploy
cat > /etc/letsencrypt/renewal-hooks/deploy/sede-copiar.sh <<'HOOK'
#!/bin/bash
set -euo pipefail
for entorno in staging produccion; do
  origen="/etc/letsencrypt/live/$entorno"
  destino="/opt/sede/$entorno/certs"
  [ -d "$origen" ] || continue
  install -m 640 "$origen/fullchain.pem" "$destino/sede.crt"
  install -m 640 "$origen/privkey.pem"   "$destino/sede.key"
  docker compose -f "/opt/sede/$entorno/compose.yaml" exec -T nginx nginx -s reload 2>/dev/null || true
done
HOOK
chmod 755 /etc/letsencrypt/renewal-hooks/deploy/sede-copiar.sh

systemctl enable --now certbot.timer >/dev/null 2>&1 || true
verde "Renovación automática activada"

echo
echo "Comprobación:"
echo "  certbot renew --dry-run"
echo
echo "Después, el despliegue ya puede terminar:"
echo "  la canalización vuelve a promover a staging y esta vez responderá."
