/**
 * EditorTexto — editor mínimo (Tiptap, MIT). Solo conoce lo que el sitio sabe mostrar;
 * lo demás, aunque se pegue, no existe en su esquema. El servidor sanea igualmente
 * (control principal): esto es la primera capa, no la única.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest'

vi.mock('../src/services/contenido', () => ({ subirImagenContenido: vi.fn() }))
import { subirImagenContenido } from '../src/services/contenido'
import EditorTexto from '../src/components/base/EditorTexto.vue'

// jsdom no calcula geometría; ProseMirror la pide al enfocar.
beforeAll(() => {
  const vacio = { length: 0, item: () => null, [Symbol.iterator]: function* () {} } as unknown as DOMRectList
  Range.prototype.getClientRects = () => vacio
  Range.prototype.getBoundingClientRect = () => new DOMRect()
  Element.prototype.getClientRects = () => vacio
})

let w: ReturnType<typeof mount> | null = null
afterEach(() => { w?.unmount(); w = null })

async function montar(props: Record<string, unknown> = {}) {
  w = mount(EditorTexto, { props: { modelValue: '', label: 'Contenido', ...props }, attachTo: document.body })
  await nextTick()
  await flushPromises()
  return w
}
const editor = () => (w!.vm as unknown as { editor: import('@tiptap/core').Editor }).editor
const html = () => editor().getHTML()

describe('EditorTexto — estructura y accesibilidad', () => {
  it('tiene barra de herramientas con nombre y un área editable con rol y etiqueta', async () => {
    await montar({ hint: 'Escriba el texto', required: true })
    expect(w!.get('[role="toolbar"]').attributes('aria-label')).toBe('Formato del texto')
    const area = w!.get('[contenteditable="true"]')
    expect(area.attributes('role')).toBe('textbox')
    expect(area.attributes('aria-multiline')).toBe('true')
    expect(area.attributes('aria-labelledby')).toBeTruthy()
    expect(w!.get('label, [data-etiqueta]').text()).toContain('Contenido')
  })

  it('cada botón tiene nombre accesible en español y estado pressed', async () => {
    await montar()
    const nombres = w!.findAll('[role="toolbar"] button').map((b) => b.attributes('aria-label'))
    expect(nombres).toEqual(expect.arrayContaining([
      'Negrita', 'Cursiva', 'Título', 'Subtítulo', 'Lista con viñetas', 'Lista numerada', 'Cita', 'Enlace', 'Imagen', 'Deshacer', 'Rehacer',
    ]))
    for (const b of w!.findAll('[role="toolbar"] button')) {
      expect(b.attributes('type')).toBe('button')
    }
    expect(w!.get('[aria-label="Negrita"]').attributes('aria-pressed')).toBe('false')
  })

  it('un error se asocia al área editable', async () => {
    await montar({ error: 'El contenido es obligatorio.' })
    expect(w!.get('[role="alert"]').text()).toBe('El contenido es obligatorio.')
    expect(w!.get('[contenteditable="true"]').attributes('aria-invalid')).toBe('true')
    expect(w!.get('[contenteditable="true"]').attributes('aria-describedby')).toBeTruthy()
  })
})

describe('EditorTexto — contenido', () => {
  it('muestra el HTML recibido y emite HTML al editar', async () => {
    await montar({ modelValue: '<p>Hola</p>' })
    expect(w!.get('[contenteditable="true"]').text()).toBe('Hola')
    editor().commands.setContent('<p>Hola <strong>mundo</strong></p>', { emitUpdate: true })
    await flushPromises()
    expect(w!.emitted('update:modelValue')!.at(-1)).toEqual(['<p>Hola <strong>mundo</strong></p>'])
  })

  it('el botón Negrita aplica el formato y refleja el estado', async () => {
    await montar({ modelValue: '<p>texto</p>' })
    editor().commands.selectAll()
    await w!.get('[aria-label="Negrita"]').trigger('click')
    expect(html()).toBe('<p><strong>texto</strong></p>')
    expect(w!.get('[aria-label="Negrita"]').attributes('aria-pressed')).toBe('true')
  })

  it('Título y Subtítulo producen h2 y h3 (nunca h1: el h1 es el de la página)', async () => {
    await montar({ modelValue: '<p>t</p>' })
    editor().commands.selectAll()
    await w!.get('[aria-label="Título"]').trigger('click')
    expect(html()).toBe('<h2>t</h2>')
    await w!.get('[aria-label="Subtítulo"]').trigger('click')
    expect(html()).toBe('<h3>t</h3>')
  })

  it('listas y cita', async () => {
    await montar({ modelValue: '<p>a</p>' })
    editor().commands.selectAll()
    await w!.get('[aria-label="Lista con viñetas"]').trigger('click')
    expect(html()).toBe('<ul><li><p>a</p></li></ul>')
    await w!.get('[aria-label="Lista con viñetas"]').trigger('click')
    await w!.get('[aria-label="Cita"]').trigger('click')
    expect(html()).toBe('<blockquote><p>a</p></blockquote>')
  })

  it('deshabilitado no se puede editar', async () => {
    await montar({ disabled: true })
    expect(editor().isEditable).toBe(false)
  })
})

describe('EditorTexto — lo que no existe en su esquema', () => {
  it.each([
    ['<p>a</p><script>alert(1)</script>', 'script'],
    ['<p onclick="alert(1)">a</p>', 'onclick'],
    ['<iframe src="https://evil.test"></iframe><p>a</p>', 'iframe'],
    ['<p style="color:red">a</p>', 'style='],
    ['<table><tr><td>a</td></tr></table>', '<table'],
    ['<h1>grande</h1>', '<h1'],
    ['<img src="https://evil.test/x.png" alt="x">', 'evil.test'],
    ['<img src="data:image/png;base64,AAAA" alt="x">', 'data:'],
  ])('al pegar %s no queda %s', async (entrada, prohibido) => {
    await montar()
    editor().commands.setContent(entrada)
    expect(html()).not.toContain(prohibido)
  })

  it('los enlaces solo admiten https, http, mailto y tel', async () => {
    await montar()
    editor().commands.setContent('<p><a href="javascript:alert(1)">x</a> <a href="https://a.test">y</a></p>')
    expect(html()).not.toContain('javascript:')
    expect(html()).toContain('href="https://a.test"')
  })
})

describe('EditorTexto — enlace', () => {
  it('abre un formulario propio (no prompt), aplica el enlace y lo puede quitar', async () => {
    await montar({ modelValue: '<p>texto</p>' })
    editor().commands.selectAll()
    await w!.get('[aria-label="Enlace"]').trigger('click')

    const campo = w!.get('input[type="url"]')
    await campo.setValue('https://santamarta.gov.co')
    await w!.get('[data-accion="aplicar-enlace"]').trigger('click')
    expect(html()).toMatch(/<a [^>]*href="https:\/\/santamarta.gov.co"/)

    editor().commands.selectAll()
    await w!.get('[aria-label="Enlace"]').trigger('click')
    await w!.get('[data-accion="quitar-enlace"]').trigger('click')
    expect(html()).not.toContain('<a ')
  })

  it('rechaza un enlace con esquema peligroso y lo explica', async () => {
    await montar({ modelValue: '<p>texto</p>' })
    editor().commands.selectAll()
    await w!.get('[aria-label="Enlace"]').trigger('click')
    await w!.get('input[type="url"]').setValue('javascript:alert(1)')
    await w!.get('[data-accion="aplicar-enlace"]').trigger('click')
    expect(html()).not.toContain('javascript:')
    expect(w!.get('[data-error-enlace]').text()).toContain('https://')
  })
})

describe('EditorTexto — imagen', () => {
  it('sube la imagen con su alt y la inserta con la URL devuelta por el servidor', async () => {
    vi.mocked(subirImagenContenido).mockResolvedValue({ uuid: 'u1', url: '/api/v1/contenido/imagenes/u1', alt: 'Alcalde' })
    URL.createObjectURL = () => 'blob:x'; URL.revokeObjectURL = () => undefined
    await montar({ modelValue: '<p>texto</p>' })
    await w!.get('[aria-label="Imagen"]').trigger('click')

    const input = w!.get('input[type="file"]')
    const f = new File([new Uint8Array(5)], 'a.png', { type: 'image/png' })
    Object.defineProperty(input.element, 'files', { value: [f], configurable: true })
    await input.trigger('change')
    await w!.get('textarea, input[data-alt]').setValue('Alcalde')
    await w!.get('[data-accion="insertar-imagen"]').trigger('click')
    await flushPromises()

    expect(subirImagenContenido).toHaveBeenCalledWith(f, 'Alcalde')
    expect(html()).toContain('<img src="/api/v1/contenido/imagenes/u1" alt="Alcalde">')
  })

  it('no inserta si falta el alt', async () => {
    URL.createObjectURL = () => 'blob:x'; URL.revokeObjectURL = () => undefined
    vi.mocked(subirImagenContenido).mockClear()
    await montar()
    await w!.get('[aria-label="Imagen"]').trigger('click')
    const input = w!.get('input[type="file"]')
    Object.defineProperty(input.element, 'files', { value: [new File([new Uint8Array(5)], 'a.png', { type: 'image/png' })], configurable: true })
    await input.trigger('change')
    await w!.get('[data-accion="insertar-imagen"]').trigger('click')
    await flushPromises()

    expect(subirImagenContenido).not.toHaveBeenCalled()
    expect(w!.text()).toContain('descripción')
  })

  it('si el servidor rechaza la imagen, lo explica y no inserta nada', async () => {
    URL.createObjectURL = () => 'blob:x'; URL.revokeObjectURL = () => undefined
    vi.mocked(subirImagenContenido).mockRejectedValue(new Error('x'))
    await montar()
    await w!.get('[aria-label="Imagen"]').trigger('click')
    const input = w!.get('input[type="file"]')
    Object.defineProperty(input.element, 'files', { value: [new File([new Uint8Array(5)], 'a.png', { type: 'image/png' })], configurable: true })
    await input.trigger('change')
    await w!.get('textarea, input[data-alt]').setValue('x')
    await w!.get('[data-accion="insertar-imagen"]').trigger('click')
    await flushPromises()

    expect(html()).not.toContain('<img')
    expect(w!.text()).toContain('No se pudo subir la imagen')
  })
})
