/**
 * Miga de pan de la cabecera.
 *
 * El nombre de cada nivel sale del segmento de la URL, y el último, del título de la
 * ruta. Un nivel intermedio sólo se enlaza si esa dirección es una ruta de verdad:
 * `/contenidos` no lo es, y enlazarla llevaba a «Página no encontrada».
 */
export interface Miga {
  label: string
  /** `null` = no es una página: se muestra como texto. */
  ruta: string | null
  esActual: boolean
}

export function construirMigas(path: string, titulo: string | undefined, existe: (ruta: string) => boolean): Miga[] {
  const segmentos = path.split('/').filter(Boolean)

  return segmentos.map((segmento, indice) => {
    const esActual = indice === segmentos.length - 1
    const ruta = '/' + segmentos.slice(0, indice + 1).join('/')
    return {
      label:
        esActual && titulo
          ? titulo
          : segmento.charAt(0).toUpperCase() + segmento.slice(1).replace(/-/g, ' '),
      ruta: esActual || existe(ruta) ? ruta : null,
      esActual,
    }
  })
}
