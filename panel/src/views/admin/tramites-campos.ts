/**
 * Descripción de los campos de cada lista del formulario de trámites.
 *
 * Los textos están escritos para quien no conoce el sistema: dicen qué se pide, no cómo se llama en
 * la base de datos. El catálogo es contenido propio y relacional: la ficha se compone de pasos,
 * requisitos (cada uno puede pertenecer a un paso), documentos, puntos de atención, normas, canales
 * de consulta y cuentas del costo.
 */
import type { CampoLista, OpcionCampo } from '@/components/base/ListaEditable.vue'
import type { Fila } from './tramites-logica'

export const TIPOS: OpcionCampo[] = [
  { valor: 'tramites', etiqueta: 'Trámite' },
  { valor: 'opa', etiqueta: 'OPA (otro procedimiento administrativo)' },
  { valor: 'consultas', etiqueta: 'Consulta de acceso a información pública' },
]
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
const CANALES_CONSULTA: OpcionCampo[] = [
  { valor: 'sede', etiqueta: 'Sede Electrónica' },
  { valor: 'presencial', etiqueta: 'Presencial' },
  { valor: 'telefonico', etiqueta: 'Teléfono' },
  { valor: 'web', etiqueta: 'Página web' },
  { valor: 'correo', etiqueta: 'Correo electrónico' },
  { valor: 'otro', etiqueta: 'Otro' },
]

export const CAMPOS_PASO: CampoLista[] = [
  { clave: 'orden', etiqueta: 'Número del paso', tipo: 'numero', requerido: true },
  { clave: 'titulo', etiqueta: 'Título del paso', tipo: 'texto', requerido: true, ayuda: 'Por ejemplo: «Reunir los documentos».' },
  { clave: 'descripcion', etiqueta: 'Descripción', tipo: 'textarea' },
]

export const CAMPOS_REQUISITO: CampoLista[] = [
  { clave: 'tipo', etiqueta: 'Qué clase de requisito es', tipo: 'select', opciones: TIPOS_REQUISITO, requerido: true },
  { clave: 'descripcion', etiqueta: 'Descripción', tipo: 'textarea', requerido: true, ayuda: 'Qué debe hacer o presentar la persona, en palabras sencillas.' },
  { clave: 'obligatorio', etiqueta: 'Es obligatorio', tipo: 'casilla', porDefecto: true },
  { clave: 'paso', etiqueta: 'Paso al que pertenece', tipo: 'numero', avanzado: true, ayuda: 'El número del paso; déjelo vacío si es un requisito general.' },
  { clave: 'nota', etiqueta: 'Nota', tipo: 'textarea', avanzado: true },
  { clave: 'url', etiqueta: 'Dirección web', tipo: 'url', avanzado: true },
  { clave: 'orden', etiqueta: 'Posición', tipo: 'numero', avanzado: true },
]

export const CAMPOS_DOCUMENTO: CampoLista[] = [
  { clave: 'nombre', etiqueta: 'Nombre del documento', tipo: 'texto', requerido: true },
  { clave: 'url', etiqueta: 'Dirección para descargarlo', tipo: 'url' },
  { clave: 'formato', etiqueta: 'Formato', tipo: 'texto', ayuda: 'Por ejemplo: PDF, DOCX.' },
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

export const NUEVO_REQUISITO = (): Fila => ({ tipo: 'documento', obligatorio: true })
export const NUEVO_PASO = (): Fila => ({ obligatorio: true })
