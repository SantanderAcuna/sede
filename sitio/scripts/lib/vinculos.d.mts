export function esRoto(estado: number): boolean
export function extraerEnlaces(html: string, base: string): { internos: Set<string>; externos: Set<string> }
export function verificarExternos(
  urls: string[],
  opciones: {
    pedir: (url: string, metodo: 'HEAD' | 'GET') => Promise<number>
    concurrencia?: number
    esperaMs?: number
  },
): Promise<Array<{ url: string; estado: number; error?: string }>>
export function codigoDeSalida(internosRotos: Array<{ ruta: string; estado: number }>): 0 | 1
export function construirInforme(datos: {
  base: string
  paginas: number
  internosRotos: Array<{ ruta: string; estado: number; error?: string; origen?: string }>
  externos?: { comprobados: number; problemas: Array<{ url: string; estado: number; error?: string }> }
}): string
export function esTransitorio(estado: number): boolean
export function comprobarConReintentos<R extends { estado: number }>(
  pedir: () => Promise<R>,
  opciones?: { esperasMs?: number[] },
): Promise<R>
