/**
 * Rutas del panel.
 *
 * Reglas que fija el plan y que estas rutas aplican:
 *
 *  - Quien no ha entrado va a la entrada DEL PANEL, no al sitio público.
 *  - Quien ya entró y visita la entrada va a su panel.
 *  - Quien no tiene el permiso de una ruta ve una pantalla que lo explica, no
 *    un error en crudo.
 *
 * El permiso se declara en la ruta, pero **el backend vuelve a comprobarlo**:
 * la interfaz no es un control de seguridad.
 *
 * Las tres reglas de arriba se aplican de verdad en `beforeEach`, contra el
 * almacén de sesión (`src/stores/sesion.ts`). Ese almacén hoy siempre está
 * vacío —el módulo de identidad no existe—, así que la consecuencia real y
 * buscada es que **sin sesión no se entra al panel** y la entrada explica que
 * la autenticación todavía no está implementada. Se prefiere una guardia que
 * bloquea a una guardia decorativa: dejar pasar «para la demo» convertiría el
 * panel en una superficie abierta que finge estar protegida.
 *
 * Los módulos del diseño que aún no tienen implementación comparten una única
 * vista de marcador y se declaran aquí con su título. Así el menú está completo
 * y ningún enlace queda muerto, sin dieciocho archivos casi idénticos.
 */
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

import { PERMISO_POR_RUTA } from '@/config/permisos'
import { useSesionStore } from '@/stores/sesion'

declare module 'vue-router' {
  interface RouteMeta {
    /** Disposición que aplica `App.vue`. Sin valor, la ruta se pinta sola. */
    layout?: 'auth' | 'blank'
    /** Título del módulo, usado por la miga de pan y el título del documento. */
    titulo?: string
    /** Aclaración del módulo, mostrada por el marcador de vista pendiente. */
    subtitulo?: string
    requiereSesion?: boolean
    permiso?: string
    soloInvitados?: boolean
  }
}

// Un solo nombre de producto en el panel (D-47): el mismo que publica
// `index.html` y el mismo que dibuja `AppLogo`. La pestaña no cambia de nombre
// al navegar, sólo le antepone el título del módulo.
const TITULO_BASE = 'SGDI · Alcaldía Distrital de Santa Marta'

/** Módulos del panel. Comparten marcador hasta que cada uno se implemente. */
const MODULOS: Array<{ ruta: string; nombre: string; titulo: string; subtitulo: string }> = [
  { ruta: 'pqrsd', nombre: 'pqrsd', titulo: 'PQRSD', subtitulo: 'Peticiones, quejas, reclamos, sugerencias y denuncias' },
  { ruta: 'tramites', nombre: 'tramites', titulo: 'Trámites', subtitulo: 'Catálogo de trámites y servicios' },
  { ruta: 'citas', nombre: 'citas', titulo: 'Citas y turnos', subtitulo: 'Agenda de atención presencial' },
  { ruta: 'notificaciones', nombre: 'notificaciones', titulo: 'Notificaciones electrónicas', subtitulo: 'Envío y constancia de notificaciones' },
  { ruta: 'sede', nombre: 'sede', titulo: 'Sede Electrónica', subtitulo: 'Publicación del sitio público' },
  { ruta: 'carpeta', nombre: 'carpeta', titulo: 'Carpeta Ciudadana', subtitulo: 'Expediente del ciudadano' },
  { ruta: 'autenticacion', nombre: 'autenticacion', titulo: 'Autenticación Digital', subtitulo: 'Mecanismos de identidad electrónica' },
  { ruta: 'cms', nombre: 'cms', titulo: 'CMS', subtitulo: 'Gestión de contenidos del portal' },
  { ruta: 'portal', nombre: 'portal', titulo: 'Portal Ciudadano', subtitulo: 'Vista previa del portal publicado' },
  { ruta: 'transparencia', nombre: 'transparencia', titulo: 'Transparencia / ITA', subtitulo: 'Índice de transparencia y acceso a la información' },
  { ruta: 'gestion-documental', nombre: 'gestion-documental', titulo: 'Gestión Documental', subtitulo: 'Ciclo de vida del documento electrónico' },
  { ruta: 'sigmi', nombre: 'sigmi', titulo: 'Interoperabilidad SIGMI', subtitulo: 'Intercambio con el sistema distrital' },
  { ruta: 'integraciones', nombre: 'integraciones', titulo: 'Conectores externos', subtitulo: 'Servicios de terceros y pasarelas' },
  { ruta: 'usuarios', nombre: 'usuarios', titulo: 'Usuarios y Roles', subtitulo: 'Cuentas, roles y permisos' },
  { ruta: 'auditoria', nombre: 'auditoria', titulo: 'Auditoría', subtitulo: 'Trazabilidad de la operación' },
  { ruta: 'reportes', nombre: 'reportes', titulo: 'Reportes', subtitulo: 'Informes y exportaciones' },
  { ruta: 'asignacion', nombre: 'asignacion', titulo: 'Motor de asignación', subtitulo: 'Reparto automático de casos' },
  { ruta: 'configuracion', nombre: 'configuracion', titulo: 'Configuración', subtitulo: 'Parámetros de la sede' },
]

