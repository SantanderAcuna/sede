/**
 * Buscador con sugerencias (T-22, E3-2 · AC-22.2, AC-22.12).
 *
 * Contrasta el componente con `docs/busqueda-interaccion.md`: el marcado exacto
 * de §4.2, el teclado de §4.3, los estados de §4.4 y los textos de §7. Las
 * respuestas de la API son promesas que la prueba resuelve a mano.
 *
 * Lo que jsdom no puede medir —tamaño de los objetivos en píxeles, que el círculo
 * flotante no tape la lista, contraste— se comprueba en navegador real (E3-4).
 */
import { mount, type VueWrapper } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'

import BuscadorGovco from '../app/components/govco/BuscadorGovco.vue'
import type { PedirSugerencias, RespuestaHttp } from '../app/composables/useSugerenciasBusqueda'
import { violacionesAxe } from './ayudas/axe'

interface Llamada {
  consulta: string
  limite: number
  resolver: (respuesta: RespuestaHttp) => void
  rechazar: (motivo: unknown) => void
}

function crearPedir() {
  const llamadas: Llamada[] = []
  const pedir: PedirSugerencias = (consulta, limite) =>
    new Promise<RespuestaHttp>((resolver, rechazar) => {
      llamadas.push({ consulta, limite, resolver, rechazar })
    })
  return { pedir, llamadas }
}

function opcion(id: number, titulo = `Trámite ${id}`) {
  return {
    id,
    type: 'sugerencias-busqueda',
    titulo,
    categoria: 'Trámite',
    tipo_recurso: 'tramite',
    url: `/tramites/tramite-${id}`,
    score: 0.5,
  }
}

function ok(...titulos: string[]): RespuestaHttp {
  return {
    status: 200,
    headers: { get: () => null },
    cuerpo: { success: true, data: titulos.map((titulo, i) => opcion(i + 1, titulo)) },
  }
}

function conEstado(status: number, cabeceras: Record<string, string> = {}): RespuestaHttp {
  return { status, headers: { get: (n: string) => cabeceras[n] ?? null }, cuerpo: { success: false, data: null } }
}

let envoltorio: VueWrapper | null = null

function montar(props: Record<string, unknown> = {}) {
  const { pedir, llamadas } = crearPedir()
  envoltorio = mount(BuscadorGovco, {
    attachTo: document.body,
    props: { conSugerencias: true, pedirSugerencias: pedir, ...props },
  })
  return { buscador: envoltorio, llamadas }
}

const campoDe = (b: VueWrapper) => b.get<HTMLInputElement>('input')
const listaDe = (b: VueWrapper) => b.get('[role="listbox"]')
const opcionesDe = (b: VueWrapper) => b.findAll('[role="option"]')
const estadoDe = (b: VueWrapper) => b.get('[role="status"]').text()

async function avanzar(ms: number) {
  await vi.advanceTimersByTimeAsync(ms)
}

async function escribir(b: VueWrapper, texto: string) {
  const campo = campoDe(b)
  campo.element.focus()
  await campo.setValue(texto)
}

/** Escribe, deja pasar la pausa y contesta la API con las opciones dadas. */
async function conLista(titulos: string[] = ['Licencia de construcción', 'Licencia de conducción', 'Licencia de urbanismo']) {
  const { buscador, llamadas } = montar()
  await escribir(buscador, 'licencia')
  await avanzar(350)
  llamadas[0]?.resolver(ok(...titulos))
  await avanzar(0)
  return { buscador, llamadas }
}

async function tecla(b: VueWrapper, key: string, extra: Record<string, unknown> = {}) {
  await campoDe(b).trigger('keydown', { key, ...extra })
}

beforeEach(() => {
  vi.useFakeTimers()
})

afterEach(() => {
  envoltorio?.unmount()
  envoltorio = null
  vi.useRealTimers()
  vi.restoreAllMocks()
})

