/**
 * Servicio de los usuarios del panel.
 *
 * GET   /api/v1/panel/usuarios         → listar()
 * GET   /api/v1/panel/usuarios/{uuid}  → obtener()
 * POST  /api/v1/panel/usuarios         → crear()
 * PATCH /api/v1/panel/usuarios/{uuid}  → actualizar()
 */
import { http, desenvolver, desenvolverPagina, type Pagina } from './http'
import type { ApiEnvelope, PaginatedEnvelope, UsuarioPanelEntrada, UsuarioPanelItem } from '@/types/api'

export const POR_PAGINA = 20

export interface ConsultaUsuarios {
  pagina?: number
  porPagina?: number
  q?: string
  estado?: string
  rol?: string
}

/** Solo viajan los filtros con valor. */
export async function listar(consulta: ConsultaUsuarios = {}): Promise<Pagina<UsuarioPanelItem>> {
  const params: Record<string, string | number> = {
    page: consulta.pagina ?? 1,
    per_page: consulta.porPagina ?? POR_PAGINA,
  }
  const q = consulta.q?.trim() ?? ''
  if (q !== '') params.q = q
  if (consulta.estado) params.estado = consulta.estado
  if (consulta.rol) params.rol = consulta.rol

  const respuesta = await http.get<PaginatedEnvelope<UsuarioPanelItem>>('/panel/usuarios', { params })
  return desenvolverPagina(respuesta.data)
}

export async function obtener(uuid: string): Promise<UsuarioPanelItem> {
  const respuesta = await http.get<ApiEnvelope<UsuarioPanelItem>>(`/panel/usuarios/${uuid}`)
  return desenvolver(respuesta.data)
}

export async function crear(datos: UsuarioPanelEntrada): Promise<UsuarioPanelItem> {
  const respuesta = await http.post<ApiEnvelope<UsuarioPanelItem>>('/panel/usuarios', datos)
  return desenvolver(respuesta.data)
}

export async function actualizar(uuid: string, datos: UsuarioPanelEntrada): Promise<UsuarioPanelItem> {
  const respuesta = await http.patch<ApiEnvelope<UsuarioPanelItem>>(`/panel/usuarios/${uuid}`, datos)
  return desenvolver(respuesta.data)
}
