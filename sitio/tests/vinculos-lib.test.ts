/**
 * Verificador de vínculos: lógica pura (M01-2, RF-01-012).
 *
 * Criterios de aceptación que fijan estas pruebas (versión vigente de RF-01-012 en
 * `criterios-aceptacion-fuente-de-verdad.md`):
 *
 *  - AC-2.1  Un vínculo INTERNO está roto si responde ≥ 400 o no responde. 2xx y 3xx no.
 *  - AC-2.2  Los vínculos EXTERNOS se verifican y se REPORTAN aparte: nunca hacen fallar la
 *            verificación (su disponibilidad no depende de la Sede).
 *  - AC-2.3  Un externo se considera con problema sólo si falla también al reintentar: un 503
 *            pasajero o un servidor que no admite HEAD no es un vínculo roto.
 *  - AC-2.4  La verificación de externos respeta un tope de concurrencia y no revienta ante
 *            un tercero que no responde.
 *  - AC-2.5  El código de salida depende SÓLO de los internos rotos.
 *  - AC-2.6  El informe dice cuántas páginas se comprobaron, lista cada vínculo roto con la
 *            página que lo enlaza, y separa los externos (que no bloquean).
 *  - AC-2.8  Una página interna que falla por algo PASAJERO (429, 502, 503, 504 o sin respuesta)
 *            se reintenta tras una pausa antes de darla por rota; un 404 o un 500 no se
 *            reintentan. Medido en staging: la sede responde 503 a propósito cuando la API no
 *            contesta, y un rastreo seguido desde una IP agota el límite de 60 peticiones por
 *            minuto de la API; sin esta regla el verificador reportaría vínculos «rotos» que
 *            funcionan.
 *  - AC-2.9  Si la PORTADA (el punto de partida) no responde bien, el informe NO dice «0 rotos» ni
 *            culpa a un vínculo: dice que no se pudo iniciar la verificación, con el estado y una
 *            pista del bloqueo (p. ej. `cf-mitigated`). Medido: desde los ejecutores de GitHub el
 *            borde respondió 403 a la portada y el informe lo presentó como «1 vínculo roto».
 *  - AC-2.7  La extracción de enlaces separa internos de externos, ignora `mailto:`, `tel:`,
 *            `javascript:`, anclas, comentarios HTML y `/cdn-cgi/` (lo inyecta Cloudflare).
 */
import { describe, expect, it, vi } from 'vitest'

import {
  codigoDeSalida,
  comprobarConReintentos,
  construirInforme,
  esRoto,
  esTransitorio,
  extraerEnlaces,
  pistaDeRespuesta,
  verificarExternos,
} from '../scripts/lib/vinculos.mjs'

const BASE = 'https://staging.santamarta.gov.co'

describe('esRoto (AC-2.1)', () => {
  it.each([[400], [403], [404], [410], [500], [503], [0]])('el estado %i está roto', (estado) => {
    expect(esRoto(estado)).toBe(true)
  })
  it.each([[200], [204], [301], [302], [304]])('el estado %i no está roto', (estado) => {
    expect(esRoto(estado)).toBe(false)
  })
})

describe('extraerEnlaces (AC-2.7)', () => {
  const html = `
    <a href="/tramites">interno relativo</a>
    <a href="${BASE}/politicas/cookies">interno absoluto</a>
    <a href="/buscar?q=licencia#resultados">con consulta y ancla</a>
    <a href="/tramites/">con barra final</a>
    <a href="https://www.gov.co/home/">externo</a>
    <a href="//www.example.org/x">externo sin protocolo</a>
    <a href="mailto:alguien@santamarta.gov.co">correo</a>
    <a href="tel:6054351719">teléfono</a>
    <a href="javascript:void(0)">js</a>
    <a href="#contenido">ancla</a>
    <a href="/cdn-cgi/l/email-protection">cloudflare</a>
    <a href="/documentos/guia.pdf">descarga</a>
    <!-- <a href="/comentado">no cuenta</a> -->
    <a href="https://www.gov.co/home/#otro">externo repetido con ancla</a>
  `
  const { internos, externos } = extraerEnlaces(html, BASE)

  it('recoge los internos normalizados y sin repetir', () => {
    expect([...internos].sort()).toEqual(['/buscar', '/politicas/cookies', '/tramites'])
  })

  it('recoge los externos sin repetir y sin ancla', () => {
    expect([...externos].sort()).toEqual(['https://www.example.org/x', 'https://www.gov.co/home/'])
  })

  it('no recoge esquemas que no son navegación ni comentarios', () => {
    const todos = [...internos, ...externos].join(' ')
    expect(todos).not.toMatch(/mailto|tel:|javascript|cdn-cgi|comentado/)
  })

  it('las descargas no se siguen como páginas', () => {
    expect([...internos].some((r) => r.endsWith('.pdf'))).toBe(false)
  })
})

