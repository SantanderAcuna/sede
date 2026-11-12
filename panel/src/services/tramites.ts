/**
 * Servicio de los trámites del panel.
 *
 * GET    /api/v1/panel/tramites         → listar()
 * GET    /api/v1/panel/tramites/{uuid}  → obtener()
 * POST   /api/v1/panel/tramites         → crear()
 * PATCH  /api/v1/panel/tramites/{uuid}  → actualizar()
 * DELETE /api/v1/panel/tramites/{uuid}  → eliminar()
 */
import { http, desenvolver, desenvolverPagina, type Pagina } from './http'
import type { ApiEnvelope, PaginatedEnvelope, TramiteInput, TramitePanelFila, TramitePanelItem } from '@/types/api'

export const POR_PAGINA = 20

export type EstadoTramiteFiltro = 'publicado' | 'borrador'

export interface ConsultaTramites {
  pagina?: number
  porPagina?: number
  /** Texto a buscar en el nombre o el código. Vacío o de espacios = sin búsqueda. */
  q?: string
  /** `''` = todos. */
  estado?: EstadoTramiteFiltro | ''
  ordenarPor?: 'nombre' | 'actualizado_en'
  direccion?: 'asc' | 'desc'
}

/** Solo viajan los filtros con valor: el servidor no recibe `q=` ni `estado=`. */
export async function listar(consulta: ConsultaTramites = {}): Promise<Pagina<TramitePanelFila>> {
  const params: Record<string, string | number> = {
    page: consulta.pagina ?? 1,
    per_page: consulta.porPagina ?? POR_PAGINA,
  }
  const q = consulta.q?.trim() ?? ''
  if (q !== '') params.q = q
  if (consulta.estado) params.estado = consulta.estado
  if (consulta.ordenarPor) params.ordenar_por = consulta.ordenarPor
  if (consulta.direccion) params.direccion = consulta.direccion

  const respuesta = await http.get<PaginatedEnvelope<TramitePanelFila>>('/panel/tramites', { params })
  return desenvolverPagina(respuesta.data)
}

export async function obtener(uuid: string): Promise<TramitePanelItem> {
  const respuesta = await http.get<ApiEnvelope<TramitePanelItem>>(`/panel/tramites/${uuid}`)
  return desenvolver(respuesta.data)
}

export async function crear(datos: TramiteInput): Promise<TramitePanelItem> {
  const respuesta = await http.post<ApiEnvelope<TramitePanelItem>>('/panel/tramites', datos)
  return desenvolver(respuesta.data)
}

export async function actualizar(uuid: string, datos: TramiteInput): Promise<TramitePanelItem> {
  const respuesta = await http.patch<ApiEnvelope<TramitePanelItem>>(`/panel/tramites/${uuid}`, datos)
  return desenvolver(respuesta.data)
}

export async function eliminar(uuid: string): Promise<void> {
  await http.delete(`/panel/tramites/${uuid}`)
}
