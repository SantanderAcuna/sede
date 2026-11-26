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
  "images "*) cat /tmp/imagenes 2> /dev/null; exit 0 ;;
  "rmi "*) [ -f /tmp/rmi_falla ] && exit 1; exit 0 ;;
esac
[ "$1" = compose ] && exit 0
exit 0
DOBLE
printf '#!/bin/bash\necho "curl $*" >> /tmp/llamadas\n[ -f /tmp/curl_falla ] && exit 1\nexit 0\n' > /dobles/curl
chmod +x /dobles/docker /dobles/curl
export PATH="/dobles:$PATH"

# --- Servidor de mentira ---------------------------------------------------------------
preparar_servidor() {
  rm -rf /opt/sede /var/lib/sede /etc/sede /tmp/llamadas /tmp/imagenes /tmp/rmi_falla /tmp/curl_falla
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
echo "4. Limpieza de imágenes: deja la actual y la anterior de CADA entorno y nada más"
preparar_servidor
# Otro entorno en el mismo servidor: sus dos versiones también se conservan.
install -d /var/lib/sede/produccion
printf 'ddddddd' > /var/lib/sede/produccion/version
printf 'eeeeeee' > /var/lib/sede/produccion/version.anterior
R=ghcr.io/santanderacuna
{
  for r in sede-app sede-nginx sede-sitio; do
    for t in "$VERSION" aaaaaaa bbbbbbb ccccccc ddddddd eeeeeee; do echo "$R/$r:$t"; done
  done
  echo "redis:8-alpine"; echo "postgres:18-alpine"; echo "nginx:1.31"
  echo "ghcr.io/otra-organizacion/sede-app:bbbbbbb"
  echo "<none>:<none>"
} > /tmp/imagenes
salida="$(SEDE_ESPERA_SEGUNDOS=5 timeout 60 bash "$GUION" staging "$VERSION" 2>&1)"; codigo=$?
[ "$codigo" -eq 0 ] && ok "el despliegue termina con 0" || mal "terminó con código $codigo (se esperaba 0)"
for r in sede-app sede-nginx sede-sitio; do
  for t in bbbbbbb ccccccc; do
    grep -qx "docker rmi $R/$r:$t" /tmp/llamadas && ok "borra $r:$t" || mal "no borró $r:$t"
  done
  for t in "$VERSION" aaaaaaa ddddddd eeeeeee; do
    grep -q "^docker rmi.* $R/$r:$t\$" /tmp/llamadas && mal "BORRÓ $r:$t, que se debe conservar" || ok "conserva $r:${t:0:7}"
  done
done
if grep -E '^docker rmi' /tmp/llamadas | grep -Eq 'redis|postgres|nginx:1|otra-organizacion'; then
  mal "tocó una imagen que no es del proyecto"
else
  ok "no toca redis, postgres, nginx base ni repositorios ajenos"
fi
grep -qx 'docker image prune -f' /tmp/llamadas && ok "limpia las colgantes (sin -a)" || mal "no limpió las colgantes con 'image prune -f'"
if grep -E '^docker (image|system) prune' /tmp/llamadas | grep -Eq -- ' -a|--all|system'; then mal "usó una poda amplia (-a/--all/system)"; else ok "ninguna poda amplia"; fi
if grep -E '^docker rmi' /tmp/llamadas | grep -Eq -- ' -f|--force'; then mal "usó rmi -f (borraría una imagen en uso)"; else ok "rmi sin -f: Docker rechaza lo que está en uso"; fi
n_compose="$(grep -n 'compose' /tmp/llamadas | tail -1 | cut -d: -f1)"
n_rmi="$(grep -n '^docker rmi' /tmp/llamadas | head -1 | cut -d: -f1)"
[ -n "$n_rmi" ] && [ "$n_rmi" -gt "$n_compose" ] && ok "borra DESPUÉS de levantar y comprobar la sede" || mal "borró antes de terminar el despliegue"

echo
echo "5. Si Docker no puede borrar una imagen, el despliegue sigue en verde y lo dice"
preparar_servidor
{ echo "$R/sede-app:bbbbbbb"; echo "$R/sede-app:$VERSION"; } > /tmp/imagenes
: > /tmp/rmi_falla
salida="$(SEDE_ESPERA_SEGUNDOS=5 timeout 60 bash "$GUION" staging "$VERSION" 2>&1)"; codigo=$?
[ "$codigo" -eq 0 ] && ok "termina con 0 aunque rmi falle" || mal "terminó con código $codigo (se esperaba 0)"
printf '%s' "$salida" | grep -q 'AVISO' && ok "avisa de lo que no pudo borrar" || mal "no avisó del fallo al borrar"

echo
echo "6. Si la sede no responde, NO se borra nada (el despliegue vuelve atrás)"
preparar_servidor
{ echo "$R/sede-app:bbbbbbb"; echo "$R/sede-app:aaaaaaa"; echo "$R/sede-app:$VERSION"; } > /tmp/imagenes
: > /tmp/curl_falla
SEDE_ESPERA_SEGUNDOS=5 timeout 60 bash "$GUION" staging "$VERSION" > /dev/null 2>&1; codigo=$?
[ "$codigo" -eq 1 ] && ok "el despliegue fallido sale con 1" || mal "salió con código $codigo (se esperaba 1)"
if grep -Eq '^docker (rmi|image prune)' /tmp/llamadas; then mal "borró imágenes en un despliegue fallido"; else ok "no borró ninguna imagen"; fi

echo
echo "7. El tope de tiempo se respeta y se dice qué quedó sin borrar"
preparar_servidor
{ echo "$R/sede-app:bbbbbbb"; echo "$R/sede-app:ccccccc"; echo "$R/sede-app:$VERSION"; } > /tmp/imagenes
salida="$(SEDE_LIMPIEZA_SEGUNDOS=0 SEDE_ESPERA_SEGUNDOS=5 timeout 60 bash "$GUION" staging "$VERSION" 2>&1)"; codigo=$?
[ "$codigo" -eq 0 ] && ok "termina con 0 con el tope agotado" || mal "terminó con código $codigo (se esperaba 0)"
if grep -q '^docker rmi' /tmp/llamadas; then mal "borró pasado el tope"; else ok "no borró nada pasado el tope"; fi
printf '%s' "$salida" | grep -Eq 'quedan [0-9]+ imágenes' && ok "dice cuántas quedan para el próximo despliegue" || mal "no dijo cuántas imágenes quedan"

echo
if [ "$fallos" -gt 0 ]; then
  printf '\033[31m%d comprobación(es) fallaron.\033[0m\n' "$fallos" >&2
  exit 1
fi
printf '\033[32mdesplegar.sh: cerrojo, caminos de entorno y limpieza de imágenes en orden.\033[0m\n'
