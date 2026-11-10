/**
 * Las secciones de la sede dentro del buscador (M01-3 · RF-01-009, CA-01-009).
 *
 * Las páginas de la sede —Transparencia, PQRSD, Normativa…— las conoce el propio
 * sitio, no la API: la fuente única es `config/sitemap.ts`, la misma que alimenta el
 * menú, el mapa del sitio y el `sitemap.xml`. Este módulo decide **cuándo una sección
 * calza con lo que escribe la persona**. Es lógica pura (sin Vue, sin red), para
 * poder probarla con tablas de casos.
 *
 * ## Reglas
 *
 *  1. **Sólo lo que publica contenido.** Una página «en preparación» no se ofrece
 *     (decisión de José, 2026-11-10); es el mismo criterio del `sitemap.xml`. Cuando
 *     una sección pasa a publicada en `config/sitemap.ts`, entra sola.
 *  2. **Tolerancia de una letra** (RF-01-009: ≤ 1 de distancia de Levenshtein), sin
 *     tildes ni mayúsculas, y también con la palabra escrita a medias: «transparenci»
 *     y «transparensi» encuentran «Transparencia».
 *  3. **Sin ruido.** Las palabras de menos de 4 letras no se exigen (de, la, una…: no
 *     distinguen nada; es la misma regla de la API) y la tolerancia a una letra sólo
 *     vale desde 5 letras: con 4, «noma» se parece a demasiadas cosas.
 *  4. **Todas las palabras que cuentan deben calzar** con alguna palabra de la
 *     sección: su nombre, su rótulo corto del menú («PQRSD») o su dirección.
 *
 * Qué no hace: no es pertinencia estadística como la de la API (trigramas sobre un
 * catálogo de cientos de trámites). Son una docena de páginas con nombres cortos; un
 * calce por palabras, explicable en una línea, es lo adecuado y lo verificable.
 */
import { menuPrincipal, todasLasRutas, type RutaPublica } from '~/config/sitemap'

/** Una sección lista para buscarse: sus palabras ya vienen normalizadas. */
export interface SeccionBuscable {
  ruta: string
  etiqueta: string
  palabras: readonly string[]
}

/** Lo que se devuelve de cada calce. */
export interface SeccionEncontrada {
  ruta: string
  etiqueta: string
}

/** Palabras de la consulta que se tienen en cuenta como máximo (como la API). */
const PALABRAS_MAXIMAS = 6
/** Las palabras más cortas no se exigen (como la API). */
const PALABRA_MINIMA = 4
/** Con menos letras que esto, una palabra sola no es una consulta. */
const MINIMO_CARACTERES = 3
/** Tolerancia de una letra sólo desde este largo. */
const LARGO_MINIMO_PARA_ERRATA = 5

/** Sin tildes ni mayúsculas, como las trata el servidor. */
export function normalizarTexto(texto: string): string {
  return texto
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
}

function palabrasDe(texto: string): string[] {
  return normalizarTexto(texto)
    .split(/[^a-z0-9]+/)
    .filter((palabra) => palabra !== '')
}

/**
 * Distancia de Levenshtein: el menor número de inserciones, borrados y cambios de
 * una letra para pasar de `a` a `b`. Dos letras trocadas cuentan 2.
 */
export function distanciaDeEdicion(a: string, b: string): number {
  if (a === b) return 0
  if (a.length === 0) return b.length
  if (b.length === 0) return a.length

  let anterior = Array.from({ length: b.length + 1 }, (_, j) => j)
  for (let i = 1; i <= a.length; i += 1) {
    const actual = [i]
    for (let j = 1; j <= b.length; j += 1) {
      const costo = a[i - 1] === b[j - 1] ? 0 : 1
      actual[j] = Math.min(
        (actual[j - 1] ?? 0) + 1, // inserción
        (anterior[j] ?? 0) + 1, // borrado
        (anterior[j - 1] ?? 0) + costo, // cambio
      )
    }
    anterior = actual
  }
  return anterior[b.length] ?? Math.max(a.length, b.length)
}

/**
 * Las secciones que se pueden buscar: las **publicadas**, sin la propia página de
 * búsqueda y sin repetir rutas.
 */
