export type EstadoVista = 'igual' | 'cambiada' | 'falta-en-base' | 'falta-en-actual'

export interface VistaComparada {
  nombre: string
  estado: EstadoVista
  alturaBase: number | null
  alturaActual: number | null
  huellaBase: string | null
  huellaActual: string | null
}

export function huella(buffer: Buffer): string
export function alturaDePng(buffer: Buffer): number | null
export function compararJuegos(base: Map<string, Buffer>, actual: Map<string, Buffer>): VistaComparada[]
export function resumirComparacion(vistas: VistaComparada[]): { iguales: number; distintas: number; codigoDeSalida: 0 | 1 | 2 }
