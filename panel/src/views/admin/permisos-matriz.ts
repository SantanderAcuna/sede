/**
 * Lógica de la matriz módulo × acción del formulario de roles.
 *
 * Cada módulo tiene sus acciones por separado (ver, crear, editar, eliminar, publicar). La regla que
 * mantiene coherente la matriz: toda acción distinta de «ver» exige el «ver» de su módulo (el
 * catálogo lo declara en `requiere`). El servidor vuelve a comprobarla y rechaza un rol que no la
 * cumpla; aquí se aplica al marcar para que quien edita nunca llegue a ese error.
 *
 * `puedeDar` es lo que quien edita puede otorgar (nadie da lo que no tiene); sin él, todo se puede.
 */
import type { GrupoPermisosPanel, PermisoPanel } from '@/types/api'

/** Las cinco columnas de la matriz, en el orden en que se leen. */
export const ACCIONES_BASE = ['ver', 'crear', 'editar', 'eliminar', 'publicar'] as const

export interface ModuloMatriz {
  modulo: string
  etiqueta: string
  /** Acciones del módulo, en el orden del catálogo. */
  acciones: Record<string, PermisoPanel>
}

type PuedeDar = (nombre: string) => boolean
const todo: PuedeDar = () => true

/** Los módulos de un grupo, con sus acciones, en el orden del catálogo. */
export function modulosDelGrupo(grupo: GrupoPermisosPanel): ModuloMatriz[] {
  const porModulo = new Map<string, ModuloMatriz>()
  for (const permiso of grupo.permisos) {
    const existente = porModulo.get(permiso.modulo)
    if (existente) existente.acciones[permiso.accion] = permiso
    else porModulo.set(permiso.modulo, { modulo: permiso.modulo, etiqueta: permiso.modulo_etiqueta, acciones: { [permiso.accion]: permiso } })
  }
  return [...porModulo.values()]
}

/** Las acciones de un módulo repartidas entre las cinco columnas base y «otras». */
export function accionesDelModulo(modulo: ModuloMatriz): { base: Array<PermisoPanel | undefined>; otras: PermisoPanel[] } {
  const base = ACCIONES_BASE.map((a) => modulo.acciones[a])
  const otras = Object.values(modulo.acciones).filter((p) => !(ACCIONES_BASE as readonly string[]).includes(p.accion))
  return { base, otras }
}

function buscar(catalogo: GrupoPermisosPanel[], nombre: string): PermisoPanel | undefined {
  for (const g of catalogo) {
    const p = g.permisos.find((x) => x.nombre === nombre)
    if (p) return p
  }
  return undefined
}

/**
 * Marca o quita un permiso conservando la coherencia.
 *
 *  - Marcar una acción marca también lo que exige (su «ver»), si quien edita puede darlo.
 *  - Quitar «ver» quita todas las acciones de ese módulo que lo exigen.
 *  - No cambia el arreglo recibido y no repite permisos.
 */
export function alternarPermiso(
  seleccion: string[],
  nombre: string,
  marcar: boolean,
  catalogo: GrupoPermisosPanel[],
  puedeDar: PuedeDar = todo,
): string[] {
  const permiso = buscar(catalogo, nombre)
  const siguiente = new Set(seleccion)

  if (marcar) {
    if (!puedeDar(nombre)) return [...siguiente]
    for (const requerido of permiso?.requiere ?? []) {
      if (puedeDar(requerido)) siguiente.add(requerido)
    }
    siguiente.add(nombre)
    return [...siguiente]
  }

  siguiente.delete(nombre)
  for (const g of catalogo) {
    for (const otro of g.permisos) {
      if (otro.requiere.includes(nombre) && puedeDar(otro.nombre)) siguiente.delete(otro.nombre)
    }
  }
  return [...siguiente]
}

/** Deja solo «ver» en cada módulo del grupo (lo que quien edita puede dar o quitar); los demás grupos no se tocan. */
export function soloVerEnGrupo(seleccion: string[], grupo: GrupoPermisosPanel, puedeDar: PuedeDar = todo): string[] {
  const siguiente = new Set(seleccion)
  for (const permiso of grupo.permisos) {
    if (!puedeDar(permiso.nombre)) continue
    if (permiso.accion === 'ver') siguiente.add(permiso.nombre)
    else siguiente.delete(permiso.nombre)
  }
  return [...siguiente]
}
