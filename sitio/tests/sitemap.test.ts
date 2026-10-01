/**
 * Invariantes de la configuración de rutas (`app/config/sitemap.ts`).
 *
 * Esta es la prueba que faltaba cuando se cometió el error más caro del
 * proyecto: el `sitemap.xml` anunció a los buscadores diez páginas vacías porque
 * el campo `publicada` se puso a `true` a mano (regresión R-03). Aquí se
 * comprueba contra el **disco**: si una ruta dice que publica contenido, tiene
 * que existir la página que la sirve; y lo que no publica no puede anunciarse.
 *
 * Además se fijan las reglas del menú que el expediente escribe con números
 * —CAG-09 (siete ítems, cuatro secciones internas como máximo), Anexo 2 §4.1.2
 * (los tres menús obligatorios y su orden)—, que hasta ahora sólo estaban
 * verificadas a ojo.
 */
import { existsSync, readdirSync, statSync } from 'node:fs'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

import { describe, expect, it } from 'vitest'

import { configRutas, menuPrincipal, todasLasRutas } from '../app/config/sitemap'

const RAIZ = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const PAGINAS = join(RAIZ, 'app/pages')

/**
 * ¿Existe una página que atienda esta ruta?
 *
 * Resuelve los dinámicos (`[slug].vue`, `[...ruta].vue`) porque medio sitio los
 * usa: `/participa/control-ciudadano` y `/politicas/uso-de-cookies` no tienen
 * fichero propio, los sirve un comodín.
 */
function existePagina(ruta: string): boolean {
  const partes = ruta.split('/').filter(Boolean)
  let directorio = PAGINAS

  for (let i = 0; i < partes.length; i += 1) {
    const esUltima = i === partes.length - 1
    if (esUltima && existsSync(join(directorio, `${partes[i]}.vue`))) return true

    const carpeta = join(directorio, partes[i])
    if (existsSync(carpeta) && statSync(carpeta).isDirectory()) {
      directorio = carpeta
      continue
    }

    // Comodín: `[algo].vue` cubre este segmento, y `[...algo].vue` cubre el resto.
    const ficheros = readdirSync(directorio)
    if (ficheros.some((f) => /^\[\.\.\..+\]\.vue$/.test(f))) return true
    if (ficheros.some((f) => /^\[.+\]\.vue$/.test(f))) return esUltima
    return false
  }

  return existsSync(join(directorio, 'index.vue'))
}

describe('el menú principal', () => {
  it('tiene exactamente siete opciones (CAG-09, FUN-012)', () => {
    expect(menuPrincipal).toHaveLength(7)
  })

  it('incluye las tres secciones obligatorias, y antes de las adicionales', () => {
    const etiquetas = menuPrincipal.map((item) => item.etiqueta)
    const transparencia = etiquetas.findIndex((e) => e.includes('Transparencia'))
    const atencion = etiquetas.findIndex((e) => e.includes('Atención y Servicios'))
    const participa = etiquetas.findIndex((e) => e === 'Participa')
    const pqrsd = etiquetas.findIndex((e) => e === 'PQRSD')

    expect(transparencia).toBeGreaterThanOrEqual(0)
    expect(atencion).toBeGreaterThan(transparencia)
    expect(participa).toBeGreaterThan(atencion)
    expect(pqrsd).toBeGreaterThan(participa)
  })

  it('ninguna opción despliega más de cuatro secciones internas (CAG-09)', () => {
    for (const item of menuPrincipal) {
      expect(item.subsecciones?.length ?? 0).toBeLessThanOrEqual(4)
    }
  })

  it('no hay rótulos vacíos ni repetidos', () => {
    const etiquetas = menuPrincipal.map((item) => item.etiqueta)
    expect(etiquetas.every((e) => e.trim().length > 0)).toBe(true)
    expect(new Set(etiquetas).size).toBe(etiquetas.length)
  })

  it('los enlaces de las subsecciones no se repiten', () => {
    const rutas = menuPrincipal.flatMap((item) =>
      (item.subsecciones ?? []).flatMap((sub) => sub.enlaces.map((enlace) => enlace.ruta)),
    )
    expect(new Set(rutas).size).toBe(rutas.length)
  })
})

describe('estado de publicación', () => {
  it('toda ruta marcada como publicada tiene su página en el disco', () => {
    const anunciadasSinPagina = todasLasRutas()
      .filter((ruta) => ruta.publicada)
      .filter((ruta) => !existePagina(ruta.ruta))
      .map((ruta) => ruta.ruta)

    expect(anunciadasSinPagina).toEqual([])
  })

  it('las cinco políticas no se anuncian como publicadas mientras no tengan documento', () => {
    const politicas = todasLasRutas().filter((ruta) => ruta.ruta.startsWith('/politicas/'))

    expect(politicas).toHaveLength(5)
    expect(politicas.every((ruta) => ruta.publicada === false)).toBe(true)
  })

  it('la búsqueda no se anuncia: su propia página declara `noindex`', () => {
    expect(todasLasRutas().find((ruta) => ruta.ruta === '/buscar')?.publicada).toBe(false)
  })

  it('ninguna ruta publicada queda sin prioridad declarada al buscador', () => {
    for (const ruta of todasLasRutas().filter((r) => r.publicada)) {
      expect(ruta.prioridadSitemap).toBeGreaterThan(0)
      expect(ruta.prioridadSitemap).toBeLessThanOrEqual(1)
      expect(ruta.frecuenciaSitemap).toBeTruthy()
    }
  })
})

describe('forma de las rutas', () => {
  it('todas son absolutas, en minúsculas, sin tildes ni espacios (RF-B1-081)', () => {
    for (const ruta of todasLasRutas()) {
      expect(ruta.ruta.startsWith('/')).toBe(true)
      expect(ruta.ruta).toBe(ruta.ruta.toLowerCase())
      expect(ruta.ruta).not.toMatch(/[áéíóúñ\s]/)
      // La portada es `/` y termina en barra: es la única excepción legítima.
      if (ruta.ruta !== '/') expect(ruta.ruta).not.toMatch(/\/$/)
    }
  })

  it('no hay rutas repetidas', () => {
    const rutas = todasLasRutas().map((ruta) => ruta.ruta)
    expect(new Set(rutas).size).toBe(rutas.length)
  })

  it('las secciones del mapa y las sueltas cubren las mismas rutas que el menú', () => {
    const enMenu = new Set(
      menuPrincipal.flatMap((item) => [
        ...(item.ruta ? [item.ruta] : []),
        ...(item.subsecciones ?? []).flatMap((sub) => sub.enlaces.map((e) => e.ruta)),
      ]),
    )
    const enConfig = new Set(todasLasRutas().map((ruta) => ruta.ruta))

    for (const ruta of enMenu) {
      expect(enConfig.has(ruta)).toBe(true)
    }
    expect(configRutas.secciones.length).toBeGreaterThan(0)
  })
})
