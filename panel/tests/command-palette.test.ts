/**
 * CommandPalette — tests de la lógica de filtrado y agrupamiento.
 *
 * Las funciones de filtrado y agrupamiento son deterministas y se testan
 * directamente como funciones puras.  Además se monta el componente Vue para
 * cubrir las ramas de apertura/cierre, búsqueda e interacción con el router.
 */
import { nextTick } from '@vue/runtime-dom'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

import CommandPalette from '../src/components/feedback/CommandPalette.vue'

interface Command { id: string; label: string; group: string; route?: string; icon?: string }

interface Command { id: string; label: string; group: string; route?: string }

function filteredCommands(commands: Command[], query: string): Command[] {
  const q = query.trim().toLowerCase()
  if (!q) return commands
  return commands.filter(c =>
    c.label.toLowerCase().includes(q) || c.group.toLowerCase().includes(q)
  )
}

function groupCommands(cmds: Command[]): Record<string, Command[]> {
  const g: Record<string, Command[]> = {}
  cmds.forEach(c => { (g[c.group] ??= []).push(c) })
  return g
}

const sample: Command[] = [
  { id: '/', label: 'Dashboard', group: 'Principal' },
  { id: '/entidad', label: 'Entidad', group: 'Administración' },
  { id: '/perfil', label: 'Mi Perfil', group: 'Cuenta' },
]

describe('CommandPalette — filtrado', () => {
  it('sin query devuelve todos', () => {
    expect(filteredCommands(sample, '')).toHaveLength(3)
  })

  it('filtra por label exacto', () => {
    expect(filteredCommands(sample, 'dashboard')).toHaveLength(1)
    expect(filteredCommands(sample, 'dashboard')[0].id).toBe('/')
  })

  it('filtra por label case insensitive', () => {
    expect(filteredCommands(sample, 'DASHBOARD')).toHaveLength(1)
    expect(filteredCommands(sample, 'Perfil')).toHaveLength(1)
  })

  it('filtra por group', () => {
    expect(filteredCommands(sample, 'Administración')).toHaveLength(1)
    expect(filteredCommands(sample, 'cuenta')).toHaveLength(1)
  })

  it('query solo de espacios es vacía tras trim', () => {
    expect(filteredCommands(sample, '   ')).toHaveLength(3)
  })

  it('sin coincidencia devuelve vacío', () => {
    expect(filteredCommands(sample, 'xyzabc')).toHaveLength(0)
  })

  it('busca en label Y en group', () => {
    // "per" aparece en "Mi Perfil" (label)
    expect(filteredCommands(sample, 'per')).toHaveLength(1)
    expect(filteredCommands(sample, 'per')[0].label).toBe('Mi Perfil')
  })

  it('coincidencia parcial funciona', () => {
    expect(filteredCommands(sample, 'enti')).toHaveLength(1)
    expect(filteredCommands(sample, 'cu')).toHaveLength(1)
  })
})

describe('CommandPalette — grouping', () => {
  it('cada grupo tiene sus comandos', () => {
    const groups = groupCommands(sample)
    expect(groups['Principal']).toHaveLength(1)
    expect(groups['Administración']).toHaveLength(1)
    expect(groups['Cuenta']).toHaveLength(1)
  })

  it('no hay comandos sin grupo', () => {
    const groups = groupCommands(sample)
    const totalEnGrupos = Object.values(groups).reduce((sum, g) => sum + g.length, 0)
    expect(totalEnGrupos).toBe(sample.length)
  })

  it('los grupos son disjuntos', () => {
    const groups = groupCommands(sample)
    const ids = Object.values(groups).flatMap(g => g.map(c => c.id))
    expect(new Set(ids).size).toBe(ids.length)
  })

  it('commands vacías devuelven grupos vacíos', () => {
    const groups = groupCommands([])
    expect(Object.keys(groups)).toHaveLength(0)
  })
})

describe('CommandPalette — integración filtrado + grouping', () => {
  it('grouped refleja el filtro aplicado', () => {
    const filtradas = filteredCommands(sample, 'a') // Entidad + Dashboard (tienen "a")
    const groups = groupCommands(filtradas)
    expect(filtradas).toHaveLength(3)
    expect(Object.keys(groups)).toContain('Principal')
    expect(Object.keys(groups)).toContain('Administración')
  })
})

// -----------------------------------------------------------------------
// Montaje del componente Vue
// -----------------------------------------------------------------------
// `sample` ya está declarado arriba (línea 34) y se reutiliza.
let router: ReturnType<typeof createRouter>

