/**
 * Qué números de página dibuja la paginación.
 *
 * Con pocas páginas se dibujan todas. Con muchas: la primera, la última, la actual con
 * sus vecinas y un «…» donde falta un tramo. Un hueco de una sola página no se abrevia:
 * se dibuja esa página, porque «…» ocuparía el mismo sitio y diría menos.
 */
export type ElementoPagina = number | '…'

export function ventanaDePaginas(actual: number, total: number): ElementoPagina[] {
  if (total < 1) return []

  const centro = Math.min(Math.max(actual, 2), Math.max(total - 1, 1))
  const visibles = new Set<number>([1, total, centro - 1, centro, centro + 1])
  const ordenadas = [...visibles].filter((p) => p >= 1 && p <= total).sort((a, b) => a - b)

  const resultado: ElementoPagina[] = []
  for (const [i, pagina] of ordenadas.entries()) {
    const anterior = ordenadas[i - 1]
    if (anterior !== undefined) {
      const salto = pagina - anterior
      if (salto === 2) resultado.push(anterior + 1)
      else if (salto > 2) resultado.push('…')
    }
    resultado.push(pagina)
  }
  return resultado
}
