/**
 * Servicio de la auditoría del panel.
 *
 * GET /api/v1/panel/auditoria → listar()
 *
 * Solo lectura. Lo más reciente primero. El servidor entrega cada acción ya dicha con palabras (`descripcion`).
 */
import { http, desenvolverPagina, type Pagina } from './client'
import type { AuditoriaItem, PaginatedEnvelope } from '@/types/api'

export const POR_PAGINA = 20

export interface ConsultaAuditoria {
  pagina?: number
  porPagina?: number
  /** Módulo (`noticias`, `banners`...). Vacío = todos. */
  recurso?: string
  /** `AAAA-MM-DD`. Vacío = sin límite. */
  desde?: string
  hasta?: string
}

/** Solo viajan los filtros que tienen valor. */
export async function listar(consulta: ConsultaAuditoria = {}): Promise<Pagina<AuditoriaItem>> {
  const params: Record<string, string | number> = {
    pagina: consulta.pagina ?? 1,
    por_pagina: consulta.porPagina ?? POR_PAGINA,
  }
  if (consulta.recurso) params.recurso = consulta.recurso
  if (consulta.desde) params.fecha_desde = consulta.desde
  if (consulta.hasta) params.fecha_hasta = consulta.hasta

  const respuesta = await http.get<PaginatedEnvelope<AuditoriaItem>>('/panel/auditoria', { params })
  return desenvolverPagina(respuesta.data)
}
