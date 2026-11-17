/**
 * Guardia de datos institucionales en el código del sitio (Fase 0B, T7; D-0.4).
 *
 * Los datos de la Entidad (teléfonos, correos, dirección, código postal, NIT, lista de
 * dependencias) viven en base de datos y se leen por la API. Escribirlos en el código crea
 * copias que se desactualizan: hoy ya hay tres copias del contacto, y una de ellas con el
 * teléfono escrito distinto. Esta prueba impide que aparezcan más.
 *
 * Línea base con trinquete:
 *  - un literal que NO está en `datos-institucionales.linea-base.json` rompe la prueba;
 *  - una entrada de la línea base que YA NO existe en el código también la rompe, para que
 *    la lista solo pueda bajar (cada archivo que la Fase 1 limpie sale de ella);
 *  - la única excepción permanente es el respaldo del pie de página (`permanente: true`).
 */
import { readdirSync, readFileSync, statSync } from 'node:fs'
import { join, relative, resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

import {
  clave,
  compararConLineaBase,
  detectarLiterales,
  type EntradaLineaBase,
} from './ayudas/datos-institucionales'

const raiz = resolve(__dirname, '..')

function listar(dir: string): string[] {
  return readdirSync(dir).flatMap((nombre) => {
    const ruta = join(dir, nombre)
    return statSync(ruta).isDirectory() ? listar(ruta) : [ruta]
  })
}

describe('detector de literales institucionales', () => {
  const detectar = (texto: string) => detectarLiterales('x.vue', texto).map((l) => `${l.tipo}:${l.valor}`)

  it('detecta un correo institucional', () => {
    expect(detectar("const c = 'nuevo@santamarta.gov.co'")).toEqual(['correo:nuevo@santamarta.gov.co'])
  })

  it('detecta un teléfono y lo normaliza sin importar los espacios', () => {
    expect(detectar("'(+57) 605 4351719'")).toEqual(['telefono:576054351719'])
    expect(detectar("'(+57) 605 435 1719'")).toEqual(['telefono:576054351719'])
  })

  it('detecta dirección, código postal, NIT y dependencias', () => {
    const texto = [
      "direccion: 'Calle 14 n.º 2-49, Palacio'",
      "codigo: '470004'",
      "nit: 'NIT 891.780.009-4'",
      "const DEPENDENCIAS: readonly string[] = ['Despacho del Alcalde', 'Secretaría de Hacienda']",
    ].join('\n')
    expect(detectar(texto)).toEqual([
      'direccion:Calle 14 n.º 2-49',
      'codigo_postal:470004',
      'nit:891.780.009-4',
      'dependencia:Despacho del Alcalde',
      'dependencia:Secretaría de Hacienda',
    ])
  })

  it('no detecta código limpio ni lo que está en comentarios', () => {
    const limpio = [
      '<!-- Despacho del Alcalde, atencionalciudadano@santamarta.gov.co -->',
      '/* (+57) 605 420 9600 */',
      '// Calle 14 n.º 2-49',
      "const total = items.length + 470",
      "const url = 'https://example.org/a'",
    ].join('\n')
    expect(detectar(limpio)).toEqual([])
  })
})

describe('comparación con la línea base (trinquete)', () => {
  const base: EntradaLineaBase[] = [
    { archivo: 'a.vue', tipo: 'correo', valor: 'x@santamarta.gov.co' },
    { archivo: 'b.vue', tipo: 'nit', valor: '891.780.009-4', permanente: true },
  ]

  it('sin diferencias, no hay nuevos ni obsoletos', () => {
    const r = compararConLineaBase(
      [{ archivo: 'a.vue', tipo: 'correo', valor: 'x@santamarta.gov.co' }, { archivo: 'b.vue', tipo: 'nit', valor: '891.780.009-4' }],
      base,
    )
    expect(r).toEqual({ nuevos: [], obsoletos: [] })
  })

  it('un literal fuera de la línea base es nuevo', () => {
    const r = compararConLineaBase(
      [{ archivo: 'a.vue', tipo: 'correo', valor: 'otro@santamarta.gov.co' }, { archivo: 'a.vue', tipo: 'correo', valor: 'x@santamarta.gov.co' }, { archivo: 'b.vue', tipo: 'nit', valor: '891.780.009-4' }],
      base,
    )
    expect(r.nuevos.map(clave)).toEqual(['a.vue|correo|otro@santamarta.gov.co'])
  })

  it('una entrada de la línea base que ya no está en el código es obsoleta, también la permanente', () => {
    const r = compararConLineaBase([], base)
    expect(r.obsoletos.map(clave)).toEqual(['a.vue|correo|x@santamarta.gov.co', 'b.vue|nit|891.780.009-4'])
  })
})

describe('código real del sitio', () => {
  const lineaBase: EntradaLineaBase[] = JSON.parse(
    readFileSync(join(__dirname, 'datos-institucionales.linea-base.json'), 'utf8'),
  ).entradas

  const encontrados = ['app', 'server']
    .flatMap((d) => listar(join(raiz, d)))
    .filter((f) => /\.(vue|ts)$/.test(f))
    .flatMap((f) => detectarLiterales(relative(raiz, f), readFileSync(f, 'utf8')))

  const { nuevos, obsoletos } = compararConLineaBase(encontrados, lineaBase)

  it('no hay datos institucionales nuevos escritos en el código', () => {
    expect(nuevos.map(clave), 'Mueve el dato a la base de datos y léelo por la API; no lo agregues a la línea base.').toEqual([])
  })

  it('no quedan entradas obsoletas en la línea base', () => {
    expect(obsoletos.map(clave), 'Quita de la línea base lo que ya limpiaste: la lista solo puede bajar.').toEqual([])
  })

  it('el único motivo de excepción permanente es el respaldo del pie de página', () => {
    const permanentes = lineaBase.filter((e) => e.permanente)
    expect([...new Set(permanentes.map((e) => e.archivo))]).toEqual(['app/components/govco/PiePaginaGovco.vue'])
  })
})
