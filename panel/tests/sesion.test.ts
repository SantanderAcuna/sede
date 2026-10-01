/**
 * Almacén de sesión (D-04).
 *
 * Lo que se prueba aquí es la propiedad que hace honesto el panel: **sin
 * autenticación no hay sesión**, y sin sesión no hay permisos. Es la razón por la
 * que las 18 rutas administrativas no se pueden abrir hoy, y conviene que esté
 * fijado por una prueba para que nadie «arregle» el panel dejando la guardia
 * pasando por defecto.
 */
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'

import { useSesionStore } from '../src/stores/sesion'

beforeEach(() => {
  setActivePinia(createPinia())
})

describe('sesión sin autenticación', () => {
  it('empieza vacía: el panel no puede afirmar que hay sesión', () => {
    const sesion = useSesionStore()

    expect(sesion.usuario).toBeNull()
    expect(sesion.iniciada).toBe(false)
  })

  it('sin sesión no se tiene ningún permiso', () => {
    const sesion = useSesionStore()

    expect(sesion.tienePermiso('pqrsd.ver')).toBe(false)
    expect(sesion.tienePermiso('panel-administrative')).toBe(false)
  })

  it('una ruta sin permiso declarado se considera pública dentro del panel', () => {
    const sesion = useSesionStore()

    expect(sesion.tienePermiso(undefined)).toBe(true)
    expect(sesion.tienePermiso('')).toBe(true)
  })
})

describe('punto de entrada de la identidad real', () => {
  it('iniciar sesión marca la sesión y habilita sus permisos', () => {
    const sesion = useSesionStore()

    sesion.iniciarSesion({ nombre: 'Editora de normativa', permisos: ['normativa.editar'] })

    expect(sesion.iniciada).toBe(true)
    expect(sesion.tienePermiso('normativa.editar')).toBe(true)
    expect(sesion.tienePermiso('usuarios.gestionar')).toBe(false)
  })

  it('cerrar sesión deja el almacén como estaba', () => {
    const sesion = useSesionStore()
    sesion.iniciarSesion({ nombre: 'Administrador', permisos: ['panel-administrative'] })

    sesion.cerrarSesion()

    expect(sesion.usuario).toBeNull()
    expect(sesion.iniciada).toBe(false)
    expect(sesion.tienePermiso('panel-administrative')).toBe(false)
  })

  it('no persiste en el almacenamiento del navegador', () => {
    const sesion = useSesionStore()
    sesion.iniciarSesion({ nombre: 'Administrador', permisos: ['panel-administrative'] })

    // Una sesión que sobrevive al cierre del servidor seguiría afirmando una
    // identidad caducada; por eso vive sólo en memoria.
    expect(Object.keys(localStorage)).toHaveLength(0)
  })
})
