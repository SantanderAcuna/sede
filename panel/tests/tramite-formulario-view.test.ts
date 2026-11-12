/**
 * TramiteFormularioView — crear y editar un elemento del catálogo (trámite, OPA o consulta).
 *
 * Se verifica lo que protege a quien edita: que se pueda guardar como borrador con solo el nombre,
 * que no se pueda publicar incompleto (y se diga qué falta), que los campos simples viajen solos,
 * que el error del servidor aparezca junto al campo, que se pueda elegir el tipo y la dependencia, y
 * que **todo** elemento se pueda eliminar (ya no hay candado por catálogo).
 */
import { flushPromises, mount } from '@vue/test-utils'
import { AxiosError } from 'axios'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('../src/services/tramites', () => ({
  POR_PAGINA: 20,
  obtener: vi.fn(),
  crear: vi.fn(),
  actualizar: vi.fn(),
  eliminar: vi.fn(),
  dependencias: vi.fn(),
}))

import * as servicio from '../src/services/tramites'
import TramiteFormularioView from '../src/views/admin/TramiteFormularioView.vue'
import type { TramitePanelItem } from '../src/types/api'

const m = vi.mocked(servicio)
const UUID = '33333333-3333-4333-8333-333333333333'

function tramite(extra: Partial<TramitePanelItem> = {}): TramitePanelItem {
  return {
    id: 1,
    uuid: UUID,
    type: 'tramites',
    slug: 'certificado',
    codigo: 'T0001',
    nombre: 'Certificado de residencia',
    resumen: null,
    url_ficha_gov_co: null,
    modalidad: null,
    tiene_costo: null,
    costo: null,
    tiempo_solucion_dias: null,
    canal_inicio: null,
    consulta_estado: null,
    dependencia_id: null,
    requisitos: [],
    pasos: [],
    documentos: [],
    puntos_atencion: [],
    normativa: [],
    canales_consulta_estado: [],
    solicitantes: [],
    medios_resultado: [],
    categoria: null,
    estado: 'borrador',
    completo: false,
    problemas: [],
    ...extra,
  } as unknown as TramitePanelItem
}

const completo = (extra: Partial<TramitePanelItem> = {}) =>
  tramite({
    resumen: 'Constancia de dónde vive.',
    url_ficha_gov_co: 'https://www.gov.co/ficha-tramites-y-servicios/T1',
    modalidad: 'presencial',
    tiene_costo: 'gratuito',
    tiempo_solucion_dias: 3,
    canal_inicio: 'propio',
    consulta_estado: 'Llamando',
    requisitos: [{ tipo: 'documento', descripcion: 'Cédula', obligatorio: true, paso: null }],
    estado: 'publicado',
    completo: true,
    ...extra,
  } as never)

const enrutador = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/tramites', name: 'tramites', component: { template: '<i>lista</i>' } },
      { path: '/tramites/nuevo', name: 'panel.tramites.nuevo', component: TramiteFormularioView },
      { path: '/tramites/:uuid', name: 'panel.tramites.editar', component: TramiteFormularioView },
    ],
  })

const montados: Array<{ unmount: () => void }> = []
async function montar(destino: string) {
  const router = enrutador()
  await router.push(destino)
  await router.isReady()
  const wrapper = mount(TramiteFormularioView, { global: { plugins: [router], stubs: { FaIcon: true, teleport: true } } })
  montados.push(wrapper)
  await flushPromises()
  return { wrapper, router }
}

const rechazo422 = (errors: Record<string, string[]>) =>
  Object.assign(new AxiosError('x'), { response: { status: 422, data: { message: 'No válido', errors } } })

beforeEach(() => {
  vi.clearAllMocks()
  m.dependencias.mockResolvedValue([{ id: 7, nombre: 'Hacienda' }])
})
afterEach(() => {
  montados.splice(0).forEach((w) => w.unmount())
})

const boton = (w: ReturnType<typeof mount>, texto: string) => w.findAll('button').find((b) => b.text().includes(texto))!
const campo = (w: ReturnType<typeof mount>, etiqueta: string) => {
  const l = w.findAll('label').find((x) => x.text().startsWith(etiqueta))!
  return w.find(`#${l.attributes('for')}`)
}