describe('sin sugerencias (el buscador del catálogo)', () => {
  it('no cambia: ni combobox, ni dirección, ni nombre de campo, ni lista', async () => {
    const { buscador, llamadas } = montar({ conSugerencias: false })
    const campo = campoDe(buscador)
    expect(campo.attributes('role')).toBeUndefined()
    expect(campo.attributes('name')).toBeUndefined()
    expect(campo.attributes('maxlength')).toBeUndefined()
    expect(buscador.get('form').attributes('action')).toBeUndefined()
    expect(buscador.find('[role="listbox"]').exists()).toBe(false)

    await escribir(buscador, 'licencia')
    await avanzar(5000)
    expect(llamadas).toHaveLength(0)
  })

  it('Esc borra el texto y Intro emite el término recortado', async () => {
    const { buscador } = montar({ conSugerencias: false })
    await escribir(buscador, '  licencia ')
    await buscador.get('form').trigger('submit')
    expect(buscador.emitted('buscar')).toEqual([['licencia']])

    await tecla(buscador, 'Escape')
    expect(campoDe(buscador).element.value).toBe('')
    expect(buscador.emitted('limpiar')).toHaveLength(1)
  })

  it('un envío en blanco no emite y deja el foco en el campo', async () => {
    const { buscador } = montar({ conSugerencias: false })
    await escribir(buscador, '   ')
    await buscador.get('form').trigger('submit')
    expect(buscador.emitted('buscar')).toBeUndefined()
    expect(document.activeElement).toBe(campoDe(buscador).element)
  })

  it('el botón de limpiar vacía, avisa y devuelve el foco', async () => {
    const { buscador } = montar({ conSugerencias: false, valorInicial: 'pqrsd' })
    expect(campoDe(buscador).element.value).toBe('pqrsd')
    await buscador.get('.btn-clean-basic-govco').trigger('click')
    expect(campoDe(buscador).element.value).toBe('')
    expect(buscador.emitted('limpiar')).toHaveLength(1)
    expect(document.activeElement).toBe(campoDe(buscador).element)
  })

  it('el contenedor marca el foco al entrar y lo quita al salir del buscador', async () => {
    const { buscador } = montar({ conSugerencias: false })
    const contenedor = buscador.get('.container-govco')
    await contenedor.trigger('focusin')
    expect(contenedor.classes()).toContain('active')
    // Pasar a otro control del propio buscador no apaga el contorno.
    await contenedor.trigger('focusout', { relatedTarget: buscador.get('.btn-search-basic-govco').element })
    expect(contenedor.classes()).toContain('active')
    await contenedor.trigger('focusout', { relatedTarget: document.body })
    expect(contenedor.classes()).not.toContain('active')
  })
})

describe('marcado accesible (§4.2)', () => {
  it('el formulario funciona sin JavaScript: GET a /buscar con el campo «q»', () => {
    const { buscador } = montar()
    const formulario = buscador.get('form')
    expect(formulario.attributes('role')).toBe('search')
    expect(formulario.attributes('aria-label')).toBe('Buscar en el sitio')
    expect(formulario.attributes('action')).toBe('/buscar')
    expect(formulario.attributes('method')).toBe('get')
    expect(campoDe(buscador).attributes('name')).toBe('q')
  })

  it('el campo es un combobox con los atributos exactos', () => {
    const { buscador } = montar()
    const campo = campoDe(buscador)
    expect(campo.attributes()).toMatchObject({
      type: 'text',
      role: 'combobox',
      'aria-autocomplete': 'list',
      'aria-expanded': 'false',
      autocomplete: 'off',
      enterkeyhint: 'search',
      maxlength: '120',
    })
    expect(campo.attributes('aria-activedescendant')).toBeUndefined()
  })

  it('aria-controls apunta a la lista, aria-describedby a la ayuda, y la etiqueta al campo', () => {
    const { buscador } = montar()
    const campo = campoDe(buscador)
    expect(campo.attributes('aria-controls')).toBe(listaDe(buscador).attributes('id'))
    const ayuda = document.getElementById(campo.attributes('aria-describedby') ?? '')
    expect(ayuda?.textContent).toBe('Escriba al menos 3 letras para ver sugerencias.')
    expect(buscador.get('label').attributes('for')).toBe(campo.attributes('id'))
  })

  it('la lista cerrada se llama «Sugerencias de búsqueda» y está oculta', () => {
    const { buscador } = montar()
    const lista = listaDe(buscador)
    expect(lista.attributes('aria-label')).toBe('Sugerencias de búsqueda')
    expect(lista.attributes('hidden')).toBeDefined()
  })

  it('hay una región de estado cortés y los botones llevan nombre', () => {
    const { buscador } = montar()
    expect(buscador.get('[role="status"]').attributes('aria-live')).toBe('polite')
    expect(buscador.get('.btn-clean-basic-govco').attributes('aria-label')).toBe('Limpiar la búsqueda')
    expect(buscador.get('.btn-search-basic-govco').attributes('aria-label')).toBe('Buscar')
    expect(buscador.get('.btn-search-basic-govco').attributes('type')).toBe('submit')
  })

  it('dos buscadores en una página no repiten identificadores', () => {
    // En una sola aplicación, como en el sitio: `useId` numera por aplicación.
    const dos = mount(
      { render: () => h('div', [h(BuscadorGovco, { conSugerencias: true }), h(BuscadorGovco, { conSugerencias: true })]) },
      { attachTo: document.body },
    )
    const campos = dos.findAll('input')
    const listas = dos.findAll('[role="listbox"]')
    expect(campos).toHaveLength(2)
    expect(campos[0]?.attributes('id')).not.toBe(campos[1]?.attributes('id'))
    expect(listas[0]?.attributes('id')).not.toBe(listas[1]?.attributes('id'))
    expect(campos[1]?.attributes('aria-controls')).toBe(listas[1]?.attributes('id'))
    dos.unmount()
  })
})

