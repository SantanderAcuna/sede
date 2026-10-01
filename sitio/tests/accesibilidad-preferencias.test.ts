/**
 * Preferencias de accesibilidad (`useAccesibilidad`).
 *
 * Este composable es de los que más importa: aplica los siete ajustes a toda la
 * página, guarda la decisión y la vuelve a aplicar al recargar. Se prueba con un
 * componente real —`createApp`— porque lo que hay que comprobar ocurre en
 * `onMounted` y en el `watch`, y eso no se ejecuta llamando a la función a secas.
 *
 * `useState` es la única pieza de Nuxt que usa el composable, así que se sustituye
 * por un `ref` equivalente: fuera de Nuxt no existe, y lo que se prueba aquí es el
 * comportamiento, no el framework.
 *
 * **La migración desde el formato anterior se prueba aparte y a propósito.** Un
 * ciudadano que ya hubiera ajustado el contraste con la versión vieja —donde era
 * un booleano y la letra iba de −5 a +5— no puede encontrárselo apagado al volver;
 * si la migración falla, el sitio parece haber olvidado su decisión.
 */
import { createApp, defineComponent, h, nextTick, ref, type Ref } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import type { useAccesibilidad as TipoUseAccesibilidad } from '../app/composables/useAccesibilidad'

const CLAVE_LOCALSTORAGE = 'sede-accesibilidad'

const POR_DEFECTO = {
  contraste: 'normal',
  letra: 0,
  espaciado: false,
  dislexia: false,
  resaltarEnlaces: false,
  guiaLectura: false,
  detenerAnimaciones: false,
}

let estado: ReturnType<typeof TipoUseAccesibilidad>
let aplicacion: ReturnType<typeof createApp> | null = null

/** Monta un componente mínimo que usa el composable y deja su estado en `estado`. */
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

/** Vuelve a montar con un `useState` limpio, como en una carga nueva de página. */
async function remontar(): Promise<void> {
  aplicacion?.unmount()
  const almacen = new Map<string, Ref<unknown>>()
  vi.stubGlobal('useState', (clave: string, iniciar: () => unknown) => {
    if (!almacen.has(clave)) almacen.set(clave, ref(iniciar()))
    return almacen.get(clave)
  })
  const modulo = await import('../app/composables/useAccesibilidad')
  vi.stubGlobal('useAccesibilidad', modulo.useAccesibilidad)
  await montar()
}

/**
 * Declara `window.matchMedia`, que jsdom no implementa.
 *
 * Se define sobre `window` y no con `vi.stubGlobal` porque el composable llama a
 * `window.matchMedia`, y en el entorno de jsdom `globalThis.matchMedia` y
 * `window.matchMedia` no son el mismo objeto: el `stubGlobal` no llegaría.
 */
function definirMatchMedia(matches: boolean): void {
  Object.defineProperty(window, 'matchMedia', {
    configurable: true,
    writable: true,
    value: (consulta: string) => ({ matches, media: consulta }) as MediaQueryList,
  })
}

/** Retira el `matchMedia` que haya declarado una prueba. */
function quitarMatchMedia(): void {
  Reflect.deleteProperty(window as unknown as Record<string, unknown>, 'matchMedia')
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
  quitarMatchMedia()
  document.documentElement.className = ''
  document.documentElement.removeAttribute('style')
})

describe('valores por defecto', () => {
  it('empieza sin ningún ajuste y con el tamaño normal', () => {
    expect(estado.preferencias.value).toEqual(POR_DEFECTO)
  })

  it('el tamaño normal es el primer paso de la escala, así que no se puede reducir', () => {
    expect(estado.puedeAumentar.value).toBe(true)
    expect(estado.puedeReducir.value).toBe(false)
  })

  it('la escala tiene cinco pasos, del 100 % al 200 %', () => {
    expect(estado.LIMITE).toBe(4)
    expect(estado.ESCALA_LETRA).toEqual([1, 1.25, 1.5, 1.75, 2])
  })

  it('no hay ajustes activos, así que restablecer no tiene nada que hacer', () => {
    expect(estado.hayAjustes.value).toBe(false)
  })
})

