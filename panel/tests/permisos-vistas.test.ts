/**
 * Permisos granulares en las pantallas: una persona con solo «ver» consulta pero no puede crear,
 * editar, eliminar ni publicar; quien edita sin «publicar» no puede cambiar la publicación.
 *
 * Es el comportamiento que pide la matriz de roles: los permisos se dan acción por acción.
 * El servidor vuelve a comprobarlos; esto evita ofrecer lo que va a rechazar.
 */
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { conSesion } from './ayudas/sesion'

vi.mock('../src/services/noticias', () => ({ POR_PAGINA: 20, listar: vi.fn(), obtener: vi.fn(), crear: vi.fn(), actualizar: vi.fn(), eliminar: vi.fn(), subirImagen: vi.fn() }))
vi.mock('../src/services/tramites', () => ({ POR_PAGINA: 20, listar: vi.fn(), obtener: vi.fn(), crear: vi.fn(), actualizar: vi.fn(), eliminar: vi.fn(), dependencias: vi.fn().mockResolvedValue([]), temas: vi.fn().mockResolvedValue([]) }))
vi.mock('../src/services/banners', () => ({ listar: vi.fn(), obtener: vi.fn(), ordenar: vi.fn(), crear: vi.fn(), actualizar: vi.fn(), eliminar: vi.fn(), subirImagen: vi.fn() }))
vi.mock('../src/services/usuarios', () => ({ POR_PAGINA: 20, listar: vi.fn(), obtener: vi.fn(), crear: vi.fn(), actualizar: vi.fn() }))
vi.mock('../src/services/roles', () => ({ listar: vi.fn(), obtener: vi.fn(), crear: vi.fn(), actualizar: vi.fn(), eliminar: vi.fn(), permisos: vi.fn() }))

import * as noticias from '../src/services/noticias'
import * as tramites from '../src/services/tramites'
import * as banners from '../src/services/banners'
import * as usuarios from '../src/services/usuarios'
import * as roles from '../src/services/roles'
import NoticiasListaView from '../src/views/admin/NoticiasListaView.vue'
import NoticiaFormularioView from '../src/views/admin/NoticiaFormularioView.vue'
import TramitesListaView from '../src/views/admin/TramitesListaView.vue'
import BannersListaView from '../src/views/admin/BannersListaView.vue'
import UsuariosListaView from '../src/views/admin/UsuariosListaView.vue'
import RolesListaView from '../src/views/admin/RolesListaView.vue'
import RolFormularioView from '../src/views/admin/RolFormularioView.vue'
import UsuariosPestanas from '../src/components/base/UsuariosPestanas.vue'
import type { Component } from 'vue'

const UUID = '11111111-1111-4111-8111-111111111111'
const vacio = { template: '<i/>' }

const enrutador = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/n', name: 'panel.noticias', component: vacio },
      { path: '/n/nueva', name: 'panel.noticias.nueva', component: vacio },
      { path: '/n/:uuid', name: 'panel.noticias.editar', component: vacio },
      { path: '/t', name: 'tramites', component: vacio },
      { path: '/t/nuevo', name: 'panel.tramites.nuevo', component: vacio },
      { path: '/t/:uuid', name: 'panel.tramites.editar', component: vacio },
      { path: '/b', name: 'panel.banners', component: vacio },
      { path: '/b/nueva', name: 'panel.banners.nuevo', component: vacio },
      { path: '/b/:uuid', name: 'panel.banners.editar', component: vacio },
      { path: '/u', name: 'usuarios', component: vacio },
      { path: '/u/nuevo', name: 'panel.usuarios.nuevo', component: vacio },
      { path: '/u/:uuid', name: 'panel.usuarios.editar', component: vacio },
      { path: '/r', name: 'panel.roles', component: vacio },
      { path: '/r/nuevo', name: 'panel.roles.nuevo', component: vacio },
      { path: '/r/:id', name: 'panel.roles.editar', component: vacio },
    ],
  })

async function montar(vista: Component, ruta: string) {
  const router = enrutador()
  await router.push(ruta)
  await router.isReady()
  const wrapper = mount(vista, { global: { plugins: [router], stubs: { FaIcon: true, teleport: true } } })
  await flushPromises()
  return wrapper
}

