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
 */
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

const rutas: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/DisposicionPanel.vue'),
    children: [
      {
        path: '',
        name: 'panel.inicio',
        component: () => import('@/views/admin/InicioView.vue'),
        meta: { requiereSesion: true, permiso: 'panel-administrative' },
      },
    ],
  },
  {
    path: '/acceso',
    name: 'acceso.entrar',
    component: () => import('@/views/acceso/EntrarView.vue'),
    meta: { soloInvitados: true },
  },
  {
    path: '/sin-permiso',
    name: 'sin-permiso',
    component: () => import('@/views/acceso/SinPermisoView.vue'),
  },
  {
    path: '/:ruta(.*)*',
    name: 'no-encontrado',
    component: () => import('@/views/acceso/NoEncontradoView.vue'),
  },
]

export default createRouter({
  // Historial del navegador, no almohadilla: las direcciones del panel tienen
  // que poder pegarse y compartirse.
  history: createWebHistory('/panel'),
  routes: rutas,
  scrollBehavior: () => ({ top: 0 }),
})
