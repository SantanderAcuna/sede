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
 *  3. **El espaciado de texto.** RF-B3-014 pide que el espaciado sea configurable,
 *     y WCAG 1.4.12 (AA) exige que el contenido **no se pierda** cuando quien lee
 *     impone interlínea 1,5, espaciado entre letras 0,12 em y entre palabras
 *     0,16 em. Aquí se ofrece el ajuste —y de paso demuestra que el diseño aguanta
 *     esos valores, que es la otra mitad del criterio— con una clase en la raíz.
 *
 * **Persistencia en localStorage (D-11).**
 * ADR-0012 compromete que las preferencias de accesibilidad se guarden en
 * `localStorage` para que sobrevivan entre sesiones. El estado vive en `useState`
 * para consistencia SSR, pero se sincroniza con `localStorage` en el cliente.
 */
import { computed, onMounted, watch } from 'vue'

const LIMITE = 5
const CLAVE = 'sede.accesibilidad'
const CLAVE_LOCALSTORAGE = 'sede-accesibilidad'
const PASO = 0.08

interface Preferencias {
  contraste: boolean
  letra: number
  /** Espaciado de texto reforzado (WCAG 1.4.12, RF-B3-014). */
  espaciado: boolean
}

/** Valores por defecto cuando no hay preferencia guardada. */
const VALORES_POR_DEFECTO: Preferencias = { contraste: false, letra: 0, espaciado: false }

/**
 * Lee las preferencias desde localStorage.
 * Solo debe llamarse en el cliente; en SSR retorna los valores por defecto.
 */
function leerDeLocalStorage(): Preferencias {
  if (!import.meta.client) return VALORES_POR_DEFECTO
  try {
    const guardadas = localStorage.getItem(CLAVE_LOCALSTORAGE)
    if (guardadas) {
      const parsed = JSON.parse(guardadas) as Partial<Preferencias>
      // Validación mínima: asegurar que los valores son del tipo correcto
      return {
        contraste: typeof parsed.contraste === 'boolean' ? parsed.contraste : VALORES_POR_DEFECTO.contraste,
        letra: typeof parsed.letra === 'number' ? parsed.letra : VALORES_POR_DEFECTO.letra,
        espaciado:
          typeof parsed.espaciado === 'boolean'
            ? parsed.espaciado
            : VALORES_POR_DEFECTO.espaciado,
      }
    }
  } catch {
    // Si localStorage falla o el JSON está corrupto, usar valores por defecto
  }
  return VALORES_POR_DEFECTO
}

/**
 * Guarda las preferencias en localStorage.
 * Solo debe llamarse en el cliente.
 */
function guardarEnLocalStorage(prefs: Preferencias): void {
  if (!import.meta.client) return
  try {
    localStorage.setItem(CLAVE_LOCALSTORAGE, JSON.stringify(prefs))
  } catch {
    // Si localStorage falla (cuota, privado, etc.), continuar sin guardar
  }
}

export function useAccesibilidad() {
  // useState garantiza consistencia SSR: todas las páginas ven el mismo estado
  // inicial durante la hidratación. Las preferencias reales se cargan de
  // localStorage en onMounted.
  const preferencias = useState<Preferencias>(CLAVE, () => VALORES_POR_DEFECTO)

  /** Aplica las preferencias al documento. Solo tiene sentido en el cliente. */
  function aplicar(): void {
    if (!import.meta.client) return

    const raiz = document.documentElement
    raiz.classList.toggle('contraste-govco', preferencias.value.contraste)
    // 1 es el tamaño normal. Cada paso mueve un 8 %, perceptible sin desarmar
    // las rejillas de la página.
    raiz.style.zoom =
      preferencias.value.letra === 0 ? '' : String(1 + preferencias.value.letra * PASO)
    raiz.classList.toggle('espaciado-govco', preferencias.value.espaciado)
  }

  function alternarContraste(): void {
    preferencias.value.contraste = !preferencias.value.contraste
  }

  /** Un paso arriba o abajo, dentro del límite que marca el Kit (5 pasos). */
  function moverLetra(paso: 1 | -1): void {
    const siguiente = preferencias.value.letra + paso
    preferencias.value.letra = Math.max(-LIMITE, Math.min(LIMITE, siguiente))
  }

  /** Enciende o apaga el espaciado de texto reforzado. */
  function alternarEspaciado(): void {
    preferencias.value.espaciado = !preferencias.value.espaciado
  }

  function restablecer(): void {
    preferencias.value = { contraste: false, letra: 0, espaciado: false }
  }

  const puedeAumentar = computed(() => preferencias.value.letra < LIMITE)
  const puedeReducir = computed(() => preferencias.value.letra > -LIMITE)

  // Al montar cargar las preferencias guardadas y aplicarlas.
  // useState ya provee el valor inicial (VALORES_POR_DEFECTO en SSR),
  // pero en el cliente debemos sobreescribir con lo guardado en localStorage.
  onMounted(() => {
    const guardadas = leerDeLocalStorage()
    preferencias.value = guardadas
    aplicar()
  })

  // Cada vez que cambian las preferencias, guardarlas en localStorage.
  watch(preferencias, (nuevas) => {
    guardarEnLocalStorage(nuevas)
    aplicar()
  }, { deep: true })

  return {
    preferencias,
    alternarContraste,
    alternarEspaciado,
    moverLetra,
    restablecer,
    puedeAumentar,
    puedeReducir,
    LIMITE,
  }
}
