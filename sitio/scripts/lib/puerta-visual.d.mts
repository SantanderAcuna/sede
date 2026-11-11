export const ANCHOS: number[]

export interface Violacion {
  regla: string
  ruta: string
  ancho: number
  mensaje: string
}

export function rutasDelSitemap(xml: string, opciones?: { maxPorPrefijo?: number }): string[]
export interface LineaBase {
  objetivosPequenos?: Record<string, number>
  cls?: Record<string, number>
}
export function juzgarMedidas(medidas: Record<string, any>, base?: LineaBase): Violacion[]
export function juzgarCromo(
  medidas: Array<{ ruta: string; ancho: number; firmaCabecera: string; firmaPie: string }>,
): Violacion[]
export function archivosDeDiseno(archivos: string[]): string[]
export interface ObjetivoMedido {
  selector: string
  x: number
  y: number
  w: number
  h: number
  etiqueta?: { x: number; y: number; w: number; h: number }
  enLinea?: boolean
}
export interface ObjetivoIncumplido {
  selector: string
  ancho: number
  alto: number
  motivo: string
}
export function objetivosIncumplidos(objetivos: ObjetivoMedido[]): ObjetivoIncumplido[]
