#!/bin/bash
#
# Emite y renueva el certificado del origen.
#
# Se emite ANTES del primer despliegue, y no después: el punto de entrada no
# arranca sin certificado, porque su configuración TLS lo referencia. Un nginx
# que arranca sin certificado y luego se "arregla" es una ventana en la que la
# sede atiende sin cifrar.
#
#   ./certificado.sh staging staging.santamarta.gov.co admin@entidad.gov.co
#   ./certificado.sh produccion sede.santamarta.gov.co admin@entidad.gov.co
#
set -euo pipefail

ENTORNO="${1:?Falta el entorno: staging o produccion}"
DOMINIO="${2:?Falta el dominio}"
CORREO="${3:?Falta el correo de contacto que exige la autoridad certificadora}"

BASE="/opt/sede/$ENTORNO"
CERTS="$BASE/certs"

rojo()  { printf '\033[31m%s\033[0m\n' "$*"; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }

if [ "$(id -u)" -ne 0 ]; then rojo "Se ejecuta como root."; exit 1; fi

command -v certbot >/dev/null 2>&1 || {
  rojo "Falta certbot. Instálalo con: apt-get install -y certbot"
  exit 1
}

echo "Entorno : $ENTORNO"
echo "Dominio : $DOMINIO"
echo

# El desafío exige que el puerto 80 esté libre. Si la pila está levantada, se
# detiene sólo el punto de entrada unos segundos.
if docker compose -f "$BASE/compose.yaml" ps --quiet nginx 2>/dev/null | grep -q .; then
  echo "Deteniendo el punto de entrada para atender el desafío…"
  docker compose -f "$BASE/compose.yaml" stop nginx
  REINICIAR_NGINX=1
else
  REINICIAR_NGINX=0
fi

certbot certonly \
  --standalone \
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

if [ "$REINICIAR_NGINX" = "1" ]; then
  docker compose -f "$BASE/compose.yaml" start nginx
  verde "Punto de entrada reiniciado"
fi

echo
echo "Comprobación:"
echo "  certbot renew --dry-run"
