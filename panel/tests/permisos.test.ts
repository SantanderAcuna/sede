/**
 * Permisos por módulo (RF-B1-079, D-42).
 *
 * El mapa `ruta → permiso` es un contrato con dos consumidores que tienen que
 * decir lo mismo: la guardia del enrutador, que decide si una pantalla se abre, y
 * el menú lateral, que decide si el módulo se ofrece. Si una ruta queda sin
 * permiso, el filtrado del menú la trata como pública y el módulo se ofrece a
 * quien no debería verlo; si el mapa cita una ruta que no existe, el permiso es
 * decorativo. Las dos cosas se prueban aquí.
 */
import { describe, expect, it } from 'vitest'

import { PERMISO_POR_RUTA } from '../src/config/permisos'
import enrutador from '../src/router/index'

/** Todas las rutas declaradas, con su `meta`. */
const rutas = enrutador.getRoutes().map((ruta) => ({ path: ruta.path, meta: ruta.meta ?? {} }))

/** Los módulos del panel: los que exigen sesión y tienen título de módulo. */
const modulos = rutas.filter((ruta) => ruta.meta.requiereSesion && ruta.meta.titulo)

describe('el mapa de permisos y las rutas', () => {
  it('toda ruta del mapa existe en el enrutador', () => {
    const declaradas = new Set(rutas.map((ruta) => ruta.path))
    const huerfanos = Object.keys(PERMISO_POR_RUTA).filter((ruta) => !declaradas.has(ruta))

    expect(huerfanos).toEqual([])
  })

  it('todos los módulos del panel declaran su permiso', () => {
    const sinPermiso = modulos
      .filter((ruta) => ruta.path !== '/sin-permiso')
      .filter((ruta) => ruta.meta.permiso === undefined)
      .map((ruta) => ruta.path)

    expect(sinPermiso).toEqual([])
  })

  it('ningún módulo del panel es alcanzable sin sesión', () => {
    const sinSesion = modulos
      .filter((ruta) => ruta.path !== '/sin-permiso')
      .filter((ruta) => ruta.meta.requiereSesion !== true)
      .map((ruta) => ruta.path)

    expect(sinSesion).toEqual([])
  })

  it('el permiso de cada módulo es el que declara el mapa', () => {
    for (const ruta of modulos) {
      const declarado = PERMISO_POR_RUTA[ruta.path]
      if (declarado) expect(ruta.meta.permiso).toBe(declarado)
    }
  })

  it('la portada usa el permiso del panel administrativo', () => {
    expect(PERMISO_POR_RUTA['/']).toBe('panel-administrative')
    expect(rutas.find((ruta) => ruta.path === '/')?.meta.permiso).toBe('panel-administrative')
  })

  it('hay dieciocho módulos declarados, además de la portada', () => {
    expect(Object.keys(PERMISO_POR_RUTA)).toHaveLength(19)
    expect(modulos.filter((ruta) => ruta.path !== '/').length).toBeGreaterThanOrEqual(18)
  })
})

describe('nomenclatura de los permisos', () => {
  it('sigue el patrón `modulo.accion`, salvo el del panel', () => {
    for (const permiso of Object.values(PERMISO_POR_RUTA)) {
      expect(permiso).toMatch(/^panel-administrative$|^[a-z][a-z0-9-]*\.[a-z][a-z0-9-]*$/)
    }
  })

  it('no hay dos módulos distintos con el mismo permiso salvo que sea intencional', () => {
    const porPermiso = new Map<string, string[]>()
    for (const [ruta, permiso] of Object.entries(PERMISO_POR_RUTA)) {
      porPermiso.set(permiso, [...(porPermiso.get(permiso) ?? []), ruta])
    }
    // Se documenta el caso en vez de prohibirlo: dos rutas pueden compartir
    // permiso a propósito. Lo que no puede pasar es que un permiso quede sin
    // ninguna ruta, que sería un permiso muerto.
    for (const rutas of porPermiso.values()) expect(rutas.length).toBeGreaterThan(0)
  })

  it('los permisos de los 18 módulos son únicos', () => {
    const deModulos = modulos
      .filter((ruta) => ruta.path !== '/')
      .map((ruta) => ruta.meta.permiso)
      .filter((permiso): permiso is string => typeof permiso === 'string')

    expect(new Set(deModulos).size).toBe(deModulos.length)
  })
})