describe('verificarExternos (AC-2.2, AC-2.3, AC-2.4)', () => {
  const respuestas = (mapa: Record<string, number | Error | Array<number | Error>>) => {
    const llamadas: Array<{ url: string; metodo: string }> = []
    const cola = new Map(Object.entries(mapa).map(([u, v]) => [u, Array.isArray(v) ? [...v] : [v]]))
    const pedir = vi.fn(async (url: string, metodo: string) => {
      llamadas.push({ url, metodo })
      const pendientes = cola.get(url) ?? [404]
      const siguiente = pendientes.length > 1 ? pendientes.shift()! : pendientes[0]!
      if (siguiente instanceof Error) throw siguiente
      return siguiente
    })
    return { pedir, llamadas }
  }

  it('devuelve sólo los externos con problema', async () => {
    const { pedir } = respuestas({ 'https://a.gov.co/': 200, 'https://b.gov.co/': 404 })
    const problemas = await verificarExternos(['https://a.gov.co/', 'https://b.gov.co/'], { pedir, esperaMs: 0 })
    expect(problemas).toEqual([{ url: 'https://b.gov.co/', estado: 404 }])
  })

  it('un 503 pasajero que se recupera al reintentar no es un problema', async () => {
    const { pedir } = respuestas({ 'https://a.gov.co/': [503, 200] })
    expect(await verificarExternos(['https://a.gov.co/'], { pedir, esperaMs: 0 })).toEqual([])
  })

  it('si el servidor no admite HEAD, reintenta con GET', async () => {
    const llamadas: string[] = []
    const pedir = vi.fn(async (_url: string, metodo: string) => {
      llamadas.push(metodo)
      return metodo === 'HEAD' ? 405 : 200
    })
    expect(await verificarExternos(['https://a.gov.co/'], { pedir, esperaMs: 0 })).toEqual([])
    expect(llamadas).toEqual(['HEAD', 'GET'])
  })

  it('un tercero que no responde queda como problema con estado 0 y el motivo, sin lanzar', async () => {
    const { pedir } = respuestas({ 'https://caido.gov.co/': new Error('timeout') })
    const problemas = await verificarExternos(['https://caido.gov.co/'], { pedir, esperaMs: 0 })
    expect(problemas).toEqual([{ url: 'https://caido.gov.co/', estado: 0, error: 'timeout' }])
  })

  it('no supera la concurrencia pedida', async () => {
    let enVuelo = 0
    let maximo = 0
    const pedir = vi.fn(async () => {
      enVuelo += 1
      maximo = Math.max(maximo, enVuelo)
      await new Promise((r) => setTimeout(r, 5))
      enVuelo -= 1
      return 200
    })
    const urls = Array.from({ length: 20 }, (_, i) => `https://h${i}.gov.co/`)
    await verificarExternos(urls, { pedir, concurrencia: 3, esperaMs: 0 })
    expect(maximo).toBeLessThanOrEqual(3)
    expect(pedir.mock.calls.length).toBeGreaterThanOrEqual(20)
  })
})

describe('codigoDeSalida (AC-2.5)', () => {
  it('es 0 sin internos rotos aunque haya externos con problema', () => {
    expect(codigoDeSalida([])).toBe(0)
  })
  it('es 1 con al menos un interno roto', () => {
    expect(codigoDeSalida([{ ruta: '/x', estado: 404 }])).toBe(1)
  })
})

describe('construirInforme (AC-2.6)', () => {
  it('sin problemas dice 0 rotos y cuántas páginas se comprobaron', () => {
    const texto = construirInforme({ base: BASE, paginas: 140, internosRotos: [], externos: { comprobados: 4, problemas: [] } })
    expect(texto).toContain('140 páginas')
    expect(texto).toMatch(/0 vínculos internos rotos/)
    expect(texto).toContain(BASE)
  })

  it('lista cada interno roto con su estado y la página que lo enlaza', () => {
    const texto = construirInforme({
      base: BASE,
      paginas: 10,
      internosRotos: [{ ruta: '/politicas/viejo', estado: 404, origen: '/' }],
      externos: { comprobados: 0, problemas: [] },
    })
    expect(texto).toMatch(/1 vínculo interno roto/)
    expect(texto).toContain('/politicas/viejo')
    expect(texto).toContain('404')
    expect(texto).toContain('(enlazada desde /)')
  })

  it('separa los externos con problema y dice que no bloquean', () => {
    const texto = construirInforme({
      base: BASE,
      paginas: 10,
      internosRotos: [],
      externos: { comprobados: 3, problemas: [{ url: 'https://b.gov.co/', estado: 404 }, { url: 'https://c.gov.co/', estado: 0, error: 'timeout' }] },
    })
    expect(texto).toMatch(/externos/i)
    expect(texto).toContain('https://b.gov.co/')
    expect(texto).toContain('timeout')
    expect(texto).toMatch(/no bloquea/i)
  })

  it('si no se verificaron externos lo dice, no finge 0 problemas', () => {
    const texto = construirInforme({ base: BASE, paginas: 10, internosRotos: [], externos: undefined })
    expect(texto).toMatch(/externos: no se verificaron/i)
  })
})