describe('crear', () => {
  it('muestra el formulario con sus secciones y un índice para llegar a ellas', async () => {
    const { wrapper } = await montar('/tramites/nuevo')
    const titulos = wrapper.findAll('h2').map((h) => h.text())
    for (const t of ['Datos principales', 'Costo', 'Requisitos', 'Pasos del trámite', 'Documentos para descargar', 'Resultado', 'Para quién es', 'Dónde se atiende', 'Normativa', 'Consulta del estado', 'Publicación']) {
      expect(titulos).toContain(t)
    }
    expect(wrapper.find('nav[aria-label="Secciones del formulario"] a[href="#t-requisitos"]').exists()).toBe(true)
  })

  it('ofrece elegir el tipo (trámite, OPA o consulta) y la dependencia', async () => {
    const { wrapper } = await montar('/tramites/nuevo')
    expect(wrapper.text()).toContain('OPA (otro procedimiento administrativo)')
    expect(wrapper.text()).toContain('Consulta de acceso a información pública')
    // La dependencia cargada aparece como opción.
    expect(wrapper.text()).toContain('Hacienda')
  })

  it('con solo el nombre se guarda como borrador y se va a la edición', async () => {
    m.crear.mockResolvedValue(tramite())
    const { wrapper, router } = await montar('/tramites/nuevo')
    await campo(wrapper, 'Nombre').setValue('Certificado de residencia')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(m.crear).toHaveBeenCalledWith({ nombre: 'Certificado de residencia', type: 'tramites' })
    expect(router.currentRoute.value.name).toBe('panel.tramites.editar')
    expect(wrapper.text()).toContain('guardado como borrador')
  })

  it('un nombre demasiado corto se explica y no se envía', async () => {
    const { wrapper } = await montar('/tramites/nuevo')
    await campo(wrapper, 'Nombre').setValue('ab')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(m.crear).not.toHaveBeenCalled()
    expect(wrapper.find('[role="alert"]').text()).toMatch(/nombre necesita al menos 3/i)
  })

  it('publicar incompleto no se envía y dice qué falta', async () => {
    const { wrapper } = await montar('/tramites/nuevo')
    await campo(wrapper, 'Nombre').setValue('Licencia')
    await wrapper.find('input[value="publicar"]').setValue(true)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(m.crear).not.toHaveBeenCalled()
    const aviso = wrapper.find('[role="alert"]').text()
    expect(aviso).toContain('Falta la dirección de la ficha en GOV.CO')
    expect(aviso).toContain('Falta al menos un requisito.')
  })

  it('el aviso de lo que faltaba desaparece cuando se corrige', async () => {
    const { wrapper } = await montar('/tramites/nuevo')
    await campo(wrapper, 'Nombre').setValue('ab')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    await campo(wrapper, 'Nombre').setValue('Licencia')
    await flushPromises()
    expect(wrapper.find('[role="alert"]').exists()).toBe(false)
  })

  it('la lista «Antes de publicar» se actualiza mientras se escribe', async () => {
    const { wrapper } = await montar('/tramites/nuevo')
    const lista = () => wrapper.find('[data-checklist]').text()
    expect(lista()).toContain('la dirección de la ficha en GOV.CO')
    await campo(wrapper, 'Dirección de la ficha en GOV.CO').setValue('https://www.gov.co/x')
    expect(lista()).not.toContain('la dirección de la ficha en GOV.CO')
  })

  it('el costo solo se pide si tiene costo', async () => {
    const { wrapper } = await montar('/tramites/nuevo')
    expect(wrapper.text()).not.toContain('Cómo se calcula')
    await campo(wrapper, '¿Tiene costo?').setValue('con_costo')
    expect(wrapper.text()).toContain('Cómo se calcula')
  })

  it('agregar un requisito crea una fila con sus campos', async () => {
    const { wrapper } = await montar('/tramites/nuevo')
    const seccion = wrapper.find('#t-requisitos')
    await seccion.find('[data-agregar]').trigger('click')
    expect(seccion.text()).toContain('Requisito 1')
    expect(seccion.findAll('select')[0]!.element).toBeTruthy()
  })
})

