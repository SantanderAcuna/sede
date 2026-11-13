/**
 * Políticas: qué falta para adoptarlas (lista) y de dónde sale cada parte del PDF (formulario).
 *
 * La lista decía «Documento oficial: Falta» en cada fila, como si cada política exigiera un adjunto
 * propio y eso fuera todo. Adoptar una política son tres pasos distintos: texto definitivo, acto
 * administrativo (tipo, número y fecha) y el archivo del acto o del texto aprobado.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { conSesion } from './ayudas/sesion'

vi.mock('../src/services/politicas', () => ({
  COLECCION_DOCUMENTO: 'documento-oficial',
  listar: vi.fn(),
  actualizar: vi.fn(),
  documentosAdjuntos: vi.fn(),
  adjuntarDocumento: vi.fn(),
  quitarArchivo: vi.fn(),
}))

import * as servicio from '../src/services/politicas'
import { pasosDeAdopcion } from '../src/views/admin/politicas-logica'
import PoliticasListaView from '../src/views/admin/PoliticasListaView.vue'
import PoliticaFormularioView from '../src/views/admin/PoliticaFormularioView.vue'
import type { PoliticaPanelItem } from '../src/types/api'

const m = vi.mocked(servicio)

const politica = (extra: Partial<PoliticaPanelItem> = {}): PoliticaPanelItem =>
  ({
    id: 1, type: 'politica', slug: 'terminos-y-condiciones-de-uso', nombre: 'Términos y condiciones de uso', version: 1,
    vigente_desde: null, acto: { tipo: null, numero: null, fecha: null }, es_ejemplo: true, documento_oficial: null,
    actualizada_en: '2026-10-29', secciones: [{ id: 'a', titulo: 'A', parrafos: ['x'] }],
    uuid: '11111111-1111-4111-8111-111111111111', componentes: { a: 'A' }, completa: true, problemas: [], ...extra,
  }) as PoliticaPanelItem

const router = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/configuracion/politicas', name: 'panel.politicas', component: { template: '<i/>' } },
      { path: '/configuracion/politicas/:slug', name: 'panel.politicas.editar', component: { template: '<i/>' } },
      { path: '/configuracion/entidad', name: 'panel.entidad', component: { template: '<i/>' } },
    ],
  })

beforeEach(() => {
  conSesion()
  vi.clearAllMocks()
  m.listar.mockResolvedValue([politica()])
  m.documentosAdjuntos.mockResolvedValue([])
})

describe('pasosDeAdopcion', () => {
  it('con texto de ejemplo, sin acto y sin archivo faltan los tres pasos', () => {
    const pasos = pasosDeAdopcion(politica())
    expect(pasos.map((p) => p.id)).toEqual(['texto', 'acto', 'archivo'])
    expect(pasos.every((p) => !p.hecho)).toBe(true)
  })

  it('el acto solo cuenta cuando tiene tipo, número y fecha', () => {
    const parcial = pasosDeAdopcion(politica({ acto: { tipo: 'Resolución', numero: '12', fecha: null } }))
    expect(parcial.find((p) => p.id === 'acto')?.hecho).toBe(false)
    const completo = pasosDeAdopcion(politica({ acto: { tipo: 'Resolución', numero: '12', fecha: '2026-11-01' } }))
    expect(completo.find((p) => p.id === 'acto')?.hecho).toBe(true)
  })

  it('texto definitivo = ya no es de ejemplo; archivo = hay documento oficial', () => {
    const pasos = pasosDeAdopcion(politica({ es_ejemplo: false, documento_oficial: { url: '/x.pdf', nombre: 'x.pdf' } as never }))
    expect(pasos.find((p) => p.id === 'texto')?.hecho).toBe(true)
    expect(pasos.find((p) => p.id === 'archivo')?.hecho).toBe(true)
  })
})

describe('lista: qué falta para adoptar cada política', () => {
  async function lista() {
    const r = router(); await r.push('/configuracion/politicas'); await r.isReady()
    const w = mount(PoliticasListaView, { global: { plugins: [r], stubs: { FaIcon: true } } })
    await flushPromises()
    return w
  }

  it('la columna ya no dice «Documento oficial» sino «Para adoptarla» y lista los tres pasos', async () => {
    const w = await lista()
    const th = w.findAll('th').map((t) => t.text())
    expect(th.some((t) => t.includes('Documento oficial'))).toBe(false)
    expect(th.some((t) => t.includes('Para adoptarla'))).toBe(true)
    const fila = w.find('tbody tr').text()
    expect(fila).toContain('Texto definitivo')
    expect(fila).toContain('Acto administrativo')
    expect(fila).toContain('Archivo del acto')
    expect(fila).toContain('0 de 3')
  })

  it('cada paso se dice con palabras (Listo / Falta), no solo con color', async () => {
    m.listar.mockResolvedValue([politica({ es_ejemplo: false })])
    const w = await lista()
    const fila = w.find('tbody tr').text()
    expect(fila).toContain('Listo')
    expect(fila).toContain('Falta')
    expect(fila).toContain('1 de 3')
  })

  it('cada fila ofrece ver el PDF de la política', async () => {
    const w = await lista()
    const enlace = w.find('a[href="/politicas/terminos-y-condiciones-de-uso.pdf"]')
    expect(enlace.exists()).toBe(true)
    expect(enlace.attributes('aria-label')).toContain('Términos y condiciones de uso')
  })
})

describe('formulario: de dónde sale lo que se imprime en el PDF', () => {
  async function formulario() {
    const r = router(); await r.push('/configuracion/politicas/terminos-y-condiciones-de-uso'); await r.isReady()
    const w = mount(PoliticaFormularioView, { global: { plugins: [r], stubs: { FaIcon: true, teleport: true } } })
    await flushPromises()
    return w
  }

  it('hay una guía «Qué se imprime en el PDF» con enlaces a cada bloque de la pantalla', async () => {
    const w = await formulario()
    const guia = w.find('[data-guia-pdf]')
    expect(guia.exists()).toBe(true)
    expect(guia.text()).toContain('Qué se imprime en el PDF')
    for (const ancla of ['#politica-datos', '#politica-apartados', '#politica-documento']) {
      expect(guia.find(`a[href="${ancla}"]`).exists()).toBe(true)
    }
  })

  it('dice que la Entidad del encabezado es fija y que el archivo adjunto no se imprime en el PDF', async () => {
    const w = await formulario()
    const t = w.find('[data-guia-pdf]').text()
    expect(t).toContain('Alcaldía Distrital de Santa Marta')
    expect(t).toMatch(/adjunto.*no se imprime|no se imprime.*adjunto/is)
  })

  it('ofrece abrir el PDF actual y aclara que muestra lo ya guardado', async () => {
    const w = await formulario()
    const enlace = w.find('[data-guia-pdf] a[href="/politicas/terminos-y-condiciones-de-uso.pdf"]')
    expect(enlace.exists()).toBe(true)
    expect(enlace.attributes('target')).toBe('_blank')
    expect(w.find('[data-guia-pdf]').text()).toContain('guardado')
  })
})
