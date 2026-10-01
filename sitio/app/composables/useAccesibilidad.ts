/**
 * Estado de accesibilidad del sitio, compartido por todas las páginas.
 *
 * Reimplementa en Vue lo que el Kit hace con `script.js`, y se aparta de él en
 * los puntos que conviene tener presentes:
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
 *     nada: es un stub. Aquí el modo de alto contraste es de verdad y hay cuatro
 *     modos excluyentes, como pide el brief de accesibilidad.
 *
 *  3. **El resto de ajustes.** Espaciado de texto (WCAG 1.4.12 / RF-B3-014),
 *     fuente para dislexia, resaltado de enlaces, guía de lectura y detención de
 *     animaciones. Cada uno es una clase sobre la raíz, no un estilo en línea.
 *
 * **Una sola preferencia por ajuste, y una sola clase por ajuste.** El brief exige
 * que la misma acción se vea y se nombre igual en toda la interfaz (CC7); para eso
 * hace falta que exista un único sitio donde vive cada estado, y éste es.
 *
 * **Persistencia en localStorage (D-11), versionada.**
 * ADR-0012 compromete que las preferencias se guarden para que sobrevivan entre
 * sesiones. Se guardan con `v: 2`; el formato anterior —`contraste` booleano y
 * `letra` de −5 a 5— se migra al leer (§5.2 del plan), porque un ciudadano que ya
 * hubiera ajustado el contraste no puede encontrárselo apagado y creer que el
 * sitio olvidó su decisión.
 */
import { computed, onMounted, ref, watch } from 'vue'

/** Los cuatro modos de contraste del brief (§5.2.4). Son excluyentes. */
export type ModoContraste = 'normal' | 'alto' | 'inverso' | 'grises'

/** Las siete preferencias del ciudadano. */
export interface Preferencias {
  contraste: ModoContraste
  /** Índice en `ESCALA_LETRA`: 0 = 100 %, 4 = 200 %. */
  letra: number
  /** Espaciado de texto reforzado (WCAG 1.4.12, RF-B3-014). */
  espaciado: boolean
  /** Fuente y disposición pensadas para lectura con dislexia. */
  dislexia: boolean
  /** Enlaces subrayados y resaltados, sin depender del color. */
  resaltarEnlaces: boolean
  /** Resalta el bloque que está bajo el puntero o el foco. */
  guiaLectura: boolean
  /** Anula animaciones y transiciones, además de lo que pida el sistema. */
  detenerAnimaciones: boolean
}

/**
 * Escala de tamaño de texto: 100 % a 200 %, como fija el brief (§5.2.3 y §6.2).
 * Seis pasos hubieran dado saltos imperceptibles; cinco dan un salto del 25 %
 * que se nota sin desarmar ninguna rejilla.
 */
export const ESCALA_LETRA = [1, 1.25, 1.5, 1.75, 2] as const

/** Último índice válido de `ESCALA_LETRA`. */
export const LIMITE = ESCALA_LETRA.length - 1

const CLAVE = 'sede.accesibilidad'
const CLAVE_PANEL = 'sede.accesibilidad.panel'
const CLAVE_LOCALSTORAGE = 'sede-accesibilidad'
const VERSION_ALMACENAMIENTO = 2

/** Clases que este composable alterna sobre `document.documentElement`. */
const CLASE_CONTRASTE: Record<ModoContraste, string | null> = {
  normal: null,
  alto: 'contraste-govco',
  inverso: 'contraste-inverso-govco',
  grises: 'contraste-grises-govco',
}

const CLASES_BOOLEANAS = {
  espaciado: 'espaciado-govco',
  dislexia: 'dislexia-govco',
  resaltarEnlaces: 'resaltar-enlaces-govco',
  guiaLectura: 'guia-lectura-govco',
  detenerAnimaciones: 'detener-animaciones-govco',
} as const

/** Los cinco ajustes que se encienden y se apagan. */
export type AjusteBooleano = keyof typeof CLASES_BOOLEANAS

/** Valores por defecto: el sitio tal como se diseñó. */
export const VALORES_POR_DEFECTO: Preferencias = {
  contraste: 'normal',
  letra: 0,
  espaciado: false,
  dislexia: false,
  resaltarEnlaces: false,
  guiaLectura: false,
  detenerAnimaciones: false,
}

/** Los cuatro modos, en el orden en que se ofrecen. */
export const MODOS_CONTRASTE: readonly ModoContraste[] = ['normal', 'alto', 'inverso', 'grises']

/** El paso de la escala más cercano a un porcentaje dado. */
function pasoMasCercano(porcentaje: number): number {
  let mejor = 0
  let menorDistancia = Number.POSITIVE_INFINITY
  ESCALA_LETRA.forEach((escala, indice) => {
    const distancia = Math.abs(escala - porcentaje)
    if (distancia < menorDistancia) {
      menorDistancia = distancia
      mejor = indice
    }
  })
  return mejor
}