describe('modos de contraste', () => {
  it('el modo normal no deja ninguna clase en la raíz', async () => {
    estado.definirContraste('normal')
    await nextTick()
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(false)
    expect(document.documentElement.classList.contains('contraste-inverso-govco')).toBe(false)
    expect(document.documentElement.classList.contains('contraste-grises-govco')).toBe(false)
  })

  it('cada modo pone su clase y quita la de los demás', async () => {
    estado.definirContraste('alto')
    await nextTick()
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(true)

    estado.definirContraste('inverso')
    await nextTick()
    expect(document.documentElement.classList.contains('contraste-inverso-govco')).toBe(true)
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(false)

    estado.definirContraste('grises')
    await nextTick()
    expect(document.documentElement.classList.contains('contraste-grises-govco')).toBe(true)
    expect(document.documentElement.classList.contains('contraste-inverso-govco')).toBe(false)

    estado.definirContraste('normal')
    await nextTick()
    expect(document.documentElement.classList.contains('contraste-grises-govco')).toBe(false)
  })

  it('alternarContraste enciende el alto contraste y lo vuelve a apagar', async () => {
    estado.alternarContraste()
    await nextTick()
    expect(estado.preferencias.value.contraste).toBe('alto')
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(true)

    estado.alternarContraste()
    await nextTick()
    expect(estado.preferencias.value.contraste).toBe('normal')
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(false)
  })

  it('alternarContraste desde otro modo vuelve al alto contraste, no al normal', async () => {
    estado.definirContraste('grises')
    await nextTick()
    estado.alternarContraste()
    await nextTick()
    expect(estado.preferencias.value.contraste).toBe('alto')
  })
})

describe('ajustes de lectura y movimiento', () => {
  const casos = [
    { metodo: 'alternarEspaciado', clave: 'espaciado', clase: 'espaciado-govco' },
    { metodo: 'alternarDislexia', clave: 'dislexia', clase: 'dislexia-govco' },
    {
      metodo: 'alternarResaltarEnlaces',
      clave: 'resaltarEnlaces',
      clase: 'resaltar-enlaces-govco',
    },
    { metodo: 'alternarGuiaLectura', clave: 'guiaLectura', clase: 'guia-lectura-govco' },
    {
      metodo: 'alternarDetenerAnimaciones',
      clave: 'detenerAnimaciones',
      clase: 'detener-animaciones-govco',
    },
  ] as const

  for (const caso of casos) {
    it(`${caso.clave} entra y sale de la raíz`, async () => {
      estado[caso.metodo]()
      await nextTick()
      expect(document.documentElement.classList.contains(caso.clase)).toBe(true)

      estado[caso.metodo]()
      await nextTick()
      expect(document.documentElement.classList.contains(caso.clase)).toBe(false)
    })
  }

  it('`alternar` enciende un ajuste por su nombre', async () => {
    estado.alternar('guiaLectura')
    await nextTick()
    expect(estado.preferencias.value.guiaLectura).toBe(true)
    expect(estado.hayAjustes.value).toBe(true)
  })
})

describe('tamaño de letra', () => {
  it('el primer paso deja la raíz sin `zoom` escrito', async () => {
    expect(document.documentElement.style.zoom).toBe('')
    expect(estado.porcentajeLetra.value).toBe(100)
  })

  it('sube por la escala del 100 % al 200 %', async () => {
    estado.moverLetra(1)
    await nextTick()
    expect(estado.preferencias.value.letra).toBe(1)
    expect(document.documentElement.style.zoom).toBe('1.25')
    expect(estado.porcentajeLetra.value).toBe(125)

    estado.moverLetra(1)
    await nextTick()
    expect(document.documentElement.style.zoom).toBe('1.5')
    expect(estado.porcentajeLetra.value).toBe(150)
  })

  it('llega al 200 % y no pasa de ahí', async () => {
    for (let paso = 0; paso < 8; paso += 1) estado.moverLetra(1)
    await nextTick()
    expect(estado.preferencias.value.letra).toBe(4)
    expect(document.documentElement.style.zoom).toBe('2')
    expect(estado.porcentajeLetra.value).toBe(200)
    expect(estado.puedeAumentar.value).toBe(false)
  })

  it('no baja del 100 %: el tamaño normal es el suelo', async () => {
    for (let paso = 0; paso < 6; paso += 1) estado.moverLetra(-1)
    await nextTick()
    expect(estado.preferencias.value.letra).toBe(0)
    expect(estado.puedeReducir.value).toBe(false)
  })

  it('vuelve al tamaño normal sin dejar `zoom` escrito en la raíz', async () => {
    estado.moverLetra(3)
    await nextTick()
    estado.restablecer()
    await nextTick()
    expect(estado.preferencias.value.letra).toBe(0)
    expect(document.documentElement.style.zoom).toBe('')
  })
})

