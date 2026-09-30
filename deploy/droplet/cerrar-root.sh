#!/bin/bash
#
# Cierra el acceso de `root` por SSH — con red de seguridad.
#
# Cerrar root es irreversible si la cuenta de administración no entra: nadie
# puede volver a abrir la puerta desde fuera. Por eso este guion:
#
#   1. comprueba los requisitos ANTES de tocar nada, incluidos los permisos que
#      hacen que SSH rechace una llave válida en silencio;
#   2. deja armado un temporizador que DEVUELVE el acceso de root si nadie lo
#      confirma dentro del plazo;
#   3. exige que el titular haya comprobado, en OTRA terminal, que la cuenta de
#      administración entra y eleva. Eso no se puede verificar desde el
#      servidor: la llave privada vive en el equipo del titular.
#
#   ./cerrar-root.sh comprobar
#   ./cerrar-root.sh aplicar [minutos]     # 10 minutos por defecto
#   ./cerrar-root.sh confirmar
#
set -euo pipefail

USUARIO_ADMIN="${USUARIO_ADMIN:-ops}"
CONF_SSH=/etc/ssh/sshd_config.d/00-sede-hardening.conf
RESPALDO=/root/00-sede-hardening.conf.respaldo
UNIDAD=sede-restaurar-root

rojo()  { printf '\033[31m%s\033[0m\n' "$*"; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }
aviso() { printf '\033[33m%s\033[0m\n' "$*"; }

ACCION="${1:-comprobar}"
MINUTOS="${2:-10}"

[ "$(id -u)" -eq 0 ] || { rojo "Se ejecuta como root, o con sudo."; exit 1; }

if [ ! -f "$CONF_SSH" ]; then
  rojo "No existe $CONF_SSH."
  rojo "Ejecuta antes preparar.sh: es quien escribe el endurecimiento de SSH."
  exit 1
fi

# --- Comprobación de requisitos ----------------------------------------------
# Cada comprobación corresponde a una forma concreta de quedarse fuera. Los
# permisos importan tanto como la llave: con StrictModes, SSH rechaza una llave
# correcta si el directorio o el archivo son accesibles por otros, y lo hace sin
# decir por qué.
FALLOS=0
comprobar() {
  FALLOS=0
  echo "Requisitos para cerrar el acceso de root"
  echo

  if id "$USUARIO_ADMIN" >/dev/null 2>&1; then
    verde "  ✓ la cuenta $USUARIO_ADMIN existe"
  else
    rojo "  ✗ la cuenta $USUARIO_ADMIN NO existe"
    FALLOS=$((FALLOS + 1))
  fi

  local consola
  consola="$(getent passwd "$USUARIO_ADMIN" | cut -d: -f7)"
  case "$consola" in
    ""|*/nologin|*/false)
      rojo "  ✗ $USUARIO_ADMIN no tiene consola utilizable (${consola:-sin entrada})"
      FALLOS=$((FALLOS + 1)) ;;
    *) verde "  ✓ $USUARIO_ADMIN tiene consola ($consola)" ;;
  esac

  local casa="/home/$USUARIO_ADMIN"
  local autorizadas="$casa/.ssh/authorized_keys"

  if [ -s "$autorizadas" ] && grep -qE '^(ssh-|sk-|ecdsa-)' "$autorizadas"; then
    verde "  ✓ $USUARIO_ADMIN tiene al menos una llave pública autorizada"
  else
    rojo "  ✗ $USUARIO_ADMIN NO tiene llaves públicas en $autorizadas"
    FALLOS=$((FALLOS + 1))
  fi

  # Con StrictModes yes (lo pone preparar.sh), estos tres permisos deciden si la
  # llave sirve o no.
  local permisos_ok=1
  [ -d "$casa" ] || permisos_ok=0
  if [ "$permisos_ok" = "1" ]; then
    local p_casa p_ssh p_llaves
    p_casa="$(stat -c '%a' "$casa" 2>/dev/null || echo '?')"
    p_ssh="$(stat -c '%a' "$casa/.ssh" 2>/dev/null || echo '?')"
    p_llaves="$(stat -c '%a' "$autorizadas" 2>/dev/null || echo '?')"
    local dueno
    dueno="$(stat -c '%U' "$autorizadas" 2>/dev/null || echo '?')"

    case "$p_casa" in
      *[2367]|*[2367][0-9]) rojo "  ✗ $casa es accesible por otros (permisos $p_casa): SSH rechazará la llave"; FALLOS=$((FALLOS + 1)) ;;
      *) verde "  ✓ permisos de $casa: $p_casa" ;;
    esac
    case "$p_ssh" in
      *[2367]|*[2367][0-9]) rojo "  ✗ $casa/.ssh es accesible por otros (permisos $p_ssh)"; FALLOS=$((FALLOS + 1)) ;;
      *) verde "  ✓ permisos de $casa/.ssh: $p_ssh" ;;
    esac
    if [ "$p_llaves" = "600" ] && [ "$dueno" = "$USUARIO_ADMIN" ]; then
      verde "  ✓ permisos de authorized_keys: 600, dueño $dueno"
    else
      rojo "  ✗ authorized_keys tiene permisos $p_llaves y dueño $dueno (deben ser 600 y $USUARIO_ADMIN)"
      FALLOS=$((FALLOS + 1))
    fi
  fi

  if id -nG "$USUARIO_ADMIN" 2>/dev/null | tr ' ' '\n' | grep -qx sudo; then
    verde "  ✓ $USUARIO_ADMIN pertenece al grupo sudo"
  else
    rojo "  ✗ $USUARIO_ADMIN NO pertenece al grupo sudo: no podría elevar"
    FALLOS=$((FALLOS + 1))
  fi

  if visudo -c >/dev/null 2>&1; then
    verde "  ✓ la configuración de sudo es válida"
  else
    rojo "  ✗ la configuración de sudo NO es válida: arréglala antes de cerrar root"
    FALLOS=$((FALLOS + 1))
  fi

  if sshd -t >/dev/null 2>&1; then
    verde "  ✓ la configuración de SSH es válida"
  else
    rojo "  ✗ la configuración de SSH NO es válida"
    FALLOS=$((FALLOS + 1))
  fi

  echo
  echo "  Valor actual: $(sshd -T 2>/dev/null | sed -n 's/^permitrootlogin /permitrootlogin /p')"
  echo
  return 0
}

