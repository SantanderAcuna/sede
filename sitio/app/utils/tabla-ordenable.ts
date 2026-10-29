/**
 * Tipos de la tabla ordenable del Kit UI (`TablaOrdenableGovco`, RF-B3-076).
 *
 * Viven aparte del componente porque los usan también las páginas que arman sus
 * filas, y un `.vue` no es lugar para importar tipos desde fuera de él.
 */

export interface ColumnaTabla {
  clave: string
  titulo: string
  ordenable?: boolean
}

export interface CeldaTabla {
  texto: string
  /** Valor para ordenar cuando el texto no lo expresa (un número, una fecha ISO). */
  orden?: number | string
}

export interface FilaTabla {
  id: string | number
  celdas: Record<string, CeldaTabla>
}
