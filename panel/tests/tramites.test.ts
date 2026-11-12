/**
 * Trámites en el panel: lógica del formulario, servicio, permiso y rutas.
 *
 * El servidor valida siempre. Aquí se comprueba lo que protege a quien edita: que solo viaje lo que
 * cambió (así un trámite del catálogo oficial no pierde datos que la pantalla no toca), que se diga en
 * español qué falta para publicar y que el permiso y las rutas existan.
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { PERMISO_POR_RUTA } from '../src/config/permisos'
import enrutador from '../src/router/index'
import type { TramitePanelItem } from '../src/types/api'
import {
  entradaDesde,
  faltaParaPublicar,
  formularioDesde,
  formularioVacio,
  hayCambios,
  mensajeLegible,
  motivosLegibles,
  problemasLocales,
  rutaLegible,
} from '../src/views/admin/tramites-logica'

const tramite = (extra: Partial<TramitePanelItem> = {}): TramitePanelItem =>
  ({
    id: 1,
    uuid: '33333333-3333-4333-8333-333333333333',
    type: 'tramites',
    slug: 'certificado-de-residencia',
    codigo: 'P0001',
    nombre: 'Certificado de residencia',
    resumen: 'Constancia de dónde vive.',
    url_ficha_gov_co: 'https://www.gov.co/ficha-tramites-y-servicios/T1',
    modalidad: 'presencial',
    tiene_costo: 'con_costo',
    costo: { tiene_costo: 'con_costo', tipo_valor: 'fijo', valor: '25000.00', moneda: 'COP', url_pago: null, descripcion: null, cuentas: [] },
    tiempo_solucion_dias: 3,
    canal_inicio: 'propio',
    url_inicio: null,
    consulta_estado: 'Llamando a la oficina',
    requisitos: [{ tipo: 'documento', descripcion: 'Cédula', obligatorio: true, cantidad: 1 }],
    documentos: [],
    momentos: [],
    audiencias: [],
    perfiles: ['Adulto mayor'],
    medios_resultado: ['Correo'],
    puntos_atencion: [],
    normativa: [],
    cuentas: [],
    canales_consulta_estado: [],
    seguimiento: { telefono: [{ numero: '4380000' }], email: [], web: [], presencial: [] },
    categoria: null,
    estado: 'publicado',
    origen: 'panel',
    completo: true,
    problemas: [],
    ...extra,
  }) as unknown as TramitePanelItem

describe('formulario', () => {
  it('carga lo que entrega la API', () => {
    const f = formularioDesde(tramite())
    expect(f.nombre).toBe('Certificado de residencia')
    expect(f.tiempo_solucion_dias).toBe('3')
    expect(f.costo_valor).toBe('25000.00')
    expect(f.requisitos).toHaveLength(1)
    // Las listas de textos sueltos se editan como filas con un solo campo.
    expect(f.perfiles).toEqual([{ valor: 'Adulto mayor' }])
    expect(f.seg_telefono).toEqual([{ numero: '4380000' }])
    expect(f.publicado).toBe(true)
  })

  it('un trámite en borrador y vacío no revienta', () => {
    const f = formularioDesde(
      tramite({ modalidad: null, tiene_costo: null, tiempo_solucion_dias: null, canal_inicio: null, seguimiento: null, costo: null, estado: 'borrador' } as never),
    )
    expect(f.modalidad).toBe('')
    expect(f.tiempo_solucion_dias).toBe('')
    expect(f.seg_telefono).toEqual([])
    expect(f.publicado).toBe(false)
  })

  it('envía solo lo que cambió', () => {
    const original = formularioDesde(tramite())
    const editado = { ...original, nombre: 'Otro nombre' }
    expect(entradaDesde(editado, original)).toEqual({ nombre: 'Otro nombre' })
    expect(entradaDesde(original, original)).toEqual({})
  })

  it('al crear envía el nombre y lo que se llenó, sin vacíos', () => {
    const vacio = formularioVacio()
    const entrada = entradaDesde({ ...vacio, nombre: '  Licencia  ', modalidad: 'presencial' }, null)
    expect(entrada).toEqual({ nombre: 'Licencia', modalidad: 'presencial' })
  })

  it('vaciar un campo envía null, no se queda el valor viejo', () => {
    const original = formularioDesde(tramite())
    expect(entradaDesde({ ...original, resumen: '' }, original)).toEqual({ resumen: null })
  })

  it('el tiempo viaja como número', () => {
    const original = formularioDesde(tramite())
    expect(entradaDesde({ ...original, tiempo_solucion_dias: '10' }, original)).toEqual({ tiempo_solucion_dias: 10 })
  })

  it('una lista cambiada viaja entera; una sin tocar no viaja', () => {
    const original = formularioDesde(tramite())
    const editado = { ...original, requisitos: [...original.requisitos, { tipo: 'pago', descripcion: 'Recibo' }] }
    const entrada = entradaDesde(editado, original)
    expect(Object.keys(entrada)).toEqual(['requisitos'])
    expect(entrada.requisitos).toHaveLength(2)
  })

  it('las filas de texto suelto vuelven a ser una lista de textos y descartan las vacías', () => {
    const original = formularioDesde(tramite())
    const entrada = entradaDesde({ ...original, perfiles: [{ valor: 'Joven' }, { valor: '  ' }] }, original)
    expect(entrada.perfiles).toEqual(['Joven'])
  })

  it('el costo viaja como un solo bloque cuando cambia cualquiera de sus partes', () => {
    const original = formularioDesde(tramite())
    const entrada = entradaDesde({ ...original, costo_valor: '30000' }, original)
    expect(entrada.costo).toMatchObject({ valor: '30000', tipo_valor: 'fijo', moneda: 'COP' })
  })

  it('el seguimiento viaja entero si cambia una de sus listas', () => {
    const original = formularioDesde(tramite())
    const entrada = entradaDesde({ ...original, seg_email: [{ email: 'a@b.co' }] }, original)
    expect(entrada.seguimiento).toEqual({ telefono: [{ numero: '4380000' }], email: [{ email: 'a@b.co' }], web: [], presencial: [] })
  })

  it('las filas conservan los datos que la pantalla no muestra', () => {
    const original = formularioDesde(tramite({ requisitos: [{ tipo: 'pago', descripcion: 'x', pago: [{ valor: '1' }], canales: [{ tipo: 'web' }] }] as never }))
    original.requisitos[0]!.descripcion = 'y'
    const entrada = entradaDesde(original, formularioDesde(tramite({ requisitos: [{ tipo: 'pago', descripcion: 'x', pago: [{ valor: '1' }], canales: [{ tipo: 'web' }] }] as never })))
    expect(entrada.requisitos).toEqual([{ tipo: 'pago', descripcion: 'y', pago: [{ valor: '1' }], canales: [{ tipo: 'web' }] }])
  })

  it('hayCambios detecta una edición y no se confunde con espacios al final', () => {
    const original = formularioDesde(tramite())
    expect(hayCambios({ ...original }, original)).toBe(false)
    expect(hayCambios({ ...original, nombre: 'Otro' }, original)).toBe(true)
  })
})

describe('qué falta para publicar', () => {
  it('lista en español lo que falta', () => {
    const f = formularioVacio()
    const falta = faltaParaPublicar(f)
    expect(falta).toContain('Falta la dirección de la ficha en GOV.CO.')
    expect(falta).toContain('Falta al menos un requisito.')
    expect(falta).toHaveLength(7)
  })

  it('un trámite completo no tiene faltantes', () => {
    expect(faltaParaPublicar(formularioDesde(tramite()))).toEqual([])
  })
})

describe('problemas antes de enviar', () => {
  it('el nombre necesita al menos 3 letras', () => {
    expect(problemasLocales({ ...formularioVacio(), nombre: 'ab' })[0]).toMatch(/nombre/i)
    expect(problemasLocales({ ...formularioVacio(), nombre: 'abc' })).toEqual([])
  })

  it('las direcciones web deben ser http o https', () => {
    const f = { ...formularioVacio(), nombre: 'Licencia', url_ficha_gov_co: 'javascript:alert(1)' }
    expect(problemasLocales(f).join(' ')).toMatch(/ficha/i)
  })

  it('el tiempo debe ser un entero de cero o más', () => {
    expect(problemasLocales({ ...formularioVacio(), nombre: 'Licencia', tiempo_solucion_dias: '-2' }).join(' ')).toMatch(/tiempo/i)
    expect(problemasLocales({ ...formularioVacio(), nombre: 'Licencia', tiempo_solucion_dias: '1.5' }).join(' ')).toMatch(/tiempo/i)
  })
})

describe('mensajes del servidor en español sencillo', () => {
  it('traduce la ruta de un campo de una lista', () => {
    expect(rutaLegible('momentos.1.titulo')).toBe('Paso 2 · Título')
    expect(rutaLegible('momentos.1.requisitos.0.tipo')).toBe('Paso 2, Requisito 1 · Tipo')
    expect(rutaLegible('seguimiento.email.0.email')).toBe('Correo de seguimiento 1 · Correo')
    expect(rutaLegible('costo.cuentas.2.titular')).toBe('Cuenta del costo 3 · Titular')
    expect(rutaLegible('url_ficha_gov_co')).toBe('Dirección de la ficha en GOV.CO')
  })

  it('cambia el nombre técnico dentro del mensaje, con o sin guiones bajos', () => {
    expect(mensajeLegible('momentos.1.titulo', 'El campo momentos.1.titulo es obligatorio.')).toBe('El campo «Paso 2 · Título» es obligatorio.')
  })

  it('junta todos los motivos y deja pasar tal cual los del trámite incompleto', () => {
    expect(motivosLegibles({ tramite: ['Falta al menos un requisito.'], 'requisitos.0.url': ['El campo requisitos.0.url debe ser una URL válida.'] })).toEqual([
      'Falta al menos un requisito.',
      'El campo «Requisito 1 · Dirección web» debe ser una URL válida.',
    ])
  })
})

describe('permiso y rutas', () => {
  it('el módulo se gobierna con tramites.gestionar', () => {
    expect(PERMISO_POR_RUTA['/tramites']).toBe('tramites.gestionar')
  })

  it('existen la lista, la de nuevo y la de edición (solo con un uuid)', () => {
    const nombres = enrutador.getRoutes().map((r) => r.name)
    expect(nombres).toContain('tramites')
    expect(nombres).toContain('panel.tramites.nuevo')
    expect(nombres).toContain('panel.tramites.editar')
    expect(enrutador.resolve('/tramites/cualquier-cosa').name).not.toBe('panel.tramites.editar')
  })
})

vi.mock('../src/services/http', async (original) => {
  const real = await original<typeof import('../src/services/http')>()
  return { ...real, http: { get: vi.fn(), post: vi.fn(), patch: vi.fn(), delete: vi.fn() } }
})

describe('servicio', () => {
  let http: { get: ReturnType<typeof vi.fn>; post: ReturnType<typeof vi.fn>; patch: ReturnType<typeof vi.fn>; delete: ReturnType<typeof vi.fn> }
  beforeEach(async () => {
    http = (await import('../src/services/http')).http as never
    Object.values(http).forEach((f) => f.mockReset())
  })

  it('listar manda solo los filtros con valor', async () => {
    http.get.mockResolvedValue({ data: { success: true, message: null, data: [], meta: { total: 0 }, links: {}, errors: null } })
    const { listar } = await import('../src/services/tramites')
    await listar({ pagina: 2, q: '  ', estado: '', ordenarPor: 'nombre', direccion: 'asc' })
    expect(http.get).toHaveBeenCalledWith('/panel/tramites', { params: { page: 2, per_page: 20, ordenar_por: 'nombre', direccion: 'asc' } })
    await listar({ q: ' predial ', estado: 'borrador' })
    expect(http.get).toHaveBeenLastCalledWith('/panel/tramites', { params: { page: 1, per_page: 20, q: 'predial', estado: 'borrador' } })
  })

  it('crear, actualizar y eliminar usan las rutas del panel', async () => {
    const respuesta = { data: { success: true, message: null, data: { uuid: 'u' }, errors: null } }
    http.post.mockResolvedValue(respuesta)
    http.patch.mockResolvedValue(respuesta)
    http.get.mockResolvedValue(respuesta)
    http.delete.mockResolvedValue({ data: null })
    const s = await import('../src/services/tramites')
    await s.crear({ nombre: 'Licencia' })
    expect(http.post).toHaveBeenCalledWith('/panel/tramites', { nombre: 'Licencia' })
    await s.actualizar('u', { nombre: 'X' })
    expect(http.patch).toHaveBeenCalledWith('/panel/tramites/u', { nombre: 'X' })
    await s.obtener('u')
    expect(http.get).toHaveBeenCalledWith('/panel/tramites/u')
    await s.eliminar('u')
    expect(http.delete).toHaveBeenCalledWith('/panel/tramites/u')
  })
})
