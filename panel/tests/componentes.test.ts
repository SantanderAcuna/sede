/**
 * Componentes base del panel.
 *
 * El panel tenía **3,92 % de cobertura**: las pruebas sólo miraban el almacén de
 * sesión, los permisos y los tokens, y ningún componente se había montado nunca.
 * Eso deja sin comprobar justo lo que la auditoría señaló en `D-16` —el foco de
 * los modales, el `aria-sort` de las tablas, los botones que declaran su tipo—,
 * porque son cosas que sólo se pueden medir montando el componente.
 *
 * Se prueban los componentes base y de aviso: son los que usan los módulos que
 * se construirán, y una prueba que se escribe cuando el consumidor todavía no
 * existe es una prueba que documenta el contrato del componente.
 */
import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'

import AccessibilityBar from '../src/components/base/AccessibilityBar.vue'
import BaseBadge from '../src/components/base/BaseBadge.vue'
import BaseButton from '../src/components/base/BaseButton.vue'
import BaseModal from '../src/components/base/BaseModal.vue'
import FormField from '../src/components/base/FormField.vue'
import KpiCard from '../src/components/base/KpiCard.vue'
import EmptyState from '../src/components/feedback/EmptyState.vue'

/*
 * Los modales se teletransportan al `body`, así que lo que deja una prueba lo
 * encuentra la siguiente. Se limpia entre pruebas: sin esto, «cerrado no dibuja
 * nada» encontraba el diálogo de la prueba anterior.
 */
afterEach(() => {
  document.body.innerHTML = ''
})

describe('BaseButton', () => {
  it('declara el tipo y lo respeta (CAG-13)', () => {
    const boton = mount(BaseButton, { props: { type: 'submit' }, slots: { default: 'Enviar' } })
    expect(boton.attributes('type')).toBe('submit')
    expect(boton.text()).toContain('Enviar')
  })

  it('por defecto es `button`, que es lo que evita envíos sin querer', () => {
    const boton = mount(BaseButton, { slots: { default: 'Hola' } })
    expect(boton.attributes('type')).toBe('button')
  })

  it('deshabilitado no emite el clic', async () => {
    const boton = mount(BaseButton, { props: { disabled: true }, slots: { default: 'No' } })
    await boton.trigger('click')
    expect(boton.emitted('click')).toBeUndefined()
    expect(boton.attributes('disabled')).toBeDefined()
  })

  it('nombra el botón cuando sólo lleva un icono', () => {
    const boton = mount(BaseButton, { props: { iconOnly: true, ariaLabel: 'Cerrar' } })
    expect(boton.attributes('aria-label')).toBe('Cerrar')
  })

  it('mientras carga avisa y no deja pulsar dos veces', () => {
    const boton = mount(BaseButton, { props: { loading: true }, slots: { default: 'Guardar' } })
    expect(boton.attributes('aria-busy')).toBe('true')
    expect(boton.attributes('disabled')).toBeDefined()
  })
})

describe('BaseBadge', () => {
  it('dibuja su texto y su variante', () => {
    const insignia = mount(BaseBadge, { props: { variant: 'success' }, slots: { default: 'Vigente' } })
    expect(insignia.text()).toContain('Vigente')
    expect(insignia.classes().join(' ')).toMatch(/emerald|green|success/)
  })
})

describe('KpiCard', () => {
  it('muestra la etiqueta y el valor', () => {
    const tarjeta = mount(KpiCard, { props: { label: 'PQRSD abiertas', value: 0 } })
    expect(tarjeta.text()).toContain('PQRSD abiertas')
    expect(tarjeta.text()).toContain('0')
  })

  it('sin datos no inventa una tendencia', () => {
    const tarjeta = mount(KpiCard, { props: { label: 'Radicados', value: 0 } })
    expect(tarjeta.text()).not.toMatch(/[+-]\d+\s*%/)
  })
})

