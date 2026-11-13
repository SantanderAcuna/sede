/**
 * Servicio de los roles y del catálogo de permisos.
 *
 * GET    /api/v1/panel/permisos    → permisos()
 * GET    /api/v1/panel/roles       → listar()
 * GET    /api/v1/panel/roles/{id}  → obtener()
 * POST   /api/v1/panel/roles       → crear()
 * PATCH  /api/v1/panel/roles/{id}  → actualizar()
 * DELETE /api/v1/panel/roles/{id}  → eliminar()
 */
import { http, desenvolver } from './http'
import type { ApiEnvelope, GrupoPermisosPanel, RolPanelEntrada, RolPanelItem } from '@/types/api'

export async function permisos(): Promise<GrupoPermisosPanel[]> {
  const respuesta = await http.get<ApiEnvelope<GrupoPermisosPanel[]>>('/panel/permisos')
  return desenvolver(respuesta.data)
}

export async function listar(): Promise<RolPanelItem[]> {
  const respuesta = await http.get<ApiEnvelope<RolPanelItem[]>>('/panel/roles')
  return desenvolver(respuesta.data)
}

export async function obtener(id: number): Promise<RolPanelItem> {
  const respuesta = await http.get<ApiEnvelope<RolPanelItem>>(`/panel/roles/${id}`)
  return desenvolver(respuesta.data)
}

export async function crear(datos: RolPanelEntrada): Promise<RolPanelItem> {
  const respuesta = await http.post<ApiEnvelope<RolPanelItem>>('/panel/roles', datos)
  return desenvolver(respuesta.data)
}

export async function actualizar(id: number, datos: RolPanelEntrada): Promise<RolPanelItem> {
  const respuesta = await http.patch<ApiEnvelope<RolPanelItem>>(`/panel/roles/${id}`, datos)
  return desenvolver(respuesta.data)
}

export async function eliminar(id: number): Promise<void> {
  await http.delete(`/panel/roles/${id}`)
}