describe('restablecer', () => {
  it('devuelve los siete ajustes a su valor inicial', async () => {
    estado.definirContraste('grises')
    estado.alternarEspaciado()
    estado.alternarDislexia()
    estado.alternarResaltarEnlaces()
    estado.alternarGuiaLectura()
    estado.alternarDetenerAnimaciones()
    estado.moverLetra(3)
    await nextTick()
    expect(estado.hayAjustes.value).toBe(true)

    estado.restablecer()
    await nextTick()

    expect(estado.preferencias.value).toEqual(POR_DEFECTO)
    expect(estado.hayAjustes.value).toBe(false)
    expect(document.documentElement.classList.contains('contraste-grises-govco')).toBe(false)
    expect(document.documentElement.classList.contains('espaciado-govco')).toBe(false)
    expect(document.documentElement.classList.contains('dislexia-govco')).toBe(false)
    expect(document.documentElement.classList.contains('resaltar-enlaces-govco')).toBe(false)
    expect(document.documentElement.classList.contains('guia-lectura-govco')).toBe(false)
    expect(document.documentElement.classList.contains('detener-animaciones-govco')).toBe(false)
    expect(document.documentElement.style.zoom).toBe('')
  })
})

describe('panel de ajustes', () => {
  it('empieza cerrado y se abre, se cierra y se alterna', async () => {
    expect(estado.panelAbierto.value).toBe(false)

    estado.abrirPanel()
    expect(estado.panelAbierto.value).toBe(true)

    estado.cerrarPanel()
    expect(estado.panelAbierto.value).toBe(false)

    estado.alternarPanel()
    expect(estado.panelAbierto.value).toBe(true)
    estado.alternarPanel()
    expect(estado.panelAbierto.value).toBe(false)
  })
})

describe('memoria entre visitas (D-11)', () => {
  it('guarda la decisión con su versión de formato', async () => {
    estado.definirContraste('alto')
    estado.moverLetra(2)
    await nextTick()

    const guardado = JSON.parse(localStorage.getItem(CLAVE_LOCALSTORAGE) ?? '{}')
    expect(guardado).toEqual({ v: 2, ...POR_DEFECTO, contraste: 'alto', letra: 2 })
  })

  it('la recupera al volver a la página y la aplica', async () => {
    localStorage.setItem(
      CLAVE_LOCALSTORAGE,
      JSON.stringify({ v: 2, ...POR_DEFECTO, contraste: 'inverso', letra: 1, espaciado: true }),
    )
    await remontar()

    expect(estado.preferencias.value.contraste).toBe('inverso')
    expect(estado.preferencias.value.letra).toBe(1)
    expect(estado.preferencias.value.espaciado).toBe(true)
    expect(document.documentElement.classList.contains('contraste-inverso-govco')).toBe(true)
    expect(document.documentElement.classList.contains('espaciado-govco')).toBe(true)
    expect(document.documentElement.style.zoom).toBe('1.25')
  })

  it('un almacenamiento corrupto no rompe la página: se vuelve a los valores por defecto', async () => {
    localStorage.setItem(CLAVE_LOCALSTORAGE, '{esto no es json')

    await expect(remontar()).resolves.toBeUndefined()
    expect(estado.preferencias.value).toEqual(POR_DEFECTO)
  })

  it('un almacenamiento que no es un objeto tampoco rompe nada', async () => {
    localStorage.setItem(CLAVE_LOCALSTORAGE, '"una cadena"')
    await remontar()
    expect(estado.preferencias.value).toEqual(POR_DEFECTO)
  })
})

