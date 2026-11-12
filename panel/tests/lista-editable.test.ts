/**
 * ListaEditable: filas que se agregan, editan, ordenan y quitan, con sus controles etiquetados.
 */
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import ListaEditable, { type CampoLista } from '../src/components/base/ListaEditable.vue'

const campos: CampoLista[] = [
  { clave: 'nombre', etiqueta: 'Nombre', tipo: 'texto', requerido: true },
  { clave: 'cantidad', etiqueta: 'Cantidad', tipo: 'numero' },
  { clave: 'tipo', etiqueta: 'Tipo', tipo: 'select', opciones: [{ valor: 'a', etiqueta: 'Opción A' }] },
  { clave: 'activo', etiqueta: 'Activo', tipo: 'casilla', porDefecto: true },
  { clave: 'nota', etiqueta: 'Nota', tipo: 'textarea', avanzado: true },
]

function montar(props: Record<string, unknown> = {}) {
  return mount(ListaEditable, {
    props: { modelValue: [], campos, etiquetaItem: 'Requisito', ...props },
    global: { stubs: { FaIcon: true } },
  })
}

describe('ListaEditable', () => {
  it('vacía dice que no hay nada y ofrece agregar', () => {
    const w = montar({ plural: 'requisitos' })
    expect(w.text()).toContain('Aún no hay requisitos.')
    expect(w.find('[data-agregar]').text()).toContain('Agregar requisito')
  })

  it('agregar emite la lista con una fila nueva', async () => {
    const w = montar({ nuevo: () => ({ activo: true }) })
    await w.find('[data-agregar]').trigger('click')
    expect(w.emitted('update:modelValue')![0]![0]).toEqual([{ activo: true }])
  })

  it('cada fila es un grupo con nombre y cada campo tiene su etiqueta', () => {
    const w = montar({ modelValue: [{ nombre: 'Cédula' }, {}] })
    const grupos = w.findAll('fieldset > legend')
    expect(grupos[0]!.text()).toBe('Requisito 1 · Cédula'.replace(' · Cédula', ''))
    const etiquetas = w.findAll('label').map((l) => l.text())
    expect(etiquetas).toContain('Nombre*')
    const input = w.find('input[type=text]')
    const id = input.attributes('id')!
    expect(w.find(`label[for="${id}"]`).exists()).toBe(true)
  })

  it('escribir emite solo el campo cambiado y conserva el resto de la fila', async () => {
    const w = montar({ modelValue: [{ nombre: 'A', pago: [{ valor: '1' }] }] })
    await w.find('input[type=text]').setValue('B')
    expect(w.emitted('update:modelValue')![0]![0]).toEqual([{ nombre: 'B', pago: [{ valor: '1' }] }])
  })

  it('un número vacío quita la clave y uno escrito viaja como número', async () => {
    const w = montar({ modelValue: [{ nombre: 'A', cantidad: 2 }] })
    const n = w.find('input[type=number]')
    await n.setValue('')
    expect(w.emitted('update:modelValue')![0]![0]).toEqual([{ nombre: 'A' }])
    await n.setValue('5')
    expect(w.emitted('update:modelValue')![1]![0]).toEqual([{ nombre: 'A', cantidad: 5 }])
  })

  it('la casilla toma su valor por defecto cuando la fila no lo trae', () => {
    const w = montar({ modelValue: [{}] })
    expect((w.find('input[type=checkbox]').element as HTMLInputElement).checked).toBe(true)
  })

  it('quitar emite la lista sin esa fila y el botón dice cuál', async () => {
    const w = montar({ modelValue: [{ nombre: 'A' }, { nombre: 'B' }] })
    const botones = w.findAll('[data-quitar]')
    expect(botones[1]!.attributes('aria-label')).toBe('Quitar Requisito 2')
    await botones[0]!.trigger('click')
    expect(w.emitted('update:modelValue')![0]![0]).toEqual([{ nombre: 'B' }])
  })

  it('subir y bajar reordenan; los extremos se deshabilitan', async () => {
    const w = montar({ modelValue: [{ nombre: 'A' }, { nombre: 'B' }] })
    expect(w.findAll('[data-mover=subir]')[0]!.attributes('disabled')).toBeDefined()
    expect(w.findAll('[data-mover=bajar]')[1]!.attributes('disabled')).toBeDefined()
    await w.findAll('[data-mover=bajar]')[0]!.trigger('click')
    expect(w.emitted('update:modelValue')![0]![0]).toEqual([{ nombre: 'B' }, { nombre: 'A' }])
  })

  it('muestra el error del servidor junto al campo y lo declara en aria', () => {
    const w = montar({ modelValue: [{ nombre: '' }], prefijoError: 'requisitos', errores: { 'requisitos.0.nombre': 'El nombre es obligatorio.' } })
    const input = w.find('input[type=text]')
    expect(input.attributes('aria-invalid')).toBe('true')
    expect(w.find(`#${input.attributes('aria-describedby')}`).text()).toBe('El nombre es obligatorio.')
  })

  it('los campos avanzados van dentro de «Más datos (opcional)»', () => {
    const w = montar({ modelValue: [{}] })
    expect(w.find('details summary').text()).toBe('Más datos (opcional)')
    expect(w.find('details textarea').exists()).toBe(true)
  })

  it('al llegar al máximo ya no ofrece agregar', () => {
    const w = montar({ modelValue: [{}, {}], max: 2 })
    expect(w.find('[data-agregar]').exists()).toBe(false)
    expect(w.text()).toContain('máximo de 2')
  })

  it('una lista anidada edita las filas de dentro', async () => {
    const anidados: CampoLista[] = [
      { clave: 'titulo', etiqueta: 'Título', tipo: 'texto' },
      { clave: 'requisitos', etiqueta: 'Requisitos', tipo: 'lista', etiquetaItem: 'Requisito', campos: [{ clave: 'descripcion', etiqueta: 'Descripción', tipo: 'texto' }] },
    ]
    const w = mount(ListaEditable, {
      props: { modelValue: [{ titulo: 'Paso', requisitos: [{ descripcion: 'x' }] }], campos: anidados, etiquetaItem: 'Momento' },
      global: { stubs: { FaIcon: true } },
    })
    const dentro = w.findAll('input[type=text]')[1]!
    await dentro.setValue('y')
    expect(w.emitted('update:modelValue')![0]![0]).toEqual([{ titulo: 'Paso', requisitos: [{ descripcion: 'y' }] }])
  })
})
