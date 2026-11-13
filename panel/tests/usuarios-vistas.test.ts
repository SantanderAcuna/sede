/**
 * Vistas de usuarios y roles, con servicios simulados.
 *
 * Se verifica lo que vive quien administra: tablas (no tarjetas), filtros pedidos al servidor,
 * que la interfaz no ofrezca lo que el servidor rechazaría, que el error del servidor se
 * muestre tal cual y que nada destructivo ocurra sin confirmar.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { AxiosError } from 'axios'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('../src/services/usuarios', () => ({ POR_PAGINA: 20, listar: vi.fn(), obtener: vi.fn(), crear: vi.fn(), actualizar: vi.fn() }))
vi.mock('../src/services/roles', () => ({ permisos: vi.fn(), listar: vi.fn(), obtener: vi.fn(), crear: vi.fn(), actualizar: vi.fn(), eliminar: vi.fn() }))

import * as usuarios from '../src/services/usuarios'
import * as roles from '../src/services/roles'
import { useSesionStore } from '../src/stores/sesion'
import { tomarAviso } from '../src/views/admin/avisos'
import UsuariosListaView from '../src/views/admin/UsuariosListaView.vue'
import UsuarioFormularioView from '../src/views/admin/UsuarioFormularioView.vue'
import RolesListaView from '../src/views/admin/RolesListaView.vue'
import RolFormularioView from '../src/views/admin/RolFormularioView.vue'
import type { RolPanelItem, UsuarioPanelItem } from '../src/types/api'

const u = vi.mocked(usuarios)
const r = vi.mocked(roles)

const UUID = '33333333-3333-4333-8333-333333333333'

const usuario = (extra: Partial<UsuarioPanelItem> = {}): UsuarioPanelItem => ({
  uuid: UUID, type: 'usuarios', name: 'Ana Pérez', email: 'ana@example.com', estado: 'activo',
  mfa_habilitado: false, roles: ['editor'], es_super_admin: false, creado_en: null, ...extra,
})
const rol = (extra: Partial<RolPanelItem> = {}): RolPanelItem => ({
  id: 7, type: 'roles', name: 'editor', permisos: ['noticias.ver'], usuarios: 0, protegido: false, ...extra,
})
const pagina = (datos: UsuarioPanelItem[]) => ({
  datos,
  meta: { current_page: 1, from: datos.length ? 1 : null, last_page: 1, path: '/x', per_page: 20, to: datos.length, total: datos.length },
  enlaces: { first: null, last: null, prev: null, next: null },
})
const permiso = (modulo: string, etiquetaModulo: string, accion: string) => ({
  nombre: `${modulo}.${accion}`, etiqueta: accion[0].toUpperCase() + accion.slice(1), descripcion: `Descripción de ${accion}.`,
  modulo, modulo_etiqueta: etiquetaModulo, accion, requiere: accion === 'ver' ? [] : [`${modulo}.ver`],
})
const catalogo = [
  { grupo: 'Contenidos del sitio', permisos: [
    ...['ver', 'crear', 'editar', 'eliminar', 'publicar'].map((a) => permiso('noticias', 'Noticias', a)),
    ...['ver', 'editar'].map((a) => permiso('entidad', 'Datos de la entidad', a)),
  ] },
]

function enrutador() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/usuarios', name: 'usuarios', component: { template: '<i/>' } },
      { path: '/usuarios/nuevo', name: 'panel.usuarios.nuevo', component: { template: '<i/>' } },
      { path: '/usuarios/:uuid', name: 'panel.usuarios.editar', component: { template: '<i/>' } },
      { path: '/usuarios/roles', name: 'panel.roles', component: { template: '<i/>' } },
      { path: '/usuarios/roles/nuevo', name: 'panel.roles.nuevo', component: { template: '<i/>' } },
      { path: '/usuarios/roles/:id', name: 'panel.roles.editar', component: { template: '<i/>' } },
    ],
  })
}

function quien(esSuper: boolean, permisos: string[] = ['usuarios.ver', 'roles.ver', 'noticias.ver'], email = 'yo@example.com') {
  setActivePinia(createPinia())
  const sesion = useSesionStore()
  sesion.usuario = { id: 1, email, nombre: 'yo', permisos: esSuper ? ['*'] : permisos }
}

async function montar(vista: object, ruta: string, opciones: { global?: object } = {}) {
  const router = enrutador()
  await router.push(ruta)
  await router.isReady()
  const envoltura = mount(vista, { global: { plugins: [router], stubs: { FaIcon: true }, ...opciones.global }, attachTo: document.body })
  await flushPromises()
  return { envoltura, router }
}

beforeEach(() => {
  vi.clearAllMocks()
  quien(true)
  r.listar.mockResolvedValue([rol(), rol({ id: 8, name: 'poderoso', permisos: ['noticias.ver', 'entidad.ver'] }), rol({ id: 1, name: 'super-admin', permisos: [], protegido: true })])
  r.permisos.mockResolvedValue(catalogo)
})
afterEach(() => { document.body.innerHTML = '' })

describe('UsuariosListaView', () => {
  it('muestra una tabla con nombre, correo, estado en palabras y roles, y pide al servidor lo filtrado', async () => {
    u.listar.mockResolvedValue(pagina([usuario(), usuario({ uuid: 'x2', name: 'Beto', email: 'beto@example.com', estado: 'bloqueado', roles: [] })]))
    const { envoltura } = await montar(UsuariosListaView, '/usuarios')

    expect(envoltura.find('table').exists()).toBe(true)
    expect(envoltura.text()).toContain('Ana Pérez')
    expect(envoltura.text()).toContain('ana@example.com')
    expect(envoltura.text()).toContain('Bloqueado')
    expect(envoltura.text()).toContain('editor')
    expect(envoltura.text()).toContain('Sin roles')

    await envoltura.find('select#usuarios-estado').setValue('bloqueado')
    expect(u.listar).toHaveBeenLastCalledWith(expect.objectContaining({ estado: 'bloqueado', pagina: 1 }))
  })

  it('un administrador común no puede editar a un súper administrador: la fila lo explica', async () => {
    quien(false)
    u.listar.mockResolvedValue(pagina([usuario({ name: 'Jefe', es_super_admin: true, roles: ['super-admin'] })]))
    const { envoltura } = await montar(UsuariosListaView, '/usuarios')
    expect(envoltura.find('a[aria-label^="Editar"]').exists()).toBe(false)
    expect(envoltura.text()).toContain('Solo un súper administrador')
  })

  it('el estado vacío dice el siguiente paso y un fallo ofrece reintentar', async () => {
    u.listar.mockResolvedValueOnce(pagina([]))
    const { envoltura } = await montar(UsuariosListaView, '/usuarios')
    expect(envoltura.text()).toContain('Aún no hay usuarios')

    u.listar.mockRejectedValueOnce(new Error('x'))
    const { envoltura: otra } = await montar(UsuariosListaView, '/usuarios')
    expect(otra.find('[role="alert"]').text()).toContain('No se pudieron cargar')
    expect(otra.text()).toContain('Reintentar')
  })

  it('lleva a las dos pestañas: usuarios y roles', async () => {
    u.listar.mockResolvedValue(pagina([usuario()]))
    const { envoltura } = await montar(UsuariosListaView, '/usuarios')
    const nav = envoltura.find('nav[aria-label="Usuarios y roles"]')
    expect(nav.text()).toContain('Usuarios')
    expect(nav.text()).toContain('Roles')
    expect(nav.find('[aria-current="page"]').text()).toContain('Usuarios')
  })
})

describe('UsuarioFormularioView', () => {
  it('al crear exige lo necesario sin llamar al servidor y explica la contraseña', async () => {
    const { envoltura } = await montar(UsuarioFormularioView, '/usuarios/nuevo')
    expect(envoltura.text()).toContain('Al menos 12 caracteres')
    await envoltura.find('form').trigger('submit')
    await flushPromises()
    expect(u.crear).not.toHaveBeenCalled()
    expect(envoltura.find('[role="alert"]').text()).toContain('nombre')
  })

  it('crea con todos los datos y los roles elegidos', async () => {
    u.crear.mockResolvedValue(usuario())
    const { envoltura, router } = await montar(UsuarioFormularioView, '/usuarios/nuevo')
    await envoltura.find('input[autocomplete="name"]').setValue('Carla Gómez')
    await envoltura.find('input[type="email"]').setValue('carla@example.com')
    await envoltura.find('input[type="password"]').setValue('Clave-Segura-2026!')
    await envoltura.find('input[type="checkbox"][value="editor"]').setValue(true)
    await envoltura.find('form').trigger('submit')
    await flushPromises()
    expect(u.crear).toHaveBeenCalledWith({ name: 'Carla Gómez', email: 'carla@example.com', password: 'Clave-Segura-2026!', estado: 'activo', roles: ['editor'] })
    expect(router.currentRoute.value.name).toBe('usuarios')
    expect(tomarAviso()).toBe('Usuario creado.')
  })

  it('solo ofrece los roles que quien administra puede asignar', async () => {
    quien(false, ['usuarios.ver', 'usuarios.crear', 'roles.ver', 'noticias.ver'])
    const { envoltura } = await montar(UsuarioFormularioView, '/usuarios/nuevo')
    const valores = envoltura.findAll('input[type="checkbox"]').map((c) => (c.element as HTMLInputElement).value)
    expect(valores).toEqual(['editor'])
  })

  it('al editar solo manda lo que cambió y no pide la contraseña', async () => {
    u.obtener.mockResolvedValue(usuario())
    u.actualizar.mockResolvedValue(usuario({ name: 'Ana María' }))
    const { envoltura } = await montar(UsuarioFormularioView, `/usuarios/${UUID}`)
    await envoltura.find('input[autocomplete="name"]').setValue('Ana María')
    await envoltura.find('form').trigger('submit')
    await flushPromises()
    expect(u.actualizar).toHaveBeenCalledWith(UUID, { name: 'Ana María' })
  })

  it('en la propia cuenta no se pueden cambiar roles ni estado, y se dice por qué', async () => {
    quien(false, ['usuarios.ver'], 'ana@example.com')
    u.obtener.mockResolvedValue(usuario())
    const { envoltura } = await montar(UsuarioFormularioView, `/usuarios/${UUID}`)
    expect(envoltura.find('select#usuario-estado').attributes('disabled')).toBeDefined()
    expect(envoltura.text()).toContain('su propia cuenta')
  })

  it('muestra el mensaje del servidor cuando rechaza (escalada, último súper administrador)', async () => {
    u.obtener.mockResolvedValue(usuario())
    const error = new AxiosError('x', '403', undefined, undefined, { status: 403, data: { message: 'Solo un súper administrador puede modificar a otro súper administrador.' } } as never)
    u.actualizar.mockRejectedValue(error)
    const { envoltura } = await montar(UsuarioFormularioView, `/usuarios/${UUID}`)
    await envoltura.find('input[autocomplete="name"]').setValue('Otro')
    await envoltura.find('form').trigger('submit')
    await flushPromises()
    expect(envoltura.find('[role="alert"]').text()).toContain('Solo un súper administrador')
  })

  it('un usuario inexistente se dice con claridad', async () => {
    u.obtener.mockRejectedValue(new AxiosError('x', '404', undefined, undefined, { status: 404, data: {} } as never))
    const { envoltura } = await montar(UsuarioFormularioView, `/usuarios/${UUID}`)
    expect(envoltura.text()).toContain('no existe')
  })
})

describe('RolesListaView', () => {
  it('lista los roles con cuántos usuarios tienen; el protegido no se edita ni se elimina', async () => {
    const { envoltura } = await montar(RolesListaView, '/usuarios/roles')
    expect(envoltura.find('table').exists()).toBe(true)
    expect(envoltura.text()).toContain('poderoso')
    expect(envoltura.text()).toContain('Protegido')
    expect(envoltura.find('a[aria-label="Editar «super-admin»"]').exists()).toBe(false)
    expect(envoltura.find('button[aria-label="Eliminar «super-admin»"]').exists()).toBe(false)
  })

  it('eliminar un rol con usuarios no se ofrece y sin usuarios pide confirmar nombrándolo', async () => {
    r.listar.mockResolvedValue([rol({ usuarios: 2 }), rol({ id: 9, name: 'vacio', usuarios: 0 })])
    r.eliminar.mockResolvedValue()
    const { envoltura } = await montar(RolesListaView, '/usuarios/roles')
    expect(envoltura.find('button[aria-label="Eliminar «editor»"]').exists()).toBe(false)
    await envoltura.find('button[aria-label="Eliminar «vacio»"]').trigger('click')
    await flushPromises()
    expect(r.eliminar).not.toHaveBeenCalled()
    expect(document.body.textContent).toContain('«vacio»')
  })
})

describe('RolFormularioView', () => {
  it('muestra cada módulo con sus acciones por separado y crea el rol con solo «ver»', async () => {
    r.crear.mockResolvedValue(rol())
    const { envoltura, router } = await montar(RolFormularioView, '/usuarios/roles/nuevo')
    expect(envoltura.text()).toContain('Contenidos del sitio')
    expect(envoltura.text()).toContain('Noticias')
    expect(envoltura.text()).not.toContain('noticias.ver')
    // Cinco acciones de noticias y dos de entidad: cada una es su propia casilla.
    expect(envoltura.findAll('input[type="checkbox"]')).toHaveLength(7)

    await envoltura.find('input[autocomplete="off"][type="text"]').setValue('redactor')
    await envoltura.find('input[type="checkbox"][value="noticias.ver"]').setValue(true)
    await envoltura.find('form').trigger('submit')
    await flushPromises()
    expect(r.crear).toHaveBeenCalledWith({ name: 'redactor', permisos: ['noticias.ver'] })
    expect(router.currentRoute.value.name).toBe('panel.roles')
  })

  it('marcar una acción marca también «ver» del módulo y quitar «ver» las quita todas', async () => {
    const { envoltura } = await montar(RolFormularioView, '/usuarios/roles/nuevo')
    const casilla = (n: string) => envoltura.find(`input[type="checkbox"][value="${n}"]`)
    await casilla('noticias.editar').setValue(true)
    expect((casilla('noticias.ver').element as HTMLInputElement).checked).toBe(true)
    expect((casilla('noticias.editar').element as HTMLInputElement).checked).toBe(true)
    expect((casilla('noticias.crear').element as HTMLInputElement).checked).toBe(false)
    expect((casilla('entidad.ver').element as HTMLInputElement).checked).toBe(false)

    await casilla('noticias.ver').setValue(false)
    expect((casilla('noticias.editar').element as HTMLInputElement).checked).toBe(false)
  })

  it('«Solo ver» deja únicamente «ver» en cada módulo del grupo', async () => {
    const { envoltura } = await montar(RolFormularioView, '/usuarios/roles/nuevo')
    await envoltura.find('input[value="noticias.eliminar"]').setValue(true)
    await envoltura.find('button[aria-label="Dar solo ver en Contenidos del sitio"]').trigger('click')
    const marcados = envoltura.findAll('input[type="checkbox"]').filter((c) => (c.element as HTMLInputElement).checked).map((c) => (c.element as HTMLInputElement).value)
    expect(marcados.sort()).toEqual(['entidad.ver', 'noticias.ver'])
  })

  it('cada casilla tiene un nombre accesible que dice la acción y el módulo', async () => {
    const { envoltura } = await montar(RolFormularioView, '/usuarios/roles/nuevo')
    expect(envoltura.find('input[value="noticias.editar"]').attributes('aria-label')).toBe('Editar noticias')
    expect(envoltura.find('input[value="entidad.ver"]').attributes('aria-label')).toBe('Ver datos de la entidad')
  })

  it('quien no es súper administrador no puede marcar permisos que no tiene', async () => {
    quien(false, ['usuarios.ver', 'noticias.ver'])
    const { envoltura } = await montar(RolFormularioView, '/usuarios/roles/nuevo')
    expect(envoltura.find('input[value="noticias.ver"]').attributes('disabled')).toBeUndefined()
    expect(envoltura.find('input[value="noticias.editar"]').attributes('disabled')).toBeDefined()
    expect(envoltura.find('input[value="entidad.ver"]').attributes('disabled')).toBeDefined()
  })

  it('el rol protegido se muestra solo para leer', async () => {
    r.obtener.mockResolvedValue(rol({ id: 1, name: 'super-admin', permisos: [], protegido: true }))
    const { envoltura } = await montar(RolFormularioView, '/usuarios/roles/1')
    expect(envoltura.text()).toContain('no se puede modificar')
    expect(envoltura.find('button[type="submit"]').exists()).toBe(false)
  })
})
