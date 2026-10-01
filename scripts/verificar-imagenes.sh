#!/usr/bin/env bash
#
# Comprueba que toda imagen de terceros está fijada por resumen (digest).
#
# **Por qué es una puerta y no una recomendación.** Una imagen referida por
# etiqueta (`postgres:18-alpine`, `mailpit:latest`) es un artefacto móvil: el día
# que su autor la vuelve a publicar, el siguiente `docker compose pull` trae un
# binario distinto al que se probó, sin que nadie haya cambiado una línea del
# repositorio. En una sede electrónica eso significa que el entorno de producción
# puede cambiar sin revisión y sin trazabilidad. Lo que se despliega tiene que ser
# lo que se probó, y para eso hace falta el resumen.
#
# Las imágenes **construidas aquí** (`${REGISTRY:-…}`) quedan exentas: su contenido
# está fijado por el Dockerfile y por el commit, no por el registro.
#
# Uso: `make imagenes` · `bash scripts/verificar-imagenes.sh`
set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"

FICHEROS=(compose.yaml)
[[ -f compose.override.yaml ]] && FICHEROS+=(compose.override.yaml)
[[ -f compose.prod.yaml ]] && FICHEROS+=(compose.prod.yaml)

TOTAL=0
FIJADAS=0
PROPIAS=0
SIN_FIJAR=()

for fichero in "${FICHEROS[@]}"; do
  # Cada `image:` con su línea, para poder señalar dónde está el problema.
  while IFS=: read -r numero valor; do
    # `valor` llega con el prefijo ": " ya recortado por el `read` de arriba.
    imagen="$(printf '%s' "$valor" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"
    [[ -z "$imagen" ]] && continue

    # Imágenes construidas en este repositorio: no se fijan por resumen.
    if [[ "$imagen" == *'${'* ]]; then
      PROPIAS=$((PROPIAS + 1))
      TOTAL=$((TOTAL + 1))
      continue
    fi

    TOTAL=$((TOTAL + 1))
    if [[ "$imagen" == *'@sha256:'* ]]; then
      FIJADAS=$((FIJADAS + 1))
      echo "  OK      $imagen"
    else
      SIN_FIJAR+=("$fichero:$numero → $imagen")
      echo "  FALLA   $imagen   ($fichero:$numero, sin resumen)"
    fi
  done < <(grep -nE '^[[:space:]]*image:' "$fichero" | sed -E 's/^([0-9]+):[[:space:]]*image:[[:space:]]*/\1:/' || true)
done

echo
echo "================================================================================"
echo "  Imágenes declaradas: $TOTAL · fijadas por resumen: $FIJADAS · propias: $PROPIAS"

if (( ${#SIN_FIJAR[@]} > 0 )); then
  echo "  Sin fijar: ${#SIN_FIJAR[@]}"
  echo
  echo "Falla: una imagen de terceros sin resumen puede cambiar sin que nadie revise nada."
  for linea in "${SIN_FIJAR[@]}"; do
    echo "  - $linea"
  done
  echo
  echo "Cómo se arregla: añadir el resumen de la etiqueta que se quiera usar, por ejemplo"
  echo "  postgres:18-alpine@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873"
  echo "El resumen de una etiqueta se consulta con:"
  echo "  docker buildx imagetools inspect <imagen>:<etiqueta>"
  exit 1
fi

echo "La puerta pasa: todas las imágenes de terceros están fijadas por resumen."