describe('editar', () => {
  it('carga el elemento y muestra su código y estado', async () => {
    m.obtener.mockResolvedValue(completo())
    const { wrapper } = await montar(`/tramites/${UUID}`)
    expect(m.obtener).toHaveBeenCalledWith(UUID)
    expect((campo(wrapper, 'Nombre').element as HTMLInputElement).value).toBe('Certificado de residencia')
    expect(wrapper.text()).toContain('T0001')
    expect(wrapper.text()).toContain('Publicado')
    expect(wrapper.find('[data-checklist]').text()).toContain('Listo')
  })

  it('sin cambios no envía nada', async () => {
    m.obtener.mockResolvedValue(completo())
    const { wrapper } = await montar(`/tramites/${UUID}`)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('No hay cambios que guardar')
  })

  it('con un cambio simple envía solo ese campo', async () => {
    m.obtener.mockResolvedValue(completo())
    m.actualizar.mockResolvedValue(completo({ nombre: 'Otro nombre' }))
    const { wrapper } = await montar(`/tramites/${UUID}`)
    await campo(wrapper, 'Nombre').setValue('Otro nombre')
    expect(wrapper.text()).toContain('Hay cambios sin guardar')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).toHaveBeenCalledWith(UUID, { nombre: 'Otro nombre' })
    expect(wrapper.text()).toContain('El trámite está publicado.')
  })

  it('despublicar envía publicado:false', async () => {
    m.obtener.mockResolvedValue(completo())
    m.actualizar.mockResolvedValue(completo({ estado: 'borrador' } as never))
    const { wrapper } = await montar(`/tramites/${UUID}`)
    await wrapper.find('input[value="borrador"]').setValue(true)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).toHaveBeenCalledWith(UUID, { publicado: false })
  })

  it('el error del servidor sale en el resumen y junto al campo exacto de la fila', async () => {
    m.obtener.mockResolvedValue(completo())
    m.actualizar.mockRejectedValue(rechazo422({ 'requisitos.0.url': ['El campo requisitos.0.url debe ser una URL válida.'] }))
    const { wrapper } = await montar(`/tramites/${UUID}`)
    const seccion = wrapper.find('#t-requisitos')
    const mas = seccion.find('details')
    const url = mas.findAll('label').find((l) => l.text() === 'Dirección web')!
    await mas.find(`#${url.attributes('for')}`).setValue('no es url')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[role="alert"]').text()).toContain('«Requisito 1 · Dirección web» debe ser una URL válida')
    const input = mas.find(`#${url.attributes('for')}`)
    expect(input.attributes('aria-invalid')).toBe('true')
  })

  it('no existe: lo dice con un enlace para volver', async () => {
    m.obtener.mockRejectedValue(Object.assign(new AxiosError('x'), { response: { status: 404, data: {} } }))
    const { wrapper } = await montar(`/tramites/${UUID}`)
    expect(wrapper.find('[role="alert"]').text()).toContain('No se encontró el trámite')
  })
})

describe('borrado', () => {
  it('ya no hay aviso de catálogo SUIT y cualquier elemento se puede eliminar', async () => {
    m.obtener.mockResolvedValue(completo())
    m.eliminar.mockResolvedValue(undefined)
    const { wrapper, router } = await montar(`/tramites/${UUID}`)
    expect(wrapper.text()).not.toContain('catálogo oficial (SUIT)')
    expect(wrapper.text()).toContain('Zona de peligro')
    await boton(wrapper, 'Eliminar trámite').trigger('click')
    expect(m.eliminar).not.toHaveBeenCalled()
    await wrapper.findAll('[role="dialog"] button').find((b) => b.text().includes('Sí, eliminar'))!.trigger('click')
    await flushPromises()
    expect(m.eliminar).toHaveBeenCalledWith(UUID)
    expect(router.currentRoute.value.name).toBe('tramites')
  })
})
