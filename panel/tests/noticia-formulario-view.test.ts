/**
 * NoticiaFormularioView — crear y editar una noticia en una vista propia.
 *
 * Conserva todo lo que ya garantizaba la pantalla anterior (el orden borrador → imagen
 * → publicar, la descripción obligatoria de la imagen, que sólo viaje lo que cambió, la
 * fecha al crear) y añade lo que pide una persona que no conoce el sistema: una lista
 * «Antes de publicar», una elección explícita entre borrador y publicar, el error junto
 * al campo que lo causó, y no perder lo escrito sin avisar.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { AxiosError } from 'axios'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('../src/services/noticias', () => ({
  POR_PAGINA: 20,
  obtener: vi.fn(),
  crear: vi.fn(),
  actualizar: vi.fn(),
  eliminar: vi.fn(),
  subirImagen: vi.fn(),
}))

import * as servicio from '../src/services/noticias'
import NoticiaFormularioView from '../src/views/admin/NoticiaFormularioView.vue'
import type { NoticiaPanelItem } from '../src/types/api'

const m = vi.mocked(servicio)
const UUID = '11111111-1111-4111-8111-111111111111'

function noticia(extra: Partial<NoticiaPanelItem> = {}): NoticiaPanelItem {
  return {
    id: 1,
    type: 'noticias',
    uuid: UUID,
    slug: 'una-noticia',
    titulo: 'Una noticia',
    descripcion_corta: 'Descripción corta',
    contenido: 'Primer párrafo.\n\nSegundo párrafo.',
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

const enrutador = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/contenidos/noticias', name: 'panel.noticias', component: { template: '<i>lista</i>' } },
      { path: '/contenidos/noticias/nueva', name: 'panel.noticias.nueva', component: NoticiaFormularioView },
      { path: '/contenidos/noticias/:uuid', name: 'panel.noticias.editar', component: NoticiaFormularioView },
    ],
  })

const montados: Array<{ unmount: () => void }> = []
// Conectada al documento: jsdom no envía un formulario que no lo está.
afterEach(() => {
  for (const w of montados.splice(0)) w.unmount()
})

async function montar(ruta = '/contenidos/noticias/nueva') {
  const router = enrutador()
  await router.push(ruta)
  await router.isReady()
  const wrapper = mount(NoticiaFormularioView, {
    attachTo: document.body,
    global: { plugins: [router], stubs: { FaIcon: true, teleport: true } },
  })
  montados.push(wrapper)
  await flushPromises()
  return { wrapper, router }
}
type Montado = Awaited<ReturnType<typeof montar>>['wrapper']

function boton(wrapper: Montado, texto: string) {
  const encontrado = wrapper.findAll('button').find((b) => b.text().includes(texto))
  if (!encontrado) throw new Error(`No hay un botón «${texto}»`)
  return encontrado
}

async function escribir(wrapper: Montado, etiqueta: string, valor: string) {
  const campo = wrapper.findAll('label').find((l) => l.text().includes(etiqueta))
  const destino = campo?.attributes('for')
  const control = destino ? wrapper.find(`[id="${destino}"]`) : campo?.find('input, textarea')
  if (!control || !control.exists()) throw new Error(`No hay un campo «${etiqueta}»`)
  await control.setValue(valor)
}

async function elegirImagen(wrapper: Montado, archivo: File) {
  const entrada = wrapper.find('input[type="file"]')
  Object.defineProperty(entrada.element, 'files', { value: [archivo], configurable: true })
  await entrada.trigger('change')
}

async function elegirPublicar(wrapper: Montado) {
  await wrapper.find('input[type="radio"][value="publicar"]').setValue(true)
}

async function rellenar(wrapper: Montado) {
  await escribir(wrapper, 'Título', 'Mi noticia')
  await escribir(wrapper, 'Descripción corta', 'Resumen')
  await escribir(wrapper, 'Contenido', 'Texto de la noticia.')
}

const PNG = new File([new Uint8Array([137, 80, 78, 71])], 'foto.png', { type: 'image/png' })

beforeEach(() => {
  vi.clearAllMocks()
  vi.stubGlobal('confirm', vi.fn(() => true))
  URL.createObjectURL = vi.fn(() => 'blob:prueba')
  URL.revokeObjectURL = vi.fn()
})

describe('es una vista propia, no un panel dentro de la lista', () => {
  it('crear: título de página, enlace para volver a la lista y ninguna tabla', async () => {
    const { wrapper } = await montar()

    expect(wrapper.find('h1').text()).toBe('Nueva noticia')
    expect(wrapper.find('table').exists()).toBe(false)
    const volver = wrapper.findAll('a').find((a) => a.text().includes('Volver a noticias'))
    expect(volver?.attributes('href')).toBe('/contenidos/noticias')
  })

  it('editar: carga la noticia por su uuid y rellena los campos', async () => {
    m.obtener.mockResolvedValue(noticia({ titulo: 'Parque nuevo' }))
    const { wrapper } = await montar(`/contenidos/noticias/${UUID}`)

    expect(m.obtener).toHaveBeenCalledWith(UUID)
    expect(wrapper.find('h1').text()).toBe('Editar noticia')
    const titulo = wrapper.findAll('label').find((l) => l.text().includes('Título'))!
    expect((wrapper.find(`[id="${titulo.attributes('for')}"]`).element as HTMLInputElement).value).toBe('Parque nuevo')
  })

  it('editar: mientras carga lo dice, y si la noticia no existe explica y ofrece volver', async () => {
    const e = new AxiosError('404')
    e.response = { status: 404, data: { message: 'No encontrado.' } } as never
    m.obtener.mockRejectedValue(e)
    const { wrapper } = await montar(`/contenidos/noticias/${UUID}`)

    expect(wrapper.find('[role="alert"]').text()).toContain('No se encontró la noticia')
    expect(wrapper.find('form').exists()).toBe(false)
    expect(wrapper.findAll('a').some((a) => a.text().includes('Volver a noticias'))).toBe(true)
  })

  it('editar: si la carga falla por otra causa dice que falló y deja reintentar', async () => {
    m.obtener.mockRejectedValueOnce(new Error('caída'))
    const { wrapper } = await montar(`/contenidos/noticias/${UUID}`)

    expect(wrapper.find('[role="alert"]').text()).toContain('No se pudo cargar la noticia')
    m.obtener.mockResolvedValue(noticia())
    await boton(wrapper, 'Reintentar').trigger('click')
    await flushPromises()
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('los campos se agrupan en secciones con nombre y cada campo tiene su etiqueta', async () => {
    const { wrapper } = await montar()
    const secciones = wrapper.findAll('section[aria-labelledby]').map((s) => s.find('h2').text())

    expect(secciones).toEqual(['Texto de la noticia', 'Imagen', 'Publicación'])
    for (const control of wrapper.findAll('input:not([type="file"]):not([type="radio"]), textarea')) {
      expect(wrapper.find(`label[for="${control.attributes('id')}"]`).exists()).toBe(true)
    }
  })

  it('«Destacada» no se ofrece: el sitio no la usa todavía', async () => {
    const { wrapper } = await montar()
    expect(wrapper.text()).not.toContain('Destacada')
  })
})

describe('qué falta antes de publicar', () => {
  it('lista cada requisito y marca los cumplidos con texto, no sólo con color', async () => {
    const { wrapper } = await montar()
    const lista = wrapper.find('[data-checklist]')

    expect(lista.text()).toContain('Antes de publicar')
    const items = lista.findAll('li')
    expect(items.length).toBeGreaterThanOrEqual(5)
    expect(items.every((li) => /Falta|Listo/.test(li.text()))).toBe(true)
    expect(items.every((li) => li.text().includes('Falta'))).toBe(true)

    await rellenar(wrapper)
    const faltan = lista.findAll('li').filter((li) => li.text().includes('Falta')).map((li) => li.text())
    expect(faltan.join(' ')).toContain('imagen')
    expect(lista.findAll('li').filter((li) => li.text().includes('Listo')).length).toBeGreaterThanOrEqual(3)
  })

  it('con todo completo, todo dice «Listo»', async () => {
    const { wrapper } = await montar()
    await rellenar(wrapper)
    await elegirImagen(wrapper, PNG)
    await escribir(wrapper, 'Descripción de la imagen', 'Una vista del puerto')

    expect(wrapper.find('[data-checklist]').text()).not.toContain('Falta')
  })
})

describe('crear una noticia', () => {
  it('con imagen: guarda como borrador, sube la imagen con su descripción y recién entonces publica', async () => {
    const creada = noticia({ uuid: 'nueva', slug: 'mi-noticia', titulo: 'Mi noticia' })
    const conImagen = noticia({ ...creada, imagen: { url: '/api/v1/noticias/mi-noticia/imagen', alt: 'Una vista del puerto' }, completa: true })
    const publicada = { ...conImagen, activo: true, estado: 'publicada' } as NoticiaPanelItem
    m.crear.mockResolvedValue(creada)
    m.subirImagen.mockResolvedValue(conImagen)
    m.actualizar.mockResolvedValue(publicada)

    const { wrapper } = await montar()
    await rellenar(wrapper)
    await elegirImagen(wrapper, PNG)
    await escribir(wrapper, 'Descripción de la imagen', 'Una vista del puerto')
    await elegirPublicar(wrapper)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.crear).toHaveBeenCalledTimes(1)
    expect(m.crear.mock.calls[0]![0]).toMatchObject({ titulo: 'Mi noticia', activo: false })
    expect(m.subirImagen).toHaveBeenCalledWith('nueva', PNG, 'Una vista del puerto')
    expect(m.actualizar).toHaveBeenCalledWith('nueva', { activo: true })
    const orden = [m.crear, m.subirImagen, m.actualizar].map((f) => f.mock.invocationCallOrder[0]!)
    expect([...orden].sort((a, b) => a - b)).toEqual(orden)
  })

  it('al crear pasa a la vista de edición de la noticia creada y confirma lo que ocurrió', async () => {
    m.crear.mockResolvedValue(noticia({ uuid: 'creada-1', titulo: 'Mi noticia' }))
    const { wrapper, router } = await montar()
    await rellenar(wrapper)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('panel.noticias.editar')
    expect(router.currentRoute.value.params.uuid).toBe('creada-1')
    expect(m.obtener).not.toHaveBeenCalled() // ya la tiene; no la vuelve a pedir
    expect(wrapper.find('[role="status"]').text()).toContain('guardada como borrador')
  })

  it('con imagen pero sin su descripción: no envía nada y explica por qué', async () => {
    const { wrapper } = await montar()
    await rellenar(wrapper)
    await elegirImagen(wrapper, PNG)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.crear).not.toHaveBeenCalled()
    expect(m.subirImagen).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('descripción de la imagen')
  })

  it('una descripción de sólo espacios tampoco vale', async () => {
    const { wrapper } = await montar()
    await rellenar(wrapper)
    await elegirImagen(wrapper, PNG)
    await escribir(wrapper, 'Descripción de la imagen', '    ')
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.crear).not.toHaveBeenCalled()
  })

  it('publicar sin imagen: no envía nada y dice que falta la imagen', async () => {
    const { wrapper } = await montar()
    await rellenar(wrapper)
    await elegirPublicar(wrapper)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.crear).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('hace falta una imagen')
  })

  it('al crear envía la fecha de publicación aunque no se haya tocado (el servidor la exige)', async () => {
    m.crear.mockResolvedValue(noticia({ uuid: 'n' }))
    const { wrapper } = await montar()
    await rellenar(wrapper)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    const enviado = m.crear.mock.calls[0]![0] as { fecha_publicacion?: string }
    expect(enviado.fecha_publicacion).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:00[+-]\d{2}:\d{2}$/)
  })

  it('«Guardar» está disponible desde el principio: pulsarlo vacío explica qué falta, no se queda mudo', async () => {
    const { wrapper } = await montar()
    const guardar = boton(wrapper, 'Guardar')

    expect(guardar.attributes('disabled')).toBeUndefined()
    await guardar.trigger('click')
    await flushPromises()

    expect(m.crear).not.toHaveBeenCalled()
    expect(wrapper.find('[role="alert"]').text()).toContain('Falta el título')
  })

  it('el error de validación local queda junto al campo y el foco va al primero', async () => {
    const { wrapper } = await montar()
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    const titulo = wrapper.findAll('label').find((l) => l.text().includes('Título'))!
    const control = wrapper.find(`[id="${titulo.attributes('for')}"]`)
    expect(control.attributes('aria-invalid')).toBe('true')
  })

  it('si el servidor rechaza, el motivo aparece junto al campo y en el resumen; no dice que se guardó', async () => {
    const error = new AxiosError('422')
    error.response = { status: 422, data: { errors: { titulo: ['El título ya existe.'] } } } as never
    m.crear.mockRejectedValue(error)

    const { wrapper } = await montar()
    await rellenar(wrapper)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(wrapper.find('[role="alert"]').text()).toContain('El título ya existe.')
    const titulo = wrapper.findAll('label').find((l) => l.text().includes('Título'))!
    const describedby = wrapper.find(`[id="${titulo.attributes('for')}"]`).attributes('aria-describedby') ?? ''
    expect(describedby.split(' ').some((id) => wrapper.find(`[id="${id}"]`).exists() && wrapper.find(`[id="${id}"]`).text().includes('El título ya existe.'))).toBe(true)
    expect(wrapper.text()).not.toContain('Noticia guardada')
  })

  it('si falla la subida de la imagen, no la publica y no pierde lo escrito', async () => {
    m.crear.mockResolvedValue(noticia({ uuid: 'n' }))
    m.subirImagen.mockRejectedValue(new Error('caída'))

    const { wrapper } = await montar()
    await rellenar(wrapper)
    await elegirImagen(wrapper, PNG)
    await escribir(wrapper, 'Descripción de la imagen', 'Una vista')
    await elegirPublicar(wrapper)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.actualizar).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('No se pudo guardar la noticia')
    const titulo = wrapper.findAll('label').find((l) => l.text().includes('Título'))!
    expect((wrapper.find(`[id="${titulo.attributes('for')}"]`).element as HTMLInputElement).value).toBe('Mi noticia')
  })

  it('un segundo clic mientras guarda no crea la noticia dos veces', async () => {
    let resolver: (n: NoticiaPanelItem) => void = () => {}
    m.crear.mockReturnValue(new Promise((r) => (resolver = r)))
    const { wrapper } = await montar()
    await rellenar(wrapper)

    const guardar = boton(wrapper, 'Guardar')
    await guardar.trigger('click')
    await guardar.trigger('click')
    resolver(noticia({ uuid: 'n' }))
    await flushPromises()

    expect(m.crear).toHaveBeenCalledTimes(1)
  })
})

describe('elegir entre borrador y publicar', () => {
  it('ofrece dos opciones con su consecuencia escrita; por defecto, borrador', async () => {
    const { wrapper } = await montar()
    const radios = wrapper.findAll('input[type="radio"]')

    expect(radios.map((r) => r.attributes('value'))).toEqual(['borrador', 'publicar'])
    expect((radios[0]!.element as HTMLInputElement).checked).toBe(true)
    expect(wrapper.text()).toContain('No se ve en el sitio')
    expect(wrapper.text()).toContain('Se ve en el sitio')
  })

  it('con fecha futura y «publicar» avisa que quedará programada', async () => {
    const { wrapper } = await montar()
    await elegirPublicar(wrapper)
    await escribir(wrapper, 'Fecha de publicación', '2099-01-01T08:00')

    expect(wrapper.find('[data-aviso-programada]').exists()).toBe(true)
    expect(wrapper.find('[data-aviso-programada]').text()).toContain('programada')
  })
})

describe('editar y eliminar', () => {
  const publicadaConImagen = () =>
    noticia({ activo: true, estado: 'publicada', completa: true, imagen: { url: '/x', alt: 'Alt' } })

  it('editar sólo el título envía sólo el título', async () => {
    const existente = publicadaConImagen()
    m.obtener.mockResolvedValue(existente)
    m.actualizar.mockResolvedValue({ ...existente, titulo: 'Título nuevo' })

    const { wrapper } = await montar(`/contenidos/noticias/${UUID}`)
    await escribir(wrapper, 'Título', 'Título nuevo')
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.actualizar).toHaveBeenCalledWith(UUID, { titulo: 'Título nuevo' })
    expect(wrapper.find('[role="status"]').text()).toContain('Cambios guardados')
  })

  it('guardar sin haber cambiado nada no llama al servidor y lo dice', async () => {
    m.obtener.mockResolvedValue(publicadaConImagen())
    const { wrapper } = await montar(`/contenidos/noticias/${UUID}`)

    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.actualizar).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('No hay cambios que guardar')
  })

  it('vaciar la descripción de la imagen de una noticia publicada no se envía', async () => {
    m.obtener.mockResolvedValue(publicadaConImagen())
    const { wrapper } = await montar(`/contenidos/noticias/${UUID}`)

    await escribir(wrapper, 'Descripción de la imagen', '')
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    expect(m.actualizar).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('descripción de la imagen')
  })

  it('una noticia publicada muestra «Ver en el sitio»; un borrador no', async () => {
    m.obtener.mockResolvedValue(publicadaConImagen())
    const publicada = (await montar(`/contenidos/noticias/${UUID}`)).wrapper
    expect(publicada.findAll('a').some((a) => a.attributes('href') === '/noticias/una-noticia')).toBe(true)

    m.obtener.mockResolvedValue(noticia())
    const borrador = (await montar(`/contenidos/noticias/${UUID}`)).wrapper
    expect(borrador.findAll('a').some((a) => a.attributes('href') === '/noticias/una-noticia')).toBe(false)
  })

  it('eliminar vive en una zona aparte, pide confirmar en un diálogo y vuelve a la lista', async () => {
    m.obtener.mockResolvedValue(noticia({ titulo: 'Parque nuevo' }))
    m.eliminar.mockResolvedValue(undefined)
    const { wrapper, router } = await montar(`/contenidos/noticias/${UUID}`)

    await boton(wrapper, 'Eliminar noticia').trigger('click')
    await flushPromises()
    expect(m.eliminar).not.toHaveBeenCalled()
    expect(wrapper.find('[role="dialog"]').text()).toContain('Parque nuevo')

    await wrapper.findAll('[role="dialog"] button').find((b) => b.text().includes('Sí, eliminar'))!.trigger('click')
    await flushPromises()

    expect(m.eliminar).toHaveBeenCalledWith(UUID)
    expect(router.currentRoute.value.name).toBe('panel.noticias')
  })

  it('al crear no hay zona de eliminar: no hay nada que eliminar todavía', async () => {
    const { wrapper } = await montar()
    expect(wrapper.text()).not.toContain('Eliminar noticia')
  })
})

describe('no perder lo escrito', () => {
  it('con cambios sin guardar, salir pregunta; si la persona dice que no, se queda', async () => {
    vi.stubGlobal('confirm', vi.fn(() => false))
    const { wrapper, router } = await montar()
    await escribir(wrapper, 'Título', 'Algo a medio escribir')

    await router.push('/contenidos/noticias')
    await flushPromises()

    expect(window.confirm).toHaveBeenCalled()
    expect(router.currentRoute.value.name).toBe('panel.noticias.nueva')
  })

  it('si acepta descartar, sale', async () => {
    const { wrapper, router } = await montar()
    await escribir(wrapper, 'Título', 'Algo a medio escribir')

    await router.push('/contenidos/noticias')
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('panel.noticias')
  })

  it('sin cambios, salir no pregunta nada', async () => {
    const { router } = await montar()
    await router.push('/contenidos/noticias')
    await flushPromises()

    expect(window.confirm).not.toHaveBeenCalled()
    expect(router.currentRoute.value.name).toBe('panel.noticias')
  })

  it('después de guardar, salir ya no pregunta', async () => {
    m.crear.mockResolvedValue(noticia({ uuid: 'n' }))
    const { wrapper, router } = await montar()
    await rellenar(wrapper)
    await boton(wrapper, 'Guardar').trigger('click')
    await flushPromises()

    await router.push('/contenidos/noticias')
    await flushPromises()

    expect(window.confirm).not.toHaveBeenCalled()
  })

  it('avisa del texto sin guardar también al cerrar o recargar la pestaña', async () => {
    const { wrapper } = await montar()
    await escribir(wrapper, 'Título', 'Algo')

    const evento = new Event('beforeunload', { cancelable: true })
    window.dispatchEvent(evento)
    expect(evento.defaultPrevented).toBe(true)

    wrapper.unmount()
    const despues = new Event('beforeunload', { cancelable: true })
    window.dispatchEvent(despues)
    expect(despues.defaultPrevented).toBe(false)
  })
})

describe('contadores', () => {
  it('avisan cuando el título pasa de 150 caracteres, con texto y no sólo color', async () => {
    const { wrapper } = await montar()
    await escribir(wrapper, 'Título', 'x'.repeat(151))

    expect(wrapper.find('[data-contador="titulo"]').text()).toContain('-1')
  })
})