describe('abrir y anunciar (§4.4)', () => {
  it('con menos de 3 caracteres no hay lista ni petición', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'li')
    await avanzar(1000)
    expect(llamadas).toHaveLength(0)
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('false')
    expect(estadoDe(buscador)).toBe('')
  })

  it('con 3 o más, pide 6 tras la pausa y abre la lista con una fila por resultado', async () => {
    const { buscador, llamadas } = await conLista()
    expect(llamadas).toHaveLength(1)
    expect(llamadas[0]).toMatchObject({ consulta: 'licencia', limite: 6 })
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('true')
    expect(listaDe(buscador).attributes('hidden')).toBeUndefined()
    expect(opcionesDe(buscador).map((o) => o.find('.titulo-sugerencia-govco').text())).toEqual([
      'Licencia de construcción',
      'Licencia de conducción',
      'Licencia de urbanismo',
    ])
    expect(opcionesDe(buscador).every((o) => o.attributes('aria-selected') === 'false')).toBe(true)
  })

  it('el nombre de una opción sale de su contenido: «título, categoría»', async () => {
    const { buscador } = await conLista()
    expect(opcionesDe(buscador)[0]?.text().replace(/\s+/g, ' ')).toBe('Licencia de construcción, Trámite')
    expect(opcionesDe(buscador)[0]?.find('.categoria-sugerencia-govco').text()).toBe('Trámite')
  })

  it('anuncia cuántas sugerencias hay, en plural y en singular', async () => {
    const plural = await conLista()
    expect(estadoDe(plural.buscador)).toBe('3 sugerencias disponibles.')
    envoltorio?.unmount()
    const singular = await conLista(['Licencia de construcción'])
    expect(estadoDe(singular.buscador)).toBe('1 sugerencia disponible.')
  })

  it('no anuncia nada mientras se teclea', async () => {
    const { buscador } = montar()
    await escribir(buscador, 'lic')
    await campoDe(buscador).setValue('lice')
    expect(estadoDe(buscador)).toBe('')
  })

  it('escribir otra vez borra la opción activa', async () => {
    const { buscador } = await conLista()
    await tecla(buscador, 'ArrowDown')
    expect(campoDe(buscador).attributes('aria-activedescendant')).toBeDefined()
    await campoDe(buscador).setValue('licencias')
    expect(campoDe(buscador).attributes('aria-activedescendant')).toBeUndefined()
  })
})

