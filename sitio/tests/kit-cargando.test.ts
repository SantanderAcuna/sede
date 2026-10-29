/**
 * Indicador de carga del Kit UI (RF-B3-070, CAG-29, AC-28.1 y AC-28.3).
 *
 * Con temporizadores simulados: a los diez segundos —ni uno antes— se añade el
 * aviso de espera prolongada.
 */
import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import CargandoGovco from '../app/components/govco/CargandoGovco.vue'
import { violacionesAxe } from './ayudas/axe'

let montado: ReturnType<typeof mount> | null = null

function montar(props: Record<string, unknown> = {}) {
  montado = mount(CargandoGovco, { attachTo: document.body, props })
  return montado
}

beforeEach(() => vi.useFakeTimers())

afterEach(() => {
  montado?.unmount()
  montado = null
  vi.useRealTimers()
})

describe('el indicador', () => {
  it('es una región de estado con el mensaje y el ícono decorativo', () => {
    const carga = montar({ mensaje: 'Cargando el catálogo…' })
    expect(carga.attributes('role')).toBe('status')
    expect(carga.text()).toContain('Cargando el catálogo…')
    expect(carga.get('.govco-spinner').attributes('aria-hidden')).toBe('true')
  })

  it('no trae aviso de espera al principio', () => {
    expect(montar().find('.aviso-carga-larga').exists()).toBe(false)
  })
})

describe('aviso a los 10 segundos (AC-28.3)', () => {
  it('a los 9,999 s todavía no; a los 10 s sí', async () => {
    const carga = montar()
    vi.advanceTimersByTime(9_999)
    await carga.vm.$nextTick()
    expect(carga.find('.aviso-carga-larga').exists()).toBe(false)
    vi.advanceTimersByTime(1)
    await carga.vm.$nextTick()
    expect(carga.find('.aviso-carga-larga').exists()).toBe(true)
    expect(carga.get('.aviso-carga-larga').text()).toContain('tardando más de lo habitual')
  })

  it('el umbral es configurable', async () => {
    const carga = montar({ segundosAntesDeAvisar: 3 })
    vi.advanceTimersByTime(3_000)
    await carga.vm.$nextTick()
    expect(carga.find('.aviso-carga-larga').exists()).toBe(true)
  })

  it('el aviso queda dentro de la región de estado, así que se anuncia', async () => {
    const carga = montar()
    vi.advanceTimersByTime(10_000)
    await carga.vm.$nextTick()
    expect(carga.get('[role="status"] .aviso-carga-larga').exists()).toBe(true)
  })

  it('si la carga termina antes (el padre lo desmonta), no queda reloj vivo', () => {
    const carga = montar()
    carga.unmount()
    montado = null
    expect(vi.getTimerCount()).toBe(0)
  })
})

describe('axe', () => {
  it('sin violaciones, con el aviso de espera y sin él', async () => {
    const carga = montar()
    vi.advanceTimersByTime(10_000)
    await carga.vm.$nextTick()
    // axe usa temporizadores propios: con los simulados se colgaría.
    vi.useRealTimers()
    expect(carga.find('.aviso-carga-larga').exists()).toBe(true)
    expect(await violacionesAxe(carga.element)).toEqual([])
    await carga.setProps({ mensaje: 'Otro' })
    expect(await violacionesAxe(carga.element)).toEqual([])
  })
})
