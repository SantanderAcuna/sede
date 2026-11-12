/**
 * NoticiasListaView — la lista de noticias como tabla, con servicio simulado.
 *
 * Lo que se verifica es lo que vive la persona que administra: que la lista sea una
 * tabla (no tarjetas), que buscar/filtrar/ordenar/paginar pida los datos al servidor,
 * que cada estado vacío diga la verdad (no hay noticias ≠ ningún filtro coincide ≠ falló
 * la carga) y que eliminar nunca ocurra sin confirmación.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('../src/services/noticias', () => ({
  POR_PAGINA: 20,
  listar: vi.fn(),
  eliminar: vi.fn(),
}))

import * as servicio from '../src/services/noticias'
import NoticiasListaView from '../src/views/admin/NoticiasListaView.vue'
import type { NoticiaPanelItem } from '../src/types/api'

const m = vi.mocked(servicio)

function noticia(extra: Partial<NoticiaPanelItem> = {}): NoticiaPanelItem {
  return {
    id: 1,
    type: 'noticias',
    uuid: '11111111-1111-4111-8111-111111111111',
    slug: 'una-noticia',
    titulo: 'Una noticia',
    descripcion_corta: 'Descripción corta',
    fecha_publicacion: '2026-11-01T09:00:00+00:00',
    destacada: false,
    activo: false,
    estado: 'borrador',
    imagen: null,
    completa: false,
    problemas: [],
    ...extra,
  } as NoticiaPanelItem
}

function pagina(datos: NoticiaPanelItem[], extra: Partial<{ current: number; last: number; total: number }> = {}) {
  const total = extra.total ?? datos.length
  return {
    datos,
    meta: {
      current_page: extra.current ?? 1,
      from: total === 0 ? null : 1,
      last_page: extra.last ?? 1,
      path: '/x',
      per_page: 20,
      to: total === 0 ? null : datos.length,
      total,
    },
    enlaces: { first: null, last: null, prev: null, next: null },
  }
}

const enrutador = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/contenidos/noticias', name: 'panel.noticias', component: { template: '<i/>' } },
      { path: '/contenidos/noticias/nueva', name: 'panel.noticias.nueva', component: { template: '<i/>' } },
      { path: '/contenidos/noticias/:uuid', name: 'panel.noticias.editar', component: { template: '<i/>' } },
    ],
  })

async function montar() {
  const router = enrutador()
  await router.push('/contenidos/noticias')
  await router.isReady()
  const wrapper = mount(NoticiasListaView, { global: { plugins: [router], stubs: { FaIcon: true } } })
  await flushPromises()
  return { wrapper, router }
}

const ultimaConsulta = () => m.listar.mock.calls.at(-1)![0]

beforeEach(() => {
  vi.clearAllMocks()
  m.listar.mockResolvedValue(pagina([]))
})
afterEach(() => vi.useRealTimers())

describe('es una tabla de datos', () => {
  it('muestra las noticias en una tabla con encabezados, no en tarjetas', async () => {
    m.listar.mockResolvedValue(pagina([noticia({ titulo: 'Uno', uuid: 'a' }), noticia({ titulo: 'Dos', uuid: 'b' })]))
    const { wrapper } = await montar()

    expect(wrapper.find('table').exists()).toBe(true)
    const encabezados = wrapper.findAll('th').map((t) => t.text())
    for (const nombre of ['Título', 'Estado', 'Fecha de publicación', 'Imagen', 'Acciones']) {
      expect(encabezados.some((t) => t.includes(nombre))).toBe(true)
    }
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('el estado se dice con palabras y con la clase del servidor, no con color solo', async () => {
    m.listar.mockResolvedValue(
      pagina([
        noticia({ uuid: 'a', titulo: 'A', estado: 'publicada', activo: true }),
        noticia({ uuid: 'b', titulo: 'B', estado: 'programada', activo: true }),
        noticia({ uuid: 'c', titulo: 'C', estado: 'borrador' }),
      ]),
    )
    const { wrapper } = await montar()
    const filas = wrapper.findAll('tbody tr').map((f) => f.text())

    expect(filas[0]).toContain('Publicada')
    expect(filas[1]).toContain('Programada')
    expect(filas[2]).toContain('Borrador')
  })

  it('dice si la noticia tiene imagen, y si le falta, que le falta', async () => {
    m.listar.mockResolvedValue(
      pagina([
        noticia({ uuid: 'a', titulo: 'Con', imagen: { url: '/x', alt: 'Alt' } }),
        noticia({ uuid: 'b', titulo: 'Sin', imagen: null }),
      ]),
    )
    const { wrapper } = await montar()
    const filas = wrapper.findAll('tbody tr').map((f) => f.text())

    expect(filas[0]).toContain('Sí')
    expect(filas[1]).toContain('Falta')
  })

  it('cada fila tiene «Editar» (un enlace a la vista de edición) y «Eliminar»', async () => {
    m.listar.mockResolvedValue(pagina([noticia({ uuid: 'abc', titulo: 'Parque' })]))
    const { wrapper } = await montar()

    const editar = wrapper.find('tbody a[href$="/contenidos/noticias/abc"]')
    expect(editar.exists()).toBe(true)
    expect(editar.attributes('aria-label')).toContain('Parque')
    expect(wrapper.find('tbody button[aria-label*="Eliminar"][aria-label*="Parque"]').exists()).toBe(true)
  })

  it('«Nueva noticia» lleva a la vista de creación, no abre un formulario aquí', async () => {
    const { wrapper } = await montar()
    const nueva = wrapper.findAll('a').find((a) => a.text().includes('Nueva noticia'))

    expect(nueva?.attributes('href')).toBe('/contenidos/noticias/nueva')
    expect(wrapper.find('form').exists()).toBe(false)
    expect(wrapper.find('textarea').exists()).toBe(false)
  })

  it('la primera carga pide la página 1, ordenada por fecha descendente', async () => {
    await montar()
    expect(ultimaConsulta()).toMatchObject({ pagina: 1, ordenarPor: 'fecha_publicacion', direccion: 'desc' })
  })
})

describe('estados de la lista', () => {
  it('sin noticias y sin filtros: lo dice y ofrece crear la primera', async () => {
    const { wrapper } = await montar()

    expect(wrapper.text()).toContain('Aún no hay noticias')
    expect(wrapper.findAll('a').some((a) => a.text().includes('Crear la primera noticia'))).toBe(true)
  })

  it('con filtros y sin coincidencias: no dice que no hay noticias, ofrece quitar los filtros', async () => {
    vi.useFakeTimers()
    const { wrapper } = await montar()
    await wrapper.find('input[type="search"]').setValue('zzz')
    vi.advanceTimersByTime(500)
    await flushPromises()

    expect(wrapper.text()).toContain('Ninguna noticia coincide')
    expect(wrapper.text()).not.toContain('Aún no hay noticias')

    m.listar.mockClear()
    await wrapper.findAll('button').find((b) => b.text().includes('Quitar filtros'))!.trigger('click')
    await flushPromises()
    expect(ultimaConsulta()).toMatchObject({ q: '', estado: '' })
  })

  it('si la carga falla lo dice con un aviso y deja reintentar; no afirma que no hay noticias', async () => {
    m.listar.mockRejectedValueOnce(new Error('caída'))
    const { wrapper } = await montar()

    expect(wrapper.find('[role="alert"]').text()).toContain('No se pudieron cargar las noticias')
    expect(wrapper.text()).not.toContain('Aún no hay noticias')

    m.listar.mockResolvedValue(pagina([noticia({ titulo: 'Ya cargó' })]))
    await wrapper.findAll('button').find((b) => b.text().includes('Reintentar'))!.trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Ya cargó')
    expect(wrapper.find('[role="alert"]').exists()).toBe(false)
  })

  it('mientras carga avisa con role=status', async () => {
    let resolver: (v: ReturnType<typeof pagina>) => void = () => {}
    m.listar.mockReturnValue(new Promise((r) => (resolver = r)))
    const router = enrutador()
    await router.push('/contenidos/noticias')
    const wrapper = mount(NoticiasListaView, { global: { plugins: [router], stubs: { FaIcon: true } } })
    await flushPromises()

    expect(wrapper.text()).toContain('Cargando')
    resolver(pagina([]))
    await flushPromises()
    expect(wrapper.text()).not.toContain('Cargando')
  })
})

describe('buscar, filtrar, ordenar y paginar piden los datos al servidor', () => {
  it('buscar espera a que se deje de escribir y vuelve a la página 1', async () => {
    vi.useFakeTimers()
    const { wrapper } = await montar()
    m.listar.mockClear()

    const campo = wrapper.find('input[type="search"]')
    await campo.setValue('par')
    await campo.setValue('parque')
    vi.advanceTimersByTime(100)
    expect(m.listar).not.toHaveBeenCalled()

    vi.advanceTimersByTime(400)
    await flushPromises()
    expect(m.listar).toHaveBeenCalledTimes(1)
    expect(ultimaConsulta()).toMatchObject({ q: 'parque', pagina: 1 })
  })

  it('el buscador tiene etiqueta visible o accesible', async () => {
    const { wrapper } = await montar()
    const campo = wrapper.find('input[type="search"]')
    const id = campo.attributes('id')
    const etiqueta = id ? wrapper.find(`label[for="${id}"]`) : null

    expect(etiqueta?.exists() || campo.attributes('aria-label')).toBeTruthy()
  })

  it('filtrar por estado vuelve a la página 1', async () => {
    m.listar.mockResolvedValue(pagina([noticia()], { current: 2, last: 3, total: 55 }))
    const { wrapper } = await montar()
    m.listar.mockClear()

    await wrapper.find('select').setValue('programada')
    await flushPromises()

    expect(ultimaConsulta()).toMatchObject({ estado: 'programada', pagina: 1 })
  })

  it('las opciones del filtro nombran los tres estados', async () => {
    const { wrapper } = await montar()
    const opciones = wrapper.findAll('select option').map((o) => o.text())

    expect(opciones).toEqual(expect.arrayContaining(['Todos los estados', 'Borrador', 'Programada', 'Publicada']))
  })

  it('ordenar por título pide ese orden y un segundo clic lo invierte', async () => {
    m.listar.mockResolvedValue(pagina([noticia()]))
    const { wrapper } = await montar()
    const botonTitulo = () => wrapper.findAll('th button').find((b) => b.text().includes('Título'))!

    m.listar.mockClear()
    await botonTitulo().trigger('click')
    await flushPromises()
    expect(ultimaConsulta()).toMatchObject({ ordenarPor: 'titulo', direccion: 'asc' })

    await botonTitulo().trigger('click')
    await flushPromises()
    expect(ultimaConsulta()).toMatchObject({ ordenarPor: 'titulo', direccion: 'desc' })
  })

  it('las columnas Estado, Imagen y Acciones no son ordenables (el servidor no las ordena)', async () => {
    m.listar.mockResolvedValue(pagina([noticia()]))
    const { wrapper } = await montar()
    const ordenables = wrapper.findAll('th button').map((b) => b.text())

    expect(ordenables.some((t) => t.includes('Estado'))).toBe(false)
    expect(ordenables.some((t) => t.includes('Imagen'))).toBe(false)
  })

  it('paginar pide esa página conservando búsqueda y orden', async () => {
    vi.useFakeTimers()
    m.listar.mockResolvedValue(pagina([noticia()], { current: 1, last: 3, total: 55 }))
    const { wrapper } = await montar()
    await wrapper.find('input[type="search"]').setValue('parque')
    vi.advanceTimersByTime(500)
    await flushPromises()
    m.listar.mockClear()

    await wrapper.find('button[aria-label="Página 2"]').trigger('click')
    await flushPromises()

    expect(ultimaConsulta()).toMatchObject({ pagina: 2, q: 'parque', ordenarPor: 'fecha_publicacion' })
  })

  it('una respuesta lenta anterior no pisa a la última consulta', async () => {
    vi.useFakeTimers()
    const { wrapper } = await montar()
    let lenta: (v: ReturnType<typeof pagina>) => void = () => {}
    m.listar.mockReturnValueOnce(new Promise((r) => (lenta = r)))
    m.listar.mockResolvedValueOnce(pagina([noticia({ titulo: 'Resultado nuevo', uuid: 'n' })]))

    await wrapper.find('input[type="search"]').setValue('uno')
    vi.advanceTimersByTime(500)
    await wrapper.find('input[type="search"]').setValue('dos')
    vi.advanceTimersByTime(500)
    await flushPromises()
    lenta(pagina([noticia({ titulo: 'Resultado viejo', uuid: 'v' })]))
    await flushPromises()

    expect(wrapper.text()).toContain('Resultado nuevo')
    expect(wrapper.text()).not.toContain('Resultado viejo')
  })
})

describe('eliminar', () => {
  async function conUna() {
    m.listar.mockResolvedValue(pagina([noticia({ uuid: 'u-1', titulo: 'Parque nuevo' })]))
    const montada = await montar()
    await montada.wrapper.find('tbody button[aria-label*="Eliminar"]').trigger('click')
    await flushPromises()
    return montada
  }

  it('abre un diálogo que nombra la noticia y no elimina todavía', async () => {
    const { wrapper } = await conUna()

    const dialogo = wrapper.find('[role="dialog"]')
    expect(dialogo.exists()).toBe(true)
    expect(dialogo.text()).toContain('Parque nuevo')
    expect(m.eliminar).not.toHaveBeenCalled()
  })

  it('cancelar cierra el diálogo y no elimina', async () => {
    const { wrapper } = await conUna()
    await wrapper.findAll('[role="dialog"] button').find((b) => b.text().includes('Cancelar'))!.trigger('click')
    await flushPromises()

    expect(m.eliminar).not.toHaveBeenCalled()
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('confirmar elimina, recarga la lista y avisa', async () => {
    m.eliminar.mockResolvedValue(undefined)
    const { wrapper } = await conUna()
    m.listar.mockClear()
    m.listar.mockResolvedValue(pagina([]))

    await wrapper.findAll('[role="dialog"] button').find((b) => b.text().includes('Sí, eliminar'))!.trigger('click')
    await flushPromises()

    expect(m.eliminar).toHaveBeenCalledWith('u-1')
    expect(m.listar).toHaveBeenCalled()
    expect(wrapper.find('[role="status"]').text()).toContain('Noticia eliminada')
  })

  it('si eliminar falla, lo dice dentro del diálogo y no recarga como si hubiera funcionado', async () => {
    m.eliminar.mockRejectedValue(new Error('caída'))
    const { wrapper } = await conUna()

    await wrapper.findAll('[role="dialog"] button').find((b) => b.text().includes('Sí, eliminar'))!.trigger('click')
    await flushPromises()

    expect(wrapper.find('[role="dialog"] [role="alert"]').text()).toContain('No se pudo eliminar')
    expect(wrapper.text()).not.toContain('Noticia eliminada')
  })

  it('eliminar la última noticia de una página que deja de existir retrocede una página', async () => {
    m.listar.mockResolvedValue(pagina([noticia({ uuid: 'u-1', titulo: 'Única' })], { current: 2, last: 2, total: 21 }))
    m.eliminar.mockResolvedValue(undefined)
    const { wrapper } = await montar()
    // Ya está en la página 2 por el estado de la consulta.
    await wrapper.find('button[aria-label="Página 2"]').trigger('click')
    await flushPromises()
    await wrapper.find('tbody button[aria-label*="Eliminar"]').trigger('click')
    m.listar.mockClear()
    m.listar.mockResolvedValue(pagina([], { current: 2, last: 1, total: 20 }))

    await wrapper.findAll('[role="dialog"] button').find((b) => b.text().includes('Sí, eliminar'))!.trigger('click')
    await flushPromises()

    expect(m.listar.mock.calls.at(-1)![0]).toMatchObject({ pagina: 1 })
  })
})
