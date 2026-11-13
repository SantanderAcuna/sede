/**
 * TramitesListaView — la lista de trámites como tabla, con servicio simulado.
 *
 * Se verifica lo que vive quien administra: tabla (no tarjetas), búsqueda/estado/orden/página pedidos al
 * servidor, estados vacíos que dicen la verdad, y que «Eliminar» solo exista para los trámites creados
 * aquí (los del catálogo oficial se despublican, no se borran) y nunca sin confirmar.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { conSesion } from './ayudas/sesion'
import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('../src/services/tramites', () => ({
  POR_PAGINA: 20,
  listar: vi.fn(),
  eliminar: vi.fn(),
}))

import * as servicio from '../src/services/tramites'
import TramitesListaView from '../src/views/admin/TramitesListaView.vue'
import type { TramitePanelFila } from '../src/types/api'

const m = vi.mocked(servicio)

function tramite(extra: Partial<TramitePanelFila> = {}): TramitePanelFila {
  return {
    id: 1,
    uuid: '11111111-1111-4111-8111-111111111111',
    type: 'tramites',
    codigo: 'P0001',
    slug: 'un-tramite',
    nombre: 'Un trámite',
    modalidad: 'presencial',
    estado: 'borrador',
    completo: false,
    problemas: ['Falta al menos un requisito.'],
    publicado_en: null,
    actualizado_en: '2026-11-10T09:00:00+00:00',
    ...extra,
  } as TramitePanelFila
}

function pagina(datos: TramitePanelFila[], extra: Partial<{ current: number; last: number; total: number }> = {}) {
  const total = extra.total ?? datos.length
  return {
    datos,
    meta: { current_page: extra.current ?? 1, from: total === 0 ? null : 1, last_page: extra.last ?? 1, path: '/x', per_page: 20, to: total === 0 ? null : datos.length, total },
    enlaces: { first: null, last: null, prev: null, next: null },
  }
}

const enrutador = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/tramites', name: 'tramites', component: { template: '<i/>' } },
      { path: '/tramites/nuevo', name: 'panel.tramites.nuevo', component: { template: '<i/>' } },
      { path: '/tramites/:uuid', name: 'panel.tramites.editar', component: { template: '<i/>' } },
    ],
  })

async function montar() {
  const router = enrutador()
  await router.push('/tramites')
  await router.isReady()
  const wrapper = mount(TramitesListaView, { global: { plugins: [router], stubs: { FaIcon: true, teleport: true } } })
  await flushPromises()
  return { wrapper, router }
}

const ultima = () => m.listar.mock.calls.at(-1)![0]

beforeEach(() => {
  conSesion()
  vi.clearAllMocks()
  m.listar.mockResolvedValue(pagina([]))
})
afterEach(() => vi.useRealTimers())

describe('tabla', () => {
  it('lista los elementos en una tabla con código, estado, tipo y acciones', async () => {
    m.listar.mockResolvedValue(pagina([tramite({ nombre: 'Uno', uuid: 'a' }), tramite({ nombre: 'Dos', uuid: 'b', codigo: 'T100', estado: 'publicado', completo: true, problemas: [] })]))
    const { wrapper } = await montar()
    const encabezados = wrapper.findAll('th').map((t) => t.text())
    for (const n of ['Nombre', 'Código', 'Estado', 'Tipo', 'Actualizado', 'Acciones']) expect(encabezados.some((t) => t.includes(n))).toBe(true)
    const filas = wrapper.findAll('tbody tr').map((f) => f.text())
    expect(filas).toHaveLength(2)
    expect(filas[0]).toContain('Borrador')
    expect(filas[1]).toContain('Publicado')
  })

  it('la columna de tipo distingue trámite, OPA y consulta', async () => {
    m.listar.mockResolvedValue(pagina([
      tramite({ uuid: 'a', nombre: 'Trámite', type: 'tramites' }),
      tramite({ uuid: 'b', nombre: 'OPA', type: 'opa', codigo: 'O1' } as never),
      tramite({ uuid: 'c', nombre: 'Consulta', type: 'consultas', codigo: 'C1' } as never),
    ]))
    const { wrapper } = await montar()
    const cuerpo = wrapper.find('tbody').text()
    expect(cuerpo).toContain('Trámite')
    expect(cuerpo).toContain('OPA')
    expect(cuerpo).toContain('Consulta')
  })

  it('un borrador dice qué le falta', async () => {
    m.listar.mockResolvedValue(pagina([tramite({ problemas: ['a', 'b', 'c'] })]))
    const { wrapper } = await montar()
    expect(wrapper.find('tbody').text()).toContain('Faltan 3 datos para publicar')
  })

  it('el nombre y «Editar» llevan a la vista de edición', async () => {
    m.listar.mockResolvedValue(pagina([tramite({ uuid: 'abc', nombre: 'Licencia' })]))
    const { wrapper } = await montar()
    expect(wrapper.find('tbody a[href$="/tramites/abc"]').text()).toBe('Licencia')
    expect(wrapper.find('tbody a[aria-label^="Editar"]').attributes('aria-label')).toContain('Licencia')
  })

  it('«Eliminar» existe en todos los elementos (todos son borrables)', async () => {
    m.listar.mockResolvedValue(pagina([tramite({ uuid: 'a', nombre: 'Uno' }), tramite({ uuid: 'b', nombre: 'Dos', codigo: 'T1' })]))
    const { wrapper } = await montar()
    const botones = wrapper.findAll('tbody button[aria-label*="Eliminar"]')
    expect(botones).toHaveLength(2)
  })

  it('«Nuevo trámite» lleva a la vista de creación', async () => {
    const { wrapper } = await montar()
    expect(wrapper.findAll('a').find((a) => a.text().includes('Nuevo trámite'))?.attributes('href')).toBe('/tramites/nuevo')
  })

  it('la primera carga pide la página 1 ordenada por lo editado más reciente', async () => {
    await montar()
    expect(ultima()).toMatchObject({ pagina: 1, ordenarPor: 'actualizado_en', direccion: 'desc' })
  })
})

describe('estados de la lista', () => {
  it('sin trámites y sin filtros: lo dice y ofrece crear el primero', async () => {
    const { wrapper } = await montar()
    expect(wrapper.text()).toContain('Aún no hay trámites')
    expect(wrapper.findAll('a').some((a) => a.text().includes('Crear el primer trámite'))).toBe(true)
  })

  it('con filtros sin coincidencias: no dice que no hay trámites y deja quitar filtros', async () => {
    vi.useFakeTimers()
    m.listar.mockResolvedValue(pagina([tramite()]))
    const { wrapper } = await montar()
    m.listar.mockResolvedValue(pagina([]))
    await wrapper.find('input[type="search"]').setValue('zzz')
    vi.advanceTimersByTime(500)
    await flushPromises()
    expect(wrapper.text()).toContain('Ningún trámite coincide')
    m.listar.mockClear()
    await wrapper.findAll('button').find((b) => b.text().includes('Quitar filtros'))!.trigger('click')
    await flushPromises()
    expect(ultima()).toMatchObject({ q: '', estado: '' })
  })

  it('si la carga falla lo dice y deja reintentar', async () => {
    m.listar.mockRejectedValueOnce(new Error('caída'))
    const { wrapper } = await montar()
    expect(wrapper.find('[role="alert"]').text()).toContain('No se pudieron cargar los trámites')
    m.listar.mockResolvedValue(pagina([tramite({ nombre: 'Ya cargó' })]))
    await wrapper.findAll('button').find((b) => b.text().includes('Reintentar'))!.trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Ya cargó')
  })
})

describe('buscar, filtrar, ordenar y paginar', () => {
  it('buscar espera a que se deje de escribir', async () => {
    vi.useFakeTimers()
    m.listar.mockResolvedValue(pagina([tramite()]))
    const { wrapper } = await montar()
    m.listar.mockClear()
    const campo = wrapper.find('input[type="search"]')
    await campo.setValue('pre')
    await campo.setValue('predial')
    vi.advanceTimersByTime(100)
    expect(m.listar).not.toHaveBeenCalled()
    vi.advanceTimersByTime(400)
    await flushPromises()
    expect(m.listar).toHaveBeenCalledTimes(1)
    expect(ultima()).toMatchObject({ q: 'predial', pagina: 1 })
  })

  it('el buscador tiene etiqueta', async () => {
    m.listar.mockResolvedValue(pagina([tramite()]))
    const { wrapper } = await montar()
    const id = wrapper.find('input[type="search"]').attributes('id')!
    expect(wrapper.find(`label[for="${id}"]`).text()).toContain('Buscar')
  })

  it('filtrar por estado ofrece publicado y borrador y vuelve a la página 1', async () => {
    m.listar.mockResolvedValue(pagina([tramite()], { current: 2, last: 3, total: 55 }))
    const { wrapper } = await montar()
    expect(wrapper.findAll('select option').map((o) => o.text())).toEqual(['Todos los estados', 'Publicado', 'Borrador'])
    m.listar.mockClear()
    await wrapper.find('select').setValue('borrador')
    await flushPromises()
    expect(ultima()).toMatchObject({ estado: 'borrador', pagina: 1 })
  })

  it('ordenar por nombre y un segundo clic invierte', async () => {
    m.listar.mockResolvedValue(pagina([tramite()]))
    const { wrapper } = await montar()
    const boton = () => wrapper.findAll('th button').find((b) => b.text().includes('Nombre'))!
    m.listar.mockClear()
    await boton().trigger('click')
    await flushPromises()
    expect(ultima()).toMatchObject({ ordenarPor: 'nombre', direccion: 'asc' })
    await boton().trigger('click')
    await flushPromises()
    expect(ultima()).toMatchObject({ ordenarPor: 'nombre', direccion: 'desc' })
  })

  it('paginar conserva búsqueda y orden', async () => {
    vi.useFakeTimers()
    m.listar.mockResolvedValue(pagina([tramite()], { current: 1, last: 3, total: 55 }))
    const { wrapper } = await montar()
    await wrapper.find('input[type="search"]').setValue('predial')
    vi.advanceTimersByTime(500)
    await flushPromises()
    m.listar.mockClear()
    await wrapper.find('button[aria-label="Página 2"]').trigger('click')
    await flushPromises()
    expect(ultima()).toMatchObject({ pagina: 2, q: 'predial', ordenarPor: 'actualizado_en' })
  })
})

describe('eliminar', () => {
  async function conUna() {
    m.listar.mockResolvedValue(pagina([tramite({ uuid: 'u-1', nombre: 'Permiso de feria' })]))
    const montada = await montar()
    await montada.wrapper.find('tbody button[aria-label*="Eliminar"]').trigger('click')
    await flushPromises()
    return montada
  }

  it('abre un diálogo que nombra el trámite y no elimina todavía', async () => {
    const { wrapper } = await conUna()
    expect(wrapper.find('[role="dialog"]').text()).toContain('Permiso de feria')
    expect(m.eliminar).not.toHaveBeenCalled()
  })

  it('confirmar elimina, recarga y avisa', async () => {
    m.eliminar.mockResolvedValue(undefined)
    const { wrapper } = await conUna()
    m.listar.mockClear()
    await wrapper.findAll('[role="dialog"] button').find((b) => b.text().includes('Sí, eliminar'))!.trigger('click')
    await flushPromises()
    expect(m.eliminar).toHaveBeenCalledWith('u-1')
    expect(m.listar).toHaveBeenCalled()
    expect(wrapper.find('[role="status"]').text()).toContain('Trámite eliminado')
  })

  it('si el servidor lo impide (409) lo dice dentro del diálogo', async () => {
    m.eliminar.mockRejectedValue(Object.assign(new Error('x'), { isAxiosError: true, response: { status: 409, data: { message: 'Este trámite viene del catálogo oficial' } } }))
    const { wrapper } = await conUna()
    await wrapper.findAll('[role="dialog"] button').find((b) => b.text().includes('Sí, eliminar'))!.trigger('click')
    await flushPromises()
    expect(wrapper.find('[role="dialog"] [role="alert"]').text()).toContain('catálogo oficial')
  })
})
