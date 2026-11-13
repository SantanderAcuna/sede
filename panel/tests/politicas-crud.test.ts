/**
 * Políticas propias de la Entidad: crear, publicar y eliminar desde el panel.
 *
 * Además de las cinco obligatorias, la Entidad puede agregar las suyas. Nacen como borrador oculto y se
 * publican solo adoptadas y con el permiso de publicar. Las cinco de base no se eliminan ni se ocultan.
 * Cada botón se ofrece solo a quien tiene su permiso (el servidor lo vuelve a comprobar).
 */
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { conSesion } from './ayudas/sesion'

vi.mock('../src/services/politicas', () => ({
  COLECCION_DOCUMENTO: 'documento-oficial',
  listar: vi.fn(),
  crear: vi.fn(),
  actualizar: vi.fn(),
  eliminar: vi.fn(),
  documentosAdjuntos: vi.fn(),
  adjuntarDocumento: vi.fn(),
  quitarArchivo: vi.fn(),
}))

import * as servicio from '../src/services/politicas'
import {
  entradaDesde,
  entradaParaCrear,
  formularioDesde,
  formularioNuevo,
  problemasLocales,
} from '../src/views/admin/politicas-logica'
import PoliticasListaView from '../src/views/admin/PoliticasListaView.vue'
import PoliticaFormularioView from '../src/views/admin/PoliticaFormularioView.vue'
import type { PoliticaPanelItem } from '../src/types/api'

const m = vi.mocked(servicio)

function politica(extra: Partial<PoliticaPanelItem> = {}): PoliticaPanelItem {
  return {
    id: 1, type: 'politica', slug: 'uso-de-cookies', nombre: 'Uso de cookies', version: 1,
    vigente_desde: null, acto: { tipo: null, numero: null, fecha: null }, es_ejemplo: true,
    publicada: true, es_base: true, documento_oficial: null, actualizada_en: '2026-10-29',
    secciones: [{ id: 'finalidad', titulo: 'Finalidad', parrafos: ['Uno.'] }],
    uuid: '11111111-1111-4111-8111-111111111111', componentes: { finalidad: 'Finalidad' },
    completa: true, problemas: [], ...extra,
  } as PoliticaPanelItem
}

const propia = (extra: Partial<PoliticaPanelItem> = {}) =>
  politica({
    id: 9, slug: 'mi-politica', nombre: 'Mi política', es_base: false, publicada: false, componentes: {},
    uuid: '22222222-2222-4222-8222-222222222222',
    secciones: [{ id: 'objeto', titulo: 'Objeto', parrafos: ['Texto.'] }], ...extra,
  })

const adoptada = { es_ejemplo: false, vigente_desde: '2026-11-01', acto: { tipo: 'Resolución', numero: '1', fecha: '2026-10-30' } }

const enrutador = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/configuracion/politicas', name: 'panel.politicas', component: { template: '<i/>' } },
      { path: '/configuracion/politicas/nueva', name: 'panel.politicas.nueva', component: { template: '<i/>' } },
      { path: '/configuracion/politicas/:slug', name: 'panel.politicas.editar', component: { template: '<i/>' } },
    ],
  })

async function montar(vista: object, ruta: string) {
  const router = enrutador()
  await router.push(ruta)
  await router.isReady()
  const wrapper = mount(vista, { attachTo: document.body, global: { plugins: [router], stubs: { FaIcon: true, teleport: true } } })
  await flushPromises()
  return { wrapper, router }
}

const lista = () => montar(PoliticasListaView, '/configuracion/politicas')
const editar = (slug: string) => montar(PoliticaFormularioView, `/configuracion/politicas/${slug}`)
const nueva = () => montar(PoliticaFormularioView, '/configuracion/politicas/nueva')

beforeEach(() => {
  conSesion()
  vi.clearAllMocks()
  m.listar.mockResolvedValue([politica(), propia()])
  m.documentosAdjuntos.mockResolvedValue([])
})