describe('teclado (§4.3)', () => {
  it('↓ con la lista cerrada y ≥ 3 caracteres pregunta ya, sin esperar la pausa', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'licencia')
    await tecla(buscador, 'ArrowDown')
    expect(llamadas).toHaveLength(1)
    llamadas[0]?.resolver(ok('Licencia de construcción'))
    await avanzar(0)
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('true')
    // Abre pero no activa nada.
    expect(campoDe(buscador).attributes('aria-activedescendant')).toBeUndefined()
  })

  it('↓ con menos de 3 caracteres no hace nada', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'li')
    await tecla(buscador, 'ArrowDown')
    expect(llamadas).toHaveLength(0)
  })

  it('Alt+↓ con la lista cerrada la abre sin activar nada', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'licencia')
    await tecla(buscador, 'ArrowDown', { altKey: true })
    llamadas[0]?.resolver(ok('Licencia de construcción', 'Licencia de conducción'))
    await avanzar(0)
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('true')
    expect(campoDe(buscador).attributes('aria-activedescendant')).toBeUndefined()
  })

  it('Alt+↓ con la lista abierta no mueve la opción activa', async () => {
    const { buscador } = await conLista()
    await tecla(buscador, 'ArrowDown', { altKey: true })
    expect(campoDe(buscador).attributes('aria-activedescendant')).toBeUndefined()
  })

  it('↓ y ↑ recorren las opciones y pasan por el campo entre la última y la primera', async () => {
    const { buscador } = await conLista()
    const campo = campoDe(buscador)
    const activa = () => opcionesDe(buscador).findIndex((o) => o.attributes('aria-selected') === 'true')

    await tecla(buscador, 'ArrowDown')
    expect(activa()).toBe(0)
    expect(campo.attributes('aria-activedescendant')).toBe(opcionesDe(buscador)[0]?.attributes('id'))
    await tecla(buscador, 'ArrowDown')
    await tecla(buscador, 'ArrowDown')
    expect(activa()).toBe(2)
    // Desde la última, vuelve al campo.
    await tecla(buscador, 'ArrowDown')
    expect(activa()).toBe(-1)
    expect(campo.attributes('aria-activedescendant')).toBeUndefined()
    // Desde el campo, ↑ salta a la última (simetría del recorrido).
    await tecla(buscador, 'ArrowUp')
    expect(activa()).toBe(2)
    await tecla(buscador, 'ArrowUp')
    await tecla(buscador, 'ArrowUp')
    expect(activa()).toBe(0)
    // Desde la primera, ↑ vuelve al campo.
    await tecla(buscador, 'ArrowUp')
    expect(activa()).toBe(-1)
  })

  it('la opción activa lleva la clase de resaltado y el foco no sale nunca del campo', async () => {
    const { buscador } = await conLista()
    await tecla(buscador, 'ArrowDown')
    await tecla(buscador, 'ArrowDown')
    expect(opcionesDe(buscador)[1]?.classes()).toContain('activa')
    expect(opcionesDe(buscador)[0]?.classes()).not.toContain('activa')
    expect(document.activeElement).toBe(campoDe(buscador).element)
  })

  it('↑ con la lista cerrada no hace nada especial', async () => {
    const { buscador } = montar()
    await escribir(buscador, 'licencia')
    const evento = new KeyboardEvent('keydown', { key: 'ArrowUp', cancelable: true })
    campoDe(buscador).element.dispatchEvent(evento)
    expect(evento.defaultPrevented).toBe(false)
  })

  it('Intro con una opción activa va a su dirección y cierra la lista (D-I3)', async () => {
    const { buscador } = await conLista()
    await tecla(buscador, 'ArrowDown')
    await tecla(buscador, 'ArrowDown')
    const evento = new KeyboardEvent('keydown', { key: 'Enter', cancelable: true, bubbles: true })
    campoDe(buscador).element.dispatchEvent(evento)
    await avanzar(0)
    expect(evento.defaultPrevented).toBe(true)
    expect(buscador.emitted('seleccionar')).toEqual([['/tramites/tramite-2']])
    expect(buscador.emitted('buscar')).toBeUndefined()
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('false')
  })

  it('Intro sin opción activa deja que el formulario se envíe', async () => {
    const { buscador } = await conLista()
    const evento = new KeyboardEvent('keydown', { key: 'Enter', cancelable: true, bubbles: true })
    campoDe(buscador).element.dispatchEvent(evento)
    expect(evento.defaultPrevented).toBe(false)
    await buscador.get('form').trigger('submit')
    expect(buscador.emitted('buscar')).toEqual([['licencia']])
    expect(buscador.emitted('seleccionar')).toBeUndefined()
  })

  it('Intro durante una composición no es una orden', async () => {
    const { buscador } = await conLista()
    await tecla(buscador, 'ArrowDown')
    await tecla(buscador, 'Enter', { isComposing: true })
    expect(buscador.emitted('seleccionar')).toBeUndefined()
  })

  it('Esc cierra la lista y deja el texto; el segundo Esc lo borra', async () => {
    const { buscador } = await conLista()
    await tecla(buscador, 'ArrowDown')
    await tecla(buscador, 'Escape')
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('false')
    expect(campoDe(buscador).element.value).toBe('licencia')
    expect(campoDe(buscador).attributes('aria-activedescendant')).toBeUndefined()
    await tecla(buscador, 'Escape')
    expect(campoDe(buscador).element.value).toBe('')
  })

  it('Esc con el campo vacío no hace nada', async () => {
    const { buscador } = montar()
    campoDe(buscador).element.focus()
    await tecla(buscador, 'Escape')
    expect(buscador.emitted('limpiar')).toBeUndefined()
  })

  it('Esc con la lista cerrada y texto lo borra (comportamiento previo)', async () => {
    const { buscador } = montar()
    await escribir(buscador, 'li')
    await tecla(buscador, 'Escape')
    expect(campoDe(buscador).element.value).toBe('')
    expect(buscador.emitted('limpiar')).toHaveLength(1)
  })

  it('Tab cierra la lista', async () => {
    const { buscador } = await conLista()
    await tecla(buscador, 'Tab')
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('false')
  })

  it.each(['ArrowLeft', 'ArrowRight', 'Home', 'End'])('%s mueve el cursor y borra la opción activa', async (key) => {
    const { buscador } = await conLista()
    await tecla(buscador, 'ArrowDown')
    expect(campoDe(buscador).attributes('aria-activedescendant')).toBeDefined()
    await tecla(buscador, key)
    expect(campoDe(buscador).attributes('aria-activedescendant')).toBeUndefined()
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('true')
  })

  it('escribir después de cerrar con Esc vuelve a abrirla', async () => {
    const { buscador, llamadas } = await conLista()
    await tecla(buscador, 'Escape')
    await campoDe(buscador).setValue('licencias')
    await avanzar(350)
    llamadas[1]?.resolver(ok('Licencias de funcionamiento'))
    await avanzar(0)
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('true')
  })

  it('una respuesta tardía no reabre una lista que la persona cerró al enviar', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'licencia')
    await avanzar(350)
    await buscador.get('form').trigger('submit')
    llamadas[0]?.resolver(ok('Licencia de construcción'))
    await avanzar(0)
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('false')
  })
})

