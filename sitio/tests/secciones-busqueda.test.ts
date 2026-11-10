/**
 * Secciones de la sede en el buscador (M01-3 · RF-01-009, CA-01-009).
 *
 * Las páginas de la sede (Transparencia, PQRSD, Normativa…) las lista el propio sitio
 * a partir de `config/sitemap.ts`, que es la fuente única de rutas. Estas pruebas fijan
 * cuándo una sección «calza» con lo que escribe la persona:
 *
 *  - **Sólo las publicadas.** Una página «en preparación» no se ofrece en la búsqueda
 *    (decisión de José, 2026-11-10): coincide con lo que hace el `sitemap.xml`.
 *  - **Tolerancia de una letra** (RF-01-009: ≤ 1 de distancia de Levenshtein) y de lo
 *    que se escribe a medias («transparenci»), sin tildes ni mayúsculas.
 *  - **Sin ruido.** Las palabras cortas no se exigen (como en la API) y la tolerancia a
 *    una letra no se aplica a palabras de menos de 5 letras.
 */
import { describe, expect, it } from 'vitest'

import { menuPrincipal, todasLasRutas, type RutaPublica } from '../app/config/sitemap'
import {
  buscarSecciones,
  crearIndiceDeSecciones,
  distanciaDeEdicion,
  indiceDelSitio,
  normalizarTexto,
  seccionesPrincipales,
} from '../app/composables/seccionesBusqueda'

function ruta(ruta: string, etiqueta: string, publicada: boolean, etiquetaMenu?: string): RutaPublica {
  return { ruta, etiqueta, etiquetaMenu, publicada, orden: 0 }
}

/** Un sitio de juguete con Transparencia ya publicada, para fijar la regla de calce. */
const RUTAS = [
  ruta('/', 'Sede Electrónica', true, 'Inicio'),
  ruta('/transparencia', 'Transparencia y acceso a la información pública', true),
  ruta('/atencion', 'Canales de atención y sedes', true, 'Canales de atención'),
  ruta('/tramites', 'Trámites y servicios', true),
  ruta('/pqrsd', 'Peticiones, quejas, reclamos, sugerencias y denuncias', true, 'PQRSD'),
  ruta('/realizar-una-peticion', 'Realizar una petición', true),
  ruta('/normativa', 'Normativa', true),
  ruta('/noticias', 'Noticias', false),
  ruta('/buscar', 'Buscar en la Sede', true),
  ruta('/participa/control-ciudadano', 'Control ciudadano', false),
]

const indice = crearIndiceDeSecciones(RUTAS)
const rutasDe = (consulta: string, limite?: number) => buscarSecciones(indice, consulta, limite).map((s) => s.ruta)

describe('normalizarTexto y distanciaDeEdicion', () => {
  it('quita tildes y mayúsculas', () => {
    expect(normalizarTexto('Atención')).toBe('atencion')
    expect(normalizarTexto('TRANSPARENCIA')).toBe('transparencia')
    expect(normalizarTexto('Peticiones, quejas')).toBe('peticiones, quejas')
  })

  it('cuenta inserciones, borrados y cambios de una letra', () => {
    expect(distanciaDeEdicion('transparencia', 'transparencia')).toBe(0)
    expect(distanciaDeEdicion('transparencia', 'transparensia')).toBe(1)
    expect(distanciaDeEdicion('transparencia', 'transparecia')).toBe(1)
    expect(distanciaDeEdicion('transparencia', 'transparenccia')).toBe(1)
    expect(distanciaDeEdicion('', 'abc')).toBe(3)
    expect(distanciaDeEdicion('pqrsd', 'pqrds')).toBe(2)
  })
})

describe('crearIndiceDeSecciones: sólo lo que publica contenido', () => {
  it('deja fuera las rutas en preparación y la propia página de búsqueda', () => {
    expect(indice.map((s) => s.ruta)).toEqual([
      '/',
      '/transparencia',
      '/atencion',
      '/tramites',
      '/pqrsd',
      '/realizar-una-peticion',
      '/normativa',
    ])
  })

  it('una página en preparación no se encuentra ni escribiendo su nombre exacto', () => {
    expect(rutasDe('Noticias')).toEqual([])
    expect(rutasDe('control ciudadano')).toEqual([])
  })
})

