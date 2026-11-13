/**
 * Imágenes que el editor inserta en el contenido (noticias, políticas, entidad).
 *
 * POST /api/v1/panel/contenido/imagenes → subirImagenContenido()
 *
 * La imagen y su texto alternativo viajan juntos. El servidor la re-codifica y
 * devuelve la URL pública con la que se inserta en el HTML.
 */
import { http, desenvolver } from './http'
import type { ApiEnvelope } from '@/types/api'

export interface ImagenContenido {
  uuid: string
  url: string
  alt: string
}

export async function subirImagenContenido(archivo: File, alt: string): Promise<ImagenContenido> {
  const cuerpo = new FormData()
  cuerpo.append('imagen', archivo)
  cuerpo.append('alt', alt)

  const respuesta = await http.post<ApiEnvelope<ImagenContenido>>('/panel/contenido/imagenes', cuerpo, {
    headers: { 'Content-Type': 'multipart/form-data' },
    timeout: 60_000,
  })
  return desenvolver(respuesta.data)
}
