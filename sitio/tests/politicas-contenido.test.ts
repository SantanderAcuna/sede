/**
 * Las cinco políticas (T-20, AC-20.2 a AC-20.8).
 *
 * Hoy ninguna tiene documento: Jurídica no lo ha entregado. Esta prueba no
 * inventa texto; fija las reglas que cada documento tendrá que cumplir **cuando
 * llegue**, para que nadie pueda publicar una política a medias, y deja armada la
 * puerta de cierre (G3) que exige las cinco publicadas.
 *
 * La puerta se activa con `EXIGIR_POLITICAS=1` (por ejemplo, en el job de CI del
 * cierre de la fase 2). Sin esa variable, las políticas pendientes no rompen el
 * CI: hacerlo bloquearía todos los PR por algo que TIC no puede entregar sola.
 */
import { describe, expect, it } from 'vitest'

import {
  COMPONENTES_MINIMOS,
  DOCUMENTOS_POLITICAS,
  type DocumentoPolitica,
  politicaPublicada,
  problemasDelDocumento,
} from '../app/config/politicas-contenido'
import { POLITICAS, todasLasRutas } from '../app/config/sitemap'
import { VERSION_POLITICA } from '../app/composables/useConsentimientoCookies'

describe('el catálogo de políticas', () => {
  it('declara contenido y componentes mínimos para las mismas cinco políticas del pie', () => {
    const slugs = POLITICAS.map((p) => p.slug).sort()
    expect(Object.keys(DOCUMENTOS_POLITICAS).sort()).toEqual(slugs)
    expect(Object.keys(COMPONENTES_MINIMOS).sort()).toEqual(slugs)
  })

  it('el sitemap anuncia una política sólo si su documento existe (R-03)', () => {
    for (const politica of POLITICAS) {
      const ruta = todasLasRutas().find((r) => r.ruta === `/politicas/${politica.slug}`)
      expect(ruta?.publicada, politica.slug).toBe(politicaPublicada(politica.slug))
    }
  })
})

describe('un documento publicado cumple lo que la norma exige', () => {
  it('los documentos reales (hoy ninguno) no tienen problemas', () => {
    for (const [slug, documento] of Object.entries(DOCUMENTOS_POLITICAS)) {
      if (documento === null) continue
      expect(problemasDelDocumento(slug, documento), slug).toEqual([])
    }
  })

  it('la política de cookies usa la misma versión que el consentimiento (AC-20.8)', () => {
    const cookies = DOCUMENTOS_POLITICAS['uso-de-cookies']
    if (cookies === null) return // todavía sin documento
    expect(cookies?.version).toBe(VERSION_POLITICA)
  })
})

describe('la comprobación detecta lo que dice detectar (con documentos de ejemplo)', () => {
  const SLUG = 'derechos-de-autor-y-uso-sobre-contenidos'
  const completo = (): DocumentoPolitica => ({
    version: 1,
    vigenteDesde: '2026-01-15',
    acto: { tipo: 'Resolución', numero: '000', fecha: '2026-01-10' },
    secciones: (COMPONENTES_MINIMOS[SLUG] ?? []).map((c) => ({ id: c.id, titulo: c.rotulo, parrafos: ['Texto de ejemplo.'] })),
  })

  it('un documento completo no tiene problemas', () => {
    expect(problemasDelDocumento(SLUG, completo())).toEqual([])
  })

  it('detecta un componente que falta', () => {
    const doc = completo()
    doc.secciones.pop()
    expect(problemasDelDocumento(SLUG, doc)).toEqual([expect.stringContaining('falta')])
  })

  it('detecta un componente vacío', () => {
    const doc = completo()
    doc.secciones[0]!.parrafos = ['  ']
    expect(problemasDelDocumento(SLUG, doc)).toEqual([expect.stringContaining('vacío')])
  })

  it('detecta un acto administrativo sin número ni fecha', () => {
    const doc = completo()
    doc.acto = { tipo: 'Resolución', numero: '', fecha: '' }
    expect(problemasDelDocumento(SLUG, doc)).toHaveLength(2)
  })

  it('detecta una vigencia mal escrita y una versión inválida', () => {
    const doc = completo()
    doc.vigenteDesde = '15/01/2026'
    doc.version = 0
    expect(problemasDelDocumento(SLUG, doc)).toHaveLength(2)
  })

  it('detecta «en preparación» dentro del texto (AC-20.7)', () => {
    const doc = completo()
    doc.secciones[1]!.parrafos = ['Este apartado está En Preparación.']
    expect(problemasDelDocumento(SLUG, doc)).toEqual(['contiene «en preparación»'])
  })
})

describe.skipIf(!process.env.EXIGIR_POLITICAS)('puerta G3: las cinco políticas están publicadas (AC-20.1)', () => {
  it.each(Object.keys(DOCUMENTOS_POLITICAS))('%s tiene su documento', (slug) => {
    expect(DOCUMENTOS_POLITICAS[slug], `«${slug}» sigue sin documento adoptado`).not.toBeNull()
  })
})
