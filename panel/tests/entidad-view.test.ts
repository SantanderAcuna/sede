/**
 * EntidadView — la pantalla que edita los datos institucionales, con servicio simulado.
 *
 * Se verifica lo que vive quien administra: que cada dato esté en una sección con nombre,
 * que guardar envíe solo lo cambiado, que se pueda BORRAR un dato (antes era imposible),
 * que un dato mal escrito se avise junto al campo, y que no se pierda lo escrito.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { conSesion } from './ayudas/sesion'
import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('../src/services/entidad', () => ({
  obtener: vi.fn(),
  actualizar: vi.fn(),
  politicasDisponibles: vi.fn(),
}))

import * as servicio from '../src/services/entidad'
import EntidadView from '../src/views/admin/EntidadView.vue'
import type { EntidadItem } from '../src/types/api'

const m = vi.mocked(servicio)

const entidad = (extra: Partial<EntidadItem> = {}): EntidadItem =>
  ({
    id: 1,
    uuid: 'u',
    type: 'entidad',
    nombre: 'Alcaldía Distrital de Santa Marta',
    sigla: 'D.T.C.H.',
    nit: '891.780.009-4',
    direccion: 'Calle 14',
    municipio: 'Santa Marta',
    departamento: 'Magdalena',
    pais: 'Colombia',
    telefono: '(+57) 605 420 9600',
    linea_atencion: null,
    linea_gratuita: null,
    linea_anticorrupcion: null,
    correo_atencion: 'a@santamarta.gov.co',
    correo_notificaciones_judiciales: null,
    horario: 'Lunes a viernes',
    codigo_postal: null,
    dominio: 'https://santamarta.gov.co',
    logo: '/logo-entidad.png',
    redes: [{ red: 'facebook', url: 'https://facebook.com/x' }],
    politicas: [{ slug: 'uso-de-cookies', nombre: 'Uso de cookies' }],
    datos_por_confirmar: [],
    ...extra,
  }) as EntidadItem

async function montar() {
  const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/', component: { template: '<i/>' } }] })
  await router.push('/')
  await router.isReady()
  const wrapper = mount(EntidadView, { attachTo: document.body, global: { plugins: [router], stubs: { FaIcon: true } } })
  await flushPromises()
  return wrapper
}

const campo = (w: ReturnType<typeof mount>, etiqueta: string) => {
  const label = w.findAll('label').find((l) => l.text().startsWith(etiqueta))
  if (!label) throw new Error(`No hay campo «${etiqueta}»`)
  return w.find(`#${label.attributes('for')}`)
}

beforeEach(() => {
  conSesion()
  vi.clearAllMocks()
  m.obtener.mockResolvedValue(entidad())
  m.politicasDisponibles.mockResolvedValue([
    { slug: 'uso-de-cookies', nombre: 'Uso de cookies' },
    { slug: 'seguridad-y-privacidad', nombre: 'Seguridad y privacidad' },
  ])
})

describe('estructura', () => {
  it('organiza los datos en secciones con nombre, no en pestañas ocultas', async () => {
    const w = await montar()
    const secciones = w.findAll('h2').map((h) => h.text())
    for (const s of ['Identidad', 'Atención al público', 'Teléfonos y correos', 'Redes sociales', 'Políticas en el pie de página']) {
      expect(secciones).toContain(s)
    }
    expect(w.find('[role="tablist"]').exists()).toBe(false)
  })

  it('carga los valores actuales', async () => {
    const w = await montar()
    expect((campo(w, 'Nombre de la entidad').element as HTMLInputElement).value).toBe('Alcaldía Distrital de Santa Marta')
    expect((campo(w, 'Teléfono principal').element as HTMLInputElement).value).toBe('(+57) 605 420 9600')
  })

  it('explica que el escudo no se edita aquí', async () => {
    const w = await montar()
    expect(w.text()).toContain('escudo')
  })

  it('si falla la carga lo dice y deja reintentar', async () => {
    m.obtener.mockRejectedValueOnce(new Error('x'))
    const w = await montar()
    expect(w.find('[role="alert"]').text()).toContain('No se pudieron cargar los datos')
    m.obtener.mockResolvedValue(entidad())
    await w.find('[role="alert"] button').trigger('click')
    await flushPromises()
    expect(w.find('form').exists()).toBe(true)
  })
})

describe('guardar', () => {
  it('sin cambios no envía nada y lo dice', async () => {
    const w = await montar()
    await w.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).not.toHaveBeenCalled()
    expect(w.text()).toContain('No hay cambios que guardar')
  })

  it('envía solo lo cambiado y confirma', async () => {
    m.actualizar.mockResolvedValue(entidad({ telefono: '123' }))
    const w = await montar()
    await campo(w, 'Teléfono principal').setValue('123')
    expect(w.text()).toContain('Hay cambios sin guardar')
    await w.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).toHaveBeenCalledWith({ telefono: '123' })
    expect(w.text()).toContain('Cambios guardados')
    expect(w.text()).not.toContain('Hay cambios sin guardar')
  })

  it('vaciar un dato opcional lo borra (envía null)', async () => {
    m.actualizar.mockResolvedValue(entidad({ correo_atencion: null }))
    const w = await montar()
    await campo(w, 'Correo de atención').setValue('')
    await w.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).toHaveBeenCalledWith({ correo_atencion: null })
  })

  it('un dato mal escrito se avisa junto al campo y no se envía', async () => {
    const w = await montar()
    await campo(w, 'Correo de atención').setValue('sin-arroba')
    await w.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).not.toHaveBeenCalled()
    const entrada = campo(w, 'Correo de atención')
    expect(entrada.attributes('aria-invalid')).toBe('true')
    expect(w.find('[role="alert"]').text()).toContain('correo')
  })

  it('un rechazo del servidor se explica y no borra lo escrito', async () => {
    m.actualizar.mockRejectedValue(
      Object.assign(new Error('x'), {
        isAxiosError: true,
        response: { status: 422, data: { message: 'Datos inválidos', errors: { nit: ['El NIT no es válido.'] } } },
      }),
    )
    const w = await montar()
    await campo(w, 'NIT').setValue('mal')
    await w.find('form').trigger('submit')
    await flushPromises()
    expect(w.find('[role="alert"]').text()).toContain('El NIT no es válido.')
    expect((campo(w, 'NIT').element as HTMLInputElement).value).toBe('mal')
  })
})

describe('redes sociales', () => {
  it('se puede quitar la última red y guardar la lista vacía', async () => {
    m.actualizar.mockResolvedValue(entidad({ redes: [] }))
    const w = await montar()
    await w.find('[data-accion="quitar-red"]').trigger('click')
    expect(w.find('[data-red]').exists()).toBe(false)
    expect(w.text()).toContain('No hay redes sociales')
    await w.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).toHaveBeenCalledWith({ redes: [] })
  })

  it('agregar una red abre una fila con su campo', async () => {
    const w = await montar()
    await w.find('[data-accion="agregar-red"]').trigger('click')
    expect(w.findAll('[data-red]')).toHaveLength(2)
  })

  it('una red sin dirección válida se avisa', async () => {
    const w = await montar()
    await w.find('[data-accion="agregar-red"]').trigger('click')
    await w.find('form').trigger('submit')
    await flushPromises()
    expect(m.actualizar).not.toHaveBeenCalled()
    expect(w.find('[role="alert"]').text()).toContain('red social')
  })
})

describe('políticas del pie de página', () => {
  it('se eligen de las políticas que existen, no se escribe el identificador', async () => {
    const w = await montar()
    const opciones = w.findAll('[data-politica] select option').map((o) => o.attributes('value'))
    expect(opciones).toContain('seguridad-y-privacidad')
    expect(w.find('[data-politica] input[placeholder*="terminos"]').exists()).toBe(false)
  })

  it('se puede agregar y quitar una política del pie', async () => {
    const w = await montar()
    await w.find('[data-accion="agregar-politica"]').trigger('click')
    expect(w.findAll('[data-politica]')).toHaveLength(2)
    await w.findAll('[data-accion="quitar-politica"]')[1]!.trigger('click')
    expect(w.findAll('[data-politica]')).toHaveLength(1)
  })
})
