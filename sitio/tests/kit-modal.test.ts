/**
 * Alerta modal del Kit UI (RF-B3-063, AC-28.1): `ModalAvisoSalida`.
 *
 * El requisito: se cierra con Esc y con clic fuera, y no ocupa toda la pantalla.
 * El cierre por Tab atrapado no se prueba aquí: depende de `offsetParent`, que
 * jsdom no calcula; lo cubre la puerta de navegador (T-29).
 */
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'

import ModalAvisoSalida from '../app/components/ModalAvisoSalida.vue'
import { violacionesAxe } from './ayudas/axe'

let montado: ReturnType<typeof mount> | null = null

async function montar(visible = true) {
  const origen = document.createElement('button')
  origen.textContent = 'Abrir'
  document.body.appendChild(origen)
  montado = mount(ModalAvisoSalida, {
    attachTo: document.body,
    props: {
      visible,
      destino: 'https://ejemplo.com/',
      nombreDestino: 'Ejemplo',
      entidadResponsable: 'Ejemplo SAS',
      origen,
    },
  })
  await montado.vm.$nextTick()
  await montado.vm.$nextTick()
  return { modal: montado, origen }
}

afterEach(() => {
  montado?.unmount()
  montado = null
  document.body.innerHTML = ''
})

describe('estado abierto', () => {
  it('es un diálogo modal con título y descripción', async () => {
    await montar()
    const d = document.querySelector('[role="dialog"]')
    expect(d?.getAttribute('aria-modal')).toBe('true')
    expect(document.getElementById(d?.getAttribute('aria-labelledby') ?? '')).not.toBeNull()
    expect(document.getElementById(d?.getAttribute('aria-describedby') ?? '')).not.toBeNull()
  })

  it('el foco entra en la acción segura (Permanecer en la sede)', async () => {
    await montar()
    expect(document.activeElement?.textContent).toContain('Permanecer en la sede')
  })

  it('cerrado, no pinta nada', async () => {
    await montar(false)
    expect(document.querySelector('[role="dialog"]')).toBeNull()
  })
})

describe('cierre', () => {
  it('con Esc emite cancelar', async () => {
    const { modal } = await montar()
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }))
    expect(modal.emitted('cancelar')).toHaveLength(1)
  })

  it('con un clic en el fondo emite cancelar', async () => {
    const { modal } = await montar()
    ;(document.querySelector('.fondo-modal') as HTMLElement).click()
    expect(modal.emitted('cancelar')).toHaveLength(1)
  })

  it('con «Permanecer» emite cancelar; con «Continuar», confirmar', async () => {
    const { modal } = await montar()
    ;(document.querySelector('.btn-cancelar') as HTMLElement).click()
    ;(document.querySelector('.btn-confirmar') as HTMLElement).click()
    expect(modal.emitted('cancelar')).toHaveLength(1)
    expect(modal.emitted('confirmar')).toHaveLength(1)
  })

  it('un clic dentro del cuadro no lo cierra', async () => {
    const { modal } = await montar()
    ;(document.querySelector('.modal-aviso-salida') as HTMLElement).click()
    expect(modal.emitted('cancelar')).toBeUndefined()
  })
})

describe('no es pantalla completa', () => {
  const fuente = readFileSync(resolve(__dirname, '../app/components/ModalAvisoSalida.vue'), 'utf8')
  const regla = /\.modal-aviso-salida\s*\{([^}]*)\}/.exec(fuente)?.[1] ?? ''

  it('el cuadro tiene ancho máximo y no fija el alto de la ventana', () => {
    expect(regla).toMatch(/max-width:\s*32rem/)
    expect(regla).not.toMatch(/(^|[^-])height:\s*100(vh|%)/)
    expect(regla).not.toMatch(/width:\s*100vw/)
  })
})

describe('axe', () => {
  it('sin violaciones abierto', async () => {
    await montar()
    expect(await violacionesAxe(document.body)).toEqual([])
  })
})
