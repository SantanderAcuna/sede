/**
 * Consentimiento de cookies (RF-B1-008, RF-01-D01, RN-01-D01).
 *
 * Lo que se prueba aquí no es que el banner se pinte —eso lo cubre la puerta de
 * accesibilidad sobre el sitio construido— sino **la validez del
 * consentimiento**, que es donde estaban los dos requisitos que un banner
 * corriente incumple: que caduque y que se vuelva a pedir cuando cambia la
 * política. También se prueba la revocación, que es la mitad de RF-B1-008 y la
 * que no se ve en una captura de pantalla.
 */
import { beforeEach, describe, expect, it } from 'vitest'

import {
  consentimientoVigente,
  leerConsentimiento,
  useConsentimientoCookies,
  VERSION_POLITICA,
} from '../app/composables/useConsentimientoCookies'

/** Fecha ISO de hace `dias`, para simular consentimientos viejos. */
function haceDias(dias: number): string {
  return new Date(Date.now() - dias * 86_400_000).toISOString()
}

/** Escribe un consentimiento a mano en el almacenamiento del navegador. */
function guardarCrudo(valor: unknown): void {
  localStorage.setItem('sede-consentimiento', typeof valor === 'string' ? valor : JSON.stringify(valor))
}

const consentimiento = useConsentimientoCookies()

beforeEach(() => {
  localStorage.clear()
  consentimiento.inicializar()
})

describe('banner de cookies', () => {
  it('se muestra cuando no hay ninguna decisión', () => {
    expect(consentimiento.visible.value).toBe(true)
  })

  it('se cierra y guarda fecha, versión y categorías al aceptar todo', () => {
    consentimiento.aceptarTodo()

    const guardado = leerConsentimiento()
    expect(consentimiento.visible.value).toBe(false)
    expect(guardado?.aceptadas).toEqual(['analitica', 'preferencias'])
    expect(guardado?.version).toBe(VERSION_POLITICA)
    expect(Number.isNaN(Date.parse(guardado?.fecha ?? ''))).toBe(false)
  })

  it('rechazar deja el sitio sólo con las cookies necesarias', () => {
    consentimiento.rechazarOpcionales()

    expect(leerConsentimiento()?.aceptadas).toEqual([])
    expect(consentimiento.puedeUsar('analitica')).toBe(false)
    expect(consentimiento.puedeUsar('preferencias')).toBe(false)
  })

  it('sólo activa una categoría después de aceptarla', () => {
    expect(consentimiento.puedeUsar('analitica')).toBe(false)
    consentimiento.aceptarTodo()
    expect(consentimiento.puedeUsar('analitica')).toBe(true)
  })
})

describe('caducidad y versionado del consentimiento', () => {
  it('un consentimiento de hace más de un año vuelve a pedirse', () => {
    guardarCrudo({ fecha: haceDias(400), version: VERSION_POLITICA, aceptadas: ['analitica'] })

    consentimiento.inicializar()

    expect(consentimientoVigente(leerConsentimiento())).toBe(false)
    expect(consentimiento.visible.value).toBe(true)
  })

  it('un consentimiento reciente no vuelve a pedirse', () => {
    guardarCrudo({ fecha: haceDias(100), version: VERSION_POLITICA, aceptadas: ['analitica'] })

    consentimiento.inicializar()

    expect(consentimientoVigente(leerConsentimiento())).toBe(true)
    expect(consentimiento.visible.value).toBe(false)
  })

  it('una versión anterior de la política invalida lo aceptado', () => {
    guardarCrudo({ fecha: haceDias(1), version: VERSION_POLITICA - 1, aceptadas: ['analitica'] })

    consentimiento.inicializar()

    expect(consentimiento.visible.value).toBe(true)
  })

  it('un consentimiento sin fecha válida no se considera vigente', () => {
    expect(consentimientoVigente({ fecha: 'no-es-una-fecha', version: 1, aceptadas: [] })).toBe(false)
    expect(consentimientoVigente(null)).toBe(false)
  })

  it('un JSON corrupto no rompe la página y vuelve a preguntar', () => {
    guardarCrudo('{esto no es json')

    expect(() => consentimiento.inicializar()).not.toThrow()
    expect(consentimiento.visible.value).toBe(true)
  })
})

describe('revocación (RF-B1-008)', () => {
  it('reabre el banner con lo aceptado premarcado', () => {
    consentimiento.aceptarTodo()
    expect(consentimiento.visible.value).toBe(false)

    consentimiento.abrirBanner()

    expect(consentimiento.visible.value).toBe(true)
    expect(consentimiento.preferencias.value.analitica).toBe(true)
    expect(consentimiento.preferencias.value.preferencias).toBe(true)
  })

  it('guardar la elección con todo desmarcado equivale a revocar', () => {
    consentimiento.aceptarTodo()
    consentimiento.abrirBanner()
    consentimiento.preferencias.value = { analitica: false, preferencias: false }

    consentimiento.guardarPreferencias()

    expect(leerConsentimiento()?.aceptadas).toEqual([])
    expect(consentimiento.puedeUsar('analitica')).toBe(false)
  })
})
