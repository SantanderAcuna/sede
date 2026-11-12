/**
 * Políticas: lista en tabla (PoliticasListaView) y edición en vista propia
 * (PoliticaFormularioView), con servicio simulado.
 *
 * Se verifica lo que vive quien administra: la lista es una tabla (no tarjetas), cada
 * estado se dice con palabras, editar abre una vista aparte por slug, guardar no envía
 * lo que no cambió, un rechazo del servidor se explica y salir con cambios pregunta.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('../src/services/politicas', () => ({
  COLECCION_DOCUMENTO: 'documento-oficial',
  listar: vi.fn(),
  actualizar: vi.fn(),
  documentosAdjuntos: vi.fn(),
  adjuntarDocumento: vi.fn(),
  quitarArchivo: vi.fn(),
}))

import * as servicio from '../src/services/politicas'
import PoliticasListaView from '../src/views/admin/PoliticasListaView.vue'
import PoliticaFormularioView from '../src/views/admin/PoliticaFormularioView.vue'
import type { PoliticaPanelItem } from '../src/types/api'

const m = vi.mocked(servicio)

function politica(extra: Partial<PoliticaPanelItem> = {}): PoliticaPanelItem {
  return {
    id: 1,
    type: 'politica',
    slug: 'uso-de-cookies',
    nombre: 'Uso de cookies',
    version: 2,
    vigente_desde: null,
    acto: { tipo: null, numero: null, fecha: null },
    es_ejemplo: true,
    documento_oficial: null,
    actualizada_en: '2026-10-29',
    secciones: [{ id: 'finalidad', titulo: 'Finalidad', parrafos: ['Uno.', 'Dos.'] }],
    uuid: '11111111-1111-4111-8111-111111111111',
    componentes: { finalidad: 'Finalidad de cada una' },
    completa: true,
    problemas: [],
    ...extra,
  } as PoliticaPanelItem
}

const enrutador = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/configuracion/politicas', name: 'panel.politicas', component: { template: '<i/>' } },
      { path: '/configuracion/politicas/:slug', name: 'panel.politicas.editar', component: { template: '<i/>' } },
    ],
  })

async function montarLista() {
  const router = enrutador()
  await router.push('/configuracion/politicas')
  await router.isReady()
  const wrapper = mount(PoliticasListaView, { global: { plugins: [router], stubs: { FaIcon: true } } })
  await flushPromises()
  return { wrapper, router }
}

async function montarFormulario(slug = 'uso-de-cookies') {
  const router = enrutador()
  await router.push(`/configuracion/politicas/${slug}`)
  await router.isReady()
  const wrapper = mount(PoliticaFormularioView, {
    attachTo: document.body,
    global: { plugins: [router], stubs: { FaIcon: true, teleport: true } },
  })
  await flushPromises()
  return { wrapper, router }
}

beforeEach(() => {
  vi.clearAllMocks()
  m.listar.mockResolvedValue([politica()])
  m.documentosAdjuntos.mockResolvedValue([])
})

describe('lista de políticas', () => {
  it('es una tabla con encabezados, no tarjetas', async () => {
    m.listar.mockResolvedValue([politica(), politica({ slug: 'otra', nombre: 'Otra', uuid: 'b' })])
    const { wrapper } = await montarLista()

    expect(wrapper.find('table').exists()).toBe(true)
    const encabezados = wrapper.findAll('th').map((t) => t.text())
    for (const nombre of ['Política', 'Estado', 'Versión', 'Vigente desde', 'Documento oficial', 'Acciones']) {
      expect(encabezados.some((t) => t.includes(nombre))).toBe(true)
    }
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('dice el estado con palabras: adoptada, texto de ejemplo, incompleta', async () => {
    m.listar.mockResolvedValue([
      politica({ slug: 'a', nombre: 'A', es_ejemplo: false }),
      politica({ slug: 'b', nombre: 'B', es_ejemplo: true }),
      politica({ slug: 'c', nombre: 'C', completa: false, problemas: ['Falta algo.'] }),
    ])
    const { wrapper } = await montarLista()
    const filas = wrapper.findAll('tbody tr').map((f) => f.text())

    expect(filas[0]).toContain('Adoptada')
    expect(filas[1]).toContain('Texto de ejemplo')
    expect(filas[2]).toContain('Incompleta')
  })

  it('dice si hay documento oficial', async () => {
    m.listar.mockResolvedValue([
      politica({ slug: 'a', nombre: 'A', documento_oficial: { nombre: 'r.pdf', tipo: 'application/pdf', tamano: 10, url: '/x' } }),
      politica({ slug: 'b', nombre: 'B', documento_oficial: null }),
    ])
    const { wrapper } = await montarLista()
    const filas = wrapper.findAll('tbody tr').map((f) => f.text())

    expect(filas[0]).toContain('Adjunto')
    expect(filas[1]).toContain('Falta')
  })

  it('«Editar» es un enlace a la vista de edición de esa política', async () => {
    const { wrapper } = await montarLista()
    const editar = wrapper.find('tbody a[aria-label^="Editar"]')

    expect(editar.attributes('aria-label')).toContain('Uso de cookies')
    expect(editar.attributes('href')).toBe('/configuracion/politicas/uso-de-cookies')
  })

  it('no ofrece crear ni eliminar: las cinco políticas son fijas', async () => {
    const { wrapper } = await montarLista()
    expect(wrapper.text()).not.toMatch(/Nueva política|Eliminar/)
  })

  it('sin políticas cargadas dice qué pasa, no «Sin resultados»', async () => {
    m.listar.mockResolvedValue([])
    const { wrapper } = await montarLista()

    expect(wrapper.find('table').exists()).toBe(false)
    expect(wrapper.text()).toContain('Todavía no hay políticas cargadas')
    expect(wrapper.text()).not.toContain('Sin resultados')
  })

  it('si falla la carga lo dice y deja reintentar', async () => {
    m.listar.mockRejectedValueOnce(new Error('x'))
    const { wrapper } = await montarLista()

    expect(wrapper.find('[role="alert"]').text()).toContain('No se pudieron cargar las políticas')
    m.listar.mockResolvedValue([politica()])
    await wrapper.find('[role="alert"] button').trigger('click')
    await flushPromises()
    expect(wrapper.find('table').exists()).toBe(true)
  })
})

describe('edición de una política', () => {
  it('carga la política por su slug y muestra sus apartados', async () => {
    const { wrapper } = await montarFormulario()

    expect(wrapper.find('h1').text()).toContain('Uso de cookies')
    expect((wrapper.find('textarea').element as HTMLTextAreaElement).value).toBe('Uno.\n\nDos.')
    expect(wrapper.find('a[href="/configuracion/politicas"]').exists()).toBe(true)
  })

  it('un slug que no existe dice que no se encontró', async () => {
    const { wrapper } = await montarFormulario('no-existe')
    expect(wrapper.text()).toContain('No se encontró la política')
  })

  it('sin cambios no se envía nada; con cambios envía solo lo que cambió', async () => {
    m.actualizar.mockResolvedValue(politica({ nombre: 'Cookies' }))
    const { wrapper } = await montarFormulario()

    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).not.toHaveBeenCalled()

    await wrapper.find('input[type="text"]').setValue('Cookies')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).toHaveBeenCalledTimes(1)
    const [slug, entrada] = m.actualizar.mock.calls[0]!
    expect(slug).toBe('uso-de-cookies')
    expect(entrada).not.toHaveProperty('secciones')
    expect(wrapper.text()).toContain('Política guardada')
  })

  it('explica el rechazo del servidor sin perder lo escrito', async () => {
    const error = Object.assign(new Error('x'), {
      isAxiosError: true,
      response: { status: 422, data: { errors: { politica: ['Falta el apartado «Duración».'] } } },
    })
    m.actualizar.mockRejectedValue(error)
    const { wrapper } = await montarFormulario()
    await wrapper.find('input[type="text"]').setValue('Cookies 2')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[role="alert"]').text()).toContain('Falta el apartado «Duración».')
    expect((wrapper.find('input[type="text"]').element as HTMLInputElement).value).toBe('Cookies 2')
  })

  it('un apartado sin texto se avisa antes de enviar', async () => {
    const { wrapper } = await montarFormulario()
    await wrapper.find('textarea').setValue('   ')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(m.actualizar).not.toHaveBeenCalled()
    expect(wrapper.find('[role="alert"]').text()).toContain('no tiene texto')
  })

  it('los apartados obligatorios no se pueden quitar; los adicionales sí', async () => {
    m.listar.mockResolvedValue([
      politica({
        secciones: [
          { id: 'finalidad', titulo: 'Finalidad', parrafos: ['Uno.'] },
          { id: 'extra', titulo: 'Extra', parrafos: ['Dos.'] },
        ],
      }),
    ])
    const { wrapper } = await montarFormulario()

    expect(wrapper.findAll('[data-accion="quitar-apartado"]')).toHaveLength(1)
    expect(wrapper.text()).toContain('Obligatorio')
  })

  it('avisa si se intenta salir con cambios sin guardar', async () => {
    const { wrapper } = await montarFormulario()
    expect(wrapper.text()).not.toContain('cambios sin guardar')
    await wrapper.find('input[type="text"]').setValue('Cambiado')
    expect(wrapper.text()).toContain('cambios sin guardar')
  })

  it('documento oficial: adjuntar usa el servicio y quitar pide confirmación', async () => {
    m.documentosAdjuntos.mockResolvedValue([
      { uuid: 'f1', original_filename: 'resolucion.pdf', size: 2048, url: '/f1' } as never,
    ])
    m.quitarArchivo.mockResolvedValue()
    const { wrapper } = await montarFormulario()

    expect(wrapper.text()).toContain('resolucion.pdf')
    await wrapper.find('[data-accion="quitar-documento"]').trigger('click')
    expect(m.quitarArchivo).not.toHaveBeenCalled()
    await wrapper.find('[data-accion="quitar-documento"]').trigger('click')
    await flushPromises()
    expect(m.quitarArchivo).toHaveBeenCalledWith('f1')
  })
})
