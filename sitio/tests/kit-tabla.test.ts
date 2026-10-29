/**
 * Tabla ordenable del Kit UI (RF-B3-076, AC-28.1 y AC-28.9).
 *
 * El orden se prueba con las filas reales de `pqrsd.vue` (los términos del
 * artículo 14 del CPACA): son las que el requisito nombra.
 */
import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'

import TablaOrdenableGovco from '../app/components/govco/TablaOrdenableGovco.vue'
import type { ColumnaTabla, FilaTabla } from '../app/utils/tabla-ordenable'
import { violacionesAxe } from './ayudas/axe'

const columnas: ColumnaTabla[] = [
  { clave: 'tipo', titulo: 'Tipo de petición', ordenable: true },
  { clave: 'termino', titulo: 'Término para resolver', ordenable: true },
  { clave: 'nota', titulo: 'Nota' },
]

const filas: FilaTabla[] = [
  { id: 'a', celdas: { tipo: { texto: 'Regla general' }, termino: { texto: 'Quince (15) días', orden: 15 }, nota: { texto: 'n1' } } },
  { id: 'b', celdas: { tipo: { texto: 'Documentos' }, termino: { texto: 'Diez (10) días', orden: 10 }, nota: { texto: 'n2' } } },
  { id: 'c', celdas: { tipo: { texto: 'Consultas' }, termino: { texto: 'Treinta (30) días', orden: 30 }, nota: { texto: 'n3' } } },
]

let montado: ReturnType<typeof mount> | null = null

function montar(extra: Record<string, unknown> = {}) {
  montado = mount(TablaOrdenableGovco, {
    attachTo: document.body,
    props: { columnas, filas, leyenda: 'Términos', columnaEncabezadoFila: 'tipo', ...extra },
  })
  return montado
}

afterEach(() => {
  montado?.unmount()
  montado = null
})

function primeraColumna(t: ReturnType<typeof mount>): string[] {
  return t.findAll('tbody tr').map((fila) => fila.get('th').text())
}

function encabezado(t: ReturnType<typeof mount>, texto: string) {
  const th = t.findAll('thead th').find((c) => c.text().includes(texto))
  if (th === undefined) throw new Error(`No hay columna «${texto}»`)
  return th
}

describe('aria-sort', () => {
  it('las columnas ordenables arrancan en none y las demás no lo llevan', () => {
    const t = montar()
    expect(encabezado(t, 'Tipo').attributes('aria-sort')).toBe('none')
    expect(encabezado(t, 'Término').attributes('aria-sort')).toBe('none')
    expect(encabezado(t, 'Nota').attributes('aria-sort')).toBeUndefined()
  })

  it('el primer clic ordena ascendente; el segundo, descendente', async () => {
    const t = montar()
    await encabezado(t, 'Término').get('button').trigger('click')
    expect(encabezado(t, 'Término').attributes('aria-sort')).toBe('ascending')
    await encabezado(t, 'Término').get('button').trigger('click')
    expect(encabezado(t, 'Término').attributes('aria-sort')).toBe('descending')
  })

  it('al ordenar por otra columna la anterior vuelve a none', async () => {
    const t = montar()
    await encabezado(t, 'Término').get('button').trigger('click')
    await encabezado(t, 'Tipo').get('button').trigger('click')
    expect(encabezado(t, 'Término').attributes('aria-sort')).toBe('none')
    expect(encabezado(t, 'Tipo').attributes('aria-sort')).toBe('ascending')
  })
})

describe('el orden de las filas', () => {
  it('sin ordenar, el de llegada', () => {
    expect(primeraColumna(montar())).toEqual(['Regla general', 'Documentos', 'Consultas'])
  })

  it('por plazo usa el número, no la letra del texto (10 < 15 < 30)', async () => {
    const t = montar()
    await encabezado(t, 'Término').get('button').trigger('click')
    expect(primeraColumna(t)).toEqual(['Documentos', 'Regla general', 'Consultas'])
    await encabezado(t, 'Término').get('button').trigger('click')
    expect(primeraColumna(t)).toEqual(['Consultas', 'Regla general', 'Documentos'])
  })

  it('por texto usa el orden del español', async () => {
    const t = montar()
    await encabezado(t, 'Tipo').get('button').trigger('click')
    expect(primeraColumna(t)).toEqual(['Consultas', 'Documentos', 'Regla general'])
  })

  it('el orden no altera los datos recibidos', async () => {
    const copia = JSON.stringify(filas)
    const t = montar()
    await encabezado(t, 'Término').get('button').trigger('click')
    expect(JSON.stringify(filas)).toBe(copia)
  })

  it('es estable: las filas que empatan conservan el orden de llegada', async () => {
    const empate: FilaTabla[] = [
      { id: 1, celdas: { tipo: { texto: 'Z' }, termino: { texto: 'x', orden: 5 } } },
      { id: 2, celdas: { tipo: { texto: 'Y' }, termino: { texto: 'x', orden: 5 } } },
    ]
    const t = montar({ filas: empate })
    await encabezado(t, 'Término').get('button').trigger('click')
    expect(primeraColumna(t)).toEqual(['Z', 'Y'])
  })
})

describe('estructura y avisos', () => {
  it('las cabeceras de columna son scope="col" y las de fila scope="row"', () => {
    const t = montar()
    for (const th of t.findAll('thead th')) expect(th.attributes('scope')).toBe('col')
    for (const th of t.findAll('tbody th')) expect(th.attributes('scope')).toBe('row')
  })

  it('anuncia el orden aplicado en una región de estado', async () => {
    const t = montar()
    await encabezado(t, 'Término').get('button').trigger('click')
    expect(t.get('[role="status"]').text()).toBe(
      'Tabla ordenada por Término para resolver, de forma ascendente.',
    )
    await encabezado(t, 'Término').get('button').trigger('click')
    expect(t.get('[role="status"]').text()).toContain('descendente')
  })

  it('el icono es decorativo y la leyenda es el caption', () => {
    const t = montar()
    expect(t.get('caption').text()).toBe('Términos')
    for (const icono of t.findAll('button .govco-svg')) {
      expect(icono.attributes('aria-hidden')).toBe('true')
    }
  })
})

describe('axe', () => {
  it('sin violaciones sin ordenar y ordenada en las dos direcciones', async () => {
    const t = montar()
    expect(await violacionesAxe(t.element)).toEqual([])
    await encabezado(t, 'Término').get('button').trigger('click')
    expect(await violacionesAxe(t.element)).toEqual([])
    await encabezado(t, 'Término').get('button').trigger('click')
    expect(await violacionesAxe(t.element)).toEqual([])
  })
})
