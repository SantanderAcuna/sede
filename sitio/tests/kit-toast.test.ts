/**
 * Toast del Kit UI (RF-B3-064, AC-28.1 y AC-28.2).
 *
 * Las tres variantes (informativa, positiva, negativa) y lo que hace que un
 * aviso temporal sea accesible: el rol correcto, tiempo suficiente y el reloj
 * detenido mientras el cursor o el foco están encima (WCAG 2.2.1).
 */
import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import ContenedorToastsGovco from '../app/components/govco/ContenedorToastsGovco.vue'
import ToastGovco from '../app/components/govco/ToastGovco.vue'
import {
  DURACION_POR_DEFECTO_MS,
  MAXIMO_VISIBLES,
  useToast,
} from '../app/composables/useToast'
import { violacionesAxe } from './ayudas/axe'

let montado: ReturnType<typeof mount> | null = null

function montar(props: Record<string, unknown> = {}) {
  montado = mount(ToastGovco, {
    attachTo: document.body,
    props: { titulo: 'Listo', mensaje: 'Guardamos su elección.', ...props },
  })
  return montado
}

beforeEach(() => {
  vi.useFakeTimers()
  useToast().limpiar()
})

afterEach(() => {
  montado?.unmount()
  montado = null
  vi.useRealTimers()
})

describe('las tres variantes', () => {
  it.each([
    ['informativa', 'info', 'status'],
    ['positiva', 'success', 'status'],
    ['negativa', 'error', 'alert'],
  ] as const)('%s usa la clase %s del Kit y el rol %s', (tipo, claseKit, rol) => {
    const toast = montar({ tipo })
    expect(toast.classes()).toContain('container-toast-govco')
    expect(toast.classes()).toContain(claseKit)
    expect(toast.attributes('role')).toBe(rol)
    expect(toast.get('.toast-header').classes()).toContain(`toast-header-${claseKit}-govco`)
  })

  it('muestra el título y el mensaje', () => {
    const toast = montar({ titulo: 'Atención', mensaje: 'Algo pasó.' })
    expect(toast.get('.toast-title-govco').text()).toBe('Atención')
    expect(toast.get('.toast-body-govco').text()).toBe('Algo pasó.')
  })
})

describe('duración suficiente y reloj detenido (AC-28.2)', () => {
  it('se cierra solo al cumplirse la duración, y no antes', () => {
    const toast = montar({ duracionMs: 5000 })
    vi.advanceTimersByTime(4999)
    expect(toast.emitted('cerrar')).toBeUndefined()
    vi.advanceTimersByTime(1)
    expect(toast.emitted('cerrar')).toHaveLength(1)
  })

  it('con duración 0 se queda hasta que se cierre', () => {
    const toast = montar({ duracionMs: 0 })
    vi.advanceTimersByTime(10 * 60 * 1000)
    expect(toast.emitted('cerrar')).toBeUndefined()
  })

  it('el cursor encima detiene el reloj y al salir sigue donde iba, no desde cero', async () => {
    const toast = montar({ duracionMs: 5000 })
    vi.advanceTimersByTime(3000)
    await toast.trigger('mouseenter')
    vi.advanceTimersByTime(60_000)
    expect(toast.emitted('cerrar')).toBeUndefined()
    await toast.trigger('mouseleave')
    vi.advanceTimersByTime(1999)
    expect(toast.emitted('cerrar')).toBeUndefined()
    vi.advanceTimersByTime(1)
    expect(toast.emitted('cerrar')).toHaveLength(1)
  })

  it('el foco dentro también lo detiene', async () => {
    const toast = montar({ duracionMs: 5000 })
    await toast.trigger('focusin')
    vi.advanceTimersByTime(60_000)
    expect(toast.emitted('cerrar')).toBeUndefined()
    await toast.trigger('focusout')
    vi.advanceTimersByTime(5000)
    expect(toast.emitted('cerrar')).toHaveLength(1)
  })

  it('con el cursor y el foco a la vez, sólo corre cuando se van los dos', async () => {
    const toast = montar({ duracionMs: 5000 })
    await toast.trigger('mouseenter')
    await toast.trigger('focusin')
    await toast.trigger('mouseleave')
    vi.advanceTimersByTime(60_000)
    expect(toast.emitted('cerrar')).toBeUndefined()
    await toast.trigger('focusout')
    vi.advanceTimersByTime(5000)
    expect(toast.emitted('cerrar')).toHaveLength(1)
  })

  it('no deja un reloj vivo al desmontarse', () => {
    const toast = montar({ duracionMs: 5000 })
    toast.unmount()
    montado = null
    expect(vi.getTimerCount()).toBe(0)
  })
})

