/**
 * Servicio de las noticias del sitio (M01-6, RF-01-013).
 *
 * GET    /api/v1/panel/noticias              → listar()
 * POST   /api/v1/panel/noticias              → crear()
 * PATCH  /api/v1/panel/noticias/{uuid}       → actualizar()
 * DELETE /api/v1/panel/noticias/{uuid}       → eliminar()
 * POST   /api/v1/panel/noticias/{uuid}/imagen → subirImagen()
 *
 * La imagen y su texto alternativo viajan juntos: el servidor no acepta una imagen
 * sin su descripción (Res. MinTIC 1519/2020, Anexo 2).
 */
import { http, desenvolver } from './http'
import type { ApiEnvelope, NoticiaInput, NoticiaPanelItem } from '@/types/api'

export const POR_PAGINA = 20

export async function listar(pagina = 1): Promise<NoticiaPanelItem[]> {
  const respuesta = await http.get<ApiEnvelope<NoticiaPanelItem[]>>('/panel/noticias', {
    params: { page: pagina, per_page: POR_PAGINA },
  })
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
