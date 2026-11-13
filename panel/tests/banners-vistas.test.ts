/**
 * Carrusel de la portada (Fase 6): lista ordenable, formulario, permiso y rutas.
 *
 * Lo que vive quien administra: ver el orden real, moverlo con botones (no solo arrastrando),
 * que el estado se diga con palabras, que ninguna imagen se suba sin su descripción y que
 * eliminar pida confirmación.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { conSesion } from './ayudas/sesion'
import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('../src/services/banners', () => ({
  listar: vi.fn(),
  obtener: vi.fn(),
  crear: vi.fn(),
  actualizar: vi.fn(),
  eliminar: vi.fn(),
  subirImagen: vi.fn(),
  ordenar: vi.fn(),
}))

import { PERMISO_POR_RUTA } from '../src/config/permisos'
import enrutadorReal from '../src/router/index'
import * as servicio from '../src/services/banners'
import type { BannerPanelItem } from '../src/types/api'
import BannerFormularioView from '../src/views/admin/BannerFormularioView.vue'
import BannersListaView from '../src/views/admin/BannersListaView.vue'

const m = vi.mocked(servicio)

const banner = (extra: Partial<BannerPanelItem> = {}): BannerPanelItem => ({
  uuid: '11111111-1111-4111-8111-111111111111',
  titulo: 'Uno',
  descripcion: null,
  enlace: null,
  orden: 1,
  activo: true,
  estado: 'vigente',
  vigente_desde: null,
  vigente_hasta: null,
  imagen: { url: '/api/v1/panel/banners/x/imagen', alt: 'Una foto' },
  problemas: [],
  ...extra,
})

const rutas = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/c', name: 'panel.banners', component: { template: '<i/>' } },
      { path: '/c/nueva', name: 'panel.banners.nuevo', component: { template: '<i/>' } },
      { path: '/c/:uuid', name: 'panel.banners.editar', component: { template: '<i/>' } },
    ],
  })

async function montarLista() {
  const router = rutas()
  await router.push('/c')
  await router.isReady()
  const wrapper = mount(BannersListaView, { global: { plugins: [router], stubs: { FaIcon: true } } })
  await flushPromises()
  return wrapper
}

async function montarForm(uuid?: string) {
  const router = rutas()
  await router.push(uuid ? `/c/${uuid}` : '/c/nueva')
  await router.isReady()
  const wrapper = mount(BannerFormularioView, { global: { plugins: [router], stubs: { FaIcon: true, teleport: true } } })
  await flushPromises()
  return { wrapper, router }
}

beforeEach(() => {
  vi.clearAllMocks()
  conSesion()
})

describe('permiso y rutas', () => {
  it('el módulo se abre con banners.ver', () => {
    expect(PERMISO_POR_RUTA['/contenidos/carrusel']).toBe('banners.ver')
  })
  it('las tres rutas existen: lista y edición piden «ver», y la de nueva pide «crear»', () => {
    const esperado = { 'panel.banners': 'banners.ver', 'panel.banners.nuevo': 'banners.crear', 'panel.banners.editar': 'banners.ver' }
    for (const [nombre, permiso] of Object.entries(esperado)) {
      const r = enrutadorReal.getRoutes().find((x) => x.name === nombre)
      expect(r?.meta.permiso).toBe(permiso)
    }
  })
})

describe('lista', () => {
  it('muestra las diapositivas en orden, con el estado en palabras', async () => {
    m.listar.mockResolvedValue([
      banner({ uuid: 'a', titulo: 'A', estado: 'vigente' }),
      banner({ uuid: 'b', titulo: 'B', estado: 'borrador', activo: false }),
      banner({ uuid: 'c', titulo: 'C', estado: 'programado' }),
      banner({ uuid: 'd', titulo: 'D', estado: 'vencido' }),
    ])
    const w = await montarLista()
    const items = w.findAll('ol > li').map((li) => li.text())
    expect(items).toHaveLength(4)
    expect(items[0]).toContain('Publicada')
    expect(items[1]).toContain('Borrador')
    expect(items[2]).toContain('Programada')
    expect(items[3]).toContain('Vencida')
  })

  it('estado vacío honesto', async () => {
    m.listar.mockResolvedValue([])
    const w = await montarLista()
    expect(w.text()).toContain('Aún no hay diapositivas')
  })

  it('falla la carga: lo dice y deja reintentar', async () => {
    m.listar.mockRejectedValueOnce(new Error('x'))
    const w = await montarLista()
    expect(w.find('[role=alert]').text()).toContain('No se pudieron cargar')
    m.listar.mockResolvedValue([])
    await w.find('[role=alert] button').trigger('click')
    await flushPromises()
    expect(w.text()).toContain('Aún no hay diapositivas')
  })

  it('los extremos no se pueden mover más allá', async () => {
    m.listar.mockResolvedValue([banner({ uuid: 'a', titulo: 'A' }), banner({ uuid: 'b', titulo: 'B' })])
    const w = await montarLista()
    expect(w.find('[aria-label="Subir «A»"]').attributes('disabled')).toBeDefined()
    expect(w.find('[aria-label="Bajar «B»"]').attributes('disabled')).toBeDefined()
    expect(w.find('[aria-label="Bajar «A»"]').attributes('disabled')).toBeUndefined()
  })

  it('«Bajar» pide el nuevo orden completo y anuncia la posición', async () => {
    const a = banner({ uuid: 'a', titulo: 'A' })
    const b = banner({ uuid: 'b', titulo: 'B' })
    m.listar.mockResolvedValue([a, b])
    m.ordenar.mockResolvedValue([b, a])
    const w = await montarLista()
    await w.find('[aria-label="Bajar «A»"]').trigger('click')
    await flushPromises()
    expect(m.ordenar).toHaveBeenCalledWith(['b', 'a'])
    expect(w.findAll('ol > li')[0]!.text()).toContain('B')
    expect(w.find('[data-anuncio]').text()).toBe('«A» quedó en la posición 2 de 2.')
  })

  it('si guardar el orden falla, lo dice y no cambia la lista', async () => {
    m.listar.mockResolvedValue([banner({ uuid: 'a', titulo: 'A' }), banner({ uuid: 'b', titulo: 'B' })])
    m.ordenar.mockRejectedValue(new Error('x'))
    const w = await montarLista()
    await w.find('[aria-label="Bajar «A»"]').trigger('click')
    await flushPromises()
    expect(w.text()).toContain('No se pudo guardar el nuevo orden')
    expect(w.findAll('ol > li')[0]!.text()).toContain('A')
  })

  it('muestra lo que le falta a una diapositiva', async () => {
    m.listar.mockResolvedValue([banner({ problemas: ['Falta la imagen.'] })])
    const w = await montarLista()
    expect(w.text()).toContain('Falta la imagen.')
  })
})

describe('formulario', () => {
  it('guardar vacío no llama al servidor y dice qué falta', async () => {
    const { wrapper } = await montarForm()
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.crear).not.toHaveBeenCalled()
    expect(wrapper.find('[role=alert]').text()).toContain('Falta el título')
  })

  it('crea como borrador por defecto, con fechas y enlace', async () => {
    m.crear.mockResolvedValue(banner({ uuid: '22222222-2222-4222-8222-222222222222', titulo: 'Hola', activo: false, estado: 'borrador', imagen: null }))
    const { wrapper } = await montarForm()
    await wrapper.find('input[type=text], input:not([type])').setValue('Hola')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.crear).toHaveBeenCalledWith(expect.objectContaining({ titulo: 'Hola', activo: false }))
    expect(wrapper.text()).toContain('Sigue como borrador')
  })

  it('publicar sin imagen se frena antes de enviar', async () => {
    const { wrapper } = await montarForm()
    await wrapper.find('input[type=text], input:not([type])').setValue('Hola')
    await wrapper.find('input[value=publicar]').setValue(true)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.crear).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('hace falta una imagen')
  })

  it('al editar carga la diapositiva y no envía nada si no hay cambios', async () => {
    m.obtener.mockResolvedValue(banner({ uuid: '11111111-1111-4111-8111-111111111111' }))
    const { wrapper } = await montarForm('11111111-1111-4111-8111-111111111111')
    expect(wrapper.find('h1').text()).toBe('Editar diapositiva')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('No hay cambios que guardar.')
  })

  it('un 404 al cargar lo dice', async () => {
    m.obtener.mockRejectedValue({ isAxiosError: true, response: { status: 404 } })
    const { wrapper } = await montarForm('11111111-1111-4111-8111-111111111111')
    expect(wrapper.text()).toContain('No se encontró la diapositiva')
  })

  it('eliminar pide confirmación', async () => {
    m.obtener.mockResolvedValue(banner())
    const { wrapper } = await montarForm('11111111-1111-4111-8111-111111111111')
    const boton = wrapper.findAll('button').find((b) => b.text() === 'Eliminar diapositiva')!
    await boton.trigger('click')
    await flushPromises()
    expect(m.eliminar).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('¿Eliminar esta diapositiva?')
    await wrapper.findAll('button').find((b) => b.text() === 'Sí, eliminar')!.trigger('click')
    await flushPromises()
    expect(m.eliminar).toHaveBeenCalledWith('11111111-1111-4111-8111-111111111111')
  })
})
