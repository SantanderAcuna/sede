/**
 * Detector de datos institucionales escritos en el código (Fase 0B, T7; D-0.4).
 *
 * Qué detecta (clase «BD» de la clasificación de datos del sitio): correos del dominio
 * institucional, teléfonos `(+57) …`, direcciones (`Calle|Carrera|… N`), código postal de
 * Santa Marta (4700NN), NIT, y la lista `DEPENDENCIAS`.
 *
 * Qué NO detecta, a propósito: comentarios (se quitan antes de buscar) y el nombre de la
 * Entidad, porque ese texto también aparece legítimamente en frases de interfaz del código
 * (cookies, aviso de salida) y no se puede distinguir sin ambigüedad. Es una guardia por
 * patrones, no un análisis sintáctico: los datos nuevos con otra forma no se ven.
 */

export type TipoLiteral = 'correo' | 'telefono' | 'direccion' | 'codigo_postal' | 'nit' | 'dependencia'

export interface Literal {
  archivo: string
  tipo: TipoLiteral
  valor: string
}

export interface EntradaLineaBase extends Literal {
  /** Solo el respaldo del pie de página; el resto de la línea base solo puede bajar. */
  permanente?: boolean
  motivo?: string
}

export const clave = (l: Literal): string => `${l.archivo}|${l.tipo}|${l.valor}`

/** Quita comentarios HTML, de bloque y de línea completa; no toca `//` dentro de URLs. */
function sinComentarios(texto: string): string {
  return texto
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/^\s*\/\/.*$/gm, '')
}

const PATRONES: Array<{ tipo: Exclude<TipoLiteral, 'dependencia'>; re: RegExp; normalizar: (m: RegExpExecArray) => string }> = [
  { tipo: 'correo', re: /[A-Za-z0-9._+-]+@santamarta\.gov\.co/g, normalizar: (m) => m[0].toLowerCase() },
  { tipo: 'telefono', re: /\(\+57\)\s*\d[\d\s]{6,}\d/g, normalizar: (m) => m[0].replace(/\D/g, '') },
  {
    tipo: 'direccion',
    re: /\b(?:Calle|Carrera|Avenida|Transversal|Diagonal)\s+\d+[^'"`,\n]*/g,
    normalizar: (m) => m[0].trim(),
  },
  { tipo: 'codigo_postal', re: /\b4700\d{2}\b/g, normalizar: (m) => m[0] },
  { tipo: 'nit', re: /\bNIT\s*(\d[\d.]*-\d)/g, normalizar: (m) => m[1] ?? m[0] },
]

export function detectarLiterales(archivo: string, texto: string): Literal[] {
  const limpio = sinComentarios(texto)
  const salida = new Map<string, Literal>()
  const agregar = (tipo: TipoLiteral, valor: string) => {
    const l = { archivo, tipo, valor }
    salida.set(clave(l), l)
  }

  for (const { tipo, re, normalizar } of PATRONES) {
    for (const m of limpio.matchAll(re)) agregar(tipo, normalizar(m as RegExpExecArray))
  }

  for (const m of limpio.matchAll(/\bDEPENDENCIAS\b[^=\n]*=\s*\[([^\]]*)\]/g)) {
    for (const s of (m[1] ?? '').matchAll(/(['"`])((?:(?!\1).)+)\1/g)) agregar('dependencia', (s[2] ?? '').trim())
  }

  return [...salida.values()]
}

/** `nuevos`: están en el código y no en la línea base. `obsoletos`: al revés. */
export function compararConLineaBase(
  encontrados: Literal[],
  lineaBase: EntradaLineaBase[],
): { nuevos: Literal[]; obsoletos: EntradaLineaBase[] } {
  const enCodigo = new Set(encontrados.map(clave))
  const enBase = new Set(lineaBase.map(clave))
  return {
    nuevos: encontrados.filter((l) => !enBase.has(clave(l))),
    obsoletos: lineaBase.filter((e) => !enCodigo.has(clave(e))),
  }
}
