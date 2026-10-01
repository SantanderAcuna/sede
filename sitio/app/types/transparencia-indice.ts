/**
 * El índice de las nueve categorías: número, nombre y segmento de ruta.
 *
 * **Por qué vive aparte del inventario.** El inventario de la sección
 * —`~/types/transparencia.ts`— pesa más de cien kilobytes porque contiene los
 * 334 documentos rastreados, y la disposición del sitio
 * (`layouts/default.vue`) se carga en **todas** las páginas. La miga de pan
 * necesita los nueve rótulos para poder nombrar la categoría en la que se está,
 * y no tiene por qué arrastrar los 334 documentos de la sección para leer nueve
 * palabras. De ahí que el índice viva aquí, ligero, y que el inventario lo
 * importe: **los nueve nombres se declaran una sola vez**, en este archivo, y
 * los usan los dos.
 *
 * **Por qué un registro y no un arreglo.** El proyecto comprueba tipos con
 * `noUncheckedIndexedAccess`, así que indexar un arreglo devuelve
 * `T | undefined` y obligaría a defenderse de un caso que las claves ya
 * descartan. Con un `Record` cuyas claves son la unión de los nueve segmentos,
 * `CATEGORIAS_INDICE.normativa` está tipado como `CategoriaIndice` y no hay nada
 * que comprobar. El orden de la norma —el único orden que importa aquí— se
 * declara aparte, en `ORDEN_CATEGORIAS`, porque un `Record` no lo tiene.
 *
 * **Estas nueve no son una lista inventada.** Son las categorías de información
 * mínima obligatoria que ordena el Anexo 2 §4.1.2 de la Resolución MinTIC 1519
 * de 2020, y su orden es el de la norma, no el alfabético. El sitio de la
 * Entidad publica ocho: la novena —Información específica de la entidad— no
 * existe allí, y es la que la Sede tiene que construir.
 */

/** Los nueve segmentos de ruta de la sección, como unión de literales. */
export type SlugCategoria =
  | 'informacion-de-las-entidades'
  | 'normativa'
  | 'contratacion'
  | 'planeacion-presupuesto-e-informes'
  | 'tramites-y-servicios'
  | 'participa'
  | 'datos-abiertos'
  | 'grupos-de-interes'
  | 'informacion-especifica-de-la-entidad'

/** Una categoría del índice: lo mínimo para nombrarla y enlazarla. */
export interface CategoriaIndice {
  /** El número que le da la norma, de 1 a 9. */
  numero: number
  /** Segmento de ruta dentro de `/transparencia`. */
  slug: SlugCategoria
  /** Nombre de la categoría, tal como lo enuncia la norma. */
  nombre: string
}

/** Las nueve categorías, indexadas por su segmento de ruta. */
export const CATEGORIAS_INDICE: Readonly<Record<SlugCategoria, CategoriaIndice>> = {
  'informacion-de-las-entidades': {
    numero: 1,
    slug: 'informacion-de-las-entidades',
    nombre: 'Información de las entidades',
  },
  normativa: { numero: 2, slug: 'normativa', nombre: 'Normativa' },
  contratacion: { numero: 3, slug: 'contratacion', nombre: 'Contratación' },
  'planeacion-presupuesto-e-informes': {
    numero: 4,
    slug: 'planeacion-presupuesto-e-informes',
    nombre: 'Planeación, presupuesto e informes',
  },
  'tramites-y-servicios': {
    numero: 5,
    slug: 'tramites-y-servicios',
    nombre: 'Trámites y servicios',
  },
  participa: { numero: 6, slug: 'participa', nombre: 'Participa' },
  'datos-abiertos': { numero: 7, slug: 'datos-abiertos', nombre: 'Datos abiertos' },
  'grupos-de-interes': {
    numero: 8,
    slug: 'grupos-de-interes',
    nombre: 'Información específica para grupos de interés',
  },
  'informacion-especifica-de-la-entidad': {
    numero: 9,
    slug: 'informacion-especifica-de-la-entidad',
    nombre: 'Información específica de la entidad',
  },
}

/** Las nueve categorías en el orden que fija la norma, no en el alfabético. */
export const ORDEN_CATEGORIAS: readonly SlugCategoria[] = [
  'informacion-de-las-entidades',
  'normativa',
  'contratacion',
  'planeacion-presupuesto-e-informes',
  'tramites-y-servicios',
  'participa',
  'datos-abiertos',
  'grupos-de-interes',
  'informacion-especifica-de-la-entidad',
]

/** La ruta de la página de detalle de una categoría. */
export function rutaDeCategoria(slug: SlugCategoria): string {
  return `/transparencia/${slug}`
}
