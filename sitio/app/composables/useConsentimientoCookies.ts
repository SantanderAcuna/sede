/**
 * Consentimiento de cookies (RF-B1-008, RF-01-D01, RN-01-D01).
 *
 * Es el estado compartido del aviso de cookies: lo escribe el banner y lo lee
 * cualquier parte del sitio que necesite saber qué ha autorizado el ciudadano.
 * Existe separado del componente por dos motivos concretos:
 *
 *  1. **La revocación.** RF-B1-008 exige que el consentimiento se pueda retirar
 *     «en cualquier momento», y eso necesita un punto de entrada que no sea el
 *     propio banner: cuando el banner se cierra, no queda nada que pulsar. Aquí
 *     vive `abrirBanner()`, que es lo que permite reabrirlo desde el pie.
 *  2. **El bloqueo.** Ninguna cookie no esencial puede activarse antes de la
 *     aceptación. Ese «antes» sólo se puede comprobar desde fuera del banner, en
 *     el sitio donde se cargue la analítica: `puedeUsar(categoria)` es la puerta
 *     que ese código debe consultar.
 *
 * **Qué se guarda y por qué así.** El consentimiento guarda la **fecha**, la
 * **versión de la política** y la **lista de categorías aceptadas**. Con la
 * versión, un cambio en la política invalida lo aceptado antes (RN-01-D01); con
 * la fecha, caduca a los doce meses. Guardar sólo un booleano —que es lo que
 * hacía la primera versión del banner— no permitiría ninguna de las dos cosas ni
 * demostrar ante una reclamación qué autorizó el ciudadano y cuándo.
 *
 * Se guarda en `localStorage` y no en una cookie: es una preferencia del
 * navegador, no un dato que deba viajar al servidor en cada petición.
 */
import { computed, ref } from 'vue'

/** Categorías de cookies. Las necesarias no se eligen: no son opcionales. */
export type CategoriaCookies = 'analitica' | 'preferencias'

/** Clave de `localStorage` donde vive el consentimiento. */
const CLAVE = 'sede-consentimiento'

/**
 * Versión de la política de cookies.
 *
 * **Hay que subirla cada vez que cambie el texto de la política o el conjunto de
 * categorías**: al subirla, los consentimientos anteriores se consideran
 * caducados y el banner se vuelve a pedir. Es la mitad del requisito de
 * versionado; la otra mitad es la fecha que se guarda con cada aceptación.
 */
export const VERSION_POLITICA = 1

/** Caducidad del consentimiento, en días (doce meses, RN-01-D01). */
const DIAS_CADUCIDAD = 365

/** Lo que se guarda por cada consentimiento. */
export interface Consentimiento {
  /** Fecha y hora de la aceptación, en ISO 8601. */
  fecha: string
  /** Versión de la política aceptada. */
  version: number
  /** Categorías aceptadas. Vacío significa «sólo las necesarias». */
  aceptadas: CategoriaCookies[]
}

/**
 * Estado compartido.
 *
 * Es un `ref` de módulo y no un `useState` de Nuxt a propósito: el banner es una
 * decisión del navegador —no hay nada que renderizar en el servidor— y el estado
 * tiene que ser el mismo para el banner y para cualquier consumidor del
 * consentimiento dentro de la misma página.
 */
const visible = ref(false)
const preferencias = ref<Record<CategoriaCookies, boolean>>({
  analitica: false,
  preferencias: false,
})

/** Lee el consentimiento guardado, o `null` si no hay o está corrupto. */
export function leerConsentimiento(): Consentimiento | null {
  if (!import.meta.client) return null
  try {
    const crudo = localStorage.getItem(CLAVE)
    if (!crudo) return null
    const guardado = JSON.parse(crudo) as Partial<Consentimiento>
    if (typeof guardado.fecha !== 'string' || typeof guardado.version !== 'number') return null
    return {
      fecha: guardado.fecha,
      version: guardado.version,
      aceptadas: Array.isArray(guardado.aceptadas) ? guardado.aceptadas : [],
    }
  } catch {
    // Un JSON corrupto no puede tumbar la página: se trata como «sin decidir».
    return null
  }
}

