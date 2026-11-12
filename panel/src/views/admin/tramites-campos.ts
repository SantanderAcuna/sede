/**
 * Descripción de los campos de cada lista del formulario de trámites.
 *
 * Los textos están escritos para quien no conoce el sistema: dicen qué se pide, no cómo se llama en la
 * base de datos. Las claves son las del contrato (`TramiteItem`).
 */
import type { CampoLista, OpcionCampo } from '@/components/base/ListaEditable.vue'
import type { Fila } from './tramites-logica'

export const MODALIDADES: OpcionCampo[] = [
  { valor: 'en_linea', etiqueta: 'En línea' },
  { valor: 'parcialmente_en_linea', etiqueta: 'Parcialmente en línea' },
  { valor: 'presencial', etiqueta: 'Presencial' },
]
export const COSTOS: OpcionCampo[] = [
  { valor: 'gratuito', etiqueta: 'Gratuito' },
  { valor: 'con_costo', etiqueta: 'Con costo' },
]
export const CANALES_INICIO: OpcionCampo[] = [
  { valor: 'propio', etiqueta: 'En la Sede Electrónica de la Alcaldía' },
  { valor: 'portal_nacional', etiqueta: 'En un portal nacional (GOV.CO u otro)' },
]
export const TIPOS_VALOR: OpcionCampo[] = [
  { valor: 'fijo', etiqueta: 'Valor fijo' },
  { valor: 'smlv', etiqueta: 'En salarios mínimos' },
  { valor: 'avaluo_liquidacion', etiqueta: 'Depende del avalúo o la liquidación' },
  { valor: 'rango', etiqueta: 'Un rango de valores' },
]
export const FECHA_CUALQUIERA: OpcionCampo[] = [
  { valor: 'si', etiqueta: 'Sí, en cualquier fecha' },
  { valor: 'no', etiqueta: 'No, solo en ciertas fechas' },
]

const TIPOS_REQUISITO: OpcionCampo[] = [
  { valor: 'documento', etiqueta: 'Documento que debe presentar' },
  { valor: 'verificacion_institucional', etiqueta: 'Verificación que hace la entidad' },
  { valor: 'solicitud', etiqueta: 'Solicitud' },
  { valor: 'formulario', etiqueta: 'Formulario' },
  { valor: 'pago', etiqueta: 'Pago' },
]
const AUDIENCIAS: OpcionCampo[] = [
  { valor: 'Ciudadano', etiqueta: 'Ciudadano' },
  { valor: 'Extranjeros', etiqueta: 'Extranjeros' },
  { valor: 'Organizaciones', etiqueta: 'Organizaciones' },
  { valor: 'Instituciones o dependencias públicas', etiqueta: 'Instituciones o dependencias públicas' },
]
const CANALES_REQUISITO: OpcionCampo[] = [
  { valor: 'web', etiqueta: 'Por la web' },
  { valor: 'presencial', etiqueta: 'Presencial' },
  { valor: 'correo', etiqueta: 'Por correo electrónico' },
  { valor: 'telefonico', etiqueta: 'Por teléfono' },
]
const CANALES_CONSULTA: OpcionCampo[] = [
  { valor: 'sede', etiqueta: 'Sede Electrónica' },
  { valor: 'presencial', etiqueta: 'Presencial' },
  { valor: 'telefonico', etiqueta: 'Teléfono' },
  { valor: 'web', etiqueta: 'Página web' },
  { valor: 'correo', etiqueta: 'Correo electrónico' },
  { valor: 'otro', etiqueta: 'Otro' },
]

export const CAMPOS_REQUISITO: CampoLista[] = [
  { clave: 'tipo', etiqueta: 'Qué clase de requisito es', tipo: 'select', opciones: TIPOS_REQUISITO, requerido: true },
  { clave: 'obligatorio', etiqueta: 'Es obligatorio', tipo: 'casilla', porDefecto: true },
  { clave: 'descripcion', etiqueta: 'Descripción', tipo: 'textarea', ayuda: 'Qué debe hacer o presentar la persona, en palabras sencillas.' },
  { clave: 'documento', etiqueta: 'Nombre del documento', tipo: 'texto', avanzado: true },
  { clave: 'cantidad', etiqueta: 'Cantidad', tipo: 'numero', avanzado: true },
  { clave: 'unidad_cantidad', etiqueta: 'Unidad de la cantidad', tipo: 'texto', avanzado: true, ayuda: 'Por ejemplo: copias, hojas.' },
  { clave: 'nota', etiqueta: 'Nota', tipo: 'textarea', avanzado: true },
  { clave: 'canal', etiqueta: 'Por qué medio se presenta', tipo: 'select', opciones: CANALES_REQUISITO, avanzado: true },
  { clave: 'url', etiqueta: 'Dirección web', tipo: 'url', avanzado: true },
  { clave: 'correo', etiqueta: 'Correo electrónico', tipo: 'correo', avanzado: true },
  { clave: 'formulario', etiqueta: 'Nombre del formulario', tipo: 'texto', avanzado: true },
  { clave: 'formulario_url', etiqueta: 'Dirección del formulario', tipo: 'url', avanzado: true },
  { clave: 'url_pago', etiqueta: 'Dirección donde se paga', tipo: 'url', avanzado: true },
  { clave: 'tipos_audiencia', etiqueta: 'A quién aplica', tipo: 'multi', opciones: AUDIENCIAS, avanzado: true, ayuda: 'Si no marca ninguno, aplica a todos.' },
  { clave: 'orden', etiqueta: 'Posición', tipo: 'numero', avanzado: true },
]

