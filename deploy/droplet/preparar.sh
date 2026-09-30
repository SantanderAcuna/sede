#!/bin/bash
#
# Prepara el servidor una sola vez.
#
# Es idempotente: volver a ejecutarlo revisa que el servidor siga como debe, en
# lugar de duplicar lo que ya está hecho. Eso importa porque un servidor se
# reconstruye, y un guion que sólo funciona en una máquina virgen obliga a
# recordar en qué orden se hizo todo.
#
# NO instala ni configura los contenedores: eso lo hace el despliegue.
#
#   curl -fsSL <url>/preparar.sh | bash
#   # o
#   ./preparar.sh
#
set -euo pipefail

USUARIO_ADMIN="${USUARIO_ADMIN:-ops}"
USUARIO_DESPLIEGUE="${USUARIO_DESPLIEGUE:-deploy}"
BASE_DESPLIEGUE=/opt/sede

rojo()  { printf '\033[31m%s\033[0m\n' "$*"; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }
paso()  { printf '\n\033[1m%s\033[0m\n' "$*"; }

if [ "$(id -u)" -ne 0 ]; then
  rojo "Este guion se ejecuta como root."
  exit 1
fi

paso "1. Paquetes base"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq \
  ca-certificates curl gnupg lsb-release \
  ufw fail2ban unattended-upgrades apt-listchanges \
  auditd audispd-plugins aide clamav certbot

paso "2. Docker, desde el repositorio oficial"
if ! command -v docker >/dev/null 2>&1; then
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  chmod a+r /etc/apt/keyrings/docker.asc
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
    > /etc/apt/sources.list.d/docker.list
  apt-get update -qq
  apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi
verde "  $(docker --version)"

paso "3. Demonio de contenedores"
install -d -m 755 /etc/docker
if [ ! -f /etc/docker/daemon.json ]; then
  cat > /etc/docker/daemon.json <<'JSON'
{
  "log-driver": "json-file",
  "log-opts": { "max-size": "10m", "max-file": "3" },
  "live-restore": true,
  "userland-proxy": false,
  "no-new-privileges": true,
  "default-address-pools": [{ "base": "172.30.0.0/16", "size": 24 }]
}
JSON
fi
systemctl enable --now docker >/dev/null
systemctl restart docker
verde "  demonio en marcha"

paso "4. Usuarios"
for grupo in sede-ssh "$USUARIO_ADMIN" "$USUARIO_DESPLIEGUE"; do
  getent group "$grupo" >/dev/null || groupadd --system "$grupo"
done

id "$USUARIO_ADMIN" >/dev/null 2>&1 || \
  useradd -m -s /bin/bash -g "$USUARIO_ADMIN" -G sudo,sede-ssh,adm,systemd-journal "$USUARIO_ADMIN"

# La cuenta de despliegue no tiene contraseña utilizable: entra sólo por llave, y
# esa llave sólo puede ejecutar el guion de despliegue.
id "$USUARIO_DESPLIEGUE" >/dev/null 2>&1 || \
  useradd -m -s /bin/bash -g "$USUARIO_DESPLIEGUE" -G sede-ssh,docker "$USUARIO_DESPLIEGUE"
passwd -l "$USUARIO_DESPLIEGUE" >/dev/null 2>&1 || true
usermod -aG docker "$USUARIO_DESPLIEGUE" 2>/dev/null || true
verde "  $USUARIO_ADMIN y $USUARIO_DESPLIEGUE"

paso "5. Reglas de sudo"
# `ops` eleva con contraseña. `deploy` no tiene contraseña, así que sólo puede
# ejecutar el guion de despliegue: darle `docker` en crudo sería darle root.
install -m 440 /dev/stdin /etc/sudoers.d/10-ops <<SUDO
$USUARIO_ADMIN ALL=(ALL:ALL) ALL
SUDO
install -m 440 /dev/stdin /etc/sudoers.d/20-deploy <<SUDO
$USUARIO_DESPLIEGUE ALL=(root) NOPASSWD: $BASE_DESPLIEGUE/desplegar.sh, /usr/local/bin/desplegar.sh
SUDO
visudo -c >/dev/null

paso "6. Cortafuegos del host"
ufw --force reset >/dev/null 2>&1 || true
ufw default deny incoming  >/dev/null
ufw default allow outgoing >/dev/null
ufw default deny routed   >/dev/null
ufw limit 22/tcp comment 'SSH con limite de tasa' >/dev/null
ufw allow 80/tcp  comment 'HTTP'  >/dev/null
ufw allow 443/tcp comment 'HTTPS' >/dev/null
ufw logging medium >/dev/null
ufw --force enable >/dev/null
verde "  $(ufw status | head -1)"