describe('composición con IME (§4.5, regla 4)', () => {
  it('no consulta durante la composición y consulta al terminarla', async () => {
    const { buscador, llamadas } = montar()
    const campo = campoDe(buscador)
    campo.element.focus()
    await campo.trigger('compositionstart')
    await campo.setValue('licenc')
    await avanzar(2000)
    expect(llamadas).toHaveLength(0)

    campo.element.value = 'licencia'
    await campo.trigger('compositionend')
    await avanzar(350)
    expect(llamadas.map((l) => l.consulta)).toEqual(['licencia'])
  })
})

describe('mensajes que no son opciones (§4.4)', () => {
  it('sin resultados: una fila de texto que no es opción, y el mismo texto se anuncia', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'zzzzz')
    await avanzar(350)
    llamadas[0]?.resolver(ok())
    await avanzar(0)
    const texto = 'No hay sugerencias. Pulse Intro para buscar en toda la sede.'
    expect(buscador.get('.mensaje-sugerencias-govco').text()).toBe(texto)
    expect(estadoDe(buscador)).toBe(texto)
    expect(opcionesDe(buscador)).toHaveLength(0)
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('true')
    expect(listaDe(buscador).attributes('hidden')).toBeDefined()
  })

  it('con el mensaje abierto, ↓ no activa nada', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'zzzzz')
    await avanzar(350)
    llamadas[0]?.resolver(ok())
    await avanzar(0)
    await tecla(buscador, 'ArrowDown')
    await tecla(buscador, 'ArrowUp')
    expect(campoDe(buscador).attributes('aria-activedescendant')).toBeUndefined()
  })

  it('error de red: «No pudimos consultar las sugerencias…», sin reintentar solo', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'licencia')
    await avanzar(350)
    llamadas[0]?.rechazar(new TypeError('sin conexión'))
    await avanzar(0)
    const texto = 'No pudimos consultar las sugerencias. Pulse Intro para buscar.'
    expect(buscador.get('.mensaje-sugerencias-govco').text()).toBe(texto)
    expect(estadoDe(buscador)).toBe(texto)
    await avanzar(10_000)
    expect(llamadas).toHaveLength(1)
  })

  it('429: dice cuántos segundos esperar, una sola vez y sin cuenta atrás', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'licencia')
    await avanzar(350)
    llamadas[0]?.resolver(conEstado(429, { 'Retry-After': '12' }))
    await avanzar(0)
    const texto =
      'Hizo muchas búsquedas seguidas. Espere 12 segundos para ver sugerencias; aun así puede pulsar Intro.'
    expect(buscador.get('.mensaje-sugerencias-govco').text()).toBe(texto)
    expect(estadoDe(buscador)).toBe(texto)
    await avanzar(5000)
    expect(estadoDe(buscador)).toBe(texto)
  })

  it('429 con un solo segundo, en singular', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'licencia')
    await avanzar(350)
    llamadas[0]?.resolver(conEstado(429, { 'Retry-After': '1' }))
    await avanzar(0)
    expect(buscador.get('.mensaje-sugerencias-govco').text()).toContain('Espere 1 segundo para')
  })

  it('al vaciar el campo con un 429 vigente, el mensaje no queda flotando', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'licencia')
    await avanzar(350)
    llamadas[0]?.resolver(conEstado(429, { 'Retry-After': '30' }))
    await avanzar(0)
    await buscador.get('.btn-clean-basic-govco').trigger('click')
    expect(buscador.find('.mensaje-sugerencias-govco').exists()).toBe(false)
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('false')
  })
})

