#!/bin/bash
#
# Pruebas de comportamiento de `desplegar.sh` (deploy/droplet/despliegue-remoto.sh) de punta a
# punta, con `docker` y `curl` sustituidos por dobles que registran lo que se les pide.
#
#   bash deploy/pruebas/prueba-desplegar-flujo.sh
#
# El guion escribe en `/opt/sede`, `/var/lib/sede` y `/etc/sede` y exige que el archivo de
# secretos sea de `root`, así que la prueba se ejecuta dentro de un contenedor Ubuntu 24.04 (el
# sistema del servidor: mismo bash 5.2.21) y nunca toca el anfitrión. Sin Docker no puede
# correr y lo dice.
#
# Qué protege (medido el 2026-11-24):
#
#   1. CERROJO. Dos despliegues del mismo entorno a la vez se pisan al migrar. El flujo de
#      GitHub los encola, pero no ve uno lanzado a mano en el servidor. Con otro despliegue en
#      curso, el guion debe salir enseguida, con código 1, sin llamar a Docker.
#   2. IMAGEN SIN ENTORNO. Con `set -e`, `preparar_entorno` devolviendo 2 terminaba el guion en
#      esa misma línea (código 2, sin aviso): el `case 2)` que sigue con el entorno del
#      servidor nunca se ejecutaba. Debe avisar y completar el despliegue.
#   3. Y el camino normal no cambia: con el cerrojo libre, el despliegue llega a `/ready` y
#      sale con 0, y deja el cerrojo libre al terminar.
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
IMAGEN="ubuntu:24.04@sha256:562111b88caa79c71391c0c4d3c2114a7d5c35915c20561fd893cb1e07929e6b"

if [ "${DENTRO_DEL_CONTENEDOR:-}" != "1" ]; then
  command -v docker > /dev/null 2>&1 || { echo "Esta prueba necesita Docker." >&2; exit 1; }
  exec docker run --rm -e DENTRO_DEL_CONTENEDOR=1 -v "$RAIZ:/repo:ro" "$IMAGEN" \
    bash /repo/deploy/pruebas/prueba-desplegar-flujo.sh
fi

GUION=/repo/deploy/droplet/despliegue-remoto.sh
fallos=0
ok()  { printf '  \033[32m✓\033[0m %s\n' "$1"; }
mal() { fallos=$((fallos + 1)); printf '  \033[31m✗ %s\033[0m\n' "$1" >&2; }

# --- Dobles de docker y curl ------------------------------------------------------------
mkdir -p /dobles
cat > /dobles/docker <<'DOBLE'
#!/bin/bash
echo "docker $*" >> /tmp/llamadas
case "$1 $2" in
  "image inspect") exit 0 ;;
  "create "*) echo cid-prueba; exit 0 ;;
  "cp "*) exit 1 ;;          # la imagen NO trae /opt/sede-entorno (anterior al ADR-0019)
  "rm "*) exit 0 ;;
esac
[ "$1" = compose ] && exit 0
exit 0
DOBLE
printf '#!/bin/bash\necho "curl $*" >> /tmp/llamadas\nexit 0\n' > /dobles/curl
chmod +x /dobles/docker /dobles/curl
export PATH="/dobles:$PATH"

# --- Servidor de mentira ---------------------------------------------------------------
preparar_servidor() {
  rm -rf /opt/sede /var/lib/sede /etc/sede /tmp/llamadas
  install -d /opt/sede/staging /var/lib/sede/staging /etc/sede
  printf 'APP_KEY=x\n' > /opt/sede/staging/.env
  printf 'services: {}\n' > /opt/sede/staging/compose.yaml
  printf 'APP_KEY=x\n' > /etc/sede/staging.secretos.env
  chmod 600 /etc/sede/staging.secretos.env
  printf 'aaaaaaa' > /var/lib/sede/staging/version
  : > /tmp/llamadas
}
VERSION=0123456789abcdef0123456789abcdef01234567

echo "1. Con otro despliegue en curso, se rechaza sin tocar nada"
preparar_servidor
# Otro despliegue «en curso»: un proceso que tiene tomado el cerrojo del entorno.
( exec 9> /var/lib/sede/staging/.cerrojo; flock 9; sleep 30 ) &
OTRO=$!
sleep 0.5
salida="$(SEDE_ESPERA_SEGUNDOS=5 timeout 20 bash "$GUION" staging "$VERSION" 2>&1)"; codigo=$?
kill "$OTRO" 2> /dev/null; wait "$OTRO" 2> /dev/null
[ "$codigo" -eq 1 ] && ok "sale con código 1" || mal "salió con código $codigo (se esperaba 1)"
printf '%s' "$salida" | grep -q 'en curso' && ok "dice que hay un despliegue en curso" || mal "no dice que hay un despliegue en curso"
if grep -q '^docker' /tmp/llamadas; then mal "llamó a Docker con otro despliegue en curso"; else ok "no llamó a Docker"; fi
[ "$(cat /var/lib/sede/staging/version)" = "aaaaaaa" ] && ok "no tocó el estado (version)" || mal "cambió el estado con otro despliegue en curso"

echo
echo "2. Imagen sin entorno: avisa y sigue con el entorno del servidor"
preparar_servidor
salida="$(SEDE_ESPERA_SEGUNDOS=5 timeout 60 bash "$GUION" staging "$VERSION" 2>&1)"; codigo=$?
[ "$codigo" -eq 0 ] && ok "termina con 0" || mal "terminó con código $codigo (se esperaba 0)"
printf '%s' "$salida" | grep -q 'no trae entorno' && ok "avisa de que la imagen no trae entorno" || mal "no avisó de la imagen sin entorno"
grep -q 'migrar run --rm migrar' /tmp/llamadas && ok "migró" || mal "no llegó a migrar"
grep -q '/ready' /tmp/llamadas && ok "esperó a /ready" || mal "no llegó a /ready"
[ "$(cat /var/lib/sede/staging/version)" = "$VERSION" ] && ok "anotó la versión desplegada" || mal "no anotó la versión"

echo
echo "3. Al terminar, el cerrojo queda libre"
if flock -n /var/lib/sede/staging/.cerrojo true 2> /dev/null; then ok "el cerrojo está libre"; else mal "el cerrojo quedó tomado"; fi

echo
if [ "$fallos" -gt 0 ]; then
  printf '\033[31m%d comprobación(es) fallaron.\033[0m\n' "$fallos" >&2
  exit 1
fi
printf '\033[32mdesplegar.sh: cerrojo y caminos de entorno en orden.\033[0m\n'