/** Comprueba que un valor es uno de los cuatro modos. */
function esModoContraste(valor: unknown): valor is ModoContraste {
  return typeof valor === 'string' && (MODOS_CONTRASTE as readonly string[]).includes(valor)
}

/** Un booleano, con el valor por defecto si no lo es. */
function booleano(valor: unknown, porDefecto: boolean): boolean {
  return typeof valor === 'boolean' ? valor : porDefecto
}

/**
 * Convierte lo que haya en el almacenamiento a la forma actual.
 *
 * Acepta el formato v2 y **migra el v1** —`contraste` booleano, `letra` de −5 a 5
 * en pasos del 8 %—. Cualquier otra cosa se descarta sin lanzar: un almacenamiento
 * corrupto no puede dejar la página sin arrancar.
 */
export function normalizarPreferencias(crudo: unknown): Preferencias {
  if (typeof crudo !== 'object' || crudo === null) return { ...VALORES_POR_DEFECTO }
  const datos = crudo as Record<string, unknown>

  // v1: el contraste era un booleano y la letra iba de −5 a +5.
  const esV1 = datos.v === undefined
  const contraste: ModoContraste = esV1
    ? datos.contraste === true
      ? 'alto'
      : 'normal'
    : esModoContraste(datos.contraste)
      ? datos.contraste
      : VALORES_POR_DEFECTO.contraste

  let letra = VALORES_POR_DEFECTO.letra
  if (typeof datos.letra === 'number' && Number.isFinite(datos.letra)) {
    if (esV1) {
      // v1: cada paso sumaba un 8 % sobre el tamaño normal.
      letra = pasoMasCercano(1 + datos.letra * 0.08)
    } else {
      letra = Math.max(0, Math.min(LIMITE, Math.round(datos.letra)))
    }
  }

  return {
    contraste,
    letra,
    espaciado: booleano(datos.espaciado, VALORES_POR_DEFECTO.espaciado),
    dislexia: booleano(datos.dislexia, VALORES_POR_DEFECTO.dislexia),
    resaltarEnlaces: booleano(datos.resaltarEnlaces, VALORES_POR_DEFECTO.resaltarEnlaces),
    guiaLectura: booleano(datos.guiaLectura, VALORES_POR_DEFECTO.guiaLectura),
    detenerAnimaciones: booleano(
      datos.detenerAnimaciones,
      VALORES_POR_DEFECTO.detenerAnimaciones,
    ),
  }
}

/** Lee las preferencias del almacenamiento. Sólo cliente. */
function leerDeLocalStorage(): { preferencias: Preferencias; habiaGuardadas: boolean } {
  if (!import.meta.client) return { preferencias: { ...VALORES_POR_DEFECTO }, habiaGuardadas: false }
  try {
    const guardadas = localStorage.getItem(CLAVE_LOCALSTORAGE)
    if (guardadas === null) {
      return { preferencias: { ...VALORES_POR_DEFECTO }, habiaGuardadas: false }
    }
    return { preferencias: normalizarPreferencias(JSON.parse(guardadas)), habiaGuardadas: true }
  } catch {
    // JSON corrupto o almacenamiento bloqueado: se sigue con los valores por defecto.
    return { preferencias: { ...VALORES_POR_DEFECTO }, habiaGuardadas: false }
  }
}

/** Guarda las preferencias. Sólo cliente. */
function guardarEnLocalStorage(preferencias: Preferencias): void {
  if (!import.meta.client) return
  try {
    localStorage.setItem(
      CLAVE_LOCALSTORAGE,
      JSON.stringify({ v: VERSION_ALMACENAMIENTO, ...preferencias }),
    )
  } catch {
    // Cuota agotada o modo privado: se sigue sin guardar. No es un error para
    // quien navega, así que no se le interrumpe con un mensaje.
  }
}