describe('cargando (§4.4)', () => {
  it('a los 200 ms aparece el indicador y se anuncia; la lista previa se conserva', async () => {
    const { buscador, llamadas } = await conLista()
    await campoDe(buscador).setValue('licencias')
    await avanzar(350)
    expect(buscador.find('.indicador-carga-buscador').exists()).toBe(false)
    await avanzar(200)
    expect(buscador.find('.indicador-carga-buscador').exists()).toBe(true)
    expect(listaDe(buscador).attributes('aria-busy')).toBe('true')
    expect(estadoDe(buscador)).toBe('Buscando sugerencias.')
    // «Nunca un vacío intermedio»: las filas de antes siguen ahí.
    expect(opcionesDe(buscador)).toHaveLength(3)

    llamadas[1]?.resolver(ok('Licencias de funcionamiento'))
    await avanzar(0)
    expect(buscador.find('.indicador-carga-buscador').exists()).toBe(false)
    expect(opcionesDe(buscador)).toHaveLength(1)
  })

  it('el indicador es decorativo para los lectores de pantalla', async () => {
    const { buscador } = montar()
    await escribir(buscador, 'licencia')
    await avanzar(350 + 200)
    expect(buscador.get('.indicador-carga-buscador').attributes('aria-hidden')).toBe('true')
  })
})

