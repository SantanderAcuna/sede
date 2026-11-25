/**
 * El respaldo de la API ensamblado para el servidor del sitio (T9.4, Fase 0C).
 *
 * Une las tres piezas ya probadas por separado: el núcleo (`respaldo-api.ts`), el almacén Redis
 * (`almacen-respaldo.ts`) y la salida hacia la API (`respaldo-ruta.ts`). Lo que aporta este archivo:
 *
 *   - **Una sola instancia por proceso.** El cortocircuito (3 fallos seguidos, 30 s) vive DENTRO del
 *     núcleo: si se creara uno por petición nunca llegaría a abrirse.
 *   - **Configuración leída en ejecución**, del `runtimeConfig` (variables `NUXT_…`), no de
 *     `process.env` al cargar el módulo: la imagen se construye una vez y se despliega en entornos
 *     con otro Redis y otro dominio.
 *   - **Almacén perezoso.** La conexión se abre en el primer uso, no al arrancar: el sitio arranca
 *     igual con Redis caído o sin configurar (ADR-0018) y nunca espera a Redis para arrancar.
 *   - **Sin Redis configurado, el respaldo no interviene:** `atender` devuelve `null` y la ruta sigue
 *     por `proxyRequest` (corte a 20 s), igual que antes de T9.4. Con Redis configurado pero caído,
 *     sí interviene: es el caso para el que se diseñó el corte de 4 s (H31).
 *   - **Cierre ordenado:** primero se esperan las escrituras en vuelo y después se cierra la
 *     conexión compartida.
 */
import { almacenCompartido, cerrarAlmacenesCompartidos, redisConfigurado, type ConfigRedis, type OperacionRedis } from './almacen-respaldo'
import { crearRespaldo, type Almacen, type EntradaRespaldo } from './respaldo-api'
import {
  atenderConRespaldo,
  crearTraer,
  type Atencion,
  type EntornoRespaldo,
  type LecturaEntrante,
} from './respaldo-ruta'

export interface ConfigSitio {
  /** `NUXT_API_INTERNA`: la base interna de la API. */
  base: string
  /** `public.dominio`: solo se guarda lo pedido por este anfitrión (H34). */
  dominio: string
  redis: ConfigRedis
}

/** Lo que se lee del `runtimeConfig`; todo opcional porque llega de variables de entorno. */
export interface ConfigEnEjecucion {
  apiInterna?: unknown
  redisHost?: unknown
  redisPort?: unknown
  redisUsuario?: unknown
  redisPassword?: unknown
  public?: { dominio?: unknown }
}

export function configDelSitio(rc: ConfigEnEjecucion): ConfigSitio {
  const puerto = Number(rc.redisPort)
  return {
    base: String(rc.apiInterna ?? ''),
    dominio: String(rc.public?.dominio ?? ''),
    redis: {
      host: String(rc.redisHost ?? ''),
      port: Number.isInteger(puerto) && puerto > 0 ? puerto : 0,
      usuario: String(rc.redisUsuario ?? ''),
      password: String(rc.redisPassword ?? ''),
    },
  }
}

export interface Dependencias {
  fetch?: typeof fetch
  almacenCompartido?: typeof almacenCompartido
  cerrarAlmacenes?: () => Promise<void>
  ahora?: () => number
  registrar?: (linea: Record<string, unknown>) => void
}

export interface EntornoDelSitio {
  atender(lectura: LecturaEntrante): Promise<Atencion | null>
  esperarEscrituras(): Promise<void>
  cerrar(): Promise<void>
}

/** Una línea JSON por evento, a la salida estándar del contenedor (donde la recoge Docker). */
function registrarEnConsola(linea: Record<string, unknown>): void {
  console.warn(JSON.stringify({ nivel: 'warn', origen: 'respaldo-api', t: new Date().toISOString(), ...linea }))
}

/** El mensaje del error sin la contraseña, por si la librería la repitiera, y con tope de largo. */
function mensajeSeguro(error: unknown, password: string): string {
  let texto = error instanceof Error ? error.message : String(error)
  if (password !== '') texto = texto.split(password).join('***')
  return texto.slice(0, 200)
}

export function crearEntornoDelSitio(config: ConfigSitio, dependencias: Dependencias = {}): EntornoDelSitio {
  const registrar = dependencias.registrar ?? registrarEnConsola
  const obtenerAlmacen = dependencias.almacenCompartido ?? almacenCompartido
  const cerrarAlmacenes = dependencias.cerrarAlmacenes ?? cerrarAlmacenesCompartidos

  const alFallar = (operacion: OperacionRedis, error: unknown): void => {
    registrar({ evento: 'respaldo_redis_fallo', operacion, error: mensajeSeguro(error, config.redis.password) })
  }

  // `almacenCompartido` devuelve siempre la misma promesa por destino: una sola conexión.
  const almacen: Almacen = {
    async leer(clave: string): Promise<EntradaRespaldo | null> {
      const real = await obtenerAlmacen(config.redis, { alFallar })
      return real === null ? null : real.leer(clave)
    },
    async guardar(clave: string, entrada: EntradaRespaldo, ttlSegundos: number): Promise<void> {
      const real = await obtenerAlmacen(config.redis, { alFallar })
      if (real !== null) await real.guardar(clave, entrada, ttlSegundos)
    },
  }

  const respaldo = crearRespaldo({
    almacen,
    traer: crearTraer(config.base, dependencias.fetch),
    ahora: dependencias.ahora ?? Date.now,
    dominio: config.dominio,
  })
  const entorno: EntornoRespaldo = { respaldo, registrar }

  // Sin Redis no hay copia que servir. Si la lectura pasara por el núcleo, su corte de 4 s
  // convertiría en 502 una API lenta que el proxy de siempre (20 s) sí espera (medido: API a 6 s →
  // TimeoutError a los 4,0 s). Así que sin Redis la ruta la sirve como antes de T9.4.
  const conRespaldo = redisConfigurado(config.redis)

  return {
    atender: lectura => (conRespaldo ? atenderConRespaldo(entorno, lectura) : Promise.resolve(null)),
    esperarEscrituras: () => respaldo.esperarEscrituras(),
    async cerrar() {
      await respaldo.esperarEscrituras()
      await cerrarAlmacenes()
    },
  }
}

let instancia: EntornoDelSitio | null = null

/** La instancia del proceso. Se crea en la primera petición, con la configuración de ESE momento. */
export function entornoDelSitio(rc: ConfigEnEjecucion): EntornoDelSitio {
  instancia ??= crearEntornoDelSitio(configDelSitio(rc))
  return instancia
}

/** Apagado ordenado (gancho `close` de Nitro) y para las pruebas. */
export async function cerrarEntornoDelSitio(): Promise<void> {
  const actual = instancia
  instancia = null
  if (actual !== null) await actual.cerrar()
}
