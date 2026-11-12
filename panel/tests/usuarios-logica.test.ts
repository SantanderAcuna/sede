/**
 * Lógica pura de usuarios y roles. Protege lo que el formulario promete: que la contraseña
 * se valide como el servidor, que solo viaje lo que cambió y que la interfaz no ofrezca lo
 * que el servidor va a rechazar (el servidor sigue siendo quien decide).
 */
import { describe, expect, it } from 'vitest'
import {
  cambiosDeRol,
  cambiosDeUsuario,
  formularioDeUsuario,
  formularioVacioDeUsuario,
  hayCambiosDeUsuario,
  problemasDeRol,
  problemasDeUsuario,
  puedeAdministrarA,
  requisitosDeClave,
  rolesAsignables,
} from '../src/views/admin/usuarios-logica'
import type { RolPanelItem, UsuarioPanelItem } from '../src/types/api'

const usuario = (extra: Partial<UsuarioPanelItem> = {}): UsuarioPanelItem => ({
  uuid: 'u1', type: 'usuarios', name: 'Ana Pérez', email: 'ana@example.com', estado: 'activo',
  mfa_habilitado: false, roles: ['editor'], es_super_admin: false, creado_en: null, ...extra,
})
const rol = (name: string, permisos: string[], extra: Partial<RolPanelItem> = {}): RolPanelItem => ({
  id: 1, type: 'roles', name, permisos, usuarios: 0, protegido: false, ...extra,
})

describe('requisitosDeClave', () => {
  it('marca qué se cumple de 12 caracteres, letras y números', () => {
    expect(requisitosDeClave('corta').map((r) => r.cumple)).toEqual([false, true, false])
    expect(requisitosDeClave('Clave-Segura-2026!').every((r) => r.cumple)).toBe(true)
    expect(requisitosDeClave('123456789012').map((r) => r.cumple)).toEqual([true, false, true])
  })
})

describe('problemasDeUsuario', () => {
  it('al crear exige nombre, correo y una contraseña que cumpla', () => {
    const p = problemasDeUsuario(formularioVacioDeUsuario(), true)
    expect(p).toEqual(expect.arrayContaining([
      expect.stringContaining('nombre'), expect.stringContaining('correo'), expect.stringContaining('contraseña'),
    ]))
  })
  it('al editar la contraseña es opcional, pero si se escribe debe cumplir', () => {
    const f = formularioDeUsuario(usuario())
    expect(problemasDeUsuario(f, false)).toEqual([])
    f.password = 'corta'
    expect(problemasDeUsuario(f, false)).toEqual([expect.stringContaining('contraseña')])
  })
  it('rechaza un correo sin forma de correo', () => {
    const f = { ...formularioVacioDeUsuario(), name: 'Ana Pérez', email: 'sin-arroba', password: 'Clave-Segura-2026!' }
    expect(problemasDeUsuario(f, true)).toEqual([expect.stringContaining('correo')])
  })
})

describe('cambiosDeUsuario', () => {
  it('solo manda lo que cambió y no manda la contraseña vacía', () => {
    const original = formularioDeUsuario(usuario())
    const f = { ...original, name: 'Ana María' }
    expect(cambiosDeUsuario(f, original)).toEqual({ name: 'Ana María' })
    expect(hayCambiosDeUsuario(f, original)).toBe(true)
    expect(hayCambiosDeUsuario(original, original)).toBe(false)
  })
  it('los roles se comparan sin importar el orden', () => {
    const original = formularioDeUsuario(usuario({ roles: ['b', 'a'] }))
    expect(cambiosDeUsuario({ ...original, roles: ['a', 'b'] }, original)).toEqual({})
    expect(cambiosDeUsuario({ ...original, roles: ['a'] }, original)).toEqual({ roles: ['a'] })
  })
  it('manda la contraseña solo si se escribió una', () => {
    const original = formularioDeUsuario(usuario())
    expect(cambiosDeUsuario({ ...original, password: 'Otra-Clave-Segura-1' }, original)).toEqual({ password: 'Otra-Clave-Segura-1' })
  })
  it('sin original (alta) manda todo', () => {
    const f = { ...formularioVacioDeUsuario(), name: 'N', email: 'n@e.co', password: 'Clave-Segura-2026!', roles: ['editor'] }
    expect(cambiosDeUsuario(f, null)).toEqual({ name: 'N', email: 'n@e.co', password: 'Clave-Segura-2026!', estado: 'activo', roles: ['editor'] })
  })
})

describe('lo que la interfaz ofrece según quien administra', () => {
  const editor = rol('editor', ['noticias.gestionar'])
  const poderoso = rol('poderoso', ['noticias.gestionar', 'entidad.gestionar'])
  const sup = rol('super-admin', [], { protegido: true })

  it('un súper administrador asigna cualquier rol', () => {
    expect(rolesAsignables([editor, poderoso, sup], { esSuper: true, permisos: [] }).map((r) => r.name)).toEqual(['editor', 'poderoso', 'super-admin'])
  })
  it('los demás solo roles dentro de sus permisos y nunca super-admin', () => {
    const ofrecidos = rolesAsignables([editor, poderoso, sup], { esSuper: false, permisos: ['noticias.gestionar', 'usuarios.gestionar'] })
    expect(ofrecidos.map((r) => r.name)).toEqual(['editor'])
  })
  it('solo un súper administrador administra a otro', () => {
    expect(puedeAdministrarA(usuario({ es_super_admin: true }), { esSuper: false, permisos: [] })).toBe(false)
    expect(puedeAdministrarA(usuario({ es_super_admin: true }), { esSuper: true, permisos: [] })).toBe(true)
    expect(puedeAdministrarA(usuario(), { esSuper: false, permisos: [] })).toBe(true)
  })
})

describe('roles', () => {
  it('exige un nombre de al menos 3 caracteres', () => {
    expect(problemasDeRol({ name: 'ab', permisos: [] })).toEqual([expect.stringContaining('nombre')])
    expect(problemasDeRol({ name: 'redactor', permisos: [] })).toEqual([])
  })
  it('solo manda lo que cambió, permisos sin importar el orden', () => {
    const original = { name: 'r', permisos: ['b', 'a'] }
    expect(cambiosDeRol({ name: 'r', permisos: ['a', 'b'] }, original)).toEqual({})
    expect(cambiosDeRol({ name: 'rr', permisos: ['a'] }, original)).toEqual({ name: 'rr', permisos: ['a'] })
    expect(cambiosDeRol({ name: 'r', permisos: ['a'] }, null)).toEqual({ name: 'r', permisos: ['a'] })
  })
})
