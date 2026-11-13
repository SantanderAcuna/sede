/**
 * Permisos por módulo del panel.
 *
 * Se declaran **una sola vez** aquí porque los consumen dos sitios que tienen
 * que decir lo mismo: `meta.permiso` de cada ruta (`src/router/index.ts`), que
 * decide si la pantalla se puede abrir, y el menú lateral
 * (`src/layouts/AdminLayout.vue`), que decide si el módulo se ofrece. Con dos
 * listas separadas, el día que una cambie la otra miente sin que nadie lo note.
 *
 * La nomenclatura es deliberadamente explícita (`modulo.accion`) porque estos
 * identificadores viajarán al backend: el nombre de un permiso es un contrato,
 * no una etiqueta de interfaz.
 *
 * La interfaz **no** es el control de seguridad: ocultar un módulo y bloquear
 * una ruta evitan el error y la confusión, pero es el backend quien vuelve a
 * comprobar el permiso en cada petición.
 */
export const PERMISO_POR_RUTA: Record<string, string> = {
  '/': 'panel-administrative',

  // Atención al ciudadano
  '/pqrsd': 'pqrsd.ver',
  '/tramites': 'tramites.ver',
  '/citas': 'citas.ver',
  '/notificaciones': 'notificaciones.ver',

  // Servicios al ciudadano
  '/sede': 'sede.publicar',
  '/carpeta': 'carpeta.ver',
  '/autenticacion': 'autenticacion.ver',

  // Contenidos
  '/cms': 'cms.ver',
  '/portal': 'portal.publicar',
  '/transparencia': 'transparencia.ver',

  // Contenidos del sitio
  '/contenidos/noticias': 'noticias.ver',
  '/contenidos/carrusel': 'banners.ver',

  // Gestión documental e integraciones
  '/gestion-documental': 'documental.ver',
  '/sigmi': 'sigmi.ver',
  '/integraciones': 'integraciones.ver',

  // Administración
  '/usuarios': 'usuarios.ver',
  '/usuarios/roles': 'roles.ver',
  '/auditoria': 'auditoria.ver',
  '/reportes': 'reportes.ver',
  '/asignacion': 'asignacion.ver',
  '/configuracion/entidad': 'entidad.ver',
  '/configuracion/politicas': 'politicas.ver',
  '/configuracion': 'configuracion.ver',

  // Mi cuenta
  '/perfil': 'perfil.ver',
}

/**
 * Permiso declarado para una ruta del panel.
 *
 * Devuelve `undefined` cuando la ruta no exige permiso propio; en tal caso la
 * guardia la deja pasar con sesión iniciada, y el menú la muestra. Es un valor
 * consciente, no un fallo silencioso: `sin-permiso` y las pantallas de acceso
 * son rutas que no pertenecen a ningún módulo.
 */
export function permisoDeRuta(ruta: string): string | undefined {
  return PERMISO_POR_RUTA[ruta]
}

/**
 * Permiso de una acción concreta sobre un módulo (`noticias`, `crear` → `noticias.crear`).
 *
 * Abrir una pantalla pide «ver»; los botones de crear, eliminar y publicar piden cada uno el suyo.
 * Así un rol puede mirar sin poder tocar, que es lo que la matriz de roles permite dar.
 */
export function permisoDe(modulo: string, accion: 'ver' | 'crear' | 'editar' | 'eliminar' | 'publicar'): string {
  return `${modulo}.${accion}`
}
