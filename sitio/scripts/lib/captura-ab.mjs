/**
 * Comparación de dos juegos de capturas hechos en la MISMA máquina (rama base y cambio).
 *
 * Lógica pura —sin navegador ni sistema de archivos— para poder probarla: ver
 * `tests/captura-ab.test.ts`, que explica por qué se compara contra la rama base y no contra
 * PNG guardados.
 */
import { createHash } from 'node:crypto'

/** SHA-256 abreviado a 16 caracteres (el mismo formato que usaba la captura de siempre). */
export function huella(buffer) {
  return createHash('sha256').update(buffer).digest('hex').slice(0, 16)
}

const FIRMA_PNG = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a])

/** Altura en píxeles según el encabezado IHDR; `null` si el búfer no es un PNG. */
export function alturaDePng(buffer) {
  if (!Buffer.isBuffer(buffer) || buffer.length < 24) return null
  if (!buffer.subarray(0, 8).equals(FIRMA_PNG)) return null
  if (buffer.toString('ascii', 12, 16) !== 'IHDR') return null
  return buffer.readUInt32BE(20)
}

/**
 * Compara vista por vista. Conserva el orden de la base y añade al final las vistas que sólo
 * están en la actual. Una vista que falta de un lado se señala: una comparación incompleta no
 * puede darse por buena.
 *
 * @param {Map<string, Buffer>} base
 * @param {Map<string, Buffer>} actual
 */
export function compararJuegos(base, actual) {
  const nombres = [...base.keys(), ...[...actual.keys()].filter((n) => !base.has(n))]

  return nombres.map((nombre) => {
    const b = base.get(nombre)
    const a = actual.get(nombre)

    if (b === undefined) {
      return { nombre, estado: 'falta-en-base', alturaBase: null, alturaActual: alturaDePng(a), huellaBase: null, huellaActual: huella(a) }
    }
    if (a === undefined) {
      return { nombre, estado: 'falta-en-actual', alturaBase: alturaDePng(b), alturaActual: null, huellaBase: huella(b), huellaActual: null }
    }

    const huellaBase = huella(b)
    const huellaActual = huella(a)
    return {
      nombre,
      estado: huellaBase === huellaActual ? 'igual' : 'cambiada',
      alturaBase: alturaDePng(b),
      alturaActual: alturaDePng(a),
      huellaBase,
      huellaActual,
    }
  })
}

/**
 * Totales y código de salida: 0 todo igual · 1 hay diferencias (incluye vistas que faltan) ·
 * 2 no hay nada que comparar (dos juegos vacíos no demuestran nada).
 */
export function resumirComparacion(vistas) {
  if (vistas.length === 0) return { iguales: 0, distintas: 0, codigoDeSalida: 2 }
  const iguales = vistas.filter((v) => v.estado === 'igual').length
  const distintas = vistas.length - iguales
  return { iguales, distintas, codigoDeSalida: distintas > 0 ? 1 : 0 }
}
