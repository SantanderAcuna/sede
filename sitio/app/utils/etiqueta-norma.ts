/** Texto de una norma de la ficha («Decreto 2715 de 2009 — Artículos 2-5»), con los espacios que el
 *  template de Vue se come entre elementos. */
export interface DatosNorma {
  tipo: string
  numero?: string | null
  anio?: number | null
  articulos?: string | null
}

export function etiquetaNorma(n: DatosNorma): string {
  return [n.tipo, n.numero, n.anio ? `de ${n.anio}` : null].filter(Boolean).join(' ')
}

export function textoNorma(n: DatosNorma): string {
  return n.articulos ? `${etiquetaNorma(n)} — ${n.articulos}` : etiquetaNorma(n)
}