paso "7. Guarda contra el bypass de Docker"
# Docker escribe sus propias reglas y se salta UFW. Esta cadena es el único
# punto donde se puede intervenir antes de que actúen. Se permite lo que SÍ debe
# llegar y se descarta el resto: así un puerto publicado por descuido no queda
# expuesto.
cat > /usr/local/sbin/sede-reglas-docker.sh <<'REGLAS'
#!/bin/bash
#
# Reglas de la cadena DOCKER-USER.
#
# Docker escribe sus propias reglas y se salta UFW. Esta cadena es el único
# punto donde se puede intervenir antes de que actúen.
#
# El orden importa, y la primera regla es la que más cuesta descubrir:
#
#  1. Las RESPUESTAS que vuelven de Internet entran por `eth0` y van al
#     contenedor. Sin esta excepción el contenedor puede enviar pero nunca
#     recibe la respuesta: parece que no tiene salida a Internet, y sí la tiene.
#     El síntoma engaña porque la resolución de nombres sigue funcionando —la
#     atiende el propio Docker— y todo lo demás falla.
#  2. Lo que debe llegar de fuera a los servicios publicados.
#  3. Todo lo demás que entre por `eth0` hacia un contenedor se descarta: así un
#     puerto publicado por descuido no queda expuesto.
set -euo pipefail

iptables -F DOCKER-USER
iptables -A DOCKER-USER -m conntrack --ctstate ESTABLISHED,RELATED -j RETURN
iptables -A DOCKER-USER -i eth0 -p tcp --dport 80  -j RETURN
iptables -A DOCKER-USER -i eth0 -p tcp --dport 443 -j RETURN
iptables -A DOCKER-USER -i eth0 -j DROP
REGLAS
chmod 750 /usr/local/sbin/sede-reglas-docker.sh

cat > /etc/systemd/system/sede-reglas-docker.service <<'UNIT'
[Unit]
Description=Reglas de la cadena DOCKER-USER para la Sede Electronica
After=docker.service
Requires=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
ExecStart=/usr/local/sbin/sede-reglas-docker.sh

[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload
systemctl enable --now sede-reglas-docker.service >/dev/null
verde "  reglas aplicadas y persistentes"

paso "8. Intercambio"
if [ ! -f /swapfile ]; then
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile >/dev/null
  swapon /swapfile
  grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
  echo 'vm.swappiness=10' > /etc/sysctl.d/99-sede-swap.conf
  sysctl -w vm.swappiness=10 >/dev/null
fi
verde "  $(swapon --show=SIZE --noheadings | xargs)"

paso "9. Actualizaciones automáticas"
cat > /etc/apt/apt.conf.d/51-sede-unattended <<'APT'
Unattended-Upgrade::Allowed-Origins {
    "${distro_id}:${distro_codename}";
    "${distro_id}:${distro_codename}-security";
    "${distro_id}ESMApps:${distro_codename}-apps-security";
    "${distro_id}ESM:${distro_codename}-infra-security";
};
// El núcleo y la libc no se actualizan solos: un reinicio inesperado en una sede
// electrónica es una caída, no una mejora.
Unattended-Upgrade::Package-Blacklist { "linux-"; "linux-image-"; "linux-headers-"; "libc6"; "libc6-dev"; };
Unattended-Upgrade::Automatic-Reboot "false";
Unattended-Upgrade::Remove-Unused-Kernel-Packages "true";
APT
systemctl enable --now unattended-upgrades >/dev/null

paso "10. Directorios del despliegue"
mkdir -p "$BASE_DESPLIEGUE"
chown root:root "$BASE_DESPLIEGUE"
chmod 755 "$BASE_DESPLIEGUE"

verde ""
verde "Servidor preparado."
echo
echo "Lo que falta, y depende de ti:"
echo "  · Copiar la llave pública de despliegue a /home/$USUARIO_DESPLIEGUE/.ssh/authorized_keys"
echo "    con la restricción command=\"$BASE_DESPLIEGUE/desplegar.sh\"."
echo "  · Emitir el certificado con certificado.sh, antes del primer despliegue."
echo "  · Reiniciar si el sistema lo pide: $( [ -f /var/run/reboot-required ] && echo 'SÍ, hay kernel nuevo' || echo 'no hace falta' )"