const pagina = <T,>(datos: T[]) => ({
  datos,
  meta: { current_page: 1, from: datos.length ? 1 : null, last_page: 1, path: '/x', per_page: 20, to: datos.length, total: datos.length },
  enlaces: { first: null, last: null, prev: null, next: null },
})

const noticia = (extra = {}) => ({
  id: 1, type: 'noticias', uuid: UUID, slug: 'una', titulo: 'Una noticia', descripcion_corta: 'Corta', contenido: '<p>Texto</p>',
  fecha_publicacion: '2026-11-01T09:00:00+00:00', destacada: false, activo: true, estado: 'publicada', imagen: { url: '/x', alt: 'a' },
  completa: true, problemas: [], ...extra,
}) as never

beforeEach(() => {
  vi.clearAllMocks()
  vi.mocked(noticias.listar).mockResolvedValue(pagina([noticia()]) as never)
  vi.mocked(noticias.obtener).mockResolvedValue(noticia())
  vi.mocked(tramites.listar).mockResolvedValue(pagina([{ id: 1, uuid: UUID, type: 'tramites', codigo: 'P0001', slug: 'un-tramite', nombre: 'Un trámite', modalidad: 'presencial', estado: 'borrador', completo: false, problemas: [], publicado_en: null, actualizado_en: '2026-11-10T09:00:00+00:00' }]) as never)
  vi.mocked(banners.listar).mockResolvedValue([
    { uuid: 'a', titulo: 'Uno', estado: 'vigente', problemas: [], imagen: null, vigente_desde: null, vigente_hasta: null, orden: 1, activo: true },
    { uuid: 'b', titulo: 'Dos', estado: 'vigente', problemas: [], imagen: null, vigente_desde: null, vigente_hasta: null, orden: 2, activo: true },
  ] as never)
  vi.mocked(usuarios.listar).mockResolvedValue(pagina([{ uuid: UUID, type: 'usuarios', name: 'Ana', email: 'a@e.co', estado: 'activo', mfa_habilitado: false, roles: [], es_super_admin: false, creado_en: null }]) as never)
  vi.mocked(roles.listar).mockResolvedValue([{ id: 7, type: 'roles', name: 'editor', permisos: [], usuarios: 0, protegido: false }] as never)
  vi.mocked(roles.obtener).mockResolvedValue({ id: 7, type: 'roles', name: 'editor', permisos: ['noticias.ver'], usuarios: 0, protegido: false } as never)
  vi.mocked(roles.permisos).mockResolvedValue([
    { grupo: 'Contenidos del sitio', permisos: [
      { nombre: 'noticias.ver', etiqueta: 'Ver', descripcion: '', modulo: 'noticias', modulo_etiqueta: 'Noticias', accion: 'ver', requiere: [] },
      { nombre: 'noticias.editar', etiqueta: 'Editar', descripcion: '', modulo: 'noticias', modulo_etiqueta: 'Noticias', accion: 'editar', requiere: ['noticias.ver'] },
    ] },
  ] as never)
})

