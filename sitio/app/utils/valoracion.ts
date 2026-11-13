/**
 * Lógica pura de la valoración de una ficha, aparte del componente para poder probarla sin montar
 * Nuxt: decidir si hay algo que enviar y construir el cuerpo de la petición tal como lo espera el
 * contrato (`POST /tramites/{slug}/valoracion`).
 */

export type ValorExperiencia = 'facil' | 'dificil' | ''

export interface EntradaValoracion {
  valor: 'facil' | 'dificil' | null
  estrellas: number | null
  comentario: string | null
}

/** Hay algo que enviar cuando se marcó la experiencia o se puso al menos una estrella. */
export function hayValoracion(valor: ValorExperiencia, estrellas: number): boolean {
  return valor !== '' || estrellas > 0
}

/** El cuerpo de la petición: lo no elegido viaja como `null`, nunca como cadena vacía ni como cero. */
export function cuerpoValoracion(valor: ValorExperiencia, estrellas: number, comentario: string): EntradaValoracion {
  return {
    valor: valor === '' ? null : valor,
    estrellas: estrellas > 0 ? estrellas : null,
    comentario: comentario.trim() === '' ? null : comentario.trim(),
  }
}
