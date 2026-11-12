/**
 * A dónde lleva una tarjeta de trámite.
 *
 * La ficha completa vive en GOV.CO (registro nacional de trámites): si el trámite guarda su
 * `url_ficha_gov_co`, la tarjeta lleva allí. Sin ella (p. ej. las consultas, que no exigen ficha
 * nacional) se queda en la ficha propia de la Sede, para no dejar un enlace roto.
 *
 * La URL viene de un campo editable en el panel, así que no se confía en ella: solo se acepta
 * `https` y un host que sea `gov.co` o un subdominio suyo. Cualquier otra cosa (otro dominio,
 * `http`, `javascript:`, un host que solo *contiene* «gov.co») cae a la ficha propia.
 */
export interface EnlaceTramite {
  href: string
  externo: boolean
}

interface TramiteEnlazable {
  slug: string
  url_ficha_gov_co?: string | null
}

function esUrlDeGovCo(valor: string): boolean {
  try {
    const url = new URL(valor)
    return url.protocol === 'https:' && (url.hostname === 'gov.co' || url.hostname.endsWith('.gov.co'))
  } catch {
    return false
  }
}

export function enlaceTramite(tramite: TramiteEnlazable): EnlaceTramite {
  const url = tramite.url_ficha_gov_co?.trim() ?? ''
  if (url !== '' && esUrlDeGovCo(url)) {
    return { href: url, externo: true }
  }
  return { href: `/tramites/${tramite.slug}`, externo: false }
}
