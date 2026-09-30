#!/bin/bash
#
# Prepara el servidor de la sede desde cero.
#
# Es idempotente: volver a ejecutarlo revisa que el servidor siga como debe, en
# lugar de duplicar lo que ya está hecho. Eso importa porque un servidor se
# reconstruye, y un guion que sólo funciona en una máquina virgen obliga a
# recordar en qué orden se hizo todo.
#
# NO cierra el acceso de `root` por SSH: eso se hace después, cuando esté
# verificado que la cuenta de administración entra y eleva. Un cierre prematuro
# deja el servidor sin nadie que pueda arreglarlo.
#
#   ./preparar.sh
#
set -euo pipefail

USUARIO_ADMIN="${USUARIO_ADMIN:-ops}"
USUARIO_DESPLIEGUE="${USUARIO_DESPLIEGUE:-deploy}"
LLAVE_ADMIN="${LLAVE_ADMIN:-}"          # ruta a la llave PÚBLICA del titular
IP_ADMIN="${IP_ADMIN:-}"                # dirección del administrador, para la lista blanca de fail2ban
BASE=/opt/sede

rojo()   { printf '\033[31m%s\033[0m\n' "$*"; }
verde()    { printf '\033[32m%s\033[0m\n' "$*"; }
aviso()  { printf '\033[33m%s\033[0m\n' "$*"; }
paso()   { printf '\n\033[1m%s\033[0m\n' "$*"; }

[ "$(id -u)" -eq 0 ] || { rojo "Se ejecuta como root."; exit 1; }

export DEBIAN_FRONTEND=noninteractive
CODENAME="$(. /etc/os-release && echo "$VERSION_CODENAME")"

paso "1. Paquetes base"
apt-get update -qq
apt-get install -y -qq \
  ca-certificates curl gnupg lsb-release \
  ufw fail2ban unattended-upgrades apt-listchanges \
  auditd audispd-plugins aide aide-common clamav certbot \
  libpam-pwquality chrony
verde "  $(. /etc/os-release && echo "$PRETTY_NAME")"

paso "2. Docker, desde el repositorio oficial"
if ! command -v docker >/dev/null 2>&1; then
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  chmod a+r /etc/apt/keyrings/docker.asc
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $CODENAME stable" \
    > /etc/apt/sources.list.d/docker.list
  apt-get update -qq
  apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi
systemctl enable --now docker >/dev/null 2>&1
verde "  $(docker --version) · compose $(docker compose version --short)"

paso "3. Demonio de contenedores"
install -d -m 755 /etc/docker
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
systemctl restart docker

paso "4. Usuarios"
for grupo in sede-ssh "$USUARIO_ADMIN" "$USUARIO_DESPLIEGUE"; do
  getent group "$grupo" >/dev/null || groupadd --system "$grupo"
done

id "$USUARIO_ADMIN" >/dev/null 2>&1 || \
  useradd -m -s /bin/bash -g "$USUARIO_ADMIN" -G sudo,sede-ssh,adm,systemd-journal "$USUARIO_ADMIN"

id "$USUARIO_DESPLIEGUE" >/dev/null 2>&1 || \
  useradd -m -s /bin/bash -g "$USUARIO_DESPLIEGUE" -G sede-ssh,docker "$USUARIO_DESPLIEGUE"
usermod -aG docker "$USUARIO_DESPLIEGUE" 2>/dev/null || true

# La contraseña de administración se ASIGNA, no sólo se guarda. Guardarla sin
# asignarla deja la cuenta bloqueada y sin forma de elevar privilegios.
if [ -n "${PASSWORD_ADMIN:-}" ]; then
  printf '%s:%s\n' "$USUARIO_ADMIN" "$PASSWORD_ADMIN" | chpasswd
  chage -M 90 -m 1 -W 14 "$USUARIO_ADMIN"
  verde "  contraseña de $USUARIO_ADMIN asignada"
else
  # No basta con mirar si la variable está definida: lo que importa es si la
  # cuenta puede elevar HOY. Avisar sin comprobarlo produce avisos falsos, y un
  # aviso falso entrena a ignorar justo el aviso que no se puede ignorar.
  case "$(passwd -S "$USUARIO_ADMIN" 2>/dev/null | awk '{print $2}')" in
    P) verde "  $USUARIO_ADMIN ya tiene contraseña utilizable (no se toca)" ;;
    *) rojo  "  $USUARIO_ADMIN NO tiene contraseña utilizable: no podría elevar."
       rojo  "  Asígnala antes de cerrar el acceso de root, por ejemplo:"
       rojo  "    PASSWORD_ADMIN='...' $(basename "$0")"
       if [ "${SEGUIR_SIN_PASSWORD:-0}" != "1" ]; then
         rojo  "  Si vas a asignarla después, repite con SEGUIR_SIN_PASSWORD=1."
         exit 1
       fi ;;
  esac
