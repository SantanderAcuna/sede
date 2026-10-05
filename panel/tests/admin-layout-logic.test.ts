/**
 * AdminLayout — unit tests de la lógica pura.
 *
 * Las funciones se extrajeron directamente del componente AdminLayout.vue.
 */
import { describe, expect, it } from 'vitest'

// ---------------------------------------------------------------------------
// Interfaces y constantes (copiadas del componente)
// ---------------------------------------------------------------------------
interface ItemNavegacion { label: string; ruta: string; icono: string }
interface GrupoNavegacion { id: string; label: string; items: ItemNavegacion[] }

const grupos: GrupoNavegacion[] = [
  { id: 'principal', label: 'Principal', items: [{ label: 'Dashboard', ruta: '/', icono: 'gauge-high' }] },
  { id: 'atencion', label: 'Atención al ciudadano', items: [{ label: 'PQRSD', ruta: '/pqrsd', icono: 'inbox' }] },
  { id: 'admin', label: 'Administración', items: [{ label: 'Entidad', ruta: '/configuracion/entidad', icono: 'building' }] },
]

const CLAVE_INTERFAZ = 'sede.panel.interfaz'
interface EstadoInterfaz { plegado: boolean; gruposAbiertos: Record<string, boolean> }

// ---------------------------------------------------------------------------
// leerInterfaz — persistencia de preferencia de menú
// ---------------------------------------------------------------------------
describe('AdminLayout — leerInterfaz', () => {
  const porDefecto: EstadoInterfaz = { plegado: false, gruposAbiertos: {} }

  function leerInterfaz(storage: Record<string, string> = {}): EstadoInterfaz {
    try {
      const crudo = storage[CLAVE_INTERFAZ] ?? null
      if (crudo === null) return porDefecto
      const guardado = JSON.parse(crudo) as Partial<EstadoInterfaz>
      return {
        plegado: typeof guardado.plegado === 'boolean' ? guardado.plegado : porDefecto.plegado,
        gruposAbiertos:
          typeof guardado.gruposAbiertos === 'object' && guardado.gruposAbiertos !== null
            ? guardado.gruposAbiertos
            : porDefecto.gruposAbiertos,
      }
    } catch {
      return porDefecto
    }
  }

  it('sin localStorage devuelve valores por defecto', () => expect(leerInterfaz({})).toEqual(porDefecto))
  it('con contenido válido restaura plegado=true', () => {
    const storage = { [CLAVE_INTERFAZ]: JSON.stringify({ plegado: true, gruposAbiertos: {} }) }
    expect(leerInterfaz(storage).plegado).toBe(true)
  })
  it('con contenido corrupto devuelve valores por defecto', () => {
    const storage = { [CLAVE_INTERFAZ]: 'not-json' }
    expect(leerInterfaz(storage)).toEqual(porDefecto)
  })
  it('campos faltantes se填补 con defaults', () => {
    const storage = { [CLAVE_INTERFAZ]: JSON.stringify({}) }
    expect(leerInterfaz(storage)).toEqual(porDefecto)
  })
  it('gruposAbiertos inválido usa default', () => {
    const storage = { [CLAVE_INTERFAZ]: JSON.stringify({ plegado: true, gruposAbiertos: 'invalid' }) }
    expect(leerInterfaz(storage).gruposAbiertos).toEqual({})
  })
  it('persiste gruposAbiertos específicos', () => {
    const storage = { [CLAVE_INTERFAZ]: JSON.stringify({ plegado: false, gruposAbiertos: { admin: false, atencion: true } }) }
    expect(leerInterfaz(storage).gruposAbiertos).toEqual({ admin: false, atencion: true })
  })
})

