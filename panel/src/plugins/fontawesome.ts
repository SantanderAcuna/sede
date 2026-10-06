/**
 * Registro de iconos de FontAwesome.
 *
 * Se registran **icono a icono**, nunca la colección completa: `library.add`
 * con el paquete entero arrastraría los ~2.000 iconos al bundle. Aquí sólo
 * entran los que la interfaz usa de verdad.
 *
 * El componente se registra globalmente como `<FaIcon>` en `main.ts`.
 *
 * Fix 2026-10-06: nombres en camelCase (FontAwesome 6) en lugar de kebab-case.
 */
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  // Navegación del panel
  faGaugeHigh, faInbox, faFileLines, faCalendarCheck, faBell, faGlobe,
  faBriefcase, faKey, faPenToSquare, faDisplay, faLandmark, faBuilding,
  faFolderOpen, faLink, faPlug, faUsers, faMagnifyingGlass, faChartLine,
  faGear, faSliders, faShieldHalved,
  // Acciones y estado
  faRightFromBracket, faBars, faChevronRight, faChevronLeft, faChevronDown,
  faCircleCheck, faCircleXmark, faCircleExclamation, faTriangleExclamation,
  faCircleInfo, faCircleHalfStroke, faCircle,
  faXmark, faPlus, faDownload, faEye, faFingerprint,
  faArrowUp, faArrowDown, faTrash, faFloppyDisk, faShareNodes, faFileContract,
  // Identidad / medios
  faIdCardClip, faMobileScreen, faInfinity, faLock,
  // Barra de accesibilidad
  faUniversalAccess, faMinus, faMoon, faRotateLeft,
} from '@fortawesome/free-solid-svg-icons'

library.add(
  // Navegación
  faGaugeHigh, faInbox, faFileLines, faCalendarCheck, faBell, faGlobe,
  faBriefcase, faKey, faPenToSquare, faDisplay, faLandmark, faBuilding,
  faFolderOpen, faLink, faPlug, faUsers, faMagnifyingGlass, faChartLine,
  faGear, faSliders, faShieldHalved,
  // Acciones y estado
  faRightFromBracket, faBars, faChevronRight, faChevronLeft, faChevronDown,
  faCircleCheck, faCircleXmark, faCircleExclamation, faTriangleExclamation,
  faCircleInfo, faCircleHalfStroke, faCircle,
  faXmark, faPlus, faDownload, faEye, faFingerprint,
  faArrowUp, faArrowDown, faTrash, faFloppyDisk, faShareNodes, faFileContract,
  // Identidad / medios
  faIdCardClip, faMobileScreen, faInfinity, faLock,
  // Barra de accesibilidad
  faUniversalAccess, faMinus, faMoon, faRotateLeft,
)

export { FontAwesomeIcon }