fi

if [ -n "$LLAVE_ADMIN" ] && [ -f "$LLAVE_ADMIN" ]; then
  install -d -m 700 -o "$USUARIO_ADMIN" -g "$USUARIO_ADMIN" "/home/$USUARIO_ADMIN/.ssh"
  cat "$LLAVE_ADMIN" > "/home/$USUARIO_ADMIN/.ssh/authorized_keys"
  chown "$USUARIO_ADMIN:$USUARIO_ADMIN" "/home/$USUARIO_ADMIN/.ssh/authorized_keys"
  chmod 600 "/home/$USUARIO_ADMIN/.ssh/authorized_keys"
  verde "  llave de $USUARIO_ADMIN instalada"
fi

paso "5. Reglas de sudo"
# `ops` eleva con contraseña. `deploy` no tiene contraseña utilizable, así que
# sólo puede ejecutar el guion de despliegue: darle `docker` en crudo sería
# darle root con otro nombre.
cat > /etc/sudoers.d/10-ops <<SUDO
$USUARIO_ADMIN ALL=(ALL:ALL) ALL
SUDO
cat > /etc/sudoers.d/20-deploy <<SUDO
$USUARIO_DESPLIEGUE ALL=(root) NOPASSWD: /usr/local/bin/desplegar.sh, $BASE/desplegar.sh
SUDO
chmod 440 /etc/sudoers.d/10-ops /etc/sudoers.d/20-deploy
visudo -c >/dev/null
verde "  reglas válidas"

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

paso "7. Fail2Ban"

# El guion instalaba fail2ban pero NO lo configuraba, así que quedaba con los
# valores por defecto de Ubuntu. Un endurecimiento que se apoya en un valor por
# defecto no es un endurecimiento: cambia con la versión del paquete, y nadie se
# entera. Aquí se fija, y se deja por escrito.
#
# Lo que se corrige, y por qué importa:
#
#   · Sin lista blanca, un error de tecleo del administrador basta para dejarlo
#     fuera. Pasó durante el aprovisionamiento: dos intentos contra `root`, que
#     ya está cerrado y por tanto siempre fallan, activaron la cárcel y dejaron
#     la administración sin acceso por SSH. La propia protección se convirtió en
#     el incidente, y recuperarse exige la consola del proveedor.
#   · Un `bantime` de un día castiga igual al que teclea mal y al que ataca. El
#     que ataca hace miles de intentos, así que le basta con una hora para
#     quedar frenado; el que teclea mal no debería perder la mañana.
#   · Faltaba la cárcel de reincidentes, que es la que encarece insistir.
# La dirección del administrador se valida antes de escribirla: si se colara algo
# que no es una dirección, la lista blanca dejaría de significar lo que dice.
if [ -n "$IP_ADMIN" ]; then
  case "$IP_ADMIN" in
    *[!0-9a-fA-F:./]*) rojo "  IP_ADMIN no parece una dirección: '$IP_ADMIN'"; exit 1 ;;
  esac
fi

install -d -m 755 /etc/fail2ban/jail.d
# El heredoc va ENTRECOMILLADO y la dirección entra por un marcador sustituido
# después. Un heredoc sin comillas expande los acentos graves como sustitución de
# órdenes: en el comentario de la cárcel `sshd` había dos palabras entre acentos
# graves y bash intentó ejecutarlas, dejando el comentario mutilado en el archivo.
cat > /etc/fail2ban/jail.d/10-sede.local <<'F2B'
[DEFAULT]
# La lista blanca siempre incluye la propia máquina. La dirección del
# administrador se añade sólo si se pasa IP_ADMIN: una dirección doméstica
# cambia, y una lista blanca obsoleta no protege de nada mientras da la falsa
# impresión de que sí.
ignoreip = 127.0.0.1/8 ::1 %%IP_ADMIN%%

# Cada ban dura el doble que el anterior. Insistir sale caro, y el que se
# equivoca una vez no paga por el que ataca.
bantime.increment = true
bantime.factor = 2
bantime.maxtime = 4w