describe('ratón y pantalla táctil (§4.5, regla 7)', () => {
  it('apretar la lista no le quita el foco al campo', async () => {
    const { buscador } = await conLista()
    const evento = new MouseEvent('mousedown', { cancelable: true, bubbles: true })
    buscador.get('.sugerencias-busqueda-govco').element.dispatchEvent(evento)
    expect(evento.defaultPrevented).toBe(true)
  })

  it('un clic sobre una opción navega a su dirección y cierra la lista', async () => {
    const { buscador } = await conLista()
    await opcionesDe(buscador)[1]?.trigger('click')
    expect(buscador.emitted('seleccionar')).toEqual([['/tramites/tramite-2']])
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('false')
  })

  it('si el foco se pierde con el puntero sobre la lista, ésta sigue abierta hasta el clic', async () => {
    const { buscador } = await conLista()
    const contenedor = buscador.get('.container-govco')
    const lista = buscador.get('.sugerencias-busqueda-govco')

    await lista.trigger('pointerdown')
    await contenedor.trigger('focusout', { relatedTarget: null })
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('true')

    await opcionesDe(buscador)[0]?.trigger('click')
    expect(buscador.emitted('seleccionar')).toEqual([['/tramites/tramite-1']])
  })

  it('al soltar fuera del buscador con el foco ya perdido, la lista se cierra', async () => {
    const { buscador } = await conLista()
    const lista = buscador.get('.sugerencias-busqueda-govco')
    await lista.trigger('pointerdown')
    campoDe(buscador).element.blur()
    await buscador.get('.container-govco').trigger('focusout', { relatedTarget: null })
    await lista.trigger('pointerup')
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('false')
  })

  it('soltar el puntero sin haber perdido el foco no cierra nada', async () => {
    const { buscador } = await conLista()
    const lista = buscador.get('.sugerencias-busqueda-govco')
    await lista.trigger('pointerdown')
    await lista.trigger('pointerup')
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('true')
  })

  it('perder el foco sin puntero sobre la lista la cierra', async () => {
    const { buscador } = await conLista()
    campoDe(buscador).element.blur()
    await buscador.get('.container-govco').trigger('focusout', { relatedTarget: null })
    expect(campoDe(buscador).attributes('aria-expanded')).toBe('false')
  })

  it('elegir una posición que ya no existe no hace nada', async () => {
    const { buscador } = await conLista()
    const vm = buscador.vm as unknown as { $: { setupState: { elegir: (i: number) => void } } }
    vm.$.setupState.elegir(99)
    expect(buscador.emitted('seleccionar')).toBeUndefined()
  })
})

describe('alto de la lista (§4.6)', () => {
  it('se limita al área visible que deja `visualViewport`, descontando el teclado', async () => {
    const visor = Object.assign(new EventTarget(), { height: 300, offsetTop: 0 })
    vi.stubGlobal('visualViewport', visor)
    Object.defineProperty(window, 'visualViewport', { value: visor, configurable: true })
    vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockReturnValue({ bottom: 100 } as DOMRect)

    const { buscador } = await conLista()
    await avanzar(0)
    const lista = buscador.get<HTMLElement>('.sugerencias-busqueda-govco')
    expect(lista.element.style.maxHeight).toBe('192px')

    // Aparece el teclado en pantalla: el área visible se acorta.
    visor.height = 250
    visor.dispatchEvent(new Event('resize'))
    await avanzar(0)
    await buscador.vm.$nextTick()
    expect(lista.element.style.maxHeight).toBe('142px')

    // Nunca menos de dos filas, aunque no quede sitio.
    visor.height = 110
    visor.dispatchEvent(new Event('resize'))
    await buscador.vm.$nextTick()
    expect(lista.element.style.maxHeight).toBe('96px')

    Reflect.deleteProperty(window, 'visualViewport')
  })

  it('sin `visualViewport` usa la altura de la ventana', async () => {
    Object.defineProperty(window, 'visualViewport', { value: undefined, configurable: true })
    vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockReturnValue({ bottom: 100 } as DOMRect)
    const { buscador } = await conLista()
    await avanzar(0)
    const esperado = `${Math.floor(window.innerHeight - 100 - 8)}px`
    expect(buscador.get<HTMLElement>('.sugerencias-busqueda-govco').element.style.maxHeight).toBe(esperado)
    window.dispatchEvent(new Event('resize'))
    Reflect.deleteProperty(window, 'visualViewport')
  })

  it('al desmontar quita sus escuchas', () => {
    const { buscador } = montar()
    const quitar = vi.spyOn(window, 'removeEventListener')
    buscador.unmount()
    envoltorio = null
    expect(quitar).toHaveBeenCalledWith('resize', expect.any(Function))
  })
})

