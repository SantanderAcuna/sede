/**
 * Tipos del contrato.
 *
 * `api.d.ts` se **genera** desde `contract/openapi.yaml` y no se versiona: se
 * produce con `make tipos`. Este archivo es la capa que lo hace usable —los
 * nombres sueltos y los sobres genéricos— y sí se escribe a mano.
 *
 * La regla: ningún tipo de la API se escribe dos veces. Si algo no está en el
 * contrato, no se inventa aquí.
 */
import type { components } from './openapi'

/** Esquemas del contrato, por su nombre. */
type Esquemas = components['schemas']

// --- Recursos ---------------------------------------------------------------
export type EntidadItem = Esquemas['EntidadItem']
export type EntidadRed = Esquemas['EntidadRed']
export type EntidadPolitica = Esquemas['EntidadPolitica']
export type EntidadInput = Esquemas['EntidadInput']
export type TramiteItem = Esquemas['TramiteItem']
export type TramiteCategoria = Esquemas['TramiteCategoria']
export type TramiteRequisito = Esquemas['TramiteRequisito']
export type TramiteDocumento = Esquemas['TramiteDocumento']
export type TramitePaso = Esquemas['TramitePaso']
export type TramiteInput = Esquemas['TramiteInput']
export type PageMeta = Esquemas['PageMeta']
export type CollectionLinks = Esquemas['CollectionLinks']

// --- Sobres -----------------------------------------------------------------
//
// El contrato declara el sobre sin genéricos porque describe una forma, no un
// tipo de dato. Aquí se vuelve genérico para que el cliente HTTP pueda
// desempaquetarlo conservando el tipo del recurso.

/** Sobre plano de una respuesta individual. */
export interface ApiEnvelope<T = unknown> {
  success: boolean
  message: string | null
  data: T | null
  errors: Record<string, string[]> | null
}

/** Sobre plano de una colección paginada. */
export interface PaginatedEnvelope<T = unknown> {
  success: boolean
  message: string | null
  data: T[]
  meta: PageMeta
  links: CollectionLinks
  errors: Record<string, string[]> | null
}