describe('lógica: crear', () => {
  it('el formulario nuevo nace de ejemplo, oculto y con un apartado en blanco', () => {
    const f = formularioNuevo()
    expect(f).toMatchObject({ nombre: '', version: 1, es_ejemplo: true, publicada: false })
    expect(f.secciones).toHaveLength(1)
    expect(f.secciones[0]).toMatchObject({ titulo: '', texto: '', obligatoria: false })
  })

  it('exige nombre y al menos un apartado con título y texto', () => {
    const f = formularioNuevo()
    expect(problemasLocales(f).join(' ')).toContain('nombre')
    f.nombre = 'Accesibilidad'
    f.secciones = []
    expect(problemasLocales(f).join(' ')).toContain('al menos un apartado')
  })

  it('el identificador de cada apartado sale de su título y no se repite', () => {
    const f = formularioNuevo()
    f.nombre = ' Accesibilidad '
    f.secciones = [
      { id: 'x', titulo: 'Objeto', texto: 'Uno.\n\nDos.', obligatoria: false },
      { id: 'y', titulo: 'Objeto', texto: 'Tres.', obligatoria: false },
    ]
    const entrada = entradaParaCrear(f)
    expect(entrada.nombre).toBe('Accesibilidad')
    expect(entrada.secciones).toEqual([
      { id: 'objeto', titulo: 'Objeto', parrafos: ['Uno.', 'Dos.'] },
      { id: 'objeto-2', titulo: 'Objeto', parrafos: ['Tres.'] },
    ])
  })

  it('al editar, «publicada» solo viaja si cambió', () => {
    const original = formularioDesde(propia(adoptada))
    const f = formularioDesde(propia(adoptada))
    expect('publicada' in entradaDesde(f, original)).toBe(false)
    f.publicada = true
    expect(entradaDesde(f, original).publicada).toBe(true)
  })
})

describe('servicio: crear y eliminar', () => {
  const peticion = { post: vi.fn(), delete: vi.fn() }

  it('POST a /panel/politicas y DELETE por slug', async () => {
    vi.resetModules()
    vi.doUnmock('../src/services/politicas')
    vi.doMock('../src/services/http', () => ({ http: peticion, desenvolver: (s: { data: unknown }) => s.data }))
    const real = await import('../src/services/politicas')
    peticion.post.mockResolvedValue({ data: { success: true, data: { slug: 'a' } } })
    peticion.delete.mockResolvedValue({ data: {} })

    await real.crear({ nombre: 'A' })
    expect(peticion.post).toHaveBeenCalledWith('/panel/politicas', { nombre: 'A' })
    await real.eliminar('a')
    expect(peticion.delete).toHaveBeenCalledWith('/panel/politicas/a')
    vi.doUnmock('../src/services/http')
  })
})

describe('lista', () => {
  it('«Nueva política» solo con permiso de crear', async () => {
    const con = await lista()
    const enlace = con.wrapper.find('a[href="/configuracion/politicas/nueva"]')
    expect(enlace.exists()).toBe(true)
    expect(enlace.text()).toContain('Nueva política')

    conSesion(['politicas.ver'])
    const sin = await lista()
    expect(sin.wrapper.find('a[href="/configuracion/politicas/nueva"]').exists()).toBe(false)
  })

  it('«Eliminar» solo en las propias y con permiso; nunca en las cinco obligatorias', async () => {
    const { wrapper } = await lista()
    expect(wrapper.find('tbody button[aria-label*="Eliminar"][aria-label*="Mi política"]').exists()).toBe(true)
    expect(wrapper.find('tbody button[aria-label*="Eliminar"][aria-label*="Uso de cookies"]').exists()).toBe(false)

    conSesion(['politicas.ver', 'politicas.editar'])
    const sin = await lista()
    expect(sin.wrapper.find('tbody button[aria-label*="Eliminar"]').exists()).toBe(false)
  })

  it('dice con palabras si está publicada u oculta, y una oculta no ofrece PDF público', async () => {
    const { wrapper } = await lista()
    const filas = wrapper.findAll('tbody tr')
    expect(filas[0]!.text()).toContain('Publicada')
    expect(filas[1]!.text()).toContain('Oculta')
    expect(filas[0]!.find('a[href$=".pdf"]').exists()).toBe(true)
    expect(filas[1]!.find('a[href$=".pdf"]').exists()).toBe(false)
  })

  it('eliminar pide confirmación y luego borra y recarga', async () => {
    m.eliminar.mockResolvedValue(undefined)
    const { wrapper } = await lista()
    await wrapper.find('tbody button[aria-label*="Eliminar"]').trigger('click')
    expect(m.eliminar).not.toHaveBeenCalled()
    expect(wrapper.find('[role="dialog"]').text()).toContain('Mi política')

    m.listar.mockResolvedValue([politica()])
    await wrapper.findAll('[role="dialog"] button').find((b) => b.text().includes('Sí, eliminar'))!.trigger('click')
    await flushPromises()
    expect(m.eliminar).toHaveBeenCalledWith('mi-politica')
    expect(wrapper.findAll('tbody tr')).toHaveLength(1)
    expect(wrapper.text()).toContain('Política eliminada')
  })

  it('ya no dice que las políticas son cinco y fijas', async () => {
    const { wrapper } = await lista()
    expect(wrapper.text()).not.toMatch(/cinco y fijas|fijas/)
  })
})

