/**
 * AuditoriaView: la bitácora se lee como frases, en una tabla, con filtros por módulo y fechas.
 *
 * Lo que vive quien audita: que ninguna fila muestre un nombre interno (`noticia_eliminada`), que un intento
 * rechazado se distinga a simple vista, que filtrar y paginar pida los datos al servidor y que cada estado
 * vacío o de error diga la verdad.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { conSesion } from './ayudas/sesion'
import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('../src/api/auditoria', () => ({
  POR_PAGINA: 20,
  listar: vi.fn(),
}))

import * as servicio from '../src/api/auditoria'
import AuditoriaView from '../src/views/admin/AuditoriaView.vue'
import type { AuditoriaItem } from '../src/types/api'

const m = vi.mocked(servicio)

function fila(extra: Partial<AuditoriaItem> = {}): AuditoriaItem {
  return {
    id: 1,
    type: 'auditoria',
    accion: 'noticia_eliminada',
    descripcion: 'Eliminó una noticia',
    recurso: 'noticias',
    recurso_nombre: 'Noticias',
    recurso_id: 5,
    cambios: { titulo: 'Entrega de obras' },
    ip_origen: '10.0.0.7',
    user_agent: 'x',
    usuario: { id: 3, email: 'ana@santamarta.gov.co' },
    created_at: '2026-11-19T04:10:00+00:00',
    ...extra,
  } as AuditoriaItem
}

function pagina(datos: AuditoriaItem[], extra: Partial<{ current: number; last: number; total: number }> = {}) {
  const total = extra.total ?? datos.length
  return {
    datos,
    meta: { current_page: extra.current ?? 1, from: total === 0 ? null : 1, last_page: extra.last ?? 1, path: '/x', per_page: 20, to: datos.length, total },
    enlaces: { first: null, last: null, prev: null, next: null },
  }
}

async function montar() {
  const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/auditoria', name: 'auditoria', component: { template: '<i/>' } }] })
  await router.push('/auditoria')
  await router.isReady()
  const wrapper = mount(AuditoriaView, { global: { plugins: [router], stubs: { FaIcon: true } } })
  await flushPromises()
  return wrapper
}

beforeEach(() => {
  conSesion()
  vi.clearAllMocks()
  m.listar.mockResolvedValue(pagina([]))
})

describe('se lee como frases', () => {
  it('muestra una tabla con quién, qué hizo, sobre qué y el detalle', async () => {
    m.listar.mockResolvedValue(pagina([fila()]))
    const w = await montar()

    const encabezados = w.findAll('th').map((t) => t.text())
    for (const nombre of ['Fecha', 'Quién', 'Qué hizo', 'Sobre', 'Detalle']) {
      expect(encabezados.some((t) => t.includes(nombre))).toBe(true)
    }
    const celdas = w.find('tbody tr').text()
    expect(celdas).toContain('ana@santamarta.gov.co')
    expect(celdas).toContain('Eliminó una noticia')
    expect(celdas).toContain('Noticias')
    expect(celdas).toContain('«Entrega de obras»')
  })

  it('nunca muestra el nombre interno de la acción', async () => {
    m.listar.mockResolvedValue(pagina([fila(), fila({ id: 2, accion: 'acceso_denegado', descripcion: 'Intento sin permiso suficiente' })]))
    const w = await montar()

    expect(w.text()).not.toContain('noticia_eliminada')
    expect(w.text()).not.toContain('acceso_denegado')
  })

  it('un intento rechazado se distingue con una insignia en palabras', async () => {
    m.listar.mockResolvedValue(
      pagina([
        fila({ id: 2, accion: 'acceso_denegado', descripcion: 'Intento sin permiso suficiente', cambios: { metodo: 'POST', estado: 403, ruta: 'api/v1/panel/noticias' } }),
        fila({ id: 1 }),
      ]),
    )
    const w = await montar()
    const filas = w.findAll('tbody tr')

    expect(filas[0]!.text()).toContain('Rechazado')
    expect(filas[1]!.text()).not.toContain('Rechazado')
  })

  it('una acción de sistema sin cuenta se dice «Sin cuenta», no se deja en blanco', async () => {
    m.listar.mockResolvedValue(pagina([fila({ usuario: undefined })]))
    const w = await montar()

    expect(w.find('tbody tr').text()).toContain('Sin cuenta')
  })
})

describe('filtros y páginas piden los datos al servidor', () => {
  it('filtrar por módulo y fechas reinicia en la página 1', async () => {
    m.listar.mockResolvedValue(pagina([fila()], { last: 3, total: 50 }))
    const w = await montar()

    await w.find('#auditoria-modulo').setValue('banners')
    await w.find('#auditoria-desde').setValue('2026-11-01')
    await w.find('#auditoria-hasta').setValue('2026-11-19')
    await flushPromises()

    const ultima = m.listar.mock.calls.at(-1)![0]!
    expect(ultima).toMatchObject({ recurso: 'banners', desde: '2026-11-01', hasta: '2026-11-19', pagina: 1 })
  })

  it('«Quitar filtros» vuelve a pedir todo', async () => {
    m.listar.mockResolvedValue(pagina([fila()]))
    const w = await montar()
    await w.find('#auditoria-modulo').setValue('banners')
    await flushPromises()

    const quitar = w.findAll('button').find((b) => b.text().includes('Quitar filtros'))!
    await quitar.trigger('click')
    await flushPromises()

    expect(m.listar.mock.calls.at(-1)![0]).toMatchObject({ recurso: '', desde: '', hasta: '' })
  })
})

describe('estados', () => {
  it('sin registros dice que aún no hay nada', async () => {
    const w = await montar()
    expect(w.text()).toContain('Aún no hay registros')
  })

  it('con filtros y sin coincidencias lo dice distinto', async () => {
    m.listar.mockResolvedValueOnce(pagina([fila()]))
    const w = await montar()
    m.listar.mockResolvedValue(pagina([]))
    await w.find('#auditoria-modulo').setValue('banners')
    await flushPromises()

    expect(w.text()).toContain('Ningún registro coincide con los filtros')
  })

  it('si falla la carga lo dice y deja reintentar', async () => {
    m.listar.mockRejectedValueOnce(new Error('x'))
    const w = await montar()
    expect(w.find('[role=alert]').text()).toContain('No se pudo cargar la auditoría')

    m.listar.mockResolvedValue(pagina([fila()]))
    await w.find('[role=alert] button').trigger('click')
    await flushPromises()
    expect(w.text()).toContain('Eliminó una noticia')
  })

  it('explica qué se registra y qué no', async () => {
    const w = await montar()
    expect(w.text()).toContain('Leer no se registra')
  })
})