describe('FormField', () => {
  it('asocia la etiqueta al campo con `for`/`id` (CAG-16)', () => {
    const campo = mount(FormField, { props: { modelValue: '', label: 'Correo electrónico' } })
    const etiqueta = campo.find('label')
    const control = campo.find('input')
    expect(etiqueta.attributes('for')).toBeTruthy()
    expect(etiqueta.attributes('for')).toBe(control.attributes('id'))
  })

  it('el error se anuncia y el campo queda marcado como inválido', () => {
    const campo = mount(FormField, {
      props: { modelValue: '', label: 'Correo', error: 'El correo no es válido' },
    })
    expect(campo.text()).toContain('El correo no es válido')
    const control = campo.find('input')
    expect(control.attributes('aria-invalid')).toBe('true')
    // El error tiene que estar descrito por el campo, no sólo cerca de él.
    const descrito = control.attributes('aria-describedby') ?? ''
    expect(descrito.length).toBeGreaterThan(0)
    expect(campo.find(`#${descrito.split(' ')[0]}`).exists()).toBe(true)
  })
})

describe('EmptyState', () => {
  it('dice que no hay nada en lugar de dejar el hueco en blanco', () => {
    const vacio = mount(EmptyState, { props: { title: 'Todavía no hay solicitudes' } })
    expect(vacio.text()).toContain('Todavía no hay solicitudes')
  })
})

describe('BaseModal', () => {
  it('se anuncia como diálogo, lleva nombre y se cierra con Escape (CAG-21)', async () => {
    const modal = mount(BaseModal, {
      props: { modelValue: true, title: 'Confirmar' },
      slots: { default: '¿Seguro?' },
      attachTo: document.body,
    })
    await modal.vm.$nextTick()

    // El diálogo se teletransporta al `body`, así que se busca en el documento y
    // no dentro del envoltorio: es donde acaba de verdad.
    const dialogo = document.querySelector('[role="dialog"]')
    expect(dialogo).not.toBeNull()
    expect(dialogo?.getAttribute('aria-modal')).toBe('true')
    // Un `role="dialog"` sin nombre accesible no se anuncia.
    const etiquetado = dialogo?.getAttribute('aria-labelledby') ?? dialogo?.getAttribute('aria-label')
    expect(etiquetado).toBeTruthy()

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))
    await modal.vm.$nextTick()

    expect(modal.emitted('update:modelValue')?.[0]).toEqual([false])
    modal.unmount()
  })

  it('cerrado no dibuja nada ni deja escuchas colgados', async () => {
    const modal = mount(BaseModal, { props: { modelValue: false }, attachTo: document.body })
    await modal.vm.$nextTick()
    expect(document.querySelector('[role="dialog"]')).toBeNull()

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))
    await modal.vm.$nextTick()
    expect(modal.emitted('update:modelValue')).toBeUndefined()
    modal.unmount()
  })
})

describe('AccessibilityBar', () => {
  it('el disparador declara su estado y a qué elemento controla', () => {
    const barra = mount(AccessibilityBar, { attachTo: document.body })
    const disparador = barra.find('button[aria-expanded]')
    expect(disparador.exists()).toBe(true)
    expect(disparador.attributes('aria-expanded')).toBe('false')
    expect(disparador.attributes('aria-controls')).toBeTruthy()
    barra.unmount()
  })

  it('al abrirse lleva los controles del Kit, todos con nombre accesible', async () => {
    const barra = mount(AccessibilityBar, { attachTo: document.body })
    await barra.find('button[aria-expanded]').trigger('click')
    await barra.vm.$nextTick()

    const botones = barra.findAll('button')
    expect(botones.length).toBeGreaterThanOrEqual(4)
    for (const boton of botones) {
      const nombre = boton.attributes('aria-label') ?? boton.text()
      expect(nombre.trim().length).toBeGreaterThan(0)
    }
    // El disparador pasa a declarar que está abierto.
    expect(barra.find('button[aria-expanded]').attributes('aria-expanded')).toBe('true')
    barra.unmount()
  })
})