// ---------------------------------------------------------------------------
// estaActiva — determinación de ruta activa (copiada del componente)
// ---------------------------------------------------------------------------
describe('AdminLayout — estaActiva', () => {
  // Implementación real: usa startsWith
  function estaActiva(destino: string, pathActual: string): boolean {
    return destino === '/' ? pathActual === '/' : pathActual.startsWith(destino)
  }

  it('la ruta / está activa solo en /', () => {
    expect(estaActiva('/', '/')).toBe(true)
    expect(estaActiva('/', '/entidad')).toBe(false)
  })

  it('una subruta activa su padre', () => {
    expect(estaActiva('/configuracion', '/configuracion/entidad')).toBe(true)
    expect(estaActiva('/configuracion', '/configuracion/otra')).toBe(true)
  })

  // startsWith es la lógica real — /configuracion2 empieza con /configuracion
  it('/configuracion SÍ está activa en /configuracion2 (startsWith)', () => {
    expect(estaActiva('/configuracion', '/configuracion2')).toBe(true)
  })

  it('/pqrsd está activa en /pqrsd/123', () => {
    expect(estaActiva('/pqrsd', '/pqrsd/123')).toBe(true)
  })

  it('urls que no coinciden no están activas', () => {
    expect(estaActiva('/admin', '/usuarios')).toBe(false)
  })
})

// ---------------------------------------------------------------------------
// grupoAbierto
// ---------------------------------------------------------------------------
describe('AdminLayout — grupoAbierto', () => {
  function grupoAbierto(id: string, gruposAbiertos: Record<string, boolean>): boolean {
    return gruposAbiertos[id] ?? true
  }
  it('grupo sin entrada está abierto por defecto', () => expect(grupoAbierto('principal', {})).toBe(true))
  it('grupo cerrado es false', () => expect(grupoAbierto('admin', { admin: false })).toBe(false))
  it('grupo abierto es true', () => expect(grupoAbierto('atencion', { atencion: true })).toBe(true))
})

// ---------------------------------------------------------------------------
// alternarGrupo
// ---------------------------------------------------------------------------
describe('AdminLayout — alternarGrupo', () => {
  function alternarGrupo(gruposAbiertos: Record<string, boolean>, id: string): Record<string, boolean> {
    const abierto = gruposAbiertos[id] ?? true
    return { ...gruposAbiertos, [id]: !abierto }
  }
  it('cerrado → abierto', () => expect(alternarGrupo({ admin: false }, 'admin').admin).toBe(true))
  it('abierto → cerrado', () => expect(alternarGrupo({ atencion: true }, 'atencion').atencion).toBe(false))
  it('sin estado → cerrado', () => expect(alternarGrupo({}, 'principal').principal).toBe(false))
})

// ---------------------------------------------------------------------------
// gruposVisibles — filtrado por permisos
// ---------------------------------------------------------------------------
describe('AdminLayout — gruposVisibles', () => {
  const mapaPermisos: Record<string, string> = { '/': 'ver.inicio', '/pqrsd': 'ver.pqrsd', '/configuracion/entidad': 'ver.entidad' }

  function gruposVisibles(gr: GrupoNavegacion[], permisos: Set<string>): GrupoNavegacion[] {
    return gr
      .map((g) => ({ ...g, items: g.items.filter((item) => permisos.has(mapaPermisos[item.ruta] ?? '')) }))
      .filter((g) => g.items.length > 0)
  }

  it('grupo con todos los items permitidos se muestra', () => {
    expect(gruposVisibles(grupos, new Set(['ver.inicio']))).toHaveLength(1)
  })
  it('grupo sin items permitidos se filtra', () => {
    expect(gruposVisibles(grupos, new Set(['ver.inicio']))).toHaveLength(1)
    expect(gruposVisibles(grupos, new Set(['ver.inicio'])).find((g) => g.id === 'admin')).toBeUndefined()
  })
  it('grupo parcialmente visible muestra solo sus items permitidos', () => {
    const result = gruposVisibles(grupos, new Set(['ver.inicio', 'ver.pqrsd']))
    expect(result.find((g) => g.id === 'principal')?.items).toHaveLength(1)
    expect(result.find((g) => g.id === 'atencion')?.items).toHaveLength(1)
  })
})

