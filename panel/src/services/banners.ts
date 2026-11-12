/**
 * Servicio del carrusel de la portada (Fase 6).
 *
 * GET    /api/v1/panel/banners              → listar()
 * GET    /api/v1/panel/banners/{uuid}       → obtener()
 * POST   /api/v1/panel/banners              → crear()
 * PATCH  /api/v1/panel/banners/{uuid}       → actualizar()
 * DELETE /api/v1/panel/banners/{uuid}       → eliminar()
 * POST   /api/v1/panel/banners/{uuid}/imagen → subirImagen()
 * PUT    /api/v1/panel/banners/orden        → ordenar()
 *
 * La imagen y su texto alternativo viajan juntos: sin descripción el servidor la rechaza.
 */
import { http, desenvolver } from './http'
import type { ApiEnvelope, BannerEntrada, BannerPanelItem } from '@/types/api'

export async function listar(): Promise<BannerPanelItem[]> {
  const r = await http.get<ApiEnvelope<BannerPanelItem[]>>('/panel/banners')
  return desenvolver(r.data)
}

export async function obtener(uuid: string): Promise<BannerPanelItem> {
  const r = await http.get<ApiEnvelope<BannerPanelItem>>(`/panel/banners/${uuid}`)
  return desenvolver(r.data)
}

export async function crear(datos: BannerEntrada): Promise<BannerPanelItem> {
  const r = await http.post<ApiEnvelope<BannerPanelItem>>('/panel/banners', datos)
  return desenvolver(r.data)
}

export async function actualizar(uuid: string, datos: BannerEntrada): Promise<BannerPanelItem> {
  const r = await http.patch<ApiEnvelope<BannerPanelItem>>(`/panel/banners/${uuid}`, datos)
  return desenvolver(r.data)
}

export async function eliminar(uuid: string): Promise<void> {
  await http.delete(`/panel/banners/${uuid}`)
}

export async function subirImagen(uuid: string, archivo: File, alt: string): Promise<BannerPanelItem> {
  const cuerpo = new FormData()
  cuerpo.append('imagen', archivo)
  cuerpo.append('alt', alt)
  const r = await http.post<ApiEnvelope<BannerPanelItem>>(`/panel/banners/${uuid}/imagen`, cuerpo, {
    headers: { 'Content-Type': 'multipart/form-data' },
    timeout: 60_000,
  })
  return desenvolver(r.data)
}

/** El nuevo orden: todos los `uuid`, en la posición en que deben quedar. */
export async function ordenar(uuids: string[]): Promise<BannerPanelItem[]> {
  const r = await http.put<ApiEnvelope<BannerPanelItem[]>>('/panel/banners/orden', { uuids })
  return desenvolver(r.data)
}