describe('se puede cerrar', () => {
  it('con el botón, que tiene nombre accesible', async () => {
    const toast = montar()
    const boton = toast.get('button')
    expect(boton.attributes('aria-label')).toBe('Cerrar notificación')
    await boton.trigger('click')
    expect(toast.emitted('cerrar')).toHaveLength(1)
  })

  it('con Esc', async () => {
    const toast = montar()
    await toast.trigger('keydown', { key: 'Escape' })
    expect(toast.emitted('cerrar')).toHaveLength(1)
  })
})

describe('useToast', () => {
  it('la negativa no caduca; las demás duran lo establecido', () => {
    const { avisos, mostrar } = useToast()
    mostrar({ mensaje: 'a', tipo: 'negativa' })
    mostrar({ mensaje: 'b', tipo: 'positiva' })
    expect(avisos.value[0]?.duracionMs).toBe(0)
    expect(avisos.value[1]?.duracionMs).toBe(DURACION_POR_DEFECTO_MS)
  })

  it('pone un título por tipo si no se da', () => {
    const { avisos, mostrar } = useToast()
    mostrar({ mensaje: 'x', tipo: 'negativa' })
    expect(avisos.value[0]?.titulo).toBe('No se pudo completar')
  })

  it('guarda como máximo MAXIMO_VISIBLES: sale el más antiguo', () => {
    const { avisos, mostrar } = useToast()
    for (let i = 1; i <= MAXIMO_VISIBLES + 2; i += 1) mostrar({ mensaje: `m${i}` })
    expect(avisos.value).toHaveLength(MAXIMO_VISIBLES)
    expect(avisos.value[0]?.mensaje).toBe('m3')
  })

  it('cerrar quita sólo el pedido', () => {
    const { avisos, mostrar, cerrar } = useToast()
    const a = mostrar({ mensaje: 'a' })
    mostrar({ mensaje: 'b' })
    cerrar(a)
    expect(avisos.value.map((x) => x.mensaje)).toEqual(['b'])
  })
})

describe('el contenedor', () => {
  it('dibuja los avisos de useToast y quita el que se cierra', async () => {
    const { mostrar } = useToast()
    montado = mount(ContenedorToastsGovco, { attachTo: document.body })
    expect(montado.findAll('.toast')).toHaveLength(0)
    mostrar({ mensaje: 'Uno', tipo: 'positiva' })
    mostrar({ mensaje: 'Dos', tipo: 'negativa' })
    await montado.vm.$nextTick()
    expect(montado.findAll('.toast')).toHaveLength(2)
    await montado.findAll('.toast button')[0]?.trigger('click')
    expect(montado.findAll('.toast')).toHaveLength(1)
  })
})

describe('axe', () => {
  it('las tres variantes no tienen violaciones', async () => {
    // axe usa temporizadores propios: con los simulados se colgaría.
    vi.useRealTimers()
    for (const tipo of ['informativa', 'positiva', 'negativa'] as const) {
      const toast = montar({ tipo })
      expect(await violacionesAxe(toast.element)).toEqual([])
      toast.unmount()
      montado = null
    }
  })
})
