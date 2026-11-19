/**
 * Auditoría: cómo se dice el detalle de un registro. Quien lee no ve nombres internos de campo
 * (`contenido`) sino lo que ve en pantalla («texto»), y un campo que no se conoce pasa tal cual.
 */
import { describe, expect, it } from 'vitest'
import { detalleDe, esRechazo, nombreDeCampo } from '../src/views/admin/auditoria-logica'

describe('nombreDeCampo', () => {
  it('traduce los campos conocidos y deja pasar los demás', () => {
    expect(nombreDeCampo('titulo')).toBe('título')
    expect(nombreDeCampo('contenido')).toBe('texto')
    expect(nombreDeCampo('campo_nuevo')).toBe('campo_nuevo')
  })
})

describe('esRechazo', () => {
  it('reconoce los tres intentos rechazados y nada más', () => {
    for (const a of ['acceso_denegado', 'datos_rechazados', 'conflicto_rechazado']) expect(esRechazo(a)).toBe(true)
    for (const a of ['noticia_creada', 'login_failed', 'login']) expect(esRechazo(a)).toBe(false)
  })
})

describe('detalleDe', () => {
  it('nombra el título entre comillas', () => {
    expect(detalleDe({ titulo: 'Entrega de obras' })).toBe('«Entrega de obras»')
    expect(detalleDe({ nombre: 'Política de accesibilidad' })).toBe('«Política de accesibilidad»')
  })

  it('dice qué campos se cambiaron, con sus nombres de pantalla', () => {
    expect(detalleDe({ titulo: 'X', campos: ['titulo', 'contenido'] })).toBe('«X» · Campos: título, texto')
  })

  it('en un rechazo por datos dice qué campos fallaron y qué se intentaba', () => {
    expect(detalleDe({ metodo: 'POST', estado: 422, campos: ['nombre'], ruta: 'api/v1/panel/tramites' })).toBe(
      'Crear en «tramites» · Campos con error: nombre',
    )
  })

  it('en un acceso denegado dice qué se intentaba', () => {
    expect(detalleDe({ metodo: 'DELETE', estado: 403, ruta: 'api/v1/panel/politicas/{slug}', registro: 'mi-politica' })).toBe(
      'Eliminar en «politicas» · Registro: mi-politica',
    )
  })

  it('sin cambios no inventa nada', () => {
    expect(detalleDe(null)).toBe('')
    expect(detalleDe({})).toBe('')
  })
})
