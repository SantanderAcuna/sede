/**
 * Trámites en el panel: lógica del formulario, servicio, permiso y rutas.
 *
 * El catálogo es contenido propio y relacional (pasos, requisitos, solicitantes, dependencia). El
 * servidor valida siempre; aquí se comprueba lo que protege a quien edita: que los campos simples
 * viajen sólo si cambiaron, que la **ficha viaje entera en cuanto cualquier parte cambia** (el
 * servidor la reemplaza completa), que se diga en español qué falta para publicar, y que el permiso
 * y las rutas existan.
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
    codigo: 'T0001',
    nombre: 'Certificado de residencia',
    resumen: 'Constancia de dónde vive.',
    url_ficha_gov_co: 'https://www.gov.co/ficha-tramites-y-servicios/T1',
    modalidad: 'presencial',
    tiene_costo: 'con_costo',
    costo: { tiene_costo: 'con_costo', tipo_valor: 'fijo', valor: 25000, moneda: 'COP', url_pago: null, descripcion: null, cuentas: [] },
    tiempo_solucion_dias: 3,
    canal_inicio: 'propio',
    url_inicio: null,
    consulta_estado: 'Llamando a la oficina',
    dependencia_id: 2,
    requisitos: [{ tipo: 'documento', descripcion: 'Cédula', obligatorio: true, paso: null }],
    pasos: [],
    documentos: [],
    puntos_atencion: [],
    normativa: [],
    canales_consulta_estado: [],
    solicitantes: ['ciudadano'],
    medios_resultado: ['Correo'],
    categoria: null,
    estado: 'publicado',
    completo: true,
    problemas: [],
    ...extra,
  }) as unknown as TramitePanelItem

describe('formulario', () => {
  it('carga lo que entrega la API', () => {
    const f = formularioDesde(tramite())
    expect(f.nombre).toBe('Certificado de residencia')
    expect(f.type).toBe('tramites')
    expect(f.tiempo_solucion_dias).toBe('3')
    expect(f.costo_valor).toBe('25000')
    expect(f.requisitos).toHaveLength(1)
    expect(f.solicitantes).toEqual(['ciudadano'])
    expect(f.dependencia_id).toBe('2')
    // Las listas de textos sueltos se editan como filas con un solo campo.
    expect(f.medios_resultado).toEqual([{ valor: 'Correo' }])
    expect(f.publicado).toBe(true)
  })

  it('un trámite en borrador y vacío no revienta', () => {
    const f = formularioDesde(
      tramite({ modalidad: null, tiene_costo: null, tiempo_solucion_dias: null, canal_inicio: null, costo: null, dependencia_id: null, estado: 'borrador' } as never),
    )
    expect(f.modalidad).toBe('')
    expect(f.tiempo_solucion_dias).toBe('')
    expect(f.dependencia_id).toBe('')
    expect(f.costo_valor).toBe('')
    expect(f.publicado).toBe(false)
  })

  it('envía solo el campo simple que cambió', () => {
    const original = formularioDesde(tramite())
    const editado = { ...original, nombre: 'Otro nombre' }
    expect(entradaDesde(editado, original)).toEqual({ nombre: 'Otro nombre' })
    expect(entradaDesde(original, original)).toEqual({})
  })

  it('al crear envía el nombre, el tipo y lo que se llenó, sin vacíos', () => {
    const vacio = formularioVacio()
    const entrada = entradaDesde({ ...vacio, nombre: '  Licencia  ', modalidad: 'presencial' }, null)
    expect(entrada).toEqual({ nombre: 'Licencia', type: 'tramites', modalidad: 'presencial' })
  })

  it('vaciar un campo envía null, no se queda el valor viejo', () => {
    const original = formularioDesde(tramite())
    expect(entradaDesde({ ...original, resumen: '' }, original)).toEqual({ resumen: null })
  })

  it('el tiempo y la dependencia viajan como número', () => {
    const original = formularioDesde(tramite())
    expect(entradaDesde({ ...original, tiempo_solucion_dias: '10' }, original)).toEqual({ tiempo_solucion_dias: 10 })
    expect(entradaDesde({ ...original, dependencia_id: '5' }, original)).toEqual({ dependencia_id: 5 })
  })

  it('cuando cambia una lista de la ficha, viaja la ficha entera (el servidor la reemplaza)', () => {
    const original = formularioDesde(tramite())
    const editado = { ...original, requisitos: [...original.requisitos, { tipo: 'pago', descripcion: 'Recibo' }] }
    const entrada = entradaDesde(editado, original) as Record<string, unknown>
    // No sólo requisitos: también las demás listas y los solicitantes, para no borrarlas.
    expect(Object.keys(entrada)).toEqual(expect.arrayContaining(['pasos', 'requisitos', 'documentos', 'puntos_atencion', 'normativa', 'canales_consulta_estado', 'solicitantes', 'costo']))
    expect(entrada.requisitos).toHaveLength(2)
    expect(entrada.solicitantes).toEqual(['ciudadano'])
  })

  it('cambiar los solicitantes también hace viajar la ficha entera', () => {
    const original = formularioDesde(tramite())
    const entrada = entradaDesde({ ...original, solicitantes: ['ciudadano', 'extranjeros'] }, original) as Record<string, unknown>
    expect(entrada.solicitantes).toEqual(['ciudadano', 'extranjeros'])
    expect(entrada.requisitos).toHaveLength(1)
  })

  it('las filas de texto suelto vuelven a ser una lista de textos y descartan las vacías', () => {
    const original = formularioDesde(tramite())
    const entrada = entradaDesde({ ...original, medios_resultado: [{ valor: 'Correo físico' }, { valor: '  ' }] }, original)
    expect(entrada.medios_resultado).toEqual(['Correo físico'])
  })

  it('el costo viaja como un solo bloque cuando cambia cualquiera de sus partes', () => {
    const original = formularioDesde(tramite())
    const entrada = entradaDesde({ ...original, costo_valor: '30000' }, original)
    expect(entrada.costo).toMatchObject({ valor: 30000, tipo_valor: 'fijo', moneda: 'COP' })
  })

  it('hayCambios detecta una edición y no se confunde con espacios al final', () => {
    const original = formularioDesde(tramite())
    expect(hayCambios({ ...original }, original)).toBe(false)
    expect(hayCambios({ ...original, nombre: 'Otro' }, original)).toBe(true)
  })
})

describe('qué falta para publicar', () => {
  it('lista en español lo que falta a un trámite', () => {
    const falta = faltaParaPublicar(formularioVacio())
    expect(falta).toContain('Falta la dirección de la ficha en GOV.CO (obligatoria para trámites y OPA).')
    expect(falta).toContain('Falta al menos un requisito.')
    expect(falta).toHaveLength(8)
  })

  it('una consulta no exige ficha de GOV.CO (Decreto 2106)', () => {
    const falta = faltaParaPublicar({ ...formularioVacio(), type: 'consultas' })
    expect(falta).not.toContain('Falta la dirección de la ficha en GOV.CO (obligatoria para trámites y OPA).')
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
    expect(rutaLegible('pasos.1.titulo')).toBe('Paso 2 · Título')
    expect(rutaLegible('requisitos.0.descripcion')).toBe('Requisito 1 · Descripción')
    expect(rutaLegible('canales_consulta_estado.0.canal')).toBe('Canal de consulta 1 · Canal')
    expect(rutaLegible('costo.cuentas.2.titular')).toBe('Cuenta del costo 3 · Titular')
    expect(rutaLegible('url_ficha_gov_co')).toBe('Dirección de la ficha en GOV.CO')
  })

  it('cambia el nombre técnico dentro del mensaje, con o sin guiones bajos', () => {
    expect(mensajeLegible('pasos.1.titulo', 'El campo pasos.1.titulo es obligatorio.')).toBe('El campo «Paso 2 · Título» es obligatorio.')
  })

  it('junta todos los motivos y deja pasar tal cual los del trámite incompleto', () => {
    expect(motivosLegibles({ tramite: ['Falta al menos un requisito.'], 'requisitos.0.url': ['El campo requisitos.0.url debe ser una URL válida.'] })).toEqual([
      'Falta al menos un requisito.',
      'El campo «Requisito 1 · Dirección web» debe ser una URL válida.',
    ])
  })
})

describe('permiso y rutas', () => {
  it('el módulo se abre con tramites.ver y crear uno pide tramites.crear', () => {
    expect(PERMISO_POR_RUTA['/tramites']).toBe('tramites.ver')
    expect(enrutador.getRoutes().find((r) => r.name === 'panel.tramites.nuevo')?.meta.permiso).toBe('tramites.crear')
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

  it('crear, actualizar, eliminar y dependencias usan las rutas del panel', async () => {
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
    http.get.mockResolvedValue({ data: { success: true, message: null, data: [{ id: 1, nombre: 'Hacienda' }], errors: null } })
    await s.dependencias()
    expect(http.get).toHaveBeenLastCalledWith('/panel/dependencias')
  })
})