export function crearIndiceDeSecciones(rutas: readonly RutaPublica[]): SeccionBuscable[] {
  const vistas = new Set<string>()
  const indice: SeccionBuscable[] = []
  for (const ruta of rutas) {
    if (!ruta.publicada || ruta.ruta === '/buscar' || vistas.has(ruta.ruta)) continue
    vistas.add(ruta.ruta)
    const palabras = new Set([
      ...palabrasDe(ruta.etiqueta),
      ...palabrasDe(ruta.etiquetaMenu ?? ''),
      ...palabrasDe(ruta.ruta),
    ])
    indice.push({ ruta: ruta.ruta, etiqueta: ruta.etiqueta, palabras: [...palabras] })
  }
  return indice
}

let indiceDelSitioMemoizado: SeccionBuscable[] | undefined

/** El índice de las rutas reales del sitio. Se arma una vez: la configuración es estática. */
export function indiceDelSitio(): SeccionBuscable[] {
  indiceDelSitioMemoizado ??= crearIndiceDeSecciones(todasLasRutas())
  return indiceDelSitioMemoizado
}

/** 3 = la misma palabra · 2 = empieza igual · 1 = a una letra de distancia · 0 = no calza. */
function calce(palabraBuscada: string, palabra: string): 0 | 1 | 2 | 3 {
  if (palabra === palabraBuscada) return 3
  if (palabra.startsWith(palabraBuscada)) return 2
  if (palabraBuscada.length >= LARGO_MINIMO_PARA_ERRATA) {
    if (distanciaDeEdicion(palabraBuscada, palabra) <= 1) return 1
    // La palabra escrita a medias y con una errata («transparensi»).
    if (palabra.length > palabraBuscada.length
      && distanciaDeEdicion(palabraBuscada, palabra.slice(0, palabraBuscada.length)) <= 1) {
      return 1
    }
  }
  return 0
}

/**
 * Las secciones que calzan con `consulta`, de la que mejor a la que peor (a igualdad,
 * en el orden del índice, que es el del menú). Vacío si la consulta es demasiado corta.
 */
export function buscarSecciones(
  indice: readonly SeccionBuscable[],
  consulta: string,
  limite = 10,
): SeccionEncontrada[] {
  const palabras = palabrasDe(consulta).slice(0, PALABRAS_MAXIMAS)
  const largas = palabras.filter((palabra) => palabra.length >= PALABRA_MINIMA)
  const exigidas = largas.length > 0 ? largas : palabras.filter((palabra) => palabra.length >= MINIMO_CARACTERES)
  if (exigidas.length === 0) return []

  const puntuadas: { seccion: SeccionBuscable; puntaje: number; orden: number }[] = []
  indice.forEach((seccion, orden) => {
    let puntaje = 0
    for (const buscada of exigidas) {
      let mejor = 0
      for (const palabra of seccion.palabras) {
        mejor = Math.max(mejor, calce(buscada, palabra))
        if (mejor === 3) break
      }
      if (mejor === 0) return
      puntaje += mejor
    }
    puntuadas.push({ seccion, puntaje, orden })
  })

  return puntuadas
    .sort((a, b) => b.puntaje - a.puntaje || a.orden - b.orden)
    .slice(0, Math.max(limite, 0))
    .map(({ seccion }) => ({ ruta: seccion.ruta, etiqueta: seccion.etiqueta }))
}

/**
 * Los enlaces que se ofrecen cuando una búsqueda no halla nada: las páginas
 * **publicadas** del menú principal, en el orden del menú y sin la portada. Salen de
 * la misma configuración, así que una sección nueva aparece al publicarse.
 */
export function seccionesPrincipales(): SeccionEncontrada[] {
  const publicadas = new Set(todasLasRutas().filter((ruta) => ruta.publicada).map((ruta) => ruta.ruta))
  const vistas = new Set<string>()
  const principales: SeccionEncontrada[] = []

  for (const item of menuPrincipal) {
    const candidatos = [
      ...(item.ruta ? [{ etiqueta: item.etiqueta, ruta: item.ruta }] : []),
      ...(item.subsecciones ?? []).flatMap((subseccion) => subseccion.enlaces),
    ]
    for (const candidato of candidatos) {
      if (candidato.ruta === '/' || !publicadas.has(candidato.ruta) || vistas.has(candidato.ruta)) continue
      vistas.add(candidato.ruta)
      principales.push({ ruta: candidato.ruta, etiqueta: candidato.etiqueta })
    }
  }
  return principales
}
