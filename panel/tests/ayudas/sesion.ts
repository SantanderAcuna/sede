import { createPinia, setActivePinia } from 'pinia'
import { useSesionStore } from '@/stores/sesion'

/**
 * Prepara una sesión de prueba. Con permisos `['*']` (súper administrador) todo está permitido;
 * con una lista, solo esos permisos. Las vistas preguntan a la sesión qué botones ofrecer.
 */
export function conSesion(permisos: string[] = ['*']): void {
  setActivePinia(createPinia())
  useSesionStore().usuario = { id: 1, email: 'prueba@example.com', nombre: 'Prueba', permisos }
}