# --- Red de seguridad ---------------------------------------------------------
# Se arma ANTES de recargar SSH: así no existe ni un instante sin red.
armar() {
  systemctl stop "$UNIDAD.timer" >/dev/null 2>&1 || true
  systemctl reset-failed "$UNIDAD.service" >/dev/null 2>&1 || true

  systemd-run --quiet \
    --unit="$UNIDAD" \
    --on-active="${MINUTOS}min" \
    --description="Restaura el acceso de root si nadie confirma el cierre" \
    /bin/bash -c "sed -i 's|^PermitRootLogin .*|PermitRootLogin prohibit-password|' '$CONF_SSH'; systemctl reload ssh; logger -t sede 'acceso de root RESTAURADO por el temporizador: nadie confirmó el cierre'"

  verde "  ✓ red de seguridad armada: en $MINUTOS minutos vuelve el acceso de root si no confirmas"
}

restaurar() {
  rojo "  Restaurando la configuración anterior…"
  if [ -f "$RESPALDO" ]; then
    cp -a "$RESPALDO" "$CONF_SSH"
    systemctl reload ssh 2>/dev/null || true
    rojo "  Configuración anterior restaurada."
  else
    rojo "  No hay respaldo: revísalo a mano antes de cerrar la sesión."
  fi
}

# --- Acciones -----------------------------------------------------------------
case "$ACCION" in
  comprobar)
    comprobar
    if [ "$FALLOS" -eq 0 ]; then
      verde "Todo en orden: se puede cerrar el acceso de root."
    else
      rojo "$FALLOS requisito(s) sin cumplir. NO cierres el acceso de root."
      exit 1
    fi
    ;;

  aplicar)
    comprobar
    if [ "$FALLOS" -ne 0 ]; then
      rojo "$FALLOS requisito(s) sin cumplir. NO se cierra el acceso de root."
      exit 1
    fi

    echo "Antes de continuar, comprueba EN OTRA TERMINAL que la cuenta de"
    echo "administración entra y eleva. Este guion no puede verificarlo: la"
    echo "llave privada está en tu equipo, no aquí."
    echo
    echo "  ssh -i <tu-llave> $USUARIO_ADMIN@<servidor>"
    echo "  sudo -v && sudo -n true && echo elevación correcta"
    echo

    if [ ! -t 0 ] && [ "${SEDE_CERRAR_ROOT_SI:-0}" != "1" ]; then
      rojo "Sin terminal interactiva. Si ya lo comprobaste, repite con SEDE_CERRAR_ROOT_SI=1."
      exit 1
    fi

    if [ -t 0 ]; then
      printf "Escribe el nombre de la cuenta de administración (%s) para cerrar root: " "$USUARIO_ADMIN"
      read -r respuesta
      if [ "$respuesta" != "$USUARIO_ADMIN" ]; then
        rojo "No coincide. No se cambia nada."
        exit 1
      fi
    fi

    cp -a "$CONF_SSH" "$RESPALDO"

    # Se cambia el valor EN EL MISMO archivo a propósito: sshd usa la PRIMERA
    # aparición de cada directiva, y este drop-in va antes que el resto.
    sed -i 's|^PermitRootLogin .*|PermitRootLogin no|' "$CONF_SSH"

    if [ "$(grep -c '^PermitRootLogin' "$CONF_SSH")" -ne 1 ] \
       || ! grep -qx 'PermitRootLogin no' "$CONF_SSH"; then
      rojo "  La directiva no quedó como debía."
      restaurar
      exit 1
    fi

    if ! sshd -t; then
      rojo "  La configuración resultante NO es válida."
      restaurar
      exit 1
    fi

    armar
    systemctl reload ssh
    verde "  ✓ acceso de root cerrado y SSH recargado"

    echo
    echo "Ahora, EN ESTA MISMA sesión no puedes comprobarlo: root ya no entra."
    echo "Comprueba desde otra terminal que $USUARIO_ADMIN sigue entrando y eleva."
    echo "Si entra, confirma el cierre:"
    echo
    echo "  sudo $0 confirmar"
    echo
    aviso "Si NO puedes entrar, no hagas nada: en $MINUTOS minutos vuelve el acceso de root."
    ;;

  confirmar)
    if systemctl is-active --quiet "$UNIDAD.timer"; then
      systemctl stop "$UNIDAD.timer"
      systemctl stop "$UNIDAD.service" >/dev/null 2>&1 || true
      verde "Red de seguridad desarmada: el acceso de root queda cerrado."
    else
      aviso "No había red de seguridad activa."
    fi

    echo
    echo "  Valor actual: $(sshd -T 2>/dev/null | sed -n 's/^permitrootlogin /permitrootlogin /p')"
    echo "  Comprueba que dice 'no'. Si dice otra cosa, alguien ya lo restauró."
    echo
    if [ -f "$RESPALDO" ]; then
      verde "Respaldo conservado en $RESPALDO por si hay que revertir a mano."
    fi
    ;;

  *)
    rojo "Acción desconocida: $ACCION"
    echo "  Uso: $0 {comprobar|aplicar [minutos]|confirmar}"
    exit 1
    ;;
esac
