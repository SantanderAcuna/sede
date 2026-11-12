/**
 * Noticias en el panel (M01-6, RF-01-013): lógica del formulario, servicio, permiso y ruta.
 *
 * El requisito: título ≤ 150, descripción ≤ 200, y toda imagen con su `alt`
 * (Res. MinTIC 1519/2020). El servidor lo valida siempre; aquí se comprueba que el
 * formulario lo exige antes de enviar y que lo explica en palabras de quien edita.
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { PERMISO_POR_RUTA } from '../src/config/permisos'
import enrutador from '../src/router/index'
import type { NoticiaPanelItem } from '../src/types/api'
import {
  caracteresRestantes,
  entradaDesde,
  formularioDesde,
  formularioVacio,
  hayCambios,
  problemasLocales,
  tieneTexto,
} from '../src/views/admin/noticias-logica'

const noticia = (extra: Partial<NoticiaPanelItem> = {}): NoticiaPanelItem => ({
  id: 7,
  type: 'noticias',
  slug: 'inauguracion-del-parque',
  titulo: 'Inauguración del parque',
  descripcion_corta: 'La Alcaldía entregó el parque.',
  fecha_publicacion: '2026-11-01T10:00:00-05:00',
  destacada: false,
  imagen: { url: '/api/v1/noticias/inauguracion-del-parque/imagen', alt: 'Cinta inaugural del parque' },
  contenido: 'Primer párrafo.\n\nSegundo párrafo.',
  uuid: '22222222-2222-4222-8222-222222222222',
  activo: true,
  completa: true,
  problemas: [],
  ...extra,
})

describe('formulario', () => {
  it('carga lo que entrega la API y el alt de la imagen', () => {
    const f = formularioDesde(noticia())
    expect(f.titulo).toBe('Inauguración del parque')
    expect(f.contenido).toBe('Primer párrafo.\n\nSegundo párrafo.')
    expect(f.imagen_alt).toBe('Cinta inaugural del parque')
    expect(f.activo).toBe(true)
    // El campo de fecha del navegador pide `AAAA-MM-DDTHH:mm`, sin zona.
    expect(f.fecha_publicacion).toMatch(/^2026-11-01T\d{2}:\d{2}$/)
  })

  it('una noticia sin imagen deja el alt vacío', () => {
    expect(formularioDesde(noticia({ imagen: null })).imagen_alt).toBe('')
  })

  it('envía sólo lo que cambió', () => {
    const original = formularioDesde(noticia())
    const editado = { ...original, titulo: 'Otro título' }

    expect(entradaDesde(editado, original)).toEqual({ titulo: 'Otro título' })
    expect(entradaDesde(original, original)).toEqual({})
  })

  it('al crear, la fecha de publicación viaja aunque no se haya cambiado', () => {
    const vacio = formularioVacio()
    const lleno = { ...vacio, titulo: 'T', descripcion_corta: 'D', contenido: 'C' }

    const entrada = entradaDesde(lleno, vacio, { completa: true })

    expect(entrada.fecha_publicacion).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:00[+-]\d{2}:\d{2}$/)
    // Al editar (sin `completa`) una fecha sin tocar sigue sin enviarse.
    expect(entradaDesde(lleno, lleno).fecha_publicacion).toBeUndefined()
  })

  it('un cambio de alt viaja como imagen_alt; vaciar el contenido no se envía como cadena vacía', () => {
    const original = formularioDesde(noticia())

    expect(entradaDesde({ ...original, imagen_alt: 'Nueva descripción' }, original)).toEqual({
      imagen_alt: 'Nueva descripción',
    })
    expect(entradaDesde({ ...original, imagen_alt: '  Con espacios  ' }, original)).toEqual({
      imagen_alt: 'Con espacios',
    })
  })

  it('la fecha viaja con zona horaria (ISO 8601)', () => {
    const original = formularioDesde(noticia())
    const entrada = entradaDesde({ ...original, fecha_publicacion: '2026-12-01T08:30' }, original)

    expect(entrada.fecha_publicacion).toMatch(/^2026-12-01T\d{2}:30:00(\.\d+)?(Z|[+-]\d{2}:\d{2})$/)
  })

  it('detecta si hay cambios', () => {
    const original = formularioDesde(noticia())
    expect(hayCambios(original, original)).toBe(false)
    expect(hayCambios({ ...original, destacada: true }, original)).toBe(true)
  })
})

describe('lo que se comprueba antes de enviar', () => {
  const ok = () => formularioDesde(noticia())

  it('una noticia completa no tiene problemas', () => {
    expect(problemasLocales(ok(), { tieneImagen: true, imagenNueva: false })).toEqual([])
  })

  it('exige título, descripción y contenido', () => {
    const vacio = { ...ok(), titulo: '  ', descripcion_corta: '', contenido: '\n' }
    expect(problemasLocales(vacio, { tieneImagen: true, imagenNueva: false })).toHaveLength(3)
  })

  it('el título admite 150 y rechaza 151; la descripción admite 200 y rechaza 201', () => {
    const contexto = { tieneImagen: true, imagenNueva: false }

    expect(problemasLocales({ ...ok(), titulo: 'a'.repeat(150), descripcion_corta: 'b'.repeat(200) }, contexto)).toEqual([])
    expect(problemasLocales({ ...ok(), titulo: 'a'.repeat(151) }, contexto)).toHaveLength(1)
    expect(problemasLocales({ ...ok(), descripcion_corta: 'b'.repeat(201) }, contexto)).toHaveLength(1)
  })

  it('no se puede publicar sin imagen', () => {
    const problemas = problemasLocales({ ...ok(), activo: true }, { tieneImagen: false, imagenNueva: false })
    expect(problemas.join(' ')).toMatch(/imagen/i)
  })

  it('un borrador sin imagen sí se puede guardar', () => {
    expect(problemasLocales({ ...ok(), activo: false }, { tieneImagen: false, imagenNueva: false })).toEqual([])
  })

  it('con imagen, el alt es obligatorio: vacío o de espacios no pasa', () => {
    for (const alt of ['', '   ']) {
      const problemas = problemasLocales({ ...ok(), imagen_alt: alt }, { tieneImagen: true, imagenNueva: false })
      expect(problemas.join(' ')).toMatch(/alt|descripci[oó]n de la imagen/i)
    }
  })

  it('al elegir una imagen nueva sin escribir su alt, no pasa aunque sea borrador', () => {
    const problemas = problemasLocales({ ...ok(), activo: false, imagen_alt: '' }, { tieneImagen: false, imagenNueva: true })
    expect(problemas.join(' ')).toMatch(/alt|descripci[oó]n de la imagen/i)
  })

  it('el alt no pasa de 250 caracteres', () => {
    const f = { ...ok(), imagen_alt: 'x'.repeat(251) }
    expect(problemasLocales(f, { tieneImagen: true, imagenNueva: false })).toHaveLength(1)
  })
})

describe('contador de caracteres', () => {
  it('dice cuántos quedan y es negativo si se pasó', () => {
    expect(caracteresRestantes('abc', 150)).toBe(147)
    expect(caracteresRestantes('a'.repeat(160), 150)).toBe(-10)
    expect(caracteresRestantes('', 200)).toBe(200)
  })
})

describe('servicio', () => {
  const peticion = { get: vi.fn(), patch: vi.fn(), post: vi.fn(), delete: vi.fn() }

  beforeEach(() => {
    vi.resetModules()
    for (const f of Object.values(peticion)) f.mockReset()
    vi.doMock('../src/services/http', () => ({
      http: peticion,
      desenvolver: (s: { data: unknown }) => s.data,
      desenvolverPagina: (s: { data: unknown; meta: unknown; links: unknown }) => ({
        datos: s.data,
        meta: s.meta,
        enlaces: s.links,
      }),
    }))
  })

  const sobrePagina = (datos: unknown[] = []) => ({
    data: {
      success: true,
      message: null,
      data: datos,
      meta: { current_page: 2, from: 21, last_page: 3, path: '/x', per_page: 20, to: 40, total: 55 },
      links: { first: null, last: null, prev: null, next: null },
      errors: null,
    },
  })

  it('crea, edita, consulta y elimina contra las rutas del contrato', async () => {
    const servicio = await import('../src/services/noticias')
    peticion.get.mockResolvedValue({ data: { success: true, data: { uuid: 'u' } } })
    peticion.post.mockResolvedValue({ data: { success: true, data: { uuid: 'u' } } })
    peticion.patch.mockResolvedValue({ data: { success: true, data: { uuid: 'u' } } })
    peticion.delete.mockResolvedValue({ data: { success: true } })

    await servicio.crear({ titulo: 'T' })
    expect(peticion.post).toHaveBeenCalledWith('/panel/noticias', { titulo: 'T' })

    await servicio.obtener('u-1')
    expect(peticion.get).toHaveBeenCalledWith('/panel/noticias/u-1')

    await servicio.actualizar('u-1', { activo: true })
    expect(peticion.patch).toHaveBeenCalledWith('/panel/noticias/u-1', { activo: true })

    await servicio.eliminar('u-1')
    expect(peticion.delete).toHaveBeenCalledWith('/panel/noticias/u-1')
  })

  it('listar entrega la página con su meta y envía sólo los filtros que tienen valor', async () => {
    const servicio = await import('../src/services/noticias')
    peticion.get.mockResolvedValue(sobrePagina([{ uuid: 'a' }]))

    const pagina = await servicio.listar({
      pagina: 2,
      porPagina: 20,
      q: '  parque ',
      estado: '',
      ordenarPor: 'titulo',
      direccion: 'asc',
    })

    expect(peticion.get).toHaveBeenCalledWith('/panel/noticias', {
      params: { page: 2, per_page: 20, q: 'parque', ordenar_por: 'titulo', direccion: 'asc' },
    })
    expect(pagina.datos).toHaveLength(1)
    expect(pagina.meta.total).toBe(55)
    expect(pagina.meta.last_page).toBe(3)
  })

  it('listar sin filtros sólo pide la página', async () => {
    const servicio = await import('../src/services/noticias')
    peticion.get.mockResolvedValue(sobrePagina())

    await servicio.listar({})

    expect(peticion.get).toHaveBeenCalledWith('/panel/noticias', { params: { page: 1, per_page: 20 } })
  })

  it('listar con estado lo envía tal cual', async () => {
    const servicio = await import('../src/services/noticias')
    peticion.get.mockResolvedValue(sobrePagina())

    await servicio.listar({ estado: 'programada' })

    expect(peticion.get).toHaveBeenCalledWith('/panel/noticias', {
      params: { page: 1, per_page: 20, estado: 'programada' },
    })
  })

  it('sube la imagen y su alt juntos, en un solo multipart', async () => {
    const servicio = await import('../src/services/noticias')
    peticion.post.mockResolvedValue({ data: { success: true, data: { uuid: 'u' } } })
    const archivo = new File(['x'], 'foto.png', { type: 'image/png' })

    await servicio.subirImagen('u-1', archivo, 'Cinta inaugural')

    const [ruta, cuerpo] = peticion.post.mock.calls[0] as [string, FormData]
    expect(ruta).toBe('/panel/noticias/u-1/imagen')
    expect(cuerpo.get('alt')).toBe('Cinta inaugural')
    expect((cuerpo.get('imagen') as File).name).toBe('foto.png')
  })
})

describe('permiso y ruta', () => {
  it('el módulo exige noticias.gestionar, el mismo permiso que comprueba el servidor', () => {
    expect(PERMISO_POR_RUTA['/contenidos/noticias']).toBe('noticias.gestionar')
  })

  it('la ruta existe, pide sesión y declara ese permiso', () => {
    const ruta = enrutador.getRoutes().find((r) => r.path === '/contenidos/noticias')

    expect(ruta).toBeDefined()
    expect(ruta?.meta.requiereSesion).toBe(true)
    expect(ruta?.meta.permiso).toBe('noticias.gestionar')
  })

  it('crear y editar son vistas aparte de la lista, con el mismo permiso', () => {
    const nueva = enrutador.getRoutes().find((r) => r.name === 'panel.noticias.nueva')
    const editar = enrutador.getRoutes().find((r) => r.name === 'panel.noticias.editar')

    expect(nueva?.path).toBe('/contenidos/noticias/nueva')
    expect(editar?.path).toMatch(/^\/contenidos\/noticias\/:uuid/)
    for (const ruta of [nueva, editar]) {
      expect(ruta?.meta.requiereSesion).toBe(true)
      expect(ruta?.meta.permiso).toBe('noticias.gestionar')
    }
    expect(nueva?.meta.titulo).toBe('Nueva noticia')
    expect(editar?.meta.titulo).toBe('Editar noticia')
  })

  it('«nueva» no se confunde con un uuid, y un uuid válido sí resuelve a editar', () => {
    expect(enrutador.resolve('/contenidos/noticias/nueva').name).toBe('panel.noticias.nueva')
    expect(enrutador.resolve('/contenidos/noticias/3f2a9c1e-1b2d-4c3e-8f4a-5b6c7d8e9f01').name).toBe(
      'panel.noticias.editar',
    )
    expect(enrutador.resolve('/contenidos/noticias/algo-que-no-es-uuid').name).toBe('no-encontrado')
  })
})

describe('tieneTexto — el contenido del editor es HTML', () => {
  it.each([
    ['', false],
    ['  ', false],
    ['<p></p>', false],
    ['<p> &nbsp; </p>', false],
    ['<p><br></p>', false],
    ['<p><img src="/api/v1/contenido/imagenes/x" alt="a"></p>', false],
    ['<p>Hola</p>', true],
    ['Hola', true],
    ['<h2>Título</h2>', true],
  ])('%j → %s', (html, esperado) => {
    expect(tieneTexto(html)).toBe(esperado)
  })

  it('un contenido solo con una imagen falta en la lista de problemas locales', () => {
    const f = { ...formularioVacio(), titulo: 'T', descripcion_corta: 'D', contenido: '<p><img src="/api/v1/contenido/imagenes/x" alt="a"></p>' }
    expect(problemasLocales(f, { tieneImagen: false, imagenNueva: false })).toContain('Falta el contenido de la noticia.')
  })
})