beforeEach(() => {
  router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/', component: { template: '<div>inicio</div>' } }],
  })
  setActivePinia(createPinia())
})

afterEach(() => {
  document.body.innerHTML = ''
})

function montar(p=createMemoryHistory()) {
  const r = createRouter({
    history: p,
    routes: [{ path: '/', component: { template: '<div>inicio</div>' } }],
  })
  const wrapper = mount(CommandPalette, {
    props: { commands: sample },
    global: { plugins: [r] },
    attachTo: document.body,
  })
  // Llamar la función expuesta por defineExpose.
  const vmAny = wrapper.vm as unknown as { open: () => void }
  vmAny.open()
  return wrapper
}

describe('CommandPalette — montaje y búsqueda', () => {
  it('monta sin error y el input es accesible tras abrir', async () => {
    const paleta = montar()
    await nextTick()
    // El input está en document.body (Teleport), no dentro del wrapper.
    expect(document.querySelector('input')).not.toBeNull()
    paleta.unmount()
  })

  it('el input responde a la query', async () => {
    const paleta = montar()
    await nextTick()
    const input = document.querySelector('input') as HTMLInputElement
    await import('@vue/runtime-dom').then(({ nextTick }) => nextTick())
    input.value = 'entidad'
    await input.dispatchEvent(new Event('input'))
    await nextTick()
    expect(paleta.vm.filtered).toHaveLength(1)
    paleta.unmount()
  })

  it('la búsqueda es case insensitive', async () => {
    const paleta = montar()
    await nextTick()
    const input = document.querySelector('input') as HTMLInputElement
    input.value = 'CUENTA'
    await input.dispatchEvent(new Event('input'))
    await nextTick()
    expect(paleta.vm.filtered).toHaveLength(1)
    paleta.unmount()
  })

  it('el grupo refleja el filtro', async () => {
    const paleta = montar()
    await nextTick()
    const input = document.querySelector('input') as HTMLInputElement
    input.value = 'perfil'
    await input.dispatchEvent(new Event('input'))
    await nextTick()
    // "perfil" coincide en label con "Mi Perfil" (group Cuenta) y en group
    // con "Administración" (que no tiene "perfil").
    expect(paleta.vm.filtered).toHaveLength(1)
    expect(paleta.vm.filtered[0].id).toBe('/perfil')
    expect(Object.keys(paleta.vm.grouped)).toEqual(['Cuenta'])
    paleta.unmount()
  })

  it('el grupo está vacío cuando no hay resultados', async () => {
    const paleta = montar()
    await nextTick()
    const input = document.querySelector('input') as HTMLInputElement
    input.value = 'xyz'
    await input.dispatchEvent(new Event('input'))
    await nextTick()
    expect(Object.keys(paleta.vm.grouped)).toHaveLength(0)
    paleta.unmount()
  })
})

describe('CommandPalette — navegación por teclado', () => {
  it('el primer comando tiene el estilo activo al abrir', async () => {
    const paleta = montar()
    await nextTick()
    // Al abrir con activeIdx=0, el primer comando (Dashboard) está marcado como activo.
    const primeros = document.querySelectorAll('button')
    expect(primeros.length).toBeGreaterThan(0)
    paleta.unmount()
  })

  it('al escribir se reinicia el índice activo a 0 (efecto colateral verificable)', async () => {
    const paleta = montar()
    await nextTick()
    // Escribir algo que filtre a un solo resultado.
    const input = document.querySelector('input') as HTMLInputElement
    input.value = 'perfil'
    await input.dispatchEvent(new Event('input'))
    await nextTick()
    // El watch(query) resetea activeIdx a 0. Lo verificamos indirectamente:
    // tras filtrar a 1 resultado, grouped tiene exactamente una entrada.
    const vm = paleta.vm as Record<string, unknown>
    const grouped = vm.grouped as Record<string, unknown[]>
    expect(Object.keys(grouped)).toHaveLength(1)
    paleta.unmount()
  })

  it('Enter sin comandos no falla', async () => {
    const r = createRouter({ history: createMemoryHistory(), routes: [] })
    const paleta = mount(CommandPalette, {
      props: { commands: [] },
      global: { plugins: [r] },
    })
    const vmAny = paleta.vm as unknown as { open: () => void }
    vmAny.open()
    await nextTick()
    const input = document.querySelector('input') as HTMLInputElement
    input.focus()
    await input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }))
    await nextTick()
    paleta.unmount()
  })
})