bantime  = 1h
findtime = 10m
maxretry = 5

# Se registra en su propio archivo: la cárcel de reincidentes lo necesita, y
# mezclarlo con el registro del sistema hace que la rotación se lleve por delante
# el rastro que hace falta para banear al que insiste.
logtarget = /var/log/fail2ban.log

[sshd]
enabled = true
port    = ssh
backend = systemd
# `normal` y no `aggressive`: el segundo cuenta como fallo cosas que también
# produce un cliente legítimo, y un falso positivo aquí echa a quien administra.
mode    = normal

[recidive]
enabled  = true
logpath  = /var/log/fail2ban.log
banaction = %(banaction_allports)s
bantime  = 4w
findtime = 1d
maxretry = 3
F2B
sed -i "s|%%IP_ADMIN%%|$IP_ADMIN|" /etc/fail2ban/jail.d/10-sede.local
if grep -q '%%IP_ADMIN%%' /etc/fail2ban/jail.d/10-sede.local; then
  rojo "  quedó un marcador sin sustituir — se retira el archivo"
  rm -f /etc/fail2ban/jail.d/10-sede.local
  exit 1
fi
chmod 644 /etc/fail2ban/jail.d/10-sede.local

# fail2ban no arranca si la configuración tiene un error, así que se comprueba
# antes de reiniciar: dejar el servicio caído por una errata deja el servidor
# sin la protección y sin nadie mirando.
if fail2ban-client -t >/dev/null 2>&1; then
  systemctl enable fail2ban >/dev/null 2>&1
  systemctl restart fail2ban
  verde "  cárceles sshd y recidive activas; ban inicial de 1 h que se duplica"
  [ -n "$IP_ADMIN" ] && verde "  lista blanca incluye $IP_ADMIN" || aviso "  sin lista blanca: pasa IP_ADMIN=<tu dirección> para añadirla"
else
  rojo "  la configuración de fail2ban NO es válida — se retira"
  rm -f /etc/fail2ban/jail.d/10-sede.local
  systemctl enable --now fail2ban >/dev/null 2>&1 || true
  exit 1
fi

paso "8. Guarda contra el bypass de Docker"
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
#     recibe contestación: parece que no tiene salida a Internet, y sí la tiene.
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
systemctl enable --now sede-reglas-docker.service >/dev/null 2>&1
/usr/local/sbin/sede-reglas-docker.sh
verde "  aplicadas y persistentes"

paso "9. Intercambio"
if [ ! -f /swapfile ]; then
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile >/dev/null
  grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
  echo 'vm.swappiness=10' > /etc/sysctl.d/99-sede-swap.conf
fi
swapon /swapfile 2>/dev/null || true
sysctl -w vm.swappiness=10 >/dev/null
verde "  $(swapon --show=SIZE --noheadings | xargs)"

paso "10. Actualizaciones automáticas"
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
Unattended-Upgrade::AutoFixInterruptedDpkg "true";
APT
systemctl enable --now unattended-upgrades >/dev/null 2>&1
verde "  activas"

paso "11. Registro del sistema"
install -d -m 755 /etc/systemd/journald.conf.d
cat > /etc/systemd/journald.conf.d/99-sede.conf <<'JOURNAL'
[Journal]
Storage=persistent
SystemMaxUse=2G
SystemKeepFree=1G
SystemMaxFileSize=128M
MaxRetentionSec=30day
MaxFileSec=1week
Compress=yes
ForwardToSyslog=no
RateLimitIntervalSec=30s
RateLimitBurst=10000
JOURNAL
systemctl restart systemd-journald
verde "  persistente, 2 GB, retención de 30 días"

