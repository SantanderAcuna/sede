import { useState } from '#imports'
import type { FichaDeRuta } from '~/utils/miga'

/**
 * La ficha (noticia o trámite) que el middleware `ficha-miga.global.ts` leyó antes de
 * dibujar la página, para que la miga de pan pueda mostrar su título.
 *
 * Se guarda con `useState`: viaja del servidor al navegador dentro del HTML, así que la
 * hidratación no vuelve a pedirla. La página usa **esta misma lectura** (ver `leerFicha`),
 * de modo que mostrar el título en la miga no cuesta ninguna consulta más a la API.
 */
export interface FichaPrecargada {
  /** La ruta a la que pertenece: sólo vale mientras se esté en ella. */
  ruta: string
  clave: string
  seccion: FichaDeRuta['seccion']
  /** La respuesta de la API, o `null` si respondió 404. */
  respuesta: unknown
}

export function useFichaPrecargada() {
  return useState<FichaPrecargada | null>('ficha-precargada', () => null)
}

type Traer = <T>(url: string) => Promise<T>

/**
 * Lee una ficha de la API. `404` → `null` (la ficha no existe o no está publicada);
 * cualquier otro fallo se propaga: que la API no conteste no es «no existe».
 */
export async function leerFicha<T>(traer: Traer, urlApi: string, ficha: FichaDeRuta): Promise<T | null> {
  try {
    return await traer<T>(`${urlApi}${ficha.endpoint}`)
  } catch (fallo) {
    const respuesta = fallo as { statusCode?: number; response?: { status?: number } }
    if ((respuesta.statusCode ?? respuesta.response?.status) === 404) return null
    throw fallo
  }
}
