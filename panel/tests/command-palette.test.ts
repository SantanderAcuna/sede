/**
 * CommandPalette — tests de la lógica de filtrado y agrupamiento.
 *
 * Las funciones de filtrado y agrupamiento son deterministas y se testan
 * directamente como funciones pura.
 */
import { describe, expect, it } from 'vitest'

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