paso "12. Auditoría del sistema"
cat > /etc/audit/rules.d/sede.rules <<'RULES'
-D
-b 8192
-f 1
-r 0
-w /etc/passwd   -p wa -k identidad
-w /etc/shadow   -p wa -k identidad
-w /etc/group    -p wa -k identidad
-w /etc/gshadow  -p wa -k identidad
-w /etc/sudoers  -p wa -k sudoers
-w /etc/sudoers.d/ -p wa -k sudoers
-w /etc/ssh/sshd_config      -p wa -k sshd
-w /etc/ssh/sshd_config.d/   -p wa -k sshd
-w /root/.ssh/authorized_keys -p wa -k claves
-a always,exit -F arch=b64 -S crontab -k cron
-w /etc/cron.d/      -p wa -k cron
-w /etc/crontab      -p wa -k cron
-w /var/spool/cron/  -p wa -k cron
-a always,exit -F arch=b64 -S setuid -S setgid -S setreuid -S setregid -k escalada
-a always,exit -F arch=b64 -S execve -F euid=0 -k root_exec
-a always,exit -F arch=b64 -S init_module -S delete_module -k kernel_modules
-a always,exit -F arch=b64 -S sethostname -S setdomainname -k red
-w /etc/hosts -p wa -k red
-a always,exit -F arch=b64 -S unlink -S unlinkat -S rename -S renameat -k borrado
-a always,exit -F arch=b64 -S chmod -S fchmod -S fchmodat -k permisos
RULES
augenrules --load >/dev/null 2>&1 || true
systemctl enable --now auditd >/dev/null 2>&1
verde "  $(auditctl -l 2>/dev/null | wc -l) reglas cargadas"

paso "13. Política de contraseñas"
cat > /etc/security/pwquality.conf <<'PWQ'
# Longitud 15: el capítulo 01 de la guía fija 14, el expediente recomienda 15 y
# el mínimo legal es 8. Quince satisface a los tres.
minlen = 15
minclass = 4
maxrepeat = 3
gecoscheck = 1
dictcheck = 1
enforcing = 1
PWQ
if ! grep -q pam_pwquality /etc/pam.d/common-password; then
  cp -a /etc/pam.d/common-password /root/common-password.original
  python3 - <<'PY'
import io, re
p='/etc/pam.d/common-password'; s=io.open(p,encoding='utf-8').read()
salida=[]
for l in s.split('\n'):
    if re.match(r'^password\s+\[success=1 default=ignore\]\s+pam_unix\.so', l):
        salida.append('password\trequisite\t\t\tpam_pwquality.so retry=3')
        # `required` y NO un salto: el control [success=1] salta el módulo
        # siguiente, y el siguiente es pam_unix, que es el que CAMBIA la
        # contraseña. Con ese control nadie podría cambiarla.
        salida.append('password\trequired\t\t\tpam_pwhistory.so remember=5 use_authtok')
    salida.append(l)
io.open(p,'w',encoding='utf-8').write('\n'.join(salida))
PY
fi
verde "  mínimo 15, 4 clases, historial de 5"

paso "14. Integridad de archivos"
install -d -m 755 /etc/aide/aide.conf.d
cat > /etc/aide/aide.conf.d/99-sede.conf <<'AIDE'
!/var/log
!/var/cache
!/var/lib/apt
!/var/lib/dpkg
!/run
!/proc
!/sys
!/tmp
!/dev
!/var/lib/docker
!/swapfile
/app    Full
/etc    Full
/bin    Full
/sbin   Full
/usr/bin Full
/usr/sbin Full
/boot   Full
AIDE
if [ ! -f /var/lib/aide/aide.db ]; then
  aviso "  generando la base (tarda unos minutos, se hace en segundo plano)"
  nohup sh -c 'aideinit -y -f >/dev/null 2>&1 && mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db' >/dev/null 2>&1 &
fi

paso "15. Antivirus"
if [ ! -s /var/lib/clamav/daily.cvd ] && [ ! -s /var/lib/clamav/daily.cld ]; then
  aviso "  descargando firmas (tarda unos minutos, en segundo plano)"
  nohup freshclam --quiet >/dev/null 2>&1 &
fi
cat > /usr/local/sbin/sede-antivirus.sh <<'SCRIPT'
#!/bin/bash
# Análisis antivirus programado.
#
# NO se usa el demonio: mantener el motor cargado cuesta más de lo que aporta en
# un servidor que no recibe archivos sin filtrar. Se analiza a diario, con la
# prioridad más baja, para no competir con la sede por CPU ni por disco.
set -euo pipefail
REGISTRO=/var/log/sede-antivirus.log
FECHA=$(date --iso-8601=seconds)
ionice -c3 nice -n 19 clamscan --recursive --infected --cross-fs=no \
  --exclude-dir='^/(proc|sys|dev|run)' --exclude-dir='^/var/lib/docker' \
  --exclude-dir='^/var/lib/clamav' /var/www /home /tmp /etc 2>/dev/null > /tmp/av.txt || true