describe('listas: con solo «ver» se consulta y nada más', () => {
  it('noticias: sin «Nueva», sin «Eliminar» y la acción dice «Ver»', async () => {
    conSesion(['noticias.ver'])
    const w = await montar(NoticiasListaView, '/n')
    expect(w.text()).not.toContain('Nueva noticia')
    expect(w.find('button[aria-label^="Eliminar"]').exists()).toBe(false)
    expect(w.find('a[aria-label="Ver «Una noticia»"]').exists()).toBe(true)
    expect(w.find('a[aria-label^="Editar"]').exists()).toBe(false)
  })

  it('noticias: con crear, editar y eliminar vuelven los tres', async () => {
    conSesion(['noticias.ver', 'noticias.crear', 'noticias.editar', 'noticias.eliminar'])
    const w = await montar(NoticiasListaView, '/n')
    expect(w.text()).toContain('Nueva noticia')
    expect(w.find('button[aria-label="Eliminar «Una noticia»"]').exists()).toBe(true)
    expect(w.find('a[aria-label="Editar «Una noticia»"]').exists()).toBe(true)
  })

  it('trámites: solo ver no ofrece crear ni eliminar', async () => {
    conSesion(['tramites.ver'])
    const w = await montar(TramitesListaView, '/t')
    expect(w.text()).not.toContain('Nuevo trámite')
    expect(w.find('button[aria-label^="Eliminar"]').exists()).toBe(false)
    expect(w.find('a[aria-label="Ver «Un trámite»"]').exists()).toBe(true)
  })

  it('carrusel: solo ver no deja reordenar ni crear', async () => {
    conSesion(['banners.ver'])
    const w = await montar(BannersListaView, '/b')
    expect(w.text()).not.toContain('Nueva diapositiva')
    expect(w.find('button[aria-label^="Subir"]').exists()).toBe(false)
    expect(w.find('button[aria-label^="Bajar"]').exists()).toBe(false)
    expect(w.find('a[aria-label="Ver «Uno»"]').exists()).toBe(true)
  })

  it('usuarios: sin «crear» no hay «Nuevo usuario»; sin «ver roles» no se consultan los roles', async () => {
    conSesion(['usuarios.ver'])
    const w = await montar(UsuariosListaView, '/u')
    expect(w.text()).not.toContain('Nuevo usuario')
    expect(vi.mocked(roles.listar)).not.toHaveBeenCalled()
    expect(w.find('a[aria-label="Ver a Ana"]').exists()).toBe(true)
  })

  it('roles: solo ver no ofrece nuevo rol ni eliminar', async () => {
    conSesion(['roles.ver'])
    const w = await montar(RolesListaView, '/r')
    expect(w.text()).not.toContain('Nuevo rol')
    expect(w.find('button[aria-label^="Eliminar"]').exists()).toBe(false)
    expect(w.find('a[aria-label="Ver «editor»"]').exists()).toBe(true)
  })

  it('las pestañas de usuarios y roles aparecen según cada «ver»', async () => {
    conSesion(['roles.ver'])
    const w = mount(UsuariosPestanas, { global: { plugins: [enrutador()] } })
    expect(w.text()).toContain('Roles y permisos')
    expect(w.text()).not.toContain('Usuarios\n')
    expect(w.findAll('a')).toHaveLength(1)
  })
})

describe('formularios: solo lectura y publicación aparte', () => {
  it('con solo «ver» la noticia se muestra deshabilitada y sin barra de guardar', async () => {
    conSesion(['noticias.ver'])
    const w = await montar(NoticiaFormularioView, `/n/${UUID}`)
    expect(w.find('[data-solo-lectura]').exists()).toBe(true)
    expect(w.find('form fieldset').attributes('disabled')).toBeDefined()
    expect(w.find('button[type="submit"]').exists()).toBe(false)
    expect(w.text()).not.toContain('Zona de peligro')
  })

  it('con «editar» pero sin «publicar» se edita, pero el estado de publicación no se toca', async () => {
    conSesion(['noticias.ver', 'noticias.editar'])
    const w = await montar(NoticiaFormularioView, `/n/${UUID}`)
    expect(w.find('[data-solo-lectura]').exists()).toBe(false)
    expect(w.find('button[type="submit"]').exists()).toBe(true)
    for (const r of w.findAll('input[type="radio"]')) expect(r.attributes('disabled')).toBeDefined()
    expect(w.find('[data-sin-publicar]').exists()).toBe(true)
  })

  it('con «publicar» los radios están disponibles', async () => {
    conSesion(['noticias.ver', 'noticias.editar', 'noticias.publicar'])
    const w = await montar(NoticiaFormularioView, `/n/${UUID}`)
    for (const r of w.findAll('input[type="radio"]')) expect(r.attributes('disabled')).toBeUndefined()
    expect(w.find('[data-sin-publicar]').exists()).toBe(false)
  })

  it('con «eliminar» aparece la zona de peligro aunque no se pueda editar', async () => {
    conSesion(['noticias.ver', 'noticias.eliminar'])
    const w = await montar(NoticiaFormularioView, `/n/${UUID}`)
    expect(w.text()).toContain('Zona de peligro')
  })

  it('un rol se consulta con «ver roles» sin poder tocar las casillas', async () => {
    conSesion(['roles.ver', 'noticias.ver'])
    const w = await montar(RolFormularioView, '/r/7')
    expect(w.find('[data-solo-lectura]').exists()).toBe(true)
    expect(w.find('button[type="submit"]').exists()).toBe(false)
    for (const c of w.findAll('input[type="checkbox"]')) expect(c.attributes('disabled')).toBeDefined()
    expect(w.find('h1').text()).toBe('Ver rol')
  })
})
