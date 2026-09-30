/**
 * Estado de accesibilidad del sitio, compartido por todas las páginas.
 *
 * Reimplementa en Vue lo que el Kit hace con `script.js`, y se aparta de él en
 * dos puntos que conviene tener presentes:
 *
 *  1. **El escalado de letra.** El Kit recorre `document.querySelectorAll('body *')`
 *     y fija un `font-size` EN LÍNEA a cada elemento, sumando 1 px por pulsación.
 *     Funciona de milagro: se acumula al re-leer el tamaño ya modificado, deja sin
 *     escalar todo lo que aparezca después (un listado que llega de la API, por
 *     ejemplo) y escribe miles de estilos en línea. Aquí se usa `zoom` sobre el
 *     elemento raíz, que escala también los `px` —y el CSS del Kit está lleno de
 *     `px`, así que subir el tamaño de la raíz no habría servido— y alcanza igual
 *     a lo que se dibuje más tarde.
 *
 *  2. **El contraste.** La clase `contrast-govco` del Kit existe en `all.css`,
 *     pero sus dos únicas reglas apuntan a `.accesibility-example`, que es la caja
 *     de demostración de su propia documentación. En una página real no cambia
 *     nada: es un stub. Aquí el botón aplica `contraste-govco`, nuestra clase, con
 *     un modo de alto contraste de verdad.
 *
 * El estado vive en `useState` para que sea el mismo en servidor y cliente: con
 * un `ref` suelto, la hidratación encontraría dos valores distintos.
 */
import { computed, onMounted, watch } from 'vue'

const LIMITE = 5
const CLAVE = 'sede.accesibilidad'
const PASO = 0.08

interface Preferencias {
  contraste: boolean
  letra: number
}

export function useAccesibilidad() {
  const preferencias = useState<Preferencias>(CLAVE, () => ({ contraste: false, letra: 0 }))

  /** Aplica las preferencias al documento. Sólo tiene sentido en el cliente. */
  function aplicar(): void {
    if (!import.meta.client) return

    const raiz = document.documentElement
    raiz.classList.toggle('contraste-govco', preferencias.value.contraste)
    // 1 es el tamaño normal. Cada paso mueve un 8 %, perceptible sin desarmar
    // las rejillas de la página.
    raiz.style.zoom =
      preferencias.value.letra === 0 ? '' : String(1 + preferencias.value.letra * PASO)
  }

  function alternarContraste(): void {
    preferencias.value.contraste = !preferencias.value.contraste
  }

  /** Un paso arriba o abajo, dentro del límite que marca el Kit (5 pasos). */
  function moverLetra(paso: 1 | -1): void {
    const siguiente = preferencias.value.letra + paso
    preferencias.value.letra = Math.max(-LIMITE, Math.min(LIMITE, siguiente))
  }

  function restablecer(): void {
    preferencias.value = { contraste: false, letra: 0 }
  }

  const puedeAumentar = computed(() => preferencias.value.letra < LIMITE)
  const puedeReducir = computed(() => preferencias.value.letra > -LIMITE)

  // Al montar hace falta aplicarlo: el servidor no tiene documento donde hacerlo.
  onMounted(aplicar)
  watch(preferencias, aplicar, { deep: true })

  return {
    preferencias,
    alternarContraste,
    moverLetra,
    restablecer,
    puedeAumentar,
    puedeReducir,
    LIMITE,
  }
}