INFECTADOS=$(grep -c "FOUND$" /tmp/av.txt 2>/dev/null || echo 0)
{ echo "[$FECHA] análisis terminado · infectados: $INFECTADOS"
  [ "$INFECTADOS" -gt 0 ] && grep "FOUND$" /tmp/av.txt; } >> "$REGISTRO"
logger -t sede-antivirus "análisis terminado: $INFECTADOS hallazgos"
rm -f /tmp/av.txt
SCRIPT
chmod 750 /usr/local/sbin/sede-antivirus.sh
echo '40 3 * * * root /usr/local/sbin/sede-antivirus.sh' > /etc/cron.d/sede-antivirus
chmod 644 /etc/cron.d/sede-antivirus
systemctl enable --now clamav-freshclam >/dev/null 2>&1
verde "  análisis diario a las 03:40, sin demonio"

paso "16. Endurecimiento de SSH"

# Los conjuntos de algoritmos NO se copian de la guía: se verifican contra lo que
# ESTA versión soporta de verdad. Una lista escrita para otra versión puede dejar
# al servidor PEOR que su valor por defecto — por ejemplo reduciendo los
# intercambios de claves a uno solo, que es un punto único de fallo.
#
# KexAlgorithms, HostKeyAlgorithms y PubkeyAcceptedAlgorithms NO se fijan a
# propósito: el valor por defecto de OpenSSH ya prefiere el híbrido post-cuántico
# (sntrup761x25519-sha512@openssh.com) y ya excluye ssh-rsa con SHA-1. Fijarlos a
# mano sólo puede empeorar la negociación. Compruébalo con:
#   sshd -T -f /dev/null | grep -E '^(kexalgorithms|hostkeyalgorithms)'
CONF_SSH=/etc/ssh/sshd_config.d/00-sede-hardening.conf

soportado() { ssh -Q "$1" 2>/dev/null | grep -qxF "$2"; }
filtrar() {
  local tipo="$1"; shift
  local lista="" a
  for a in "$@"; do
    if soportado "$tipo" "$a"; then
      lista="${lista:+$lista,}$a"
    else
      # El aviso va a stderr A PROPÓSITO: filtrar() se invoca dentro de "$( )",
      # así que cualquier cosa escrita en stdout se colaría dentro de la lista
      # de algoritmos y produciría una directiva corrupta.
      aviso "  se omite $a: esta versión de OpenSSH no lo ofrece" >&2
    fi
  done
  # tr -cd: la lista sólo puede contener caracteres válidos de un nombre de
  # algoritmo. Así nunca puede inyectar nada en la orden sed que la sustituye.
  printf '%s' "$lista" | tr -cd 'A-Za-z0-9@.,_-'
}

CIPHERS="$(filtrar cipher chacha20-poly1305@openssh.com aes256-gcm@openssh.com aes128-gcm@openssh.com)"
MACS="$(filtrar mac hmac-sha2-512-etm@openssh.com hmac-sha2-256-etm@openssh.com umac-128-etm@openssh.com)"
# Se sanea a caracteres seguros: este valor sólo alimenta un COMENTARIO, y no
# puede permitirse que un salto de línea o un "|" tumbe el endurecimiento entero
# rompiendo la orden sed de más abajo.
# Volver a ejecutar este guion NO debe reabrir el acceso de root. `cerrar-root.sh`
# se ejecuta DESPUÉS, y cambia esta misma directiva a `no`; si esta segunda
# ejecución la reescribiera, reabriría root sin que nadie lo pidiera y sin avisar.
# Un guion idempotente que deshace el trabajo posterior no es idempotente: es
# peligroso, porque se ejecuta justamente cuando algo va mal.
PERMITROOT="prohibit-password"
if [ -f "$CONF_SSH" ] && grep -qx 'PermitRootLogin no' "$CONF_SSH"; then
  PERMITROOT="no"
  aviso "  el acceso de root ya estaba cerrado: se conserva cerrado"
fi
VERSION_OPENSSH="$(ssh -V 2>&1 | head -1 | cut -d, -f1 | tr -cd 'A-Za-z0-9._+ -')"

# El heredoc va ENTRECOMILLADO y los valores dinámicos entran por marcadores
# sustituidos después. Un heredoc SIN comillas expande los acentos graves como
# sustitución de comandos: basta escribir una palabra entre acentos graves en un
# comentario para que bash intente ejecutarla.
cat > "$CONF_SSH" <<'SSHD'
# Endurecimiento SSH — Sede Electrónica
# Verificado contra %%OPENSSH%%
#
# Sólo se endurece lo que no puede empeorar la negociación. KexAlgorithms,
# HostKeyAlgorithms y PubkeyAcceptedAlgorithms se dejan en su valor por defecto:
# ya prefieren el híbrido post-cuántico y ya excluyen ssh-rsa con SHA-1.