const rutas: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/AdminLayout.vue'),
    children: [
      {
        path: '',
        name: 'panel.inicio',
        component: () => import('@/views/admin/InicioView.vue'),
        // El permiso sale del mapa compartido y no de una cadena suelta: la
        // misma entrada alimenta el menú lateral, así que no pueden divergir.
        meta: { requiereSesion: true, permiso: PERMISO_POR_RUTA['/'], titulo: 'Dashboard' },
      },
      {
        path: 'perfil',
        name: 'panel.perfil',
        component: () => import('@/views/admin/PerfilView.vue'),
        meta: { requiereSesion: true, titulo: 'Mi perfil' },
      },
      ...MODULOS.map(
        (modulo): RouteRecordRaw => ({
          path: modulo.ruta,
          name: modulo.nombre,
          component: () => import('@/views/admin/EnConstruccionView.vue'),
          meta: {
            requiereSesion: true,
            permiso: PERMISO_POR_RUTA[`/${modulo.ruta}`],
            titulo: modulo.titulo,
            subtitulo: modulo.subtitulo,
          },
        }),
      ),
    ],
  },
  {
    path: '/acceso',
    name: 'acceso.entrar',
    component: () => import('@/views/acceso/EntrarView.vue'),
    meta: { soloInvitados: true, layout: 'auth', titulo: 'Iniciar sesión' },
  },
  {
    path: '/acceso/mfa',
    name: 'acceso.mfa',
    component: () => import('@/views/acceso/MfaView.vue'),
    meta: { soloInvitados: true, layout: 'auth', titulo: 'Verificación en dos pasos' },
  },
  {
    path: '/recuperar',
    name: 'acceso.recuperar',
    component: () => import('@/views/acceso/RecuperarView.vue'),
    meta: { soloInvitados: true, layout: 'auth', titulo: 'Recuperar contraseña' },
  },
  {
    path: '/sin-permiso',
    name: 'sin-permiso',
    component: () => import('@/views/acceso/SinPermisoView.vue'),
    // Pide sesión: decirle «no tiene permiso» a quien ni siquiera ha entrado
    // confunde más que ayudar. Sin sesión se va a la entrada, como en el resto
    // del panel; con sesión pero sin el permiso del módulo, sí se explica aquí.
    meta: { requiereSesion: true, layout: 'blank', titulo: 'Sin permiso' },
  },
  {
    path: '/:ruta(.*)*',
    name: 'no-encontrado',
    component: () => import('@/views/acceso/NoEncontradoView.vue'),
    meta: { layout: 'blank', titulo: 'Página no encontrada' },
  },
]

const enrutador = createRouter({
  // Historial del navegador, no almohadilla: las direcciones del panel tienen
  // que poder pegarse y compartirse.
  history: createWebHistory('/admin'),
  routes: rutas,
  scrollBehavior: () => ({ top: 0 }),
})

/**
 * Guardias de navegación.
 *
 * Aplican los tres metadatos que las rutas ya declaraban y que hasta ahora no
 * leía nadie. El orden importa: primero la sesión (no se entra al panel sin
 * ella), después el permiso (con sesión, pero sin el permiso del módulo se
 * explica la situación en `sin-permiso`), y por último `soloInvitados` (quien
 * ya entró no vuelve a la pantalla de acceso).
 */
enrutador.beforeEach((destino) => {
  const sesion = useSesionStore()

  if (destino.meta.requiereSesion && !sesion.iniciada) {
    return { name: 'acceso.entrar' }
  }

  if (destino.meta.permiso && !sesion.tienePermiso(destino.meta.permiso)) {
    return { name: 'sin-permiso' }
  }

  if (destino.meta.soloInvitados && sesion.iniciada) {
    return { name: 'panel.inicio' }
  }

  return true
})

enrutador.afterEach((destino) => {
  document.title = destino.meta.titulo ? `${destino.meta.titulo} · ${TITULO_BASE}` : TITULO_BASE
})

export default enrutador