describe('crear desde el formulario', () => {
  it('abre en blanco, con un apartado, sin cargar ninguna política', async () => {
    const { wrapper } = await nueva()
    expect(wrapper.find('h1').text()).toContain('Nueva política')
    expect(wrapper.findAll('[data-apartado]')).toHaveLength(1)
    expect(wrapper.text()).toContain('oculta')
    expect(m.listar).not.toHaveBeenCalled()
  })

  it('el documento oficial se adjunta después de guardar la primera vez', async () => {
    const { wrapper } = await nueva()
    expect(wrapper.find('input[type="file"]').exists()).toBe(false)
    expect(wrapper.text()).toContain('después de guardar')
  })

  it('sin nombre ni texto no envía nada y lo explica', async () => {
    const { wrapper } = await nueva()
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.crear).not.toHaveBeenCalled()
    expect(wrapper.find('[role="alert"]').text()).toContain('nombre')
  })

  it('con datos válidos crea y abre la política recién creada', async () => {
    m.crear.mockResolvedValue(propia({ slug: 'accesibilidad', nombre: 'Accesibilidad' }))
    const { wrapper, router } = await nueva()
    await wrapper.find('input[type="text"]').setValue('Accesibilidad')
    const apartado = wrapper.find('[data-apartado]')
    await apartado.find('input[type="text"]').setValue('Objeto')
    await apartado.find('textarea').setValue('Primero.\n\nSegundo.')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(m.crear).toHaveBeenCalledTimes(1)
    const [entrada] = m.crear.mock.calls[0]!
    expect(entrada.nombre).toBe('Accesibilidad')
    expect(entrada.secciones).toEqual([{ id: 'objeto', titulo: 'Objeto', parrafos: ['Primero.', 'Segundo.'] }])
    expect(router.currentRoute.value.fullPath).toBe('/configuracion/politicas/accesibilidad')
  })

  it('se pueden agregar y quitar apartados libres, pero siempre queda uno', async () => {
    const { wrapper } = await nueva()
    expect(wrapper.find('[data-accion="quitar-apartado"]').exists()).toBe(false)
    await wrapper.find('input[placeholder="Título del apartado nuevo"]').setValue('Alcance')
    await wrapper.findAll('button').find((b) => b.text().includes('Agregar apartado'))!.trigger('click')
    expect(wrapper.findAll('[data-apartado]')).toHaveLength(2)
    await wrapper.findAll('[data-accion="quitar-apartado"]')[0]!.trigger('click')
    expect(wrapper.findAll('[data-apartado]')).toHaveLength(1)
    expect(wrapper.find('[data-accion="quitar-apartado"]').exists()).toBe(false)
  })

  it('un rechazo del servidor se explica sin perder lo escrito', async () => {
    m.crear.mockRejectedValue(Object.assign(new Error('x'), {
      isAxiosError: true, response: { status: 422, data: { errors: { politica: ['Texto del rechazo.'] } } },
    }))
    const { wrapper } = await nueva()
    await wrapper.find('input[type="text"]').setValue('Accesibilidad')
    const apartado = wrapper.find('[data-apartado]')
    await apartado.find('input[type="text"]').setValue('Objeto')
    await apartado.find('textarea').setValue('Texto.')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(wrapper.find('[role="alert"]').text()).toContain('Texto del rechazo.')
    expect((wrapper.find('input[type="text"]').element as HTMLInputElement).value).toBe('Accesibilidad')
  })
})

