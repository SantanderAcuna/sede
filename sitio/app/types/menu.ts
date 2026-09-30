/**
 * Forma del menú principal de la Sede.
 *
 * Se declara aparte y no dentro de la disposición porque el menú es contenido
 * normado, no un detalle de maquetación: lo fijan los criterios funcionales del
 * expediente y conviene poder leerlo sin abrir el componente.
 *
 * Los límites no son gusto:
 *
 *  - **FUN-012** — como máximo **7 opciones** principales y desplegables de
 *    **2 niveles**. La estructura de estos tipos hace imposible un tercer nivel:
 *    una subsección sólo admite enlaces, no otras subsecciones.
 *  - **FUN-013** — las tres secciones obligatorias: Transparencia y acceso a la
 *    información pública, Servicios a la Ciudadanía y Participa.
 *
 * La estructura coincide con la que espera `MenuNavegacionGovco`, que reproduce
 * la del Kit. Se declara aquí en lugar de importarse de allí para no atar el
 * contenido del sitio al componente que lo pinta.
 */

/** Un enlace de último nivel: no admite más desplegables (FUN-012). */
export interface EnlaceMenu {
  etiqueta: string
  ruta: string
}

/** Un bloque desplegable dentro de una opción principal. */
export interface SubseccionMenu {
  titulo: string
  enlaces: EnlaceMenu[]
}

/** Una opción del menú principal. */
export interface ItemMenu {
  etiqueta: string
  /** Ausente cuando la opción sólo despliega subsecciones. */
  ruta?: string
  subsecciones?: SubseccionMenu[]
}

/** El menú completo. */
export type MenuPrincipal = ItemMenu[]
