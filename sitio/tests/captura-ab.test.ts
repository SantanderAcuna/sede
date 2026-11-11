/**
 * La captura visual contra la rama base (hallazgo del 2026-11-10).
 *
 * Por qué existe. La captura visual guardaba PNG en `tests/visual/referencia/` y comparaba
 * por hash, byte a byte. Medido: esa referencia (fijada el 2026-10-02) NO se reproduce en
 * otra máquina ni con el código de ese mismo día —las 11 vistas salen «cambiadas» y cuatro
 * páginas que no tienen datos de la API (accesibilidad, atención, normativa, PQRSD) difieren
 * ya en su altura—, mientras que dos ejecuciones seguidas en una misma máquina dan hashes
 * idénticos. Es decir: el hash es determinista **dentro de una máquina** y no entre máquinas.
 *
 * La comparación correcta es, entonces, entre dos renders de la MISMA máquina: la rama base
 * y el cambio. Estas pruebas fijan la parte pura de esa comparación.
 */
import { createHash } from 'node:crypto'
import { describe, expect, it } from 'vitest'

import {
  alturaDePng,
  compararJuegos,
  huella,
  resumirComparacion,
} from '../scripts/lib/captura-ab.mjs'

/** Un PNG mínimo válido en sus primeros 24 bytes (firma + IHDR) con el ancho y alto dados. */
function png(ancho: number, alto: number, relleno = ''): Buffer {
  const cabecera = Buffer.alloc(24)
  Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]).copy(cabecera, 0)
  cabecera.writeUInt32BE(13, 8)
  cabecera.write('IHDR', 12, 'ascii')
  cabecera.writeUInt32BE(ancho, 16)
  cabecera.writeUInt32BE(alto, 20)
  return Buffer.concat([cabecera, Buffer.from(relleno)])
}

const juego = (entradas: Record<string, Buffer>) => new Map(Object.entries(entradas))

describe('huella y alturaDePng', () => {
  it('la huella es el SHA-256 abreviado a 16 caracteres, como la de la captura de siempre', () => {
    const b = Buffer.from('hola')
    expect(huella(b)).toBe(createHash('sha256').update(b).digest('hex').slice(0, 16))
    expect(huella(b)).toHaveLength(16)
  })

  it('lee la altura del encabezado del PNG', () => {
    expect(alturaDePng(png(1300, 2887))).toBe(2887)
    expect(alturaDePng(png(390, 844))).toBe(844)
  })

  it('un búfer que no es PNG no tiene altura', () => {
    expect(alturaDePng(Buffer.from('no soy un png, pero largo: 0123456789012345'))).toBeNull()
    expect(alturaDePng(Buffer.alloc(3))).toBeNull()
  })
})

describe('compararJuegos', () => {
  it('dos juegos idénticos: todas las vistas «igual»', () => {
    const a = juego({ portada: png(10, 20, 'x'), pqrsd: png(10, 30, 'y') })
    const b = juego({ portada: png(10, 20, 'x'), pqrsd: png(10, 30, 'y') })
    const r = compararJuegos(a, b)
    expect(r.map((v) => v.estado)).toEqual(['igual', 'igual'])
  })

  it('una vista con otro contenido es «cambiada» y dice la altura de cada lado', () => {
    const base = juego({ portada: png(1300, 2577, 'a') })
    const actual = juego({ portada: png(1300, 2600, 'b') })
    const [v] = compararJuegos(base, actual)
    expect(v).toMatchObject({ nombre: 'portada', estado: 'cambiada', alturaBase: 2577, alturaActual: 2600 })
    expect(v.huellaBase).not.toBe(v.huellaActual)
  })

  it('misma altura pero distinto contenido también es «cambiada» (un color, por ejemplo)', () => {
    const [v] = compararJuegos(juego({ a: png(5, 5, 'x') }), juego({ a: png(5, 5, 'z') }))
    expect(v.estado).toBe('cambiada')
    expect(v.alturaBase).toBe(v.alturaActual)
  })

  it('una vista que sólo existe de un lado se señala, no se ignora', () => {
    const r = compararJuegos(juego({ a: png(1, 1), b: png(1, 1) }), juego({ a: png(1, 1), c: png(1, 1) }))
    const estados = Object.fromEntries(r.map((v) => [v.nombre, v.estado]))
    expect(estados).toEqual({ a: 'igual', b: 'falta-en-actual', c: 'falta-en-base' })
  })

  it('mantiene el orden de la base y luego las que sólo están en la actual', () => {
    const r = compararJuegos(juego({ z: png(1, 1), a: png(1, 1) }), juego({ a: png(1, 1), z: png(1, 1), m: png(1, 1) }))
    expect(r.map((v) => v.nombre)).toEqual(['z', 'a', 'm'])
  })

  it('dos juegos vacíos no son una comparación válida: se devuelve vacío y el resumen lo rechaza', () => {
    expect(compararJuegos(new Map(), new Map())).toEqual([])
    expect(resumirComparacion([]).codigoDeSalida).toBe(2)
  })
})

describe('resumirComparacion', () => {
  it('todo igual: salida 0', () => {
    const r = resumirComparacion(compararJuegos(juego({ a: png(1, 1) }), juego({ a: png(1, 1) })))
    expect(r).toMatchObject({ iguales: 1, distintas: 0, codigoDeSalida: 0 })
  })

  it('cualquier vista cambiada: salida 1 y cuenta las distintas', () => {
    const r = resumirComparacion(
      compararJuegos(juego({ a: png(1, 1, 'x'), b: png(1, 1) }), juego({ a: png(1, 1, 'y'), b: png(1, 1) })),
    )
    expect(r).toMatchObject({ iguales: 1, distintas: 1, codigoDeSalida: 1 })
  })

  it('una vista faltante cuenta como diferencia: no se puede dar por buena una comparación incompleta', () => {
    const r = resumirComparacion(compararJuegos(juego({ a: png(1, 1) }), juego({})))
    expect(r.codigoDeSalida).toBe(1)
    expect(r.distintas).toBe(1)
  })
})
