/**
 * Paginación del Kit UI (RF-B3-075, AC-28.1 y AC-28.4).
 *
 * `aria-current="page"`, elipsis decorativa, extremos apagados y el área activa
 * de 44 × 44 px. jsdom no mide píxeles, así que el tamaño se comprueba de dos
 * maneras: la regla CSS que lo fija (aquí) y el navegador real (`diseno.mjs`).
 */
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'

import PaginacionGovco from '../app/components/govco/PaginacionGovco.vue'
import { violacionesAxe } from './ayudas/axe'

let montado: ReturnType<typeof mount> | null = null

function montar(props: Record<string, unknown>) {
  montado = mount(PaginacionGovco, { attachTo: document.body, props })
  return montado
}

afterEach(() => {
  montado?.unmount()
  montado = null
})

function numeros(p: ReturnType<typeof mount>): string[] {
  return p.findAll('li').map((li) => li.text())
}

describe('aria-current', () => {
  it('está en la página actual y sólo en ella', () => {
    const p = montar({ paginaActual: 3, totalPaginas: 5 })
    const actuales = p.findAll('[aria-current="page"]')
    expect(actuales).toHaveLength(1)
    expect(actuales[0]?.text()).toBe('3')
  })

  it('la página activa lleva la clase active del contenedor', () => {
    const p = montar({ paginaActual: 2, totalPaginas: 4 })
    expect(p.findAll('li.active')).toHaveLength(1)
  })
})

describe('qué páginas se dibujan', () => {
  it('con pocas, todas', () => {
    expect(numeros(montar({ paginaActual: 1, totalPaginas: 4 }))).toEqual([
      'Anterior', '1', '2', '3', '4', 'Siguiente',
    ])
  })

  it('con muchas, la primera, la última y la vecindad, con elipsis', () => {
    expect(numeros(montar({ paginaActual: 10, totalPaginas: 20 }))).toEqual([
      'Anterior', '1', '…', '9', '10', '11', '…', '20', 'Siguiente',
    ])
  })

  it('cerca del principio no hay elipsis inicial', () => {
    expect(numeros(montar({ paginaActual: 2, totalPaginas: 20 }))).toEqual([
      'Anterior', '1', '2', '3', '…', '20', 'Siguiente',
    ])
  })

  it('la elipsis es decorativa: no es un control ni se lee', () => {
    const p = montar({ paginaActual: 10, totalPaginas: 20 })
    const puntos = p.findAll('span.page-link')
    expect(puntos).toHaveLength(2)
    for (const punto of puntos) expect(punto.attributes('aria-hidden')).toBe('true')
  })

  it('con una sola página se dibuja igual, con los dos extremos apagados', () => {
    const p = montar({ paginaActual: 1, totalPaginas: 1 })
    expect(numeros(p)).toEqual(['Anterior', '1', 'Siguiente'])
    const botones = p.findAll('button')
    expect(botones[0]?.attributes('disabled')).toBeDefined()
    expect(botones[2]?.attributes('disabled')).toBeDefined()
  })

  it('una página fuera de rango se lleva al rango', () => {
    const p = montar({ paginaActual: 99, totalPaginas: 3 })
    expect(p.get('[aria-current="page"]').text()).toBe('3')
  })
})

describe('los extremos y los clics', () => {
  it('«Anterior» no existe en la primera ni «Siguiente» en la última', () => {
    const primera = montar({ paginaActual: 1, totalPaginas: 3 })
    expect(primera.findAll('button')[0]?.attributes('disabled')).toBeDefined()
    primera.unmount()
    const ultima = montar({ paginaActual: 3, totalPaginas: 3 })
    const botones = ultima.findAll('button')
    expect(botones[botones.length - 1]?.attributes('disabled')).toBeDefined()
  })

  it('pulsar una página, Anterior o Siguiente avisa con el número destino', async () => {
    const p = montar({ paginaActual: 2, totalPaginas: 5 })
    const botones = p.findAll('button')
    await botones[0]?.trigger('click')
    await botones[botones.length - 1]?.trigger('click')
    await p.findAll('button').find((b) => b.text() === '5')?.trigger('click')
    expect(p.emitted('cambiar')).toEqual([[1], [3], [5]])
  })

  it('pulsar la página en que ya se está no emite nada', async () => {
    const p = montar({ paginaActual: 2, totalPaginas: 5 })
    await p.findAll('button').find((b) => b.text() === '2')?.trigger('click')
    expect(p.emitted('cambiar')).toBeUndefined()
  })

  it('extremosApagados apaga Anterior y Siguiente aunque haya páginas (carga o fallo)', () => {
    const p = montar({ paginaActual: 3, totalPaginas: 5, extremosApagados: true })
    const botones = p.findAll('button')
    expect(botones[0]?.attributes('disabled')).toBeDefined()
    expect(botones[botones.length - 1]?.attributes('disabled')).toBeDefined()
  })
})

