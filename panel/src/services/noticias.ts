/**
 * Servicio de las noticias del sitio (M01-6, RF-01-013).
 *
 * GET    /api/v1/panel/noticias              → listar()
 * GET    /api/v1/panel/noticias/{uuid}       → obtener()
 * POST   /api/v1/panel/noticias              → crear()
 * PATCH  /api/v1/panel/noticias/{uuid}       → actualizar()
 * DELETE /api/v1/panel/noticias/{uuid}       → eliminar()
 * POST   /api/v1/panel/noticias/{uuid}/imagen → subirImagen()
 *
 * La imagen y su texto alternativo viajan juntos: el servidor no acepta una imagen
 * sin su descripción (Res. MinTIC 1519/2020, Anexo 2).
 */
import { http, desenvolver, desenvolverPagina, type Pagina } from './http'
import type { ApiEnvelope, NoticiaInput, NoticiaPanelItem, PaginatedEnvelope } from '@/types/api'

export const POR_PAGINA = 20

export type EstadoNoticiaFiltro = 'borrador' | 'programada' | 'publicada'

export interface ConsultaNoticias {
  pagina?: number
  porPagina?: number
  /** Texto a buscar en el título. Vacío o de espacios = sin búsqueda. */
  q?: string
  /** `''` = todos. */
  estado?: EstadoNoticiaFiltro | ''
  ordenarPor?: 'fecha_publicacion' | 'titulo'
  direccion?: 'asc' | 'desc'
}

/** Sólo viajan los filtros que tienen valor: el servidor no recibe `q=` ni `estado=`. */
export async function listar(consulta: ConsultaNoticias = {}): Promise<Pagina<NoticiaPanelItem>> {
  const params: Record<string, string | number> = {
    page: consulta.pagina ?? 1,
    per_page: consulta.porPagina ?? POR_PAGINA,
  }
  const q = consulta.q?.trim() ?? ''
  if (q !== '') params.q = q
  if (consulta.estado) params.estado = consulta.estado
  if (consulta.ordenarPor) params.ordenar_por = consulta.ordenarPor
  if (consulta.direccion) params.direccion = consulta.direccion

  const respuesta = await http.get<PaginatedEnvelope<NoticiaPanelItem>>('/panel/noticias', { params })
  return desenvolverPagina(respuesta.data)
}

export async function obtener(uuid: string): Promise<NoticiaPanelItem> {
  const respuesta = await http.get<ApiEnvelope<NoticiaPanelItem>>(`/panel/noticias/${uuid}`)
  return desenvolver(respuesta.data)
}

export async function crear(datos: NoticiaInput): Promise<NoticiaPanelItem> {
  const respuesta = await http.post<ApiEnvelope<NoticiaPanelItem>>('/panel/noticias', datos)
  return desenvolver(respuesta.data)
}

export async function actualizar(uuid: string, datos: NoticiaInput): Promise<NoticiaPanelItem> {
  const respuesta = await http.patch<ApiEnvelope<NoticiaPanelItem>>(`/panel/noticias/${uuid}`, datos)
  return desenvolver(respuesta.data)
}

export async function eliminar(uuid: string): Promise<void> {
  await http.delete(`/panel/noticias/${uuid}`)
}

export async function subirImagen(uuid: string, archivo: File, alt: string): Promise<NoticiaPanelItem> {
  const cuerpo = new FormData()
  cuerpo.append('imagen', archivo)
  cuerpo.append('alt', alt)

  const respuesta = await http.post<ApiEnvelope<NoticiaPanelItem>>(`/panel/noticias/${uuid}/imagen`, cuerpo, {
    headers: { 'Content-Type': 'multipart/form-data' },
    timeout: 60_000,
  })
  return desenvolver(respuesta.data)
}