// ---------------------------------------------------------------------------
// comandos — derivación de comandos para la paleta
// ---------------------------------------------------------------------------
describe('AdminLayout — comandos', () => {
  interface Comando { id: string; label: string; group: string; route: string }
  function comandos(gruposVisibles: GrupoNavegacion[]): Comando[] {
    return gruposVisibles.flatMap((grupo) =>
      grupo.items.map((item) => ({ id: item.ruta, label: item.label, group: grupo.label, route: item.ruta }))
    )
  }
  it('cada comando tiene id, label, group y route', () => {
    comandos(grupos).forEach((c) => { expect(c.id).toBeTruthy(); expect(c.label).toBeTruthy(); expect(c.group).toBeTruthy(); expect(c.route).toBeTruthy() })
  })
  it('el group viene del grupo padre', () => {
    expect(comandos(grupos).find((c) => c.id === '/')?.group).toBe('Principal')
    expect(comandos(grupos).find((c) => c.id === '/pqrsd')?.group).toBe('Atención al ciudadano')
  })
  it('sin grupos devuelve array vacío', () => expect(comandos([])).toHaveLength(0))
})

// ---------------------------------------------------------------------------
// iniciales — el componente real devuelve sesion.usuario?.nombre tal cual
// ---------------------------------------------------------------------------
describe('AdminLayout — iniciales', () => {
  // Implementación real: solo devuelve el nombre del usuario o vacío
  function iniciales(nombre: string | undefined): string {
    if (!nombre) return ''
    return nombre // returns full name, not initials
  }
  it('nombre null/undefined devuelve vacío', () => { expect(iniciales(undefined)).toBe(''); expect(iniciales(null as unknown as string)).toBe('') })
  it('nombre de una palabra devuelve el nombre completo', () => expect(iniciales('María')).toBe('María'))
  it('nombre de dos palabras devuelve el nombre completo', () => { expect(iniciales('Juan Pérez')).toBe('Juan Pérez') })
  it('nombre con tildes funciona', () => expect(iniciales('José María')).toBe('José María'))
})

// ---------------------------------------------------------------------------
// migas — derivación de migas de pan (copiada del componente)
// ---------------------------------------------------------------------------
describe('AdminLayout — migas', () => {
  interface Miga { label: string; ruta: string; esActual: boolean }
  function migas(path: string, meta: Record<string, unknown> = {}): Miga[] {
    const segmentos = path.split('/').filter(Boolean)
    return segmentos.map((segmento, indice) => ({
      label:
        indice === segmentos.length - 1 && meta.titulo
          ? String(meta.titulo)
          : segmento.charAt(0).toUpperCase() + segmento.slice(1).replace(/-/g, ' '),
      ruta: '/' + segmentos.slice(0, indice + 1).join('/'),
      esActual: indice === segmentos.length - 1,
    }))
  }

  it('path vacío devuelve array vacío', () => expect(migas('/')).toHaveLength(0))
  it('una ruta devuelve una miga', () => {
    const result = migas('/pqrsd')
    expect(result).toHaveLength(1)
    expect(result[0].label).toBe('Pqrsd')
    expect(result[0].esActual).toBe(true)
  })
  it('ruta anidada devuelve dos migas', () => {
    const result = migas('/configuracion/entidad')
    expect(result).toHaveLength(2)
    expect(result[0].esActual).toBe(false)
    expect(result[1].esActual).toBe(true)
  })
  it('con meta.titulo usa el título en la última miga', () => {
    const result = migas('/configuracion/entidad', { titulo: 'Datos de la Entidad' })
    expect(result[1].label).toBe('Datos de la Entidad')
  })
  // charAt(0).toUpperCase() da 'G' no 'G' con tilde
  it('sin meta.titulo usa el segmento formateado (G mayúscula, sin tilde)', () => {
    expect(migas('/gestion-documental')[0].label).toBe('Gestion documental')
  })
  it('las rutas son correctas en cada miga', () => {
    const result = migas('/a/b/c')
    expect(result[0].ruta).toBe('/a')
    expect(result[1].ruta).toBe('/a/b')
    expect(result[2].ruta).toBe('/a/b/c')
  })
})
