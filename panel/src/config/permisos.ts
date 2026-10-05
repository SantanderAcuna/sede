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
  '/autenticacion': 'autenticacion.gestionar',

  // Contenidos
  '/cms': 'cms.gestionar',
  '/portal': 'portal.publicar',
  '/transparencia': 'transparencia.ver',

  // Gestión documental e integraciones
  '/gestion-documental': 'documental.gestionar',
  '/sigmi': 'sigmi.ver',
  '/integraciones': 'integraciones.gestionar',

  // Administración
  '/usuarios': 'usuarios.gestionar',
  '/auditoria': 'auditoria.ver',
  '/reportes': 'reportes.ver',
  '/asignacion': 'asignacion.gestionar',
  '/configuracion/entidad': 'entidad.gestionar',
  '/configuracion': 'configuracion.gestionar',
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
