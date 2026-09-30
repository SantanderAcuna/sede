import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import type { Permiso } from '@/types/auth';

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean;
    permisos?: Permiso[];
    title?: string;
    layout?: 'admin' | 'auth' | 'blank';
  }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { layout: 'auth', title: 'Iniciar sesión' },
  },
  {
    path: '/mfa',
    name: 'mfa',
    component: () => import('@/views/auth/MfaView.vue'),
    meta: { layout: 'auth', title: 'Verificación MFA' },
  },
  {
    path: '/',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: { name: 'dashboard' } },
      { path: 'dashboard', name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
        meta: { title: 'Dashboard' } },
      { path: 'pqrsd', name: 'pqrsd',
        component: () => import('@/views/pqrsd/PqrsdListView.vue'),
        meta: { title: 'PQRSD', permisos: ['pqrsd.read'] } },
      { path: 'pqrsd/:id', name: 'pqrsd-detail',
        component: () => import('@/views/pqrsd/PqrsdDetailView.vue'),
        meta: { title: 'Detalle PQRSD', permisos: ['pqrsd.read'] } },
      { path: 'tramites', name: 'tramites',
        component: () => import('@/views/tramites/TramitesView.vue'),
        meta: { title: 'Trámites', permisos: ['tramites.read'] } },
      { path: 'citas', name: 'citas',
        component: () => import('@/views/citas/CitasView.vue'),
        meta: { title: 'Citas y Turnos' } },
      { path: 'notificaciones', name: 'notificaciones',
        component: () => import('@/views/notificaciones/NotificacionesView.vue'),
        meta: { title: 'Notificaciones electrónicas' } },
      { path: 'sede', name: 'sede',
        component: () => import('@/views/servicios/SedeElectronicaView.vue'),
        meta: { title: 'Sede Electrónica' } },
      { path: 'carpeta', name: 'carpeta',
        component: () => import('@/views/servicios/CarpetaCiudadanaView.vue'),
        meta: { title: 'Carpeta Ciudadana' } },
      { path: 'autenticacion', name: 'autenticacion',
        component: () => import('@/views/servicios/AutenticacionDigitalView.vue'),
        meta: { title: 'Autenticación Digital' } },
      { path: 'cms', name: 'cms',
        component: () => import('@/views/cms/CmsView.vue'),
        meta: { title: 'CMS', permisos: ['cms.read'] } },
      { path: 'portal', name: 'portal',
        component: () => import('@/views/cms/PortalCiudadanoView.vue'),
        meta: { title: 'Portal Ciudadano', permisos: ['cms.read'] } },
      { path: 'transparencia', name: 'transparencia',
        component: () => import('@/views/transparencia/TransparenciaView.vue'),
        meta: { title: 'Transparencia / ITA' } },
      { path: 'gestion-documental', name: 'gestion-documental',
        component: () => import('@/views/documental/GestionDocumentalView.vue'),
        meta: { title: 'Gestión Documental' } },
      { path: 'sigmi', name: 'sigmi',
        component: () => import('@/views/integraciones/SigmiView.vue'),
        meta: { title: 'Interoperabilidad SIGMI' } },
      { path: 'integraciones', name: 'integraciones',
        component: () => import('@/views/integraciones/IntegracionesView.vue'),
        meta: { title: 'Conectores externos' } },
      { path: 'usuarios', name: 'usuarios',
        component: () => import('@/views/admin/UsuariosView.vue'),
        meta: { title: 'Usuarios y Roles', permisos: ['usuarios.read'] } },
      { path: 'auditoria', name: 'auditoria',
        component: () => import('@/views/admin/AuditoriaView.vue'),
        meta: { title: 'Auditoría', permisos: ['auditoria.read'] } },
      { path: 'reportes', name: 'reportes',
        component: () => import('@/views/admin/ReportesView.vue'),
        meta: { title: 'Reportes', permisos: ['reportes.read'] } },
      { path: 'asignacion', name: 'asignacion',
        component: () => import('@/views/admin/AsignacionView.vue'),
        meta: { title: 'Motor de asignación', permisos: ['config.read'] } },
      { path: 'configuracion', name: 'configuracion',
        component: () => import('@/views/admin/ConfiguracionView.vue'),
        meta: { title: 'Configuración', permisos: ['config.read'] } },
    ],
  },
  { path: '/403', name: 'forbidden', component: () => import('@/views/errors/ForbiddenView.vue'), meta: { layout: 'blank' } },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/errors/NotFoundView.vue'), meta: { layout: 'blank' } },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach((to) => {
  const auth = useAuthStore();
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }
  if (to.meta.permisos?.length) {
    const ok = to.meta.permisos.some(p => auth.can(p));
    if (!ok) return { name: 'forbidden' };
  }
  return true;
});

router.afterEach((to) => {
  const base = 'SGDI · Alcaldía de Santa Marta';
  document.title = to.meta.title ? `${to.meta.title} · ${base}` : base;
});

export default router;