describe('buscarSecciones (CA-01-009, escenario «cobertura del índice»)', () => {
  it('«transparencia» devuelve la página homónima como primer resultado', () => {
    expect(rutasDe('transparencia')[0]).toBe('/transparencia')
  })

  it('con la palabra a medias («transparenci») también la encuentra', () => {
    expect(rutasDe('transparenci')).toEqual(['/transparencia'])
  })

  it('tolera una errata de una letra («transparensia»)', () => {
    expect(rutasDe('transparensia')).toEqual(['/transparencia'])
  })

  it('tolera una errata en una palabra escrita a medias («transparensi»)', () => {
    expect(rutasDe('transparensi')).toEqual(['/transparencia'])
  })

  it('no distingue tildes ni mayúsculas', () => {
    expect(rutasDe('ATENCIÓN')).toEqual(['/atencion'])
    expect(rutasDe('atencion')).toEqual(['/atencion'])
    expect(rutasDe('Trámites')).toEqual(['/tramites'])
  })

  it('no acepta dos erratas', () => {
    expect(rutasDe('transparanzia')).toEqual([])
  })

  it('una sigla sale por el rótulo corto del menú', () => {
    expect(rutasDe('pqrsd')).toEqual(['/pqrsd'])
    expect(rutasDe('pqr')).toEqual(['/pqrsd'])
  })

  it('con varias palabras deben calzar todas las que cuentan', () => {
    expect(rutasDe('realizar peticion')).toEqual(['/realizar-una-peticion'])
    expect(rutasDe('canales de atencion')).toEqual(['/atencion'])
    // «de», «la», «una» no distinguen nada: no se exigen (la API tampoco las exige).
    expect(rutasDe('una peticion').sort()).toEqual(['/pqrsd', '/realizar-una-peticion'])
  })

  it('la palabra exacta va antes que la que sólo empieza igual', () => {
    expect(rutasDe('peticion')).toEqual(['/realizar-una-peticion', '/pqrsd'])
  })

  it('sin coincidencia, vacío', () => {
    expect(rutasDe('xyzxyz')).toEqual([])
    expect(rutasDe('licencia de conduccion')).toEqual([])
  })

  it('con menos de 3 caracteres no hay nada que buscar', () => {
    expect(rutasDe('')).toEqual([])
    expect(rutasDe('  ')).toEqual([])
    expect(rutasDe('tr')).toEqual([])
    expect(rutasDe('de la')).toEqual([])
  })

  it('la tolerancia de una letra no se aplica a palabras de menos de 5 letras', () => {
    // «noma» está a una letra de «norma» pero con 4 letras se parece a demasiadas cosas.
    expect(rutasDe('noma')).toEqual([])
    expect(rutasDe('norma')).toEqual(['/normativa'])
  })

  it('respeta el límite pedido', () => {
    expect(rutasDe('ser', 1).length).toBeLessThanOrEqual(1)
    expect(rutasDe('tra', 1)).toHaveLength(1)
  })

  it('cada resultado trae la etiqueta completa de la ruta', () => {
    expect(buscarSecciones(indice, 'pqrsd')).toEqual([
      { ruta: '/pqrsd', etiqueta: 'Peticiones, quejas, reclamos, sugerencias y denuncias' },
    ])
  })
})

describe('con la configuración real del sitio', () => {
  const real = indiceDelSitio()
  const rutas = todasLasRutas()
  const publicadas = new Set(rutas.filter((r) => r.publicada).map((r) => r.ruta))

  it('el índice es exactamente lo publicado, sin /buscar', () => {
    expect(real.map((s) => s.ruta).sort()).toEqual([...publicadas].filter((r) => r !== '/buscar').sort())
  })

  it('buscando el nombre de cada página publicada, se la encuentra', () => {
    for (const r of rutas.filter((x) => x.publicada && x.ruta !== '/buscar')) {
      const encontradas = buscarSecciones(real, r.etiqueta).map((s) => s.ruta)
      expect(encontradas, `«${r.etiqueta}»`).toContain(r.ruta)
    }
  })

  it('nunca devuelve una página que no publica contenido', () => {
    for (const r of rutas) {
      for (const hallada of buscarSecciones(real, r.etiqueta)) {
        expect(publicadas.has(hallada.ruta), `${hallada.ruta} por «${r.etiqueta}»`).toBe(true)
      }
    }
  })

  it('encuentra las secciones que hoy publican (PQRSD, normativa, atención)', () => {
    expect(buscarSecciones(real, 'pqrsd').map((s) => s.ruta)).toContain('/pqrsd')
    expect(buscarSecciones(real, 'normativa').map((s) => s.ruta)).toContain('/normativa')
    expect(buscarSecciones(real, 'atencion').map((s) => s.ruta)).toContain('/atencion')
  })
})

describe('seccionesPrincipales (enlaces de «sin resultados», CA-01-009)', () => {
  const principales = seccionesPrincipales()
  const publicadas = new Set(todasLasRutas().filter((r) => r.publicada).map((r) => r.ruta))

  it('son páginas publicadas del menú principal, sin la portada, en el orden del menú', () => {
    expect(principales.length).toBeGreaterThanOrEqual(3)
    expect(principales.every((s) => publicadas.has(s.ruta))).toBe(true)
    expect(principales.map((s) => s.ruta)).not.toContain('/')

    const delMenu = menuPrincipal.flatMap((item) => [
      ...(item.ruta ? [item.ruta] : []),
      ...(item.subsecciones ?? []).flatMap((sub) => sub.enlaces.map((e) => e.ruta)),
    ])
    const posiciones = principales.map((s) => delMenu.indexOf(s.ruta))
    expect(posiciones.every((p) => p >= 0)).toBe(true)
    expect(posiciones).toEqual([...posiciones].sort((a, b) => a - b))
  })

  it('incluye trámites, PQRSD y normativa, que hoy publican contenido', () => {
    const rutasPrincipales = principales.map((s) => s.ruta)
    expect(rutasPrincipales).toEqual(expect.arrayContaining(['/tramites', '/pqrsd', '/normativa']))
  })

  it('cada enlace trae un rótulo legible y una ruta interna', () => {
    for (const seccion of principales) {
      expect(seccion.etiqueta.trim()).not.toBe('')
      expect(seccion.ruta.startsWith('/')).toBe(true)
    }
  })
})
