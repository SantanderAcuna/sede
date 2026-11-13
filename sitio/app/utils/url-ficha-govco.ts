/**
 * Enlace a la ficha oficial de un trámite en GOV.CO, p. ej.
 * `https://www.gov.co/ficha-tramites-y-servicios/T41040`.
 *
 * Es el destino de las normas de la ficha: la descarga de SUIT (`tramites1.suit.gov.co`) depende de
 * un servidor ajeno que a veces responde 502. Se usa la URL que trae el trámite si es https de
 * `gov.co`; si falta, se construye con el código (`T` + dígitos). Si ninguna de las dos es fiable,
 * no hay enlace: no se inventa.
 */
const BASE = 'https://www.gov.co/ficha-tramites-y-servicios/'

export interface DatosFichaGovCo {
  url_ficha_gov_co?: string | null
  codigo?: string | null
}

function esUrlGovCo(valor: string): boolean {
  try {
    const u = new URL(valor)
    return u.protocol === 'https:' && (u.hostname === 'gov.co' || u.hostname.endsWith('.gov.co'))
  }
  catch {
    return false
  }
}

export function urlFichaGovCo(tramite: DatosFichaGovCo): string | null {
  const propia = tramite.url_ficha_gov_co?.trim()
  if (propia && esUrlGovCo(propia)) return propia

  const codigo = tramite.codigo?.trim()
  return codigo && /^T\d+$/.test(codigo) ? `${BASE}${codigo}` : null
}