export function useAccesibilidad() {
  // `useState` garantiza consistencia SSR: todas las páginas ven el mismo estado
  // inicial durante la hidratación. Las preferencias reales llegan en `onMounted`.
  const preferencias = useState<Preferencias>(CLAVE, () => ({ ...VALORES_POR_DEFECTO }))

  /** Si el panel de ajustes está abierto. Compartido por sus dos disparadores. */
  const panelAbierto = useState<boolean>(CLAVE_PANEL, () => false)

  /** Si el sistema pide más contraste y el ciudadano aún no ha elegido nada. */
  const sugerenciaContrasteAlto = ref(false)

  /**
   * Aplica las preferencias al documento. Único punto que toca el DOM.
   * `zoom` escala también los `px`, que es de lo que está hecho el Kit.
   */
  function aplicar(): void {
    if (!import.meta.client) return

    const raiz = document.documentElement

    for (const clase of Object.values(CLASE_CONTRASTE)) {
      if (clase !== null) raiz.classList.remove(clase)
    }
    const claseContraste = CLASE_CONTRASTE[preferencias.value.contraste]
    if (claseContraste !== null) raiz.classList.add(claseContraste)

    for (const [ajuste, clase] of Object.entries(CLASES_BOOLEANAS)) {
      raiz.classList.toggle(clase, preferencias.value[ajuste as keyof typeof CLASES_BOOLEANAS])
    }

    const escala = ESCALA_LETRA[preferencias.value.letra] ?? 1
    raiz.style.zoom = escala === 1 ? '' : String(escala)
  }

  /** Cambia el modo de contraste. */
  function definirContraste(modo: ModoContraste): void {
    preferencias.value.contraste = modo
  }

  /** Alterna entre alto contraste y normal, que es el atajo de teclado. */
  function alternarContraste(): void {
    preferencias.value.contraste = preferencias.value.contraste === 'alto' ? 'normal' : 'alto'
  }

  /** Un paso arriba o abajo dentro de la escala del 100 % al 200 %. */
  function moverLetra(paso: 1 | -1): void {
    const siguiente = preferencias.value.letra + paso
    preferencias.value.letra = Math.max(0, Math.min(LIMITE, siguiente))
  }

  /** Enciende o apaga un ajuste booleano por su nombre. */
  function alternar(ajuste: AjusteBooleano): void {
    preferencias.value[ajuste] = !preferencias.value[ajuste]
  }

  function alternarEspaciado(): void {
    alternar('espaciado')
  }

  function alternarDislexia(): void {
    alternar('dislexia')
  }

  function alternarResaltarEnlaces(): void {
    alternar('resaltarEnlaces')
  }

  function alternarGuiaLectura(): void {
    alternar('guiaLectura')
  }

  function alternarDetenerAnimaciones(): void {
    alternar('detenerAnimaciones')
  }

  /** Devuelve las siete preferencias a su valor inicial. */
  function restablecer(): void {
    preferencias.value = { ...VALORES_POR_DEFECTO }
  }

  function abrirPanel(): void {
    panelAbierto.value = true
  }

  function cerrarPanel(): void {
    panelAbierto.value = false
  }

  function alternarPanel(): void {
    panelAbierto.value = !panelAbierto.value
  }

  const puedeAumentar = computed(() => preferencias.value.letra < LIMITE)
  const puedeReducir = computed(() => preferencias.value.letra > 0)

  /** El tamaño de texto actual en porcentaje, para mostrarlo y anunciarlo. */
  const porcentajeLetra = computed(() =>
    Math.round((ESCALA_LETRA[preferencias.value.letra] ?? 1) * 100),
  )

  /** Si hay algún ajuste distinto del valor por defecto. */
  const hayAjustes = computed(
    () =>
      preferencias.value.contraste !== VALORES_POR_DEFECTO.contraste ||
      preferencias.value.letra !== VALORES_POR_DEFECTO.letra ||
      preferencias.value.espaciado ||
      preferencias.value.dislexia ||
      preferencias.value.resaltarEnlaces ||
      preferencias.value.guiaLectura ||
      preferencias.value.detenerAnimaciones,
  )

  onMounted(() => {
    const { preferencias: guardadas, habiaGuardadas } = leerDeLocalStorage()

    /*
     * Cuando no hay nada guardado **no se escribe nada**. Escribir aquí los
     * valores por defecto registraría una «preferencia» que el ciudadano nunca
     * expresó, y —más importante— haría que en la visita siguiente el sitio
     * creyera que ya eligió, con lo que dejaría de poder distinguir entre «no ha
     * elegido» y «eligió lo de por defecto». Esa distinción es justo la que
     * permite ofrecerle el modo de alto contraste cuando su sistema lo pide.
     */
    if (!habiaGuardadas) {
      aplicar()

      // El brief prohíbe forzar cambios sin consentimiento: cuando el sistema
      // pide más contraste se **avisa** dentro del panel, no se aplica el modo.
      //
      // Se comprueba que `matchMedia` exista porque no está en todos los entornos
      // —jsdom, donde corren las pruebas, no lo implementa— y una preferencia
      // consultiva no puede tumbar la carga de la página.
      if (
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-contrast: more)').matches
      ) {
        sugerenciaContrasteAlto.value = true
      }
      return
    }

    // Sí había algo guardado: se adopta y el `watch` persiste la forma migrada,
    // que es lo que convierte una preferencia del formato anterior en una del
    // actual sin que el ciudadano tenga que volver a elegir.
    preferencias.value = guardadas
    aplicar()
  })

  // Cada cambio se guarda y se aplica. `deep` porque las preferencias son un objeto.
  watch(
    preferencias,
    (nuevas) => {
      guardarEnLocalStorage(nuevas)
      aplicar()
    },
    { deep: true },
  )

  return {
    preferencias,
    panelAbierto,
    sugerenciaContrasteAlto,
    porcentajeLetra,
    hayAjustes,
    puedeAumentar,
    puedeReducir,
    LIMITE,
    ESCALA_LETRA,
    definirContraste,
    alternarContraste,
    moverLetra,
    alternar,
    alternarEspaciado,
    alternarDislexia,
    alternarResaltarEnlaces,
    alternarGuiaLectura,
    alternarDetenerAnimaciones,
    restablecer,
    abrirPanel,
    cerrarPanel,
    alternarPanel,
  }
}
