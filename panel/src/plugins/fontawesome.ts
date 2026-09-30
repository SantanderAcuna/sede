/**
 * Registro de iconos de FontAwesome.
 *
 * Se registran **icono a icono**, nunca la colección completa: `library.add`
 * con el paquete entero arrastraría los ~2.000 iconos al bundle. Aquí sólo
 * entran los que la interfaz usa de verdad.
 *
 * El componente se registra globalmente como `<FaIcon>` en `main.ts`.
 */
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  // Navegación del panel
  faGaugeHigh, faInbox, faFileLines, faCalendarCheck, faBell, faGlobe,
  faBriefcase, faKey, faPenToSquare, faDisplay, faLandmark, faFolderOpen,
  faLink, faPlug, faUsers, faMagnifyingGlass, faChartLine, faGear, faSliders,
  // Acciones y estado
  faRightFromBracket, faBars, faChevronRight, faChevronLeft, faChevronDown,
  faCircleCheck, faTriangleExclamation, faCircleInfo, faXmark, faPlus,
  faDownload, faEye, faShieldHalved, faFingerprint, faArrowUp, faArrowDown,
  // Barra de accesibilidad
  faUniversalAccess, faMinus, faMoon, faRotateLeft, faCircleHalfStroke,
} from '@fortawesome/free-solid-svg-icons'

library.add(
  faGaugeHigh, faInbox, faFileLines, faCalendarCheck, faBell, faGlobe,
  faBriefcase, faKey, faPenToSquare, faDisplay, faLandmark, faFolderOpen,
  faLink, faPlug, faUsers, faMagnifyingGlass, faChartLine, faGear, faSliders,
  faRightFromBracket, faBars, faChevronRight, faChevronLeft, faChevronDown,
  faCircleCheck, faTriangleExclamation, faCircleInfo, faXmark, faPlus,
  faDownload, faEye, faShieldHalved, faFingerprint, faArrowUp, faArrowDown,
  faUniversalAccess, faMinus, faMoon, faRotateLeft, faCircleHalfStroke,
)

export { FontAwesomeIcon }