Ciphers %%CIPHERS%%
MACs %%MACS%%

PubkeyAuthentication yes
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitEmptyPasswords no
AuthenticationMethods publickey
UsePAM yes

LoginGraceTime 30
MaxAuthTries 3
MaxSessions 4
MaxStartups 3:50:10
ClientAliveInterval 300
ClientAliveCountMax 2

AllowAgentForwarding no
AllowTcpForwarding no
AllowStreamLocalForwarding no
GatewayPorts no
X11Forwarding no
PermitUserEnvironment no
PermitTTY yes

# `root` sigue permitido POR LLAVE en este paso. Se cierra al final, cuando esté
# verificado que la cuenta de administración entra y eleva.
PermitRootLogin %%PERMITROOT%%
StrictModes yes
UseDNS no
Compression no
TCPKeepAlive no
PrintMotd no
DebianBanner no
SSHD
sed -i "s|%%OPENSSH%%|$VERSION_OPENSSH|; s|%%CIPHERS%%|$CIPHERS|; s|%%MACS%%|$MACS|; s|%%PERMITROOT%%|$PERMITROOT|" "$CONF_SSH"
# Un conjunto que quedó vacío se retira: una directiva sin valor es un archivo roto.
sed -i -E '/^(Ciphers|MACs)[[:space:]]*$/d' "$CONF_SSH"
if grep -q '%%[A-Z_]*%%' "$CONF_SSH"; then
  rojo "  quedó un marcador sin sustituir — se retira el archivo"
  rm -f "$CONF_SSH"
  exit 1
fi
chmod 644 "$CONF_SSH"

if sshd -t 2>&1; then
  systemctl reload ssh 2>/dev/null || systemctl restart ssh
  verde "  aplicado y recargado"
else
  rojo "  sintaxis INVÁLIDA — se retira el archivo"
  rm -f "$CONF_SSH"
  exit 1
fi

paso "17. Directorios del despliegue"
install -d -m 755 "$BASE"

verde ""
verde "Servidor preparado."
echo
echo "Comprobación:"
printf "  ssh          : %s\n" "$(systemctl is-active ssh)"
printf "  ufw          : %s\n" "$(ufw status | head -1 | cut -d: -f2 | xargs)"
printf "  fail2ban     : %s\n" "$(systemctl is-active fail2ban)"
printf "  auditd       : %s\n" "$(systemctl is-active auditd)"
printf "  docker       : %s\n" "$(systemctl is-active docker)"
printf "  intercambio  : %s\n" "$(swapon --show=SIZE --noheadings | xargs)"
# El estado post-cuántico se muestra porque es lo PRIMERO que se pierde cuando
# alguien fija KexAlgorithms a mano con una lista copiada de otra versión.
KEX_EFECTIVO="$(sshd -T 2>/dev/null | sed -n 's/^kexalgorithms //p')"
case "$KEX_EFECTIVO" in
  sntrup761x25519-sha512@openssh.com,*|mlkem768x25519-sha256,*)
    printf "  intercambio PQ: preferido (%s)\n" "${KEX_EFECTIVO%%,*}" ;;
  "")
    printf "  intercambio PQ: no se pudo leer la configuración\n" ;;
  *)
    printf "  intercambio PQ: NO preferido (primero: %s)\n" "${KEX_EFECTIVO%%,*}" ;;
esac
echo
echo "Lo que falta, y NO lo hace este guion:"
echo "  · Cerrar el acceso de root, DESPUÉS de verificar que $USUARIO_ADMIN entra y eleva."
echo "  · Copiar la llave pública de despliegue y restringirla al guion de despliegue."
echo "  · Emitir el certificado antes del primer despliegue."
echo "  · Reiniciar si el sistema lo pide."
echo
echo "Si una cárcel te deja fuera, la orden de recuperación es:"
echo "  fail2ban-client set sshd unbanip <tu-dirección>"
echo "Desde fuera del servidor hace falta la consola del proveedor."
echo "Si NO pasaste IP_ADMIN, añade tu dirección a ignoreip y reinicia fail2ban."