describe('accesibilidad automática (TC-BUS-26, en jsdom)', () => {
  it('lista cerrada: sin violaciones', async () => {
    vi.useRealTimers()
    const { buscador } = montar()
    expect(await violacionesAxe(buscador.element)).toEqual([])
  })

  it('lista abierta con una opción activa: sin violaciones', async () => {
    const { buscador } = await conLista()
    await tecla(buscador, 'ArrowDown')
    vi.useRealTimers()
    expect(await violacionesAxe(buscador.element)).toEqual([])
  })

  it('sin resultados: sin violaciones', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'zzzzz')
    await avanzar(350)
    llamadas[0]?.resolver(ok())
    await avanzar(0)
    vi.useRealTimers()
    expect(await violacionesAxe(buscador.element)).toEqual([])
  })

  it('con error: sin violaciones', async () => {
    const { buscador, llamadas } = montar()
    await escribir(buscador, 'licencia')
    await avanzar(350)
    llamadas[0]?.rechazar(new TypeError('sin conexión'))
    await avanzar(0)
    vi.useRealTimers()
    expect(await violacionesAxe(buscador.element)).toEqual([])
  })
})

describe('formulario por GET sin lista (la página de resultados)', () => {
  it('`accion` da al formulario su dirección, el campo «q» y el máximo, sin volverlo combobox', () => {
    const { buscador } = montar({ conSugerencias: false, accion: '/buscar' })
    expect(buscador.get('form').attributes('action')).toBe('/buscar')
    expect(buscador.get('form').attributes('method')).toBe('get')
    const campo = campoDe(buscador)
    expect(campo.attributes('name')).toBe('q')
    expect(campo.attributes('maxlength')).toBe('120')
    expect(campo.attributes('role')).toBeUndefined()
    expect(buscador.find('[role="listbox"]').exists()).toBe(false)
  })

  it('sin `accion` ni sugerencias, el formulario no apunta a ninguna parte', () => {
    const { buscador } = montar({ conSugerencias: false })
    expect(buscador.get('form').attributes('action')).toBeUndefined()
    expect(campoDe(buscador).attributes('name')).toBeUndefined()
  })

  it('si el término de fuera cambia (otra búsqueda, el botón «atrás»), el campo lo sigue', async () => {
    const { buscador } = montar({ conSugerencias: false, accion: '/buscar', valorInicial: 'salud' })
    expect(campoDe(buscador).element.value).toBe('salud')
    await buscador.setProps({ valorInicial: 'licencia' })
    expect(campoDe(buscador).element.value).toBe('licencia')
  })

  it('`enfocar()` lleva el foco al campo', () => {
    const { buscador } = montar({ conSugerencias: false, accion: '/buscar' })
    ;(buscador.vm as unknown as { enfocar: () => void }).enfocar()
    expect(document.activeElement).toBe(campoDe(buscador).element)
  })
})

describe('secciones de la sede en la lista (M01-3 · RF-01-009)', () => {
  /** Escribe, deja pasar la pausa y contesta la API con la lista cruda que se le dé. */
  async function conRespuesta(texto: string, datos: ReturnType<typeof opcion>[]) {
    const { buscador, llamadas } = montar()
    await escribir(buscador, texto)
    await avanzar(350)
    llamadas[0]?.resolver({ status: 200, headers: { get: () => null }, cuerpo: { success: true, data: datos } })
    await avanzar(0)
    return buscador
  }

  it('una sección sale con su nombre y la categoría escrita «Sección»', async () => {
    const buscador = await conRespuesta('pqrsd', [])
    const filas = opcionesDe(buscador)
    expect(filas).toHaveLength(1)
    expect(filas[0]?.text()).toContain('Peticiones, quejas, reclamos, sugerencias y denuncias')
    expect(filas[0]?.text()).toContain('Sección')
  })

  it('elegir una sección lleva a su ruta con Intro', async () => {
    const buscador = await conRespuesta('pqrsd', [])
    await tecla(buscador, 'ArrowDown')
    await tecla(buscador, 'Enter')
    expect(buscador.emitted('seleccionar')).toEqual([['/pqrsd']])
  })
})