describe('esTransitorio y comprobarConReintentos (AC-2.8)', () => {
  it.each([[0], [429], [502], [503], [504]])('el estado %i es pasajero', (estado) => {
    expect(esTransitorio(estado)).toBe(true)
  })
  it.each([[200], [301], [404], [410], [500]])('el estado %i no es pasajero', (estado) => {
    expect(esTransitorio(estado)).toBe(false)
  })

  const secuencia = (estados: number[]) => {
    let n = 0
    return vi.fn(async () => ({ estado: estados[Math.min(n++, estados.length - 1)]!, cuerpo: '' }))
  }

  it('un 503 que se recupera al reintentar devuelve el resultado bueno', async () => {
    const pedir = secuencia([503, 200])
    const r = await comprobarConReintentos(pedir, { esperasMs: [0, 0] })
    expect(r.estado).toBe(200)
    expect(pedir).toHaveBeenCalledTimes(2)
  })

  it('un 503 persistente se da por roto tras agotar los reintentos (1 intento + 2)', async () => {
    const pedir = secuencia([503])
    const r = await comprobarConReintentos(pedir, { esperasMs: [0, 0] })
    expect(r.estado).toBe(503)
    expect(pedir).toHaveBeenCalledTimes(3)
  })

  it.each([[404], [500]])('un %i no se reintenta', async (estado) => {
    const pedir = secuencia([estado])
    const r = await comprobarConReintentos(pedir, { esperasMs: [0, 0] })
    expect(r.estado).toBe(estado)
    expect(pedir).toHaveBeenCalledTimes(1)
  })

  it('espera entre intentos lo indicado', async () => {
    vi.useFakeTimers()
    try {
      const pedir = secuencia([429, 429, 200])
      const promesa = comprobarConReintentos(pedir, { esperasMs: [1000, 5000] })
      await vi.advanceTimersByTimeAsync(0)
      expect(pedir).toHaveBeenCalledTimes(1)
      await vi.advanceTimersByTimeAsync(1000)
      expect(pedir).toHaveBeenCalledTimes(2)
      await vi.advanceTimersByTimeAsync(5000)
      expect(pedir).toHaveBeenCalledTimes(3)
      expect((await promesa).estado).toBe(200)
    } finally {
      vi.useRealTimers()
    }
  })
})

describe('pistaDeRespuesta (AC-2.9)', () => {
  const encabezados = (mapa: Record<string, string>) => ({ get: (n: string) => mapa[n.toLowerCase()] ?? null })

  it('recoge el motivo del borde cuando lo declara', () => {
    const pista = pistaDeRespuesta(
      encabezados({ 'cf-mitigated': 'challenge', server: 'cloudflare', 'cf-ray': 'abc123-MIA' }),
      '<html><title>Just a moment...</title></html>',
    )
    expect(pista).toContain('cf-mitigated=challenge')
    expect(pista).toContain('server=cloudflare')
    expect(pista).toContain('Just a moment...')
  })

  it('sin pistas devuelve texto vacío', () => {
    expect(pistaDeRespuesta(encabezados({}), '')).toBe('')
  })

  it('recorta el cuerpo: no vuelca una página entera en el informe', () => {
    const pista = pistaDeRespuesta(encabezados({}), `<p>${'x'.repeat(5000)}</p>`)
    expect(pista.length).toBeLessThanOrEqual(200)
  })
})

describe('construirInforme con la portada caída (AC-2.9)', () => {
  const texto = construirInforme({
    base: BASE,
    paginas: 1,
    internosRotos: [{ ruta: '/', estado: 403 }],
    externos: undefined,
    inicio: { ruta: '/', estado: 403, pista: 'cf-mitigated=challenge; server=cloudflare' },
  })

  it('dice que no se pudo iniciar y no habla de vínculos rotos de la Sede', () => {
    expect(texto).toMatch(/No se pudo iniciar la verificación/)
    expect(texto).toContain('403')
    expect(texto).toContain('cf-mitigated=challenge')
    expect(texto).not.toMatch(/\d+ vínculos? internos? rotos?/)
  })
})
