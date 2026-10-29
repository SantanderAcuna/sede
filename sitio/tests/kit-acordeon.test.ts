/**
 * Acordeón del Kit UI (RF-B3-062, AC-28.1 y AC-28.8).
 *
 * El requisito enumera: `aria-expanded`, que **no se abra al recibir el foco** y
 * cuatro estados. Tres de ellos se prueban aquí con el DOM; el cuarto —el
 * «pasar el cursor»— es CSS y se comprueba en la tabla de estados de los
 * componentes (`kit-estados-css.test.ts`) y en navegador (T-29).
 */
import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'

import AcordeonGovco from '../app/components/govco/AcordeonGovco.vue'
import { violacionesAxe } from './ayudas/axe'

let envoltorio: ReturnType<typeof mount> | null = null

function montar(props: Record<string, unknown> = {}) {
  envoltorio = mount(AcordeonGovco, {
    attachTo: document.body,
    props: { titulo: 'Paso uno', ...props },
    slots: { default: '<p>Contenido del paso</p>' },
  })
  return envoltorio
}

afterEach(() => {
  envoltorio?.unmount()
  envoltorio = null
})

describe('estado normal (contraído)', () => {
  it('el botón anuncia aria-expanded="false" y apunta a su región', () => {
    const acordeon = montar()
    const boton = acordeon.get('button')
    expect(boton.attributes('aria-expanded')).toBe('false')
    const region = acordeon.get('[role="region"]')
    expect(boton.attributes('aria-controls')).toBe(region.attributes('id'))
    expect(region.attributes('aria-labelledby')).toBe(boton.attributes('id'))
  })

  it('el contenido sigue en el HTML pero oculto (rastreable, fuera del teclado)', () => {
    const acordeon = montar()
    const region = acordeon.get('[role="region"]')
    expect(region.attributes('hidden')).toBeDefined()
    expect(region.text()).toContain('Contenido del paso')
  })

  it('el botón lleva la clase collapsed del Kit, que dibuja el chevron', () => {
    expect(montar().get('button').classes()).toContain('collapsed')
  })
})

describe('estado activo (expandido)', () => {
  it('un clic lo abre: aria-expanded="true" y la región deja de estar oculta', async () => {
    const acordeon = montar()
    await acordeon.get('button').trigger('click')
    expect(acordeon.get('button').attributes('aria-expanded')).toBe('true')
    expect(acordeon.get('[role="region"]').attributes('hidden')).toBeUndefined()
    expect(acordeon.get('button').classes()).not.toContain('collapsed')
  })

  it('un segundo clic lo vuelve a cerrar', async () => {
    const acordeon = montar()
    await acordeon.get('button').trigger('click')
    await acordeon.get('button').trigger('click')
    expect(acordeon.get('button').attributes('aria-expanded')).toBe('false')
  })

  it('puede nacer abierto (v-model:abierto) y avisa de los cambios', async () => {
    const acordeon = montar({ abierto: true })
    expect(acordeon.get('button').attributes('aria-expanded')).toBe('true')
    await acordeon.get('button').trigger('click')
    expect(acordeon.emitted('update:abierto')?.[0]).toEqual([false])
  })
})

describe('estado de foco: recibir el foco NO abre el panel', () => {
  it('enfocar el botón, con foco o con tabulación, no cambia aria-expanded', async () => {
    const acordeon = montar()
    const boton = acordeon.get('button')
    ;(boton.element as HTMLButtonElement).focus()
    await boton.trigger('focus')
    await boton.trigger('focusin')
    expect(boton.attributes('aria-expanded')).toBe('false')
    expect(acordeon.get('[role="region"]').attributes('hidden')).toBeDefined()
  })

  it('el botón es un <button> nativo: Intro y Espacio lo activan sin código propio', () => {
    expect(montar().get('button').element.tagName).toBe('BUTTON')
    expect(montar().get('button').attributes('type')).toBe('button')
  })
})

describe('estado deshabilitado', () => {
  it('queda disabled, cerrado, y un clic no lo abre', async () => {
    const acordeon = montar({ deshabilitado: true })
    const boton = acordeon.get('button')
    expect(boton.attributes('disabled')).toBeDefined()
    await boton.trigger('click')
    expect(boton.attributes('aria-expanded')).toBe('false')
  })
})

describe('estructura', () => {
  it('el encabezado respeta el nivel pedido', () => {
    expect(montar({ nivel: 4 }).find('h4').exists()).toBe(true)
  })

  it('el número es decoración: no se anuncia dos veces', () => {
    const acordeon = montar({ numero: 3 })
    expect(acordeon.get('.icon-button-accordion-govco').attributes('aria-hidden')).toBe('true')
  })
})

describe('axe', () => {
  it('no hay violaciones contraído, expandido ni deshabilitado', async () => {
    const acordeon = montar({ numero: 1 })
    expect(await violacionesAxe(acordeon.element)).toEqual([])
    await acordeon.get('button').trigger('click')
    expect(await violacionesAxe(acordeon.element)).toEqual([])
    await acordeon.setProps({ deshabilitado: true })
    expect(await violacionesAxe(acordeon.element)).toEqual([])
  })
})
