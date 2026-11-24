/**
 * Almacén Redis del respaldo de la API (T9.3, D-0C.2).
 *
 * Implementa el `Almacen` que espera el núcleo (`respaldo-api.ts`) sobre `ioredis`, conectado como
 * el usuario ACL `sitio` del Redis compartido con Laravel (sesiones y colas). Tres reglas mandan:
 *
 * 1. **Redis es una ayuda.** Si se cae, se congela, rechaza el comando o devuelve basura, el
 *    almacén responde «no hay respaldo» y sigue: nunca lanza y nunca espera más que
 *    `TIEMPO_COMANDO_MS`. El sitio se comporta como hoy.
 * 2. **Toda clave lleva TTL.** El Redis compartido usa `volatile-lru`: solo expulsa claves con TTL.
 *    Una clave inmortal del sitio nunca se liberaría y competiría con las sesiones por la memoria.
 *    Por eso la única escritura posible es `SET … EX`, con un TTL entero y positivo.
 * 3. **Solo `sitio:*`.** La ACL (`~sitio:* -@all +get +set +del +ping +auth`) lo impone en Redis;
 *    aquí además se rechaza antes de enviar nada, para que un error de programación no se convierta
 *    en un `NOPERM` en el registro de ACL.
 *
 * Como `canal-interno.ts` y `respaldo-api.ts`, el cuerpo es lógica pura sobre un cliente inyectado;
 * `ioredis` solo se carga en `conectarAlmacen`.
 */
import type { RedisOptions } from 'ioredis'

import type { Almacen, EntradaRespaldo } from './respaldo-api'

/**
 * Cuánto se espera a Redis antes de abandonar un comando. Es un poco mayor que el
 * `commandTimeout` de `ioredis` (300 ms), de modo que normalmente gana el del cliente; este es el
 * cinturón por si el cliente no cumple.
 */
export const TIEMPO_COMANDO_MS = 400
/** Un mismo tipo de fallo se informa como máximo una vez en este intervalo. */
export const INTERVALO_INFORME_MS = 30_000
/** Cuánto se espera, como máximo, a la primera conexión al arrancar (connectTimeout 500 ms + margen). */
export const ESPERA_CONEXION_MS = 700
const PREFIJO = 'sitio:'

/** Lo que el almacén necesita del cliente: lo que cubre la ACL, y nada más. */
export interface ClienteRedis {
  get(clave: string): Promise<string | null>
  set(clave: string, valor: string, modo: 'EX', segundos: number): Promise<unknown>
  quit(): Promise<unknown>
  disconnect(): void
}

export type OperacionRedis = 'leer' | 'guardar' | 'conexion' | 'cerrar'

export interface OpcionesAlmacen {
  /** Para el registro del llamador. Se invoca como mucho una vez cada 30 s por operación. */
  alFallar?: (operacion: OperacionRedis, error: unknown) => void
  /** Tiempo máximo de espera por comando; se inyecta para probar sin esperar. */
  tiempoMs?: number
  ahora?: () => number
}

export interface AlmacenRedis extends Almacen {
  /** Para quien tenga el cliente real: errores de conexión que no pasan por un comando. */
  informarFallo(operacion: OperacionRedis, error: unknown): void
  cerrar(): Promise<void>
}

/** Rechaza al pasar `ms`. Se engancha a la promesa original, así que un fallo tardío no queda sin atender. */
function conLimite<T>(promesa: Promise<T>, ms: number): Promise<T> {
  return new Promise<T>((resolver, rechazar) => {
    const temporizador = setTimeout(() => rechazar(new Error('Redis no contestó a tiempo.')), ms)
    promesa.then(
      (valor) => { clearTimeout(temporizador); resolver(valor) },
      (error) => { clearTimeout(temporizador); rechazar(error) },
    )
  })
}

function esEntrada(valor: unknown): valor is EntradaRespaldo {
  if (typeof valor !== 'object' || valor === null) return false
  const { cuerpo, tipo, guardadoEn } = valor as Record<string, unknown>
  return typeof cuerpo === 'string' && typeof tipo === 'string' && typeof guardadoEn === 'number' && Number.isFinite(guardadoEn)
}

export function crearAlmacenRedis(cliente: ClienteRedis, opciones: OpcionesAlmacen = {}): AlmacenRedis {
  const tiempoMs = opciones.tiempoMs ?? TIEMPO_COMANDO_MS
  const ahora = opciones.ahora ?? Date.now
  const ultimoInforme = new Map<OperacionRedis, number>()

  function informarFallo(operacion: OperacionRedis, error: unknown): void {
    const antes = ultimoInforme.get(operacion)
    const t = ahora()
    if (antes !== undefined && t - antes < INTERVALO_INFORME_MS) return
    ultimoInforme.set(operacion, t)
    try {
      opciones.alFallar?.(operacion, error)
    } catch {
      // El registro no puede romper al almacén.
    }
  }

  async function leer(clave: string): Promise<EntradaRespaldo | null> {
    if (!clave.startsWith(PREFIJO)) return null
    try {
      const crudo = await conLimite((async () => cliente.get(clave))(), tiempoMs)
      if (crudo === null) return null
      const valor: unknown = JSON.parse(crudo)
      return esEntrada(valor) ? valor : null
    } catch (error) {
      informarFallo('leer', error)
      return null
    }
  }

  async function guardar(clave: string, entrada: EntradaRespaldo, ttlSegundos: number): Promise<void> {
    if (!clave.startsWith(PREFIJO)) return
    // Sin TTL entero y positivo no se escribe: una clave inmortal no la expulsa `volatile-lru`.
    if (!Number.isInteger(ttlSegundos) || ttlSegundos <= 0) return
    try {
      await conLimite((async () => cliente.set(clave, JSON.stringify(entrada), 'EX', ttlSegundos))(), tiempoMs)
    } catch (error) {
      informarFallo('guardar', error)
    }
  }

  async function cerrar(): Promise<void> {
    try {
      await conLimite((async () => cliente.quit())(), tiempoMs)
    } catch {
      try {
        cliente.disconnect()
      } catch {
        // Ya estaba cerrado.
      }
    }
  }

  return { leer, guardar, cerrar, informarFallo }
}

