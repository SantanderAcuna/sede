/**
 * NoticiasView — el flujo completo de la pantalla, con el servicio simulado.
 *
 * Las pruebas de `noticias.test.ts` cubren la lógica pura y el servicio; esto cubre lo
 * que hace la pantalla con ellos, que es donde está el riesgo: el orden de las llamadas
 * (una noticia no se publica antes de tener imagen), que no se envíe nada si falta la
 * descripción de la imagen, y qué se le dice a quien edita cuando el servidor rechaza.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { AxiosError } from 'axios'
import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('../src/services/noticias', () => ({
  POR_PAGINA: 20,
  listar: vi.fn(),
  crear: vi.fn(),
  actualizar: vi.fn(),
  eliminar: vi.fn(),
  subirImagen: vi.fn(),
}))

import * as servicio from '../src/services/noticias'
import NoticiasView from '../src/views/admin/NoticiasView.vue'
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
    contenido: 'Primer párrafo.\n\nSegundo párrafo.',
    fecha_publicacion: '2026-11-01T09:00:00+00:00',
    destacada: false,
    activo: false,
    imagen: null,
    completa: false,
    problemas: [],
    ...extra,
  } as NoticiaPanelItem
}

const CONTROLES = { global: { stubs: { FaIcon: true } } }

async function montar() {
  const wrapper = mount(NoticiasView, CONTROLES)
  await flushPromises()
  return wrapper
}

function boton(wrapper: Awaited<ReturnType<typeof montar>>, texto: string) {
  const encontrado = wrapper.findAll('button').find((b) => b.text().includes(texto))
  if (!encontrado) throw new Error(`No hay un botón «${texto}»`)
  return encontrado
}

async function escribir(wrapper: Awaited<ReturnType<typeof montar>>, etiqueta: string, valor: string) {
  const campo = wrapper.findAll('label').find((l) => l.text().includes(etiqueta))
  // `FormField` separa la etiqueta del control (`for`/`id`); los campos nativos lo llevan dentro.
  const destino = campo?.attributes('for')
  const control = destino ? wrapper.find(`[id="${destino}"]`) : campo?.find('input, textarea')
  if (!control) throw new Error(`No hay un campo «${etiqueta}»`)
  await control.setValue(valor)
}

async function elegirImagen(wrapper: Awaited<ReturnType<typeof montar>>, archivo: File) {
  const entrada = wrapper.find('input[type="file"]')
  Object.defineProperty(entrada.element, 'files', { value: [archivo], configurable: true })
  await entrada.trigger('change')
}

const PNG = new File([new Uint8Array([137, 80, 78, 71])], 'foto.png', { type: 'image/png' })

beforeEach(() => {
  vi.clearAllMocks()
  vi.stubGlobal('confirm', () => true)
  URL.createObjectURL = vi.fn(() => 'blob:prueba')
  URL.revokeObjectURL = vi.fn()
  m.listar.mockResolvedValue([])
})

describe('lista', () => {
  it('muestra cada noticia con su estado', async () => {
    m.listar.mockResolvedValue([
      noticia({ titulo: 'Publicada uno', activo: true, slug: 'a', uuid: 'a' }),
      noticia({ titulo: 'Borrador dos', activo: false, slug: 'b', uuid: 'b' }),
    ])
    const wrapper = await montar()
    expect(wrapper.text()).toContain('Publicada uno')
    expect(wrapper.text()).toContain('Borrador dos')
    expect(wrapper.text()).toContain('Publicada')
    expect(wrapper.text()).toContain('Borrador')
  })

  it('sin noticias lo dice y ofrece crear la primera', async () => {
    const wrapper = await montar()
    expect(wrapper.text()).toContain('Aún no hay noticias')
  })

  it('si la carga falla, lo dice y no afirma que no hay noticias', async () => {
    m.listar.mockRejectedValue(new Error('caída'))
    const wrapper = await montar()
    expect(wrapper.text()).toContain('No se pudieron cargar las noticias')
    expect(wrapper.text()).not.toContain('Aún no hay noticias')
  })
})

describe('crear una noticia', () => {
  async function rellenar(wrapper: Awaited<ReturnType<typeof montar>>) {
    await boton(wrapper, 'Nueva noticia').trigger('click')
    await escribir(wrapper, 'Título', 'Mi noticia')
    await escribir(wrapper, 'Descripción corta', 'Resumen')
    await escribir(wrapper, 'Contenido', 'Texto de la noticia.')
  }

  it('con imagen: guarda como borrador, sube la imagen con su descripción y recién entonces publica', async () => {
    const creada = noticia({ uuid: 'nueva', slug: 'mi-noticia', titulo: 'Mi noticia' })
    const conImagen = noticia({ ...creada, imagen: { url: '/api/v1/noticias/mi-noticia/imagen', alt: 'Una vista del puerto' }, completa: true })
    const publicada = { ...conImagen, activo: true }
    m.crear.mockResolvedValue(creada)
    m.subirImagen.mockResolvedValue(conImagen)
    m.actualizar.mockResolvedValue(publicada)

    const wrapper = await montar()
    await rellenar(wrapper)
    await elegirImagen(wrapper, PNG)
    await escribir(wrapper, 'Descripción de la imagen', 'Una vista del puerto')
    await wrapper.findAll('input[type="checkbox"]')[0]!.setValue(true) // Publicada
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.crear).toHaveBeenCalledTimes(1)
    expect(m.crear.mock.calls[0]![0]).toMatchObject({ titulo: 'Mi noticia', activo: false })
    expect(m.subirImagen).toHaveBeenCalledWith('nueva', PNG, 'Una vista del puerto')
    expect(m.actualizar).toHaveBeenCalledWith('nueva', { activo: true })

    // El orden importa: publicar antes de tener imagen lo rechazaría el servidor.
    const orden = [m.crear, m.subirImagen, m.actualizar].map((f) => f.mock.invocationCallOrder[0]!)
    expect([...orden].sort((a, b) => a - b)).toEqual(orden)
    expect(wrapper.text()).toContain('guardada y publicada')
  })

  it('con imagen pero sin su descripción: no envía nada y explica por qué', async () => {
    const wrapper = await montar()
    await rellenar(wrapper)
    await elegirImagen(wrapper, PNG)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.crear).not.toHaveBeenCalled()
    expect(m.subirImagen).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('descripción de la imagen')
  })

  it('una descripción de sólo espacios tampoco vale', async () => {
    const wrapper = await montar()
    await rellenar(wrapper)
    await elegirImagen(wrapper, PNG)
    await escribir(wrapper, 'Descripción de la imagen', '    ')
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.crear).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('descripción de la imagen')
  })

  it('publicada y sin imagen: no envía nada y dice que falta la imagen', async () => {
    const wrapper = await montar()
    await rellenar(wrapper)
    await wrapper.findAll('input[type="checkbox"]')[0]!.setValue(true)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.crear).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('hace falta una imagen')
  })

  it('como borrador y sin imagen se puede guardar', async () => {
    m.crear.mockResolvedValue(noticia({ uuid: 'n', titulo: 'Mi noticia' }))
    const wrapper = await montar()
    await rellenar(wrapper)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.crear).toHaveBeenCalledTimes(1)
    expect(m.subirImagen).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('borrador')
  })

  it('si el servidor rechaza, muestra sus motivos y no dice que se guardó', async () => {
    const error = new AxiosError('422')
    error.response = { status: 422, data: { errors: { titulo: ['El título ya existe.'] } } } as never
    m.crear.mockRejectedValue(error)

    const wrapper = await montar()
    await rellenar(wrapper)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('El título ya existe.')
    expect(wrapper.text()).not.toContain('Noticia guardada')
  })

  it('si falla la subida de la imagen, no la publica', async () => {
    m.crear.mockResolvedValue(noticia({ uuid: 'n' }))
    m.subirImagen.mockRejectedValue(new Error('caída'))

    const wrapper = await montar()
    await rellenar(wrapper)
    await elegirImagen(wrapper, PNG)
    await escribir(wrapper, 'Descripción de la imagen', 'Una vista')
    await wrapper.findAll('input[type="checkbox"]')[0]!.setValue(true)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.actualizar).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('No se pudo guardar la noticia')
  })
})

describe('editar y eliminar', () => {
  it('editar sólo el título envía sólo el título', async () => {
    const existente = noticia({ activo: true, completa: true, imagen: { url: '/x', alt: 'Alt' } })
    m.listar.mockResolvedValue([existente])
    m.actualizar.mockResolvedValue({ ...existente, titulo: 'Título nuevo' })

    const wrapper = await montar()
    await wrapper.find('[data-noticia="una-noticia"]').trigger('click')
    await escribir(wrapper, 'Título', 'Título nuevo')
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.actualizar).toHaveBeenCalledWith(existente.uuid, { titulo: 'Título nuevo' })
  })

  it('vaciar la descripción de una imagen de una noticia publicada no se envía', async () => {
    const existente = noticia({ activo: true, completa: true, imagen: { url: '/x', alt: 'Alt' } })
    m.listar.mockResolvedValue([existente])

    const wrapper = await montar()
    await wrapper.find('[data-noticia="una-noticia"]').trigger('click')
    await escribir(wrapper, 'Descripción de la imagen', '')
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.actualizar).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('descripción de la imagen')
  })

  it('eliminar pide confirmar y sólo entonces elimina', async () => {
    const existente = noticia()
    m.listar.mockResolvedValue([existente])
    m.eliminar.mockResolvedValue(undefined)

    const wrapper = await montar()
    await wrapper.find('[data-noticia="una-noticia"]').trigger('click')

    await boton(wrapper, 'Eliminar').trigger('click')
    expect(m.eliminar).not.toHaveBeenCalled()

    await boton(wrapper, 'Confirmar').trigger('click')
    await flushPromises()
    expect(m.eliminar).toHaveBeenCalledWith(existente.uuid)
    expect(wrapper.find('[data-noticia="una-noticia"]').exists()).toBe(false)
  })

  it('el contador avisa cuando el título pasa de 150 caracteres', async () => {
    const wrapper = await montar()
    await boton(wrapper, 'Nueva noticia').trigger('click')
    await escribir(wrapper, 'Título', 'x'.repeat(151))
    expect(wrapper.find('[data-contador="titulo"]').text()).toContain('-1')
  })
})
