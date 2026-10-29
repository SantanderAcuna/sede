/**
 * Servicio de las políticas del sitio y de su documento oficial adjunto.
 *
 * GET   /api/v1/panel/politicas               → listar()
 * PATCH /api/v1/panel/politicas/{slug}        → actualizar()
 * GET   /api/v1/panel/entidad/{uuid}/archivos → documentosAdjuntos()
 * POST  /api/v1/panel/entidad/{uuid}/archivos → adjuntarDocumento()
 * DELETE /api/v1/panel/archivos/{uuid}        → quitarArchivo()
 *
 * El documento oficial se guarda en la tabla común de archivos del sistema
 * (`file_media`), en la colección `documento-oficial`; la ruta se llama
 * `/entidad/{uuid}/archivos` por historia, pero acepta el UUID de una política.
 */
import { http, desenvolver } from './http'
import type { ApiEnvelope, ArchivoItem, PoliticaInput, PoliticaPanelItem } from '@/types/api'

/** Colección de `file_media` donde vive el documento oficial de una política. */
export const COLECCION_DOCUMENTO = 'documento-oficial'

export async function listar(): Promise<PoliticaPanelItem[]> {
  const respuesta = await http.get<ApiEnvelope<PoliticaPanelItem[]>>('/panel/politicas')
  return desenvolver(respuesta.data)
}

export async function actualizar(slug: string, datos: PoliticaInput): Promise<PoliticaPanelItem> {
  const respuesta = await http.patch<ApiEnvelope<PoliticaPanelItem>>(`/panel/politicas/${slug}`, datos)
  return desenvolver(respuesta.data)
}

/** Los archivos adjuntos como documento oficial, el más reciente primero. */
export async function documentosAdjuntos(uuidPolitica: string): Promise<ArchivoItem[]> {
  const respuesta = await http.get<ApiEnvelope<ArchivoItem[]>>(
    `/panel/entidad/${uuidPolitica}/archivos`,
    { params: { collection: COLECCION_DOCUMENTO } },
  )
  return desenvolver(respuesta.data)
}

export async function adjuntarDocumento(uuidPolitica: string, archivo: File): Promise<ArchivoItem> {
  const cuerpo = new FormData()
  cuerpo.append('archivo', archivo)
  cuerpo.append('collection', COLECCION_DOCUMENTO)

  const respuesta = await http.post<ApiEnvelope<ArchivoItem>>(
    `/panel/entidad/${uuidPolitica}/archivos`,
    cuerpo,
    // Sin tiempo máximo corto: un PDF de varios MB tarda más que una consulta.
    { headers: { 'Content-Type': 'multipart/form-data' }, timeout: 60_000 },
  )
  return desenvolver(respuesta.data)
}

export async function quitarArchivo(uuidArchivo: string): Promise<void> {
  await http.delete(`/panel/archivos/${uuidArchivo}`)
}