export interface ConfigRedis {
  host: string
  port: number
  usuario: string
  password: string
}

/**
 * Las opciones de `ioredis` que se comprobaron contra Redis 8 con la ACL mínima (ficha 0C, H35 y B4):
 * - `enableReadyCheck: false`: el chequeo de «listo» envía `INFO`, que la ACL no permite.
 * - `disableClientInfo: true`: sin él, cada conexión envía `CLIENT SETINFO` y la ACL lo rechaza.
 * - `enableOfflineQueue: false` y `maxRetriesPerRequest: 0`: sin conexión, el comando falla al
 *   instante en vez de esperar en una cola.
 * - `connectTimeout` y `commandTimeout` cortos: el sitio no espera a una caché.
 * - Sin `lazyConnect`: con la cola desactivada, el primer comando de una conexión perezosa fallaría.
 */
export function opcionesRedis(config: ConfigRedis): RedisOptions {
  return {
    host: config.host,
    port: config.port,
    username: config.usuario,
    password: config.password,
    db: 0,
    enableReadyCheck: false,
    disableClientInfo: true,
    enableOfflineQueue: false,
    maxRetriesPerRequest: 0,
    connectTimeout: 500,
    commandTimeout: 300,
    retryStrategy: (intento: number) => Math.min(intento * 200, 5_000),
  }
}

/**
 * Conecta el almacén. Devuelve `null` si falta alguna pieza de la configuración: el sitio arranca
 * igual, sin respaldo (el sitio no depende de Redis para arrancar, ADR-0018).
 */
export async function conectarAlmacen(
  config: ConfigRedis,
  opciones: OpcionesAlmacen = {},
): Promise<AlmacenRedis | null> {
  const completa = config.host !== '' && config.usuario !== '' && config.password !== ''
    && Number.isInteger(config.port) && config.port > 0
  if (!completa) return null

  const { default: Redis } = await import('ioredis')
  const cliente = new Redis(opcionesRedis(config))
  const almacen = crearAlmacenRedis(cliente as unknown as ClienteRedis, opciones)

  // Con la cola desactivada, un comando enviado antes de terminar el saludo de conexión falla
  // («Stream isn't writeable»; medido contra Redis real). Se espera a `ready`, o al primer fallo si
  // Redis está caído, con un tope: el almacén se devuelve igual y falla abierto.
  const conectado = new Promise<void>((resolver) => {
    const temporizador = setTimeout(resolver, ESPERA_CONEXION_MS)
    const listo = () => { clearTimeout(temporizador); resolver() }
    cliente.once('ready', listo)
    cliente.once('error', listo)
    cliente.once('end', listo)
  })
  // Sin este oyente, `ioredis` escribe «Unhandled error event» en cada reintento.
  cliente.on('error', error => almacen.informarFallo('conexion', error))
  await conectado
  return almacen
}

type Crear = (config: ConfigRedis, opciones: OpcionesAlmacen) => Promise<AlmacenRedis | null>

const compartidos = new Map<string, Promise<AlmacenRedis | null>>()

/**
 * El almacén de TODO el proceso: una sola conexión por destino, aunque se pida en cada petición.
 *
 * `conectarAlmacen` abre una conexión nueva en cada llamada; llamarlo por petición agotaría las
 * conexiones del Redis compartido con Laravel. Esto lo hace imposible por construcción: la primera
 * llamada guarda la promesa y las demás (también las simultáneas) reciben la misma. Si abrir falla
 * (por ejemplo, no se pudo cargar `ioredis`) devuelve `null`, el sitio sigue sin respaldo, y la
 * siguiente petición lo reintenta. `crear` se inyecta solo para probar.
 */
export function almacenCompartido(
  config: ConfigRedis,
  opciones: OpcionesAlmacen = {},
  crear: Crear = conectarAlmacen,
): Promise<AlmacenRedis | null> {
  const clave = `${config.host}:${config.port}:${config.usuario}`
  const existente = compartidos.get(clave)
  if (existente !== undefined) return existente

  const completa = config.host !== '' && config.usuario !== '' && config.password !== ''
    && Number.isInteger(config.port) && config.port > 0
  if (!completa) return Promise.resolve(null)

  const promesa = crear(config, opciones).catch((error: unknown) => {
    compartidos.delete(clave)
    opciones.alFallar?.('conexion', error)
    return null
  })
  compartidos.set(clave, promesa)
  return promesa
}

/** Cierra las conexiones compartidas (apagado ordenado del servidor) y permite abrirlas de nuevo. */
export async function cerrarAlmacenesCompartidos(): Promise<void> {
  const abiertos = [...compartidos.values()]
  compartidos.clear()
  await Promise.all(abiertos.map(async (promesa) => {
    try {
      await (await promesa)?.cerrar()
    } catch {
      // Ya estaba cerrado.
    }
  }))
}