/** Escribe el consentimiento. Si el navegador lo bloquea, se sigue sin guardar. */
function guardar(consentimiento: Consentimiento): void {
  if (!import.meta.client) return
  try {
    localStorage.setItem(CLAVE, JSON.stringify(consentimiento))
  } catch {
    // Modo privado, cuota agotada o almacenamiento deshabilitado: la sesión
    // sigue funcionando; sólo no se recuerda la decisión.
  }
}

/**
 * Comprueba si un consentimiento sigue siendo válido: ni ha pasado un año, ni la
 * política ha cambiado de versión desde que se dio.
 */
export function consentimientoVigente(consentimiento: Consentimiento | null): boolean {
  if (!consentimiento) return false
  if (consentimiento.version < VERSION_POLITICA) return false

  const dias = (Date.now() - new Date(consentimiento.fecha).getTime()) / 86_400_000
  return Number.isFinite(dias) && dias <= DIAS_CADUCIDAD
}

/** Guarda una decisión, cierra el banner y refleja las categorías aceptadas. */
function decidir(aceptadas: CategoriaCookies[]): void {
  guardar({ fecha: new Date().toISOString(), version: VERSION_POLITICA, aceptadas })
  preferencias.value = {
    analitica: aceptadas.includes('analitica'),
    preferencias: aceptadas.includes('preferencias'),
  }
  visible.value = false
}

export function useConsentimientoCookies() {
  /** El ciudadano ha decidido ya y la decisión sigue vigente. */
  const decidido = computed(() => {
    const guardado = leerConsentimiento()
    return consentimientoVigente(guardado)
  })

  /**
   * ¿Puede activarse esta categoría? Es la única pregunta que debe hacerse el
   * código que cargue analítica o preferencias: si devuelve `false`, no se
   * activa nada. Hoy no hay ninguna cookie no esencial —por eso el banner no
   * bloquea nada todavía—, pero la puerta queda puesta antes de que llegue.
   */
  function puedeUsar(categoria: CategoriaCookies): boolean {
    if (categoria === 'analitica' || categoria === 'preferencias') {
      return preferencias.value[categoria] === true && decidido.value
    }
    return false
  }

  /**
   * Muestra el banner si no hay decisión vigente.
   *
   * Se llama al montar el banner y **no** durante el renderizado: decidirlo en el
   * `setup` haría que el servidor pintara la página sin banner y el cliente con
   * él en la misma hidratación, que es un desajuste de hidratación garantizado.
   */
  function inicializar(): void {
    if (!import.meta.client) return
    const guardado = leerConsentimiento()
    if (consentimientoVigente(guardado)) {
      preferencias.value = {
        analitica: guardado?.aceptadas.includes('analitica') ?? false,
        preferencias: guardado?.aceptadas.includes('preferencias') ?? false,
      }
      visible.value = false
      return
    }
    // Sin decisión, o caducada, o con una versión anterior de la política: se
    // vuelve a preguntar (RN-01-D01).
    preferencias.value = { analitica: false, preferencias: false }
    visible.value = true
  }

  /** Reabre el banner para revisar o retirar lo aceptado (RF-B1-008). */
  function abrirBanner(): void {
    const guardado = leerConsentimiento()
    preferencias.value = {
      analitica: guardado?.aceptadas.includes('analitica') ?? false,
      preferencias: guardado?.aceptadas.includes('preferencias') ?? false,
    }
    visible.value = true
  }

  return {
    visible,
    preferencias,
    decidido,
    puedeUsar,
    inicializar,
    abrirBanner,
    aceptarTodo: () => decidir(['analitica', 'preferencias']),
    rechazarOpcionales: () => decidir([]),
    guardarPreferencias: () =>
      decidir(
        (Object.entries(preferencias.value) as [CategoriaCookies, boolean][])
          .filter(([, aceptada]) => aceptada)
          .map(([categoria]) => categoria),
      ),
    /** Revoca todo lo aceptado y deja el sitio sólo con las cookies necesarias. */
    revocar: () => decidir([]),
  }
}
