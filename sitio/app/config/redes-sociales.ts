/**
 * Redes sociales del pie de página.
 *
 * La fuente es la API (`/identidad/footer`): una sola lista, editable desde el
 * panel. Este módulo la traduce a lo que dibuja el pie —nombre, usuario e icono
 * del Kit— y define el respaldo que se usa si la API no responde, para que el
 * pie nunca aparezca sin sus redes por una caída del servicio.
 *
 * Qué entra y qué no:
 *  - Sólo redes conocidas, porque cada una necesita un icono que exista de verdad
 *    en el bundle del Kit; una desconocida se descarta en lugar de dibujar un hueco.
 *  - Sólo URL `https:`; cualquier otra cosa (`javascript:`, `http:`, texto suelto)
 *    se descarta. El pie no debe poder enlazar a donde el origen de datos no
 *    garantice.
 *  - Sin repetir red: gana la primera.
 */

export type IconoSocial = 'govco-facebook-f' | 'govco-instagram' | 'govco-twitter' | 'govco-youtube'

export interface RedSocial {
  red: string
  usuario: string
  url: string
  icono: IconoSocial
}

/** Lo que la API entrega por red (`FooterResource.redes`). */
export interface RedDeApi {
  red: string
  url: string
}

const CATALOGO: Record<string, { nombre: string; icono: IconoSocial }> = {
  facebook: { nombre: 'Facebook', icono: 'govco-facebook-f' },
  instagram: { nombre: 'Instagram', icono: 'govco-instagram' },
  // `govco-twitter` es el icono que trae el ejemplo del Kit; existe también
  // `govco-twitter-x`, pero se respeta el que el Kit publica.
  x: { nombre: 'X (antes Twitter)', icono: 'govco-twitter' },
  youtube: { nombre: 'YouTube', icono: 'govco-youtube' },
}

/** El usuario que se muestra: el último tramo de la ruta, sin «@» ni codificación. */
function usuarioDe(url: URL): string {
  const tramo = url.pathname.split('/').filter(Boolean).pop() ?? url.hostname
  let texto = tramo
  try {
    texto = decodeURIComponent(tramo)
  } catch {
    // Codificación rota: se muestra tal cual en lugar de fallar.
  }
  return texto.replace(/^@/, '')
}

export function redesDesdeApi(redes: readonly RedDeApi[] | null | undefined): RedSocial[] {
  const resultado: RedSocial[] = []
  const vistas = new Set<string>()

  for (const entrada of redes ?? []) {
    const clave = String(entrada?.red ?? '').trim().toLowerCase()
    const catalogo = CATALOGO[clave]
    if (!catalogo || vistas.has(clave)) continue

    let url: URL
    try {
      url = new URL(String(entrada.url ?? ''))
    } catch {
      continue
    }
    if (url.protocol !== 'https:') continue

    vistas.add(clave)
    resultado.push({ red: catalogo.nombre, usuario: usuarioDe(url), url: url.href, icono: catalogo.icono })
  }

  return resultado
}

/**
 * Respaldo si la API no responde o devuelve una lista vacía. Son las cuentas que
 * publica www.santamarta.gov.co; deben coincidir con `EntidadSeeder` (una prueba
 * de backend lo comprueba).
 */
export const REDES_RESPALDO: RedSocial[] = redesDesdeApi([
  { red: 'facebook', url: 'https://www.facebook.com/SantaMartaDTCH' },
  { red: 'instagram', url: 'https://www.instagram.com/santamartadtch' },
  { red: 'x', url: 'https://x.com/SantaMartaDTCH' },
  { red: 'youtube', url: 'https://www.youtube.com/@Alcald%C3%ADadeSantaMartaDTCH' },
])