describe('área activa de 44 × 44 px (AC-28.4)', () => {
  const css = readFileSync(resolve(__dirname, '../app/assets/css/sitio.css'), 'utf8')
  const regla = /\.paginacion-govco \.page-link\s*\{([^}]*)\}/.exec(css)?.[1] ?? ''

  it('la regla de .page-link fija min-width y min-height de 2.75rem (= 44 px a 16 px)', () => {
    expect(regla).toMatch(/min-width:\s*2\.75rem/)
    expect(regla).toMatch(/min-height:\s*2\.75rem/)
  })

  it('no está acotada a una media query: vale en móvil y en escritorio', () => {
    const sinComentarios = css.replace(/\/\*[\s\S]*?\*\//g, '')
    const posicion = sinComentarios.indexOf('.paginacion-govco .page-link {')
    expect(posicion).toBeGreaterThan(-1)
    const antes = sinComentarios.slice(0, posicion)
    const profundidad = (antes.match(/\{/g) ?? []).length - (antes.match(/\}/g) ?? []).length
    expect(profundidad).toBe(0)
  })
})

describe('axe', () => {
  it('sin violaciones en la primera, en una intermedia y con elipsis', async () => {
    for (const [paginaActual, totalPaginas] of [[1, 1], [1, 5], [10, 20]] as const) {
      const p = montar({ paginaActual, totalPaginas })
      expect(await violacionesAxe(p.element)).toEqual([])
      p.unmount()
      montado = null
    }
  })

  it('la navegación tiene nombre accesible', () => {
    expect(montar({ paginaActual: 1, totalPaginas: 2 }).get('nav').attributes('aria-label')).toBe(
      'Paginación de los resultados',
    )
  })
})

describe('modo enlaces (páginas de resultados que se comparten por su URL)', () => {
  const enlace = (n: number) => `/buscar?q=salud&page=${n}`

  it('los controles son enlaces con la dirección de cada página, y no botones', () => {
    const p = montar({ paginaActual: 2, totalPaginas: 4, enlace })
    expect(p.findAll('button')).toHaveLength(0)
    const href = (texto: string) => p.findAll('a').find((a) => a.text() === texto)?.attributes('href')
    expect(href('Anterior')).toBe('/buscar?q=salud&page=1')
    expect(href('1')).toBe('/buscar?q=salud&page=1')
    expect(href('3')).toBe('/buscar?q=salud&page=3')
    expect(href('Siguiente')).toBe('/buscar?q=salud&page=3')
  })

  it('la página actual lleva aria-current="page" y sólo ella', () => {
    const p = montar({ paginaActual: 3, totalPaginas: 5, enlace })
    const actuales = p.findAll('[aria-current]')
    expect(actuales).toHaveLength(1)
    expect(actuales[0]?.text()).toBe('3')
    expect(actuales[0]?.attributes('aria-current')).toBe('page')
  })

  it('con JavaScript el clic se intercepta y se emite `cambiar`, sin seguir el enlace', async () => {
    const p = montar({ paginaActual: 1, totalPaginas: 3, enlace })
    const evento = new MouseEvent('click', { cancelable: true, bubbles: true })
    p.findAll('a').find((a) => a.text() === '2')?.element.dispatchEvent(evento)
    expect(evento.defaultPrevented).toBe(true)
    expect(p.emitted('cambiar')).toEqual([[2]])
    await p.findAll('a').find((a) => a.text() === 'Siguiente')?.trigger('click')
    expect(p.emitted('cambiar')).toEqual([[2], [2]])
  })

  it('«Anterior» en la primera y «Siguiente» en la última no son enlaces: quedan apagados', async () => {
    const primera = montar({ paginaActual: 1, totalPaginas: 3, enlace })
    const anterior = primera.findAll('li')[0]
    expect(anterior?.classes()).toContain('disabled')
    expect(anterior?.find('a').exists()).toBe(false)
    expect(anterior?.get('span').attributes('aria-disabled')).toBe('true')
    primera.unmount()

    const ultima = montar({ paginaActual: 3, totalPaginas: 3, enlace })
    const siguiente = ultima.findAll('li').at(-1)
    expect(siguiente?.find('a').exists()).toBe(false)
    expect(siguiente?.get('span').attributes('aria-disabled')).toBe('true')
    await siguiente?.get('span').trigger('click')
    expect(ultima.emitted('cambiar')).toBeUndefined()
  })

  it('la elipsis sigue siendo decorativa y no es un enlace', () => {
    const p = montar({ paginaActual: 5, totalPaginas: 20, enlace })
    const elipsis = p.findAll('li').filter((li) => li.text() === '…')
    expect(elipsis.length).toBeGreaterThan(0)
    for (const li of elipsis) {
      expect(li.find('a').exists()).toBe(false)
      expect(li.get('.page-link').attributes('aria-hidden')).toBe('true')
    }
  })

  it('sin axe de por medio: no hay violaciones en modo enlaces', async () => {
    const p = montar({ paginaActual: 2, totalPaginas: 5, enlace })
    expect(await violacionesAxe(p.element)).toEqual([])
  })
})