describe('publicar y eliminar desde la política', () => {
  const marca = (w: { find: (s: string) => { element: Element; exists: () => boolean } }) =>
    w.find('input[data-campo="publicada"]')

  it('una propia de ejemplo no se puede publicar y dice por qué', async () => {
    m.listar.mockResolvedValue([propia()])
    const { wrapper } = await editar('mi-politica')
    expect((marca(wrapper).element as HTMLInputElement).disabled).toBe(true)
    expect(wrapper.text()).toContain('adóptela')
  })

  it('adoptada y con permiso de publicar, se publica al guardar', async () => {
    m.listar.mockResolvedValue([propia(adoptada)])
    m.actualizar.mockResolvedValue(propia({ ...adoptada, publicada: true }))
    const { wrapper } = await editar('mi-politica')
    const caja = marca(wrapper)
    expect((caja.element as HTMLInputElement).disabled).toBe(false)
    await (caja as unknown as { setValue: (v: boolean) => Promise<void> }).setValue(true)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).toHaveBeenCalledWith('mi-politica', expect.objectContaining({ publicada: true }))
  })

  it('sin el permiso de publicar la casilla queda bloqueada y lo dice', async () => {
    conSesion(['politicas.ver', 'politicas.editar'])
    m.listar.mockResolvedValue([propia(adoptada)])
    const { wrapper } = await editar('mi-politica')
    expect((marca(wrapper).element as HTMLInputElement).disabled).toBe(true)
    expect(wrapper.text()).toContain('permiso')
  })

  it('las cinco obligatorias no ofrecen ocultarse ni eliminarse', async () => {
    const { wrapper } = await editar('uso-de-cookies')
    expect(marca(wrapper).exists()).toBe(false)
    expect(wrapper.text()).toContain('siempre publicada')
    expect(wrapper.find('[data-accion="eliminar-politica"]').exists()).toBe(false)
  })

  it('una oculta no enlaza a su página ni a su PDF públicos', async () => {
    m.listar.mockResolvedValue([propia()])
    const { wrapper } = await editar('mi-politica')
    expect(wrapper.find('a[href="/politicas/mi-politica"]').exists()).toBe(false)
    expect(wrapper.find('a[href="/politicas/mi-politica.pdf"]').exists()).toBe(false)
  })

  it('«Eliminar política» pide confirmación, borra y vuelve a la lista', async () => {
    m.listar.mockResolvedValue([propia()])
    m.eliminar.mockResolvedValue(undefined)
    const { wrapper, router } = await editar('mi-politica')
    await wrapper.find('[data-accion="eliminar-politica"]').trigger('click')
    expect(m.eliminar).not.toHaveBeenCalled()
    await wrapper.findAll('[role="dialog"] button').find((b) => b.text().includes('Sí, eliminar'))!.trigger('click')
    await flushPromises()
    expect(m.eliminar).toHaveBeenCalledWith('mi-politica')
    expect(router.currentRoute.value.fullPath).toBe('/configuracion/politicas')
  })

  it('sin el permiso de eliminar no aparece la zona de peligro', async () => {
    conSesion(['politicas.ver', 'politicas.editar'])
    m.listar.mockResolvedValue([propia()])
    const { wrapper } = await editar('mi-politica')
    expect(wrapper.find('[data-accion="eliminar-politica"]').exists()).toBe(false)
  })
})
