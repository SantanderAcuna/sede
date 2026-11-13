/**
 * Lógica de la matriz módulo × acción del formulario de roles: agrupa por módulo y mantiene la
 * regla «toda acción distinta de Ver exige el Ver de su módulo».
 */
import { describe, expect, it } from 'vitest'
import { ACCIONES_BASE, accionesDelModulo, alternarPermiso, modulosDelGrupo, soloVerEnGrupo } from '../src/views/admin/permisos-matriz'
import type { GrupoPermisosPanel, PermisoPanel } from '../src/types/api'

const p = (modulo: string, accion: string, etiquetaModulo = modulo): PermisoPanel => ({
  nombre: `${modulo}.${accion}`, etiqueta: accion, descripcion: `${accion} ${modulo}`, modulo, modulo_etiqueta: etiquetaModulo, accion,
  requiere: accion === 'ver' ? [] : [`${modulo}.ver`],
})
const grupo: GrupoPermisosPanel = {
  grupo: 'Contenidos',
  permisos: [
    p('noticias', 'ver', 'Noticias'), p('noticias', 'crear', 'Noticias'), p('noticias', 'editar', 'Noticias'),
    p('noticias', 'eliminar', 'Noticias'), p('noticias', 'publicar', 'Noticias'),
    p('politicas', 'ver', 'Políticas'), p('politicas', 'editar', 'Políticas'),
  ],
}

describe('modulosDelGrupo', () => {
  it('agrupa por módulo, en el orden del catálogo, con sus acciones', () => {
    const m = modulosDelGrupo(grupo)
    expect(m.map((x) => x.modulo)).toEqual(['noticias', 'politicas'])
    expect(m[0]?.etiqueta).toBe('Noticias')
    expect(Object.keys(m[0]?.acciones ?? {})).toEqual(['ver', 'crear', 'editar', 'eliminar', 'publicar'])
    expect(Object.keys(m[1]?.acciones ?? {})).toEqual(['ver', 'editar'])
  })

  it('separa las acciones que no son de las cinco columnas base', () => {
    const g: GrupoPermisosPanel = { grupo: 'Admin', permisos: [{ ...p('panel-administrative', 'entrar'), requiere: [] }] }
    const [modulo] = modulosDelGrupo(g)
    expect(accionesDelModulo(modulo!).otras.map((x) => x.accion)).toEqual(['entrar'])
    expect(ACCIONES_BASE).toEqual(['ver', 'crear', 'editar', 'eliminar', 'publicar'])
  })
})

describe('alternarPermiso', () => {
  const catalogo = [grupo]

  it('marcar una acción marca también el Ver de su módulo', () => {
    expect(alternarPermiso([], 'noticias.editar', true, catalogo).sort()).toEqual(['noticias.editar', 'noticias.ver'])
  })

  it('marcar Ver no marca nada más', () => {
    expect(alternarPermiso([], 'noticias.ver', true, catalogo)).toEqual(['noticias.ver'])
  })

  it('quitar Ver quita todas las acciones de ese módulo y deja las de los demás', () => {
    const antes = ['noticias.ver', 'noticias.editar', 'noticias.publicar', 'politicas.ver']
    expect(alternarPermiso(antes, 'noticias.ver', false, catalogo)).toEqual(['politicas.ver'])
  })

  it('quitar una acción distinta de Ver deja el Ver', () => {
    expect(alternarPermiso(['noticias.ver', 'noticias.editar'], 'noticias.editar', false, catalogo)).toEqual(['noticias.ver'])
  })

  it('no repite permisos y no cambia el arreglo original', () => {
    const antes = ['noticias.ver']
    const despues = alternarPermiso(antes, 'noticias.crear', true, catalogo)
    expect(antes).toEqual(['noticias.ver'])
    expect(new Set(despues).size).toBe(despues.length)
  })

  it('respeta lo que quien edita puede dar: no marca lo que no puede otorgar', () => {
    const puedeDar = (n: string): boolean => n !== 'noticias.publicar'
    expect(alternarPermiso([], 'noticias.publicar', true, catalogo, puedeDar)).toEqual([])
    expect(alternarPermiso([], 'noticias.editar', true, catalogo, puedeDar).sort()).toEqual(['noticias.editar', 'noticias.ver'])
  })
})

describe('soloVerEnGrupo', () => {
  it('deja solo el Ver de cada módulo del grupo y no toca otros grupos', () => {
    const antes = ['noticias.editar', 'noticias.ver', 'politicas.editar', 'politicas.ver', 'otro.eliminar', 'otro.ver']
    expect(soloVerEnGrupo(antes, grupo).sort()).toEqual(['noticias.ver', 'otro.eliminar', 'otro.ver', 'politicas.ver'])
  })

  it('con puedeDar, no quita lo que quien edita no puede quitar ni pone Ver que no puede dar', () => {
    const puedeDar = (n: string): boolean => n.startsWith('noticias.')
    expect(soloVerEnGrupo(['politicas.ver', 'politicas.editar'], grupo, puedeDar).sort()).toEqual(['noticias.ver', 'politicas.editar', 'politicas.ver'])
  })
})
