/**
 * Noticias del home (M01-6, RF-01-013) en el sitio.
 *
 * «El home muestra las 6 más recientes en orden inverso, cada una con imagen y
 * `alt`, título ≤ 150, descripción ≤ 200, fecha y enlace; con 0 noticias, CT-01.»
 *
 * Aquí se prueba lo que no depende de Nuxt: la lógica pura (fecha, párrafos,
 * estado de la sección) y la tarjeta montada en jsdom. Las páginas completas
 * —portada, listado y detalle— se verifican en staging con el navegador.
 */
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import TarjetaNoticia from '../app/components/TarjetaNoticia.vue'
import {
  TEXTO_SIN_NOTICIAS,
  estadoDeNoticias,
  fechaLarga,
  urlDeImagen,
} from '../app/utils/noticias'
import { violacionesAxe } from './ayudas/axe'

const noticia = (extra: Record<string, unknown> = {}) => ({
  id: 1,
  type: 'noticias' as const,
  slug: 'inauguracion-del-parque',
  titulo: 'Inauguración del parque',
  descripcion_corta: 'La Alcaldía entregó el parque a la comunidad.',
  fecha_publicacion: '2026-11-01T10:00:00-05:00',
  destacada: false,
  imagen: { url: '/api/v1/noticias/inauguracion-del-parque/imagen', alt: 'Cinta inaugural del parque' },
  ...extra,
})

const NuxtLink = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const montar = (props: Record<string, unknown>) =>
  mount(TarjetaNoticia, { attachTo: document.body, props, global: { stubs: { NuxtLink } } })

describe('fecha', () => {
  it('se escribe en español, con el mes en letras, en la hora de Colombia', () => {
    expect(fechaLarga('2026-11-01T10:00:00-05:00')).toBe('1 de noviembre de 2026')
  })

  it('una hora cercana a la medianoche no cambia de día por la zona horaria del servidor', () => {
    // 2026-11-02T03:30Z es el 1 de noviembre a las 22:30 en Bogotá.
    expect(fechaLarga('2026-11-02T03:30:00Z')).toBe('1 de noviembre de 2026')
  })

  it('una fecha ilegible no inventa nada', () => {
    expect(fechaLarga('no es una fecha')).toBe('')
  })
})

describe('imagen', () => {
  it('sólo acepta la dirección estable de la API; cualquier otra no se pinta', () => {
    const buena = { url: '/api/v1/noticias/x/imagen', alt: 'a' }
    expect(urlDeImagen(buena)).toBe('/api/v1/noticias/x/imagen')
    expect(urlDeImagen(null)).toBeNull()
    expect(urlDeImagen({ url: 'https://otro.example/foto.png', alt: 'a' })).toBeNull()
    expect(urlDeImagen({ url: '//otro.example/foto.png', alt: 'a' })).toBeNull()
    expect(urlDeImagen({ url: 'javascript:alert(1)', alt: 'a' })).toBeNull()
  })
})

describe('estado de la sección (CT-01)', () => {
  it('distingue una API caída de una Entidad sin noticias', () => {
    expect(estadoDeNoticias(null)).toBe('error')
    expect(estadoDeNoticias({ data: [] })).toBe('vacio')
    expect(estadoDeNoticias({ data: [noticia()] })).toBe('con-noticias')
  })

  it('el texto de «sin noticias» sigue la regla CT-01 y no promete nada', () => {
    expect(TEXTO_SIN_NOTICIAS).toContain('No consta información publicada')
    expect(TEXTO_SIN_NOTICIAS).toMatch(/Entidad/)
  })
})

describe('tarjeta de noticia', () => {
  it('muestra imagen con su alt, título, descripción, fecha y un solo enlace al detalle', () => {
    const t = montar({ noticia: noticia() })

    const img = t.get('img')
    expect(img.attributes('alt')).toBe('Cinta inaugural del parque')
    expect(img.attributes('src')).toBe('/api/v1/noticias/inauguracion-del-parque/imagen')
    expect(t.get('h3').text()).toBe('Inauguración del parque')
    expect(t.text()).toContain('La Alcaldía entregó el parque a la comunidad.')

    const hora = t.get('time')
    expect(hora.attributes('datetime')).toBe('2026-11-01T10:00:00-05:00')
    expect(hora.text()).toBe('1 de noviembre de 2026')

    const enlaces = t.findAll('a')
    expect(enlaces).toHaveLength(1)
    expect(enlaces[0]?.attributes('href')).toBe('/noticias/inauguracion-del-parque')
    t.unmount()
  })

  it('no recorta un título de 150 caracteres', () => {
    const titulo = 'T'.repeat(150)
    const t = montar({ noticia: noticia({ titulo }) })
    expect(t.get('h3').text()).toBe(titulo)
    t.unmount()
  })

  it('sin imagen no inventa una: no pinta ningún <img>', () => {
    const t = montar({ noticia: noticia({ imagen: null }) })
    expect(t.find('img').exists()).toBe(false)
    t.unmount()
  })

  it('la imagen reserva su caja y se recorta al mostrar (sin deformarse ni saltar)', () => {
    const fuente = readFileSync(resolve(__dirname, '../app/components/TarjetaNoticia.vue'), 'utf8')
    expect(fuente).toMatch(/aspect-ratio:\s*16\s*\/\s*9/)
    expect(fuente).toMatch(/object-fit:\s*cover/)
    expect(fuente).toMatch(/loading="lazy"/)
  })

  it('no tiene violaciones de accesibilidad (axe)', async () => {
    const t = montar({ noticia: noticia() })
    expect(await violacionesAxe(t.element)).toEqual([])
    t.unmount()
  })
})