export const CAMPOS_MOMENTO: CampoLista[] = [
  { clave: 'orden', etiqueta: 'Número del paso', tipo: 'numero', requerido: true },
  { clave: 'titulo', etiqueta: 'Título del paso', tipo: 'texto', requerido: true, ayuda: 'Por ejemplo: «Reunir los documentos».' },
  { clave: 'descripcion', etiqueta: 'Descripción', tipo: 'textarea' },
  { clave: 'requisitos', etiqueta: 'Requisitos de este paso', tipo: 'lista', etiquetaItem: 'Requisito', campos: CAMPOS_REQUISITO, nuevoItem: () => ({ tipo: 'documento', obligatorio: true }) },
]

export const CAMPOS_DOCUMENTO: CampoLista[] = [
  { clave: 'nombre', etiqueta: 'Nombre del documento', tipo: 'texto', requerido: true },
  { clave: 'url', etiqueta: 'Dirección para descargarlo', tipo: 'url' },
  { clave: 'formato', etiqueta: 'Formato', tipo: 'texto', ayuda: 'Por ejemplo: PDF, DOCX.' },
]

export const CAMPOS_AUDIENCIA: CampoLista[] = [
  { clave: 'grupo', etiqueta: 'Grupo', tipo: 'texto', ayuda: 'Por ejemplo: Ciudadano.' },
  { clave: 'nombre', etiqueta: 'Nombre', tipo: 'texto', requerido: true },
  { clave: 'descripcion', etiqueta: 'Descripción', tipo: 'textarea' },
]

export const CAMPOS_TEXTO: CampoLista[] = [{ clave: 'valor', etiqueta: 'Texto', tipo: 'texto', requerido: true }]

export const CAMPOS_PUNTO: CampoLista[] = [
  { clave: 'nombre', etiqueta: 'Nombre del lugar', tipo: 'texto', requerido: true },
  { clave: 'direccion', etiqueta: 'Dirección', tipo: 'texto', requerido: true },
  { clave: 'horario', etiqueta: 'Horario de atención', tipo: 'texto' },
  { clave: 'telefono', etiqueta: 'Teléfono', tipo: 'texto' },
  { clave: 'municipio', etiqueta: 'Municipio', tipo: 'texto', avanzado: true },
  { clave: 'departamento', etiqueta: 'Departamento', tipo: 'texto', avanzado: true },
  { clave: 'latitud', etiqueta: 'Latitud', tipo: 'decimal', avanzado: true },
  { clave: 'longitud', etiqueta: 'Longitud', tipo: 'decimal', avanzado: true },
]

export const CAMPOS_NORMA: CampoLista[] = [
  { clave: 'tipo', etiqueta: 'Clase de norma', tipo: 'texto', requerido: true, ayuda: 'Por ejemplo: Ley, Decreto, Acuerdo.' },
  { clave: 'numero', etiqueta: 'Número', tipo: 'texto' },
  { clave: 'anio', etiqueta: 'Año', tipo: 'numero' },
  { clave: 'articulos', etiqueta: 'Artículos que aplican', tipo: 'texto' },
  { clave: 'url', etiqueta: 'Dirección web de la norma', tipo: 'url' },
  { clave: 'url_descarga', etiqueta: 'Dirección para descargarla', tipo: 'url', avanzado: true },
]

/** Cuentas bancarias de un pago (forma de la fuente oficial: banco / tipo / número / titular). */
export const CAMPOS_CUENTA: CampoLista[] = [
  { clave: 'banco', etiqueta: 'Banco', tipo: 'texto' },
  { clave: 'tipo', etiqueta: 'Tipo de cuenta', tipo: 'texto', ayuda: 'Por ejemplo: Ahorros, Corriente.' },
  { clave: 'numero', etiqueta: 'Número de cuenta', tipo: 'texto' },
  { clave: 'titular', etiqueta: 'Titular', tipo: 'texto' },
]

export const CAMPOS_CANAL_CONSULTA: CampoLista[] = [
  { clave: 'canal', etiqueta: 'Canal', tipo: 'select', opciones: CANALES_CONSULTA, requerido: true },
  { clave: 'habilitado', etiqueta: 'Está disponible', tipo: 'casilla', porDefecto: true },
  { clave: 'nombre', etiqueta: 'Nombre', tipo: 'texto' },
  { clave: 'url', etiqueta: 'Dirección web', tipo: 'url' },
  { clave: 'correo', etiqueta: 'Correo electrónico', tipo: 'correo' },
  { clave: 'telefono', etiqueta: 'Teléfono', tipo: 'texto' },
  { clave: 'horario', etiqueta: 'Horario', tipo: 'texto' },
]

export const CAMPOS_TELEFONO: CampoLista[] = [
  { clave: 'numero', etiqueta: 'Número', tipo: 'texto' },
  { clave: 'extension', etiqueta: 'Extensión', tipo: 'texto' },
  { clave: 'horario', etiqueta: 'Horario', tipo: 'texto' },
]
export const CAMPOS_CORREO: CampoLista[] = [{ clave: 'email', etiqueta: 'Correo electrónico', tipo: 'correo' }]
export const CAMPOS_WEB: CampoLista[] = [
  { clave: 'url', etiqueta: 'Dirección web', tipo: 'url' },
  { clave: 'nombre', etiqueta: 'Nombre', tipo: 'texto' },
]
export const CAMPOS_PRESENCIAL: CampoLista[] = [
  { clave: 'nombre', etiqueta: 'Nombre del lugar', tipo: 'texto', requerido: true },
  { clave: 'direccion', etiqueta: 'Dirección', tipo: 'texto', requerido: true },
  { clave: 'horario', etiqueta: 'Horario', tipo: 'texto' },
  { clave: 'telefono', etiqueta: 'Teléfono', tipo: 'texto' },
]

export const NUEVO_REQUISITO = (): Fila => ({ tipo: 'documento', obligatorio: true })
