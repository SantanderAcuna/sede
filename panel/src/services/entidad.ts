/**
 * Servicio de gestión de la entidad institucional.
 *
 * Se comunica con el backend a través del contrato OpenAPI:
 * GET  /api/v1/entidad          → obtener()
 * PATCH /api/v1/panel/entidad   → actualizar()
 */
import { http, desenvolver } from './http'
import type { ApiEnvelope, EntidadItem, EntidadInput } from '@/types/api'

/**
 * Obtiene los datos de la entidad (público).
 */
export async function obtener(): Promise<EntidadItem> {
  const respuesta = await http.get<ApiEnvelope<EntidadItem>>('/entidad')
  return desenvolver(respuesta.data)
}

/**
 * Actualiza los datos de la entidad desde el panel de administración.
 *
 * Todos los campos son opcionales para actualizaciones parciales.
 */
export async function actualizar(datos: Partial<EntidadInput>): Promise<EntidadItem> {
  const respuesta = await http.patch<ApiEnvelope<EntidadItem>>(
    '/panel/entidad',
    datos
  )
  return desenvolver(respuesta.data)
}

/**
 * Las políticas que existen, para elegir a cuál lleva cada enlace del pie de página.
 * Es la lectura pública: no pide permisos de políticas.
 */
export async function politicasDisponibles(): Promise<Array<{ slug: string; nombre: string }>> {
  const respuesta = await http.get<ApiEnvelope<Array<{ slug: string; nombre: string }>>>('/politicas')
  return desenvolver(respuesta.data).map((p) => ({ slug: p.slug, nombre: p.nombre }))
}
