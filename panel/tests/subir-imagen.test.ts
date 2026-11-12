/**
 * SubirImagen — elegir una imagen sin el botón nativo en inglés, validando en el
 * cliente tipo y tamaño (el servidor vuelve a validar siempre) y exigiendo el alt.
 */
import { mount } from '@vue/test-utils'
import { describe, expect, it, beforeAll } from 'vitest'

import SubirImagen from '../src/components/base/SubirImagen.vue'

beforeAll(() => {
  URL.createObjectURL = () => 'blob:prueba'
  URL.revokeObjectURL = () => undefined
})

const archivo = (nombre: string, tipo: string, bytes = 10) => new File([new Uint8Array(bytes)], nombre, { type: tipo })

async function elegir(w: ReturnType<typeof mount>, f: File) {
  const input = w.get('input[type="file"]')
  Object.defineProperty(input.element, 'files', { value: [f], configurable: true })
  await input.trigger('change')
}

describe('SubirImagen', () => {
  it('muestra un botón en español y no el texto nativo del navegador', () => {
    const w = mount(SubirImagen)
    expect(w.text()).toContain('Elegir una imagen')
    expect(w.get('input[type="file"]').classes()).toContain('sr-only')
    expect(w.get('input[type="file"]').attributes('accept')).toBe('image/png,image/jpeg,image/webp')
  })

  it('acepta una imagen válida: la anuncia y muestra su vista previa', async () => {
    const w = mount(SubirImagen)
    const f = archivo('foto.png', 'image/png')
    await elegir(w, f)

    expect(w.emitted('seleccionada')?.[0]).toEqual([f])
    expect(w.find('img').attributes('src')).toBe('blob:prueba')
    expect(w.text()).toContain('foto.png')
    expect(w.text()).toContain('Cambiar la imagen')
  })

  it.each([
    ['archivo.svg', 'image/svg+xml', 'Solo se aceptan imágenes PNG, JPG o WebP.'],
    ['doc.pdf', 'application/pdf', 'Solo se aceptan imágenes PNG, JPG o WebP.'],
    ['animada.gif', 'image/gif', 'Solo se aceptan imágenes PNG, JPG o WebP.'],
  ])('rechaza %s con un mensaje claro y sin emitir', async (nombre, tipo, mensaje) => {
    const w = mount(SubirImagen)
    await elegir(w, archivo(nombre, tipo))

    expect(w.emitted('seleccionada')).toBeUndefined()
    expect(w.get('[role="alert"]').text()).toBe(mensaje)
  })

  it('rechaza una imagen de más de 5 MB', async () => {
    const w = mount(SubirImagen)
    await elegir(w, archivo('grande.png', 'image/png', 5 * 1024 * 1024 + 1))

    expect(w.emitted('seleccionada')).toBeUndefined()
    expect(w.get('[role="alert"]').text()).toContain('5 MB')
  })

  it('con alt requerido muestra el campo y lo vincula con v-model', async () => {
    const w = mount(SubirImagen, { props: { pedirAlt: true, alt: '' } })
    expect(w.find('textarea, input[type="text"]').exists()).toBe(true)
    await w.get('textarea, input[type="text"]').setValue('Alcalde')
    expect(w.emitted('update:alt')?.[0]).toEqual(['Alcalde'])
  })

  it('muestra la imagen actual cuando ya hay una', () => {
    const w = mount(SubirImagen, { props: { actual: { url: '/x.png', alt: 'Antes' } } })
    expect(w.get('img').attributes('src')).toBe('/x.png')
    expect(w.text()).toContain('Cambiar la imagen')
  })

  it('un error externo (del servidor) se anuncia', () => {
    const w = mount(SubirImagen, { props: { error: 'La imagen es demasiado grande.' } })
    expect(w.get('[role="alert"]').text()).toBe('La imagen es demasiado grande.')
  })
})