describe('migración desde el formato anterior', () => {
  it('un contraste booleano se convierte en modo alto o normal', async () => {
    const modulo = await import('../app/composables/useAccesibilidad')
    expect(modulo.normalizarPreferencias({ contraste: true, letra: 0, espaciado: false }).contraste)
      .toBe('alto')
    expect(modulo.normalizarPreferencias({ contraste: false, letra: 0, espaciado: false }).contraste)
      .toBe('normal')
  })

  it('la letra antigua se reasigna al paso más cercano de la escala nueva', async () => {
    const modulo = await import('../app/composables/useAccesibilidad')
    const letraDe = (antigua: number) =>
      modulo.normalizarPreferencias({ contraste: false, letra: antigua, espaciado: false }).letra

    // La escala antigua sumaba un 8 % por paso: 1,08 · 1,16 · 1,24 · 1,32 · 1,40.
    expect(letraDe(0)).toBe(0)
    expect(letraDe(1)).toBe(0)
    expect(letraDe(2)).toBe(1)
    expect(letraDe(3)).toBe(1)
    expect(letraDe(4)).toBe(1)
    expect(letraDe(5)).toBe(2)
    // Los valores negativos bajaban del tamaño normal, que ya no existe.
    expect(letraDe(-5)).toBe(0)
  })

  it('el resto de ajustes del formato viejo se conserva', async () => {
    const modulo = await import('../app/composables/useAccesibilidad')
    const migrado = modulo.normalizarPreferencias({
      contraste: true,
      letra: 5,
      espaciado: true,
    })
    expect(migrado).toEqual({ ...POR_DEFECTO, contraste: 'alto', letra: 2, espaciado: true })
  })

  it('un modo de contraste desconocido cae al valor por defecto', async () => {
    const modulo = await import('../app/composables/useAccesibilidad')
    const migrado = modulo.normalizarPreferencias({ v: 2, contraste: 'sepia', letra: 99 })
    expect(migrado.contraste).toBe('normal')
    expect(migrado.letra).toBe(4)
  })

  it('un valor que no es un objeto devuelve los valores por defecto', async () => {
    const modulo = await import('../app/composables/useAccesibilidad')
    expect(modulo.normalizarPreferencias(null)).toEqual(POR_DEFECTO)
    expect(modulo.normalizarPreferencias(42)).toEqual(POR_DEFECTO)
  })

  it('las preferencias guardadas en formato viejo se aplican al cargar', async () => {
    localStorage.setItem(
      CLAVE_LOCALSTORAGE,
      JSON.stringify({ contraste: true, letra: 5, espaciado: true }),
    )
    await remontar()

    expect(estado.preferencias.value.contraste).toBe('alto')
    expect(estado.preferencias.value.letra).toBe(2)
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(true)
    expect(document.documentElement.style.zoom).toBe('1.5')
  })
})

describe('preferencia del sistema: más contraste', () => {
  it('con el sistema pidiendo más contraste se sugiere, no se impone', async () => {
    definirMatchMedia(true)
    await remontar()

    expect(estado.sugerenciaContrasteAlto.value).toBe(true)
    // Lo importante: se avisa, pero **no** se activa el modo por su cuenta.
    expect(estado.preferencias.value.contraste).toBe('normal')
    expect(document.documentElement.classList.contains('contraste-govco')).toBe(false)
    quitarMatchMedia()
  })

  it('si el ciudadano ya había elegido, no se le sugiere nada', async () => {
    localStorage.setItem(CLAVE_LOCALSTORAGE, JSON.stringify({ v: 2, ...POR_DEFECTO }))
    definirMatchMedia(true)
    await remontar()

    expect(estado.sugerenciaContrasteAlto.value).toBe(false)
    quitarMatchMedia()
  })

  it('un sistema que no pide más contraste no genera sugerencia', async () => {
    definirMatchMedia(false)
    await remontar()

    expect(estado.sugerenciaContrasteAlto.value).toBe(false)
    quitarMatchMedia()
  })

  it('un entorno sin `matchMedia` —como jsdom sin la prueba anterior— no rompe la carga', async () => {
    quitarMatchMedia()
    await expect(remontar()).resolves.toBeUndefined()
    expect(estado.sugerenciaContrasteAlto.value).toBe(false)
  })
})
