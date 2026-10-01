/**
 * Preferencias de accesibilidad (`useAccesibilidad`).
 *
 * Este composable estaba en **0 % de cobertura** y es de los que más importa:
 * aplica contraste, tamaño de letra y espaciado a toda la página, guarda la
 * decisión y la vuelve a aplicar al recargar. Se prueba con un componente real
 * —`createApp`— porque lo que hay que comprobar ocurre en `onMounted` y en el
 * `watch`, y eso no se ejecuta llamando a la función a secas.
 *
 * `useState` es la única pieza de Nuxt que usa el composable, así que se sustituye
 * por un `ref` equivalente: fuera de Nuxt no existe, y lo que se prueba aquí es el
 * comportamiento, no el framework.
 */
import { createApp, defineComponent, h, nextTick, ref, type Ref } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import type { useAccesibilidad as TipoUseAccesibilidad } from '../app/composables/useAccesibilidad'

const CLAVE_LOCALSTORAGE = 'sede-accesibilidad'

let estado: ReturnType<typeof TipoUseAccesibilidad>
let aplicacion: ReturnType<typeof createApp> | null = null

/** Monta un componente mínimo que usa el composable y devuelve su estado. */
async function montar(): Promise<void> {
  const Anfitrion = defineComponent({
    setup() {
      estado = (globalThis as unknown as { useAccesibilidad: typeof TipoUseAccesibilidad })
        .useAccesibilidad()
      return () => h('div')
    },
  })
  aplicacion = createApp(Anfitrion)
  aplicacion.mount(document.createElement('div'))
  await nextTick()
  await nextTick()
}

beforeEach(async () => {
  vi.resetModules()
  localStorage.clear()
  document.documentElement.className = ''
  document.documentElement.removeAttribute('style')

  // Equivalente mínimo de `useState` de Nuxt: un `ref` con valor inicial.
  const almacen = new Map<string, Ref<unknown>>()
  vi.stubGlobal('useState', (clave: string, iniciar: () => unknown) => {
    if (!almacen.has(clave)) almacen.set(clave, ref(iniciar()))
    return almacen.get(clave)
  })

  const modulo = await import('../app/composables/useAccesibilidad')
  vi.stubGlobal('useAccesibilidad', modulo.useAccesibilidad)
  await montar()
})

afterEach(() => {
  aplicacion?.unmount()
  aplicacion = null
  vi.unstubAllGlobals()
  document.documentElement.className = ''
  document.documentElement.removeAttribute('style')
})

describe('valores por defecto', () => {
  it('empieza sin contraste, sin espaciado y con la letra normal', () => {
    expect(estado.preferencias.value).toEqual({ contraste: false, letra: 0, espaciado: false })
  })

  it('puede subir y bajar la letra desde el valor normal', () => {
    expect(estado.puedeAumentar.value).toBe(true)
    expect(estado.puedeReducir.value).toBe(true)
  })

  it('el límite del Kit son cinco pasos', () => {
    expect(estado.LIMITE).toBe(5)
  })
})

describe('contraste', () => {
  it('la clase de alto contraste entra y sale de la raíz', async () => {
    estado.alternarContraste()
    await nextTick()
    expect(estado.preferencias.value.contraste).toBe(true)
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(true)

    estado.alternarContraste()
    await nextTick()
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(false)
  })
})

describe('espaciado de texto (RF-B3-014)', () => {
  it('la clase de espaciado entra y sale de la raíz', async () => {
    estado.alternarEspaciado()
    await nextTick()
    expect(document.documentElement.classList.contains('espaciado-govco')).toBe(true)

    estado.alternarEspaciado()
    await nextTick()
    expect(document.documentElement.classList.contains('espaciado-govco')).toBe(false)
  })
})

describe('tamaño de letra', () => {
  it('escala la raíz en pasos del 8 %', async () => {
    estado.moverLetra(1)
    await nextTick()
    expect(estado.preferencias.value.letra).toBe(1)
    expect(document.documentElement.style.zoom).toBe('1.08')
  })

  it('no pasa del límite por arriba ni por abajo', async () => {
    for (let paso = 0; paso < 8; paso += 1) estado.moverLetra(1)
    await nextTick()
    expect(estado.preferencias.value.letra).toBe(5)
    expect(estado.puedeAumentar.value).toBe(false)

    for (let paso = 0; paso < 12; paso += 1) estado.moverLetra(-1)
    await nextTick()
    expect(estado.preferencias.value.letra).toBe(-5)
    expect(estado.puedeReducir.value).toBe(false)
  })

  it('vuelve al tamaño normal sin dejar `zoom` escrito en la raíz', async () => {
    estado.moverLetra(2)
    await nextTick()
    estado.restablecer()
    await nextTick()
    expect(estado.preferencias.value.letra).toBe(0)
    expect(document.documentElement.style.zoom).toBe('')
  })
})

describe('restablecer', () => {
  it('devuelve las tres preferencias a su valor inicial', async () => {
    estado.alternarContraste()
    estado.alternarEspaciado()
    estado.moverLetra(3)
    await nextTick()

    estado.restablecer()
    await nextTick()

    expect(estado.preferencias.value).toEqual({ contraste: false, letra: 0, espaciado: false })
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(false)
    expect(document.documentElement.classList.contains('espaciado-govco')).toBe(false)
  })
})

describe('memoria entre visitas (D-11)', () => {
  it('guarda la decisión en el almacenamiento del navegador', async () => {
    estado.alternarContraste()
    estado.moverLetra(2)
    await nextTick()

    const guardado = JSON.parse(localStorage.getItem(CLAVE_LOCALSTORAGE) ?? '{}')
    expect(guardado).toEqual({ contraste: true, letra: 2, espaciado: false })
  })

  it('la recupera al volver a la página y la aplica', async () => {
    localStorage.setItem(
      CLAVE_LOCALSTORAGE,
      JSON.stringify({ contraste: true, letra: 1, espaciado: true }),
    )
    aplicacion?.unmount()

    const almacen = new Map<string, Ref<unknown>>()
    vi.stubGlobal('useState', (clave: string, iniciar: () => unknown) => {
      if (!almacen.has(clave)) almacen.set(clave, ref(iniciar()))
      return almacen.get(clave)
    })
    const modulo = await import('../app/composables/useAccesibilidad')
    vi.stubGlobal('useAccesibilidad', modulo.useAccesibilidad)
    await montar()

    expect(estado.preferencias.value).toEqual({ contraste: true, letra: 1, espaciado: true })
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(true)
    expect(document.documentElement.classList.contains('espaciado-govco')).toBe(true)
    expect(document.documentElement.style.zoom).toBe('1.08')
  })

  it('un almacenamiento corrupto no rompe la página: se vuelve a los valores por defecto', async () => {
    localStorage.setItem(CLAVE_LOCALSTORAGE, '{esto no es json')
    aplicacion?.unmount()

    const almacen = new Map<string, Ref<unknown>>()
    vi.stubGlobal('useState', (clave: string, iniciar: () => unknown) => {
      if (!almacen.has(clave)) almacen.set(clave, ref(iniciar()))
      return almacen.get(clave)
    })
    const modulo = await import('../app/composables/useAccesibilidad')
    vi.stubGlobal('useAccesibilidad', modulo.useAccesibilidad)

    await expect(montar()).resolves.toBeUndefined()
    expect(estado.preferencias.value).toEqual({ contraste: false, letra: 0, espaciado: false })
  })
})
