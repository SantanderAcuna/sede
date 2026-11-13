/**
 * Lógica pura de la pantalla de trámites: pasar entre lo que entrega la API y lo que edita el
 * formulario, decidir qué se envía y explicar en español lo que falta para publicar.
 *
 * El catálogo es **contenido propio y relacional**: la ficha viaja por partes (pasos, requisitos,
 * documentos, puntos de atención, normas, canales de consulta, solicitantes y cuentas del costo),
 * con la misma forma al leerla y al guardarla. El elemento puede ser un trámite, una OPA o una
 * consulta (`type`), y su dependencia responsable (`dependencia_id`) se elige de una lista.
 *
 * **Regla de guardado.** Los campos simples viajan sólo si cambiaron. La **ficha** (las listas y
 * los solicitantes) viaja **entera en cuanto cualquier parte de ella cambia**: el servidor
 * reemplaza la ficha completa, así que enviar sólo una lista borraría las demás. Un trámite abierto
 * y guardado sin tocar no envía nada.
 */
import type { TramiteInput, TramitePanelItem } from '@/types/api'

/** Una fila de una lista editable: los campos que se ven y los que la pantalla no toca. */
export type Fila = Record<string, unknown>

export interface FormularioTramite {
  nombre: string
  /** Grupo del catálogo: `tramites`, `opa` o `consultas`. */
  type: string
  resumen: string
  url_ficha_gov_co: string
  modalidad: string
  tiene_costo: string
  /** Texto del campo numérico; vacío = sin dato. */
  tiempo_solucion_dias: string
  canal_inicio: string
  url_inicio: string
  url_manual_tramite_en_linea: string
  consulta_estado: string
  resultado: string
  observaciones_resultado: string
  /** `''` = sin dato, `si`, `no`. */
  fecha_cualquiera: string
  cuando_se_puede_realizar: string
  url_calendario: string
  palabras_relacionadas: string
  /** Id de la dependencia responsable, como texto del selector; `''` = sin asignar. */
  dependencia_id: string
  costo_tipo_valor: string
  costo_valor: string
  costo_moneda: string
  costo_url_pago: string
  costo_descripcion: string
  costo_cuentas: Fila[]
  categoria_slug: string
  categoria_nombre: string
  pasos: Fila[]
  requisitos: Fila[]
  documentos: Fila[]
  puntos_atencion: Fila[]
  normativa: Fila[]
  canales_consulta_estado: Fila[]
  /** Tipos de solicitante marcados (grupos): `ciudadano`, `extranjeros`, … */
  solicitantes: string[]
  medios_resultado: Fila[]
  publicado: boolean
}

export const NOMBRE_MIN = 3
export const NOMBRE_MAX = 200

/** Los grupos de solicitante que la ficha reconoce (RF-B1-022). */
export const SOLICITANTES: Array<{ valor: string; etiqueta: string }> = [
  { valor: 'ciudadano', etiqueta: 'Ciudadanos' },
  { valor: 'extranjeros', etiqueta: 'Extranjeros' },
  { valor: 'organizaciones', etiqueta: 'Organizaciones' },
  { valor: 'instituciones', etiqueta: 'Instituciones o dependencias públicas' },
]

/** Las partes de la ficha que viajan juntas (ver la regla de guardado). */
const FICHA_LISTAS = ['pasos', 'requisitos', 'documentos', 'puntos_atencion', 'normativa', 'canales_consulta_estado'] as const

const texto = (v: unknown): string => (v === null || v === undefined ? '' : String(v))
const copia = <T>(v: T): T => JSON.parse(JSON.stringify(v ?? null)) as T
const filas = (v: unknown): Fila[] => (Array.isArray(v) ? (copia(v) as Fila[]) : [])
const filasDeTexto = (v: unknown): Fila[] => (Array.isArray(v) ? v.map((x) => ({ valor: texto(x) })) : [])

export function formularioVacio(): FormularioTramite {
  return {
    nombre: '', type: 'tramites', resumen: '', url_ficha_gov_co: '', modalidad: '', tiene_costo: '', tiempo_solucion_dias: '',
    canal_inicio: '', url_inicio: '', url_manual_tramite_en_linea: '', consulta_estado: '', resultado: '', observaciones_resultado: '',
    fecha_cualquiera: '', cuando_se_puede_realizar: '', url_calendario: '', palabras_relacionadas: '', dependencia_id: '',
    costo_tipo_valor: '', costo_valor: '', costo_moneda: '', costo_url_pago: '', costo_descripcion: '', costo_cuentas: [],
    categoria_slug: '', categoria_nombre: '',
    pasos: [], requisitos: [], documentos: [], puntos_atencion: [], normativa: [], canales_consulta_estado: [],
    solicitantes: [], medios_resultado: [], publicado: false,
  }
}

export function formularioDesde(t: TramitePanelItem): FormularioTramite {
  const costo = (t.costo ?? null) as Record<string, unknown> | null
  const fecha = t.fecha_cualquiera
  const dependenciaId = (t as { dependencia_id?: number | null }).dependencia_id
  return {
    nombre: texto(t.nombre),
    type: texto(t.type) || 'tramites',
    resumen: texto(t.resumen),
    url_ficha_gov_co: texto(t.url_ficha_gov_co),
    modalidad: texto(t.modalidad),
    tiene_costo: texto(t.tiene_costo),
    tiempo_solucion_dias: texto(t.tiempo_solucion_dias),
    canal_inicio: texto(t.canal_inicio),
    url_inicio: texto(t.url_inicio),
    url_manual_tramite_en_linea: texto(t.url_manual_tramite_en_linea),
    consulta_estado: texto(t.consulta_estado),
    resultado: texto(t.resultado),
    observaciones_resultado: texto(t.observaciones_resultado),
    fecha_cualquiera: fecha === true ? 'si' : fecha === false ? 'no' : '',
    cuando_se_puede_realizar: texto(t.cuando_se_puede_realizar),
    url_calendario: texto(t.url_calendario),
    palabras_relacionadas: texto(t.palabras_relacionadas),
    dependencia_id: dependenciaId === null || dependenciaId === undefined ? '' : String(dependenciaId),
    costo_tipo_valor: texto(costo?.tipo_valor),
    costo_valor: texto(costo?.valor),
    costo_moneda: texto(costo?.moneda),
    costo_url_pago: texto(costo?.url_pago),
    costo_descripcion: texto(costo?.descripcion),
    costo_cuentas: filas(costo?.cuentas),
    categoria_slug: texto(t.categoria?.slug),
    categoria_nombre: texto(t.categoria?.nombre),
    pasos: filas(t.pasos),
    requisitos: filas(t.requisitos),
    documentos: filas(t.documentos),
    puntos_atencion: filas((t as { puntos_atencion?: unknown }).puntos_atencion),
    normativa: filas((t as { normativa?: unknown }).normativa),
    canales_consulta_estado: filas((t as { canales_consulta_estado?: unknown }).canales_consulta_estado),
    solicitantes: Array.isArray((t as { solicitantes?: unknown }).solicitantes)
      ? ((t as { solicitantes: unknown[] }).solicitantes.filter((x): x is string => typeof x === 'string'))
      : [],
    medios_resultado: filasDeTexto(t.medios_resultado),
    publicado: t.estado === 'publicado',
  }
}

// --- Limpieza de filas ----------------------------------------------------------------------------

const vacio = (v: unknown): boolean =>
  v === null || v === undefined || (typeof v === 'string' && v.trim() === '') || (Array.isArray(v) && v.length === 0)

/** Una fila sin ningún dato (la que se agregó y no se llenó) no se envía. */
function limpiarFila(fila: Fila): Fila | null {
  const limpia: Fila = {}
  for (const [k, v] of Object.entries(fila)) {
    if (typeof v === 'string') {
      if (v.trim() !== '') limpia[k] = v.trim()
    } else if (Array.isArray(v)) {
      const sub = v.some((x) => typeof x === 'object' && x !== null) ? limpiarFilas(v as Fila[]) : v
      if (sub.length > 0) limpia[k] = sub
    } else if (v !== null && v !== undefined) {
      limpia[k] = v
    }
  }
  // Un booleano o un número sueltos (p. ej. «obligatorio», «paso») no bastan para que la fila cuente
  // como llenada: hace falta algo de texto.
  const conDatos = Object.values(limpia).some((v) => typeof v === 'string')
  return conDatos ? limpia : null
}

function limpiarFilas(lista: Fila[]): Fila[] {
  return lista.map(limpiarFila).filter((f): f is Fila => f !== null)
}

const soloTextos = (lista: Fila[]): string[] =>
  lista.map((f) => texto(f.valor).trim()).filter((t) => t !== '')

const enNumero = (s: string): number | null => (s.trim() === '' ? null : Number(s))
const enTexto = (s: string): string | null => (s.trim() === '' ? null : s.trim())

type Entrada = Record<string, unknown>

/** El bloque de costo, ya en la forma de la API. */
function bloqueCosto(f: FormularioTramite): Entrada {
  return {
    ...(f.tiene_costo !== '' ? { tiene_costo: f.tiene_costo } : {}),
    tipo_valor: enTexto(f.costo_tipo_valor),
    valor: enNumero(f.costo_valor),
    moneda: enTexto(f.costo_moneda),
    url_pago: enTexto(f.costo_url_pago),
    descripcion: enTexto(f.costo_descripcion),
    cuentas: limpiarFilas(f.costo_cuentas),
  }
}

/** Los campos simples (no la ficha), ya en la forma de la API. */
function escalares(f: FormularioTramite): Entrada {
  const hayCategoria = f.categoria_slug.trim() !== '' || f.categoria_nombre.trim() !== ''
  return {
    nombre: f.nombre.trim(),
    type: f.type,
    resumen: enTexto(f.resumen),
    url_ficha_gov_co: enTexto(f.url_ficha_gov_co),
    modalidad: enTexto(f.modalidad),
    tiene_costo: enTexto(f.tiene_costo),
    tiempo_solucion_dias: enNumero(f.tiempo_solucion_dias),
    canal_inicio: enTexto(f.canal_inicio),
    url_inicio: enTexto(f.url_inicio),
    url_manual_tramite_en_linea: enTexto(f.url_manual_tramite_en_linea),
    consulta_estado: enTexto(f.consulta_estado),
    resultado: enTexto(f.resultado),
    observaciones_resultado: enTexto(f.observaciones_resultado),
    fecha_cualquiera: f.fecha_cualquiera === 'si' ? true : f.fecha_cualquiera === 'no' ? false : null,
    cuando_se_puede_realizar: enTexto(f.cuando_se_puede_realizar),
    url_calendario: enTexto(f.url_calendario),
    palabras_relacionadas: enTexto(f.palabras_relacionadas),
    dependencia_id: enNumero(f.dependencia_id),
    medios_resultado: soloTextos(f.medios_resultado),
    categoria: hayCategoria ? { slug: f.categoria_slug.trim(), nombre: f.categoria_nombre.trim() } : null,
  }
}

/** La ficha (listas + solicitantes), ya en la forma de la API. */
function ficha(f: FormularioTramite): Entrada {
  return {
    pasos: limpiarFilas(f.pasos),
    requisitos: limpiarFilas(f.requisitos),
    documentos: limpiarFilas(f.documentos),
    puntos_atencion: limpiarFilas(f.puntos_atencion),
    normativa: limpiarFilas(f.normativa),
    canales_consulta_estado: limpiarFilas(f.canales_consulta_estado),
    solicitantes: [...f.solicitantes],
  }
}

/** ¿El bloque de costo está sin datos? Al crear no se envía vacío. */
const costoVacio = (c: unknown): boolean =>
  typeof c === 'object' && c !== null && Object.values(c).every((v) => vacio(v))

/** ¿Alguna parte de la ficha o del costo cambió respecto del original? */
function fichaCambio(f: FormularioTramite, original: FormularioTramite): boolean {
  const a = ficha(f)
  const b = ficha(original)
  for (const k of [...FICHA_LISTAS, 'solicitantes']) {
    if (JSON.stringify(a[k]) !== JSON.stringify(b[k])) return true
  }
  return JSON.stringify(bloqueCosto(f)) !== JSON.stringify(bloqueCosto(original))
}

/**
 * Lo que se envía al servidor.
 *
 * - Al **crear** (`original` nulo): el nombre, el tipo y lo que se llenó.
 * - Al **editar**: los campos simples que cambiaron; y si **cualquier** parte de la ficha o del
 *   costo cambió, la ficha y el costo **enteros**, porque el servidor reemplaza la ficha completa.
 */
export function entradaDesde(f: FormularioTramite, original: FormularioTramite | null): TramiteInput {
  const salida: Entrada = {}

  if (original === null) {
    for (const [k, v] of Object.entries(escalares(f))) {
      if (k === 'nombre' || k === 'type') salida[k] = v
      else if (!vacio(v) && v !== null) salida[k] = v
    }
    const costo = bloqueCosto(f)
    if (!costoVacio(costo)) salida.costo = costo
    for (const [k, v] of Object.entries(ficha(f))) {
      if (!vacio(v)) salida[k] = v
    }
    if (f.publicado) salida.publicado = true
    return salida as TramiteInput
  }

  const nuevo = escalares(f)
  const viejo = escalares(original)
  for (const [k, v] of Object.entries(nuevo)) {
    if (JSON.stringify(v) !== JSON.stringify(viejo[k])) salida[k] = v
  }
  if (f.publicado !== original.publicado) salida.publicado = f.publicado
  if (fichaCambio(f, original)) {
    Object.assign(salida, ficha(f))
    salida.costo = bloqueCosto(f)
  }
  return salida as TramiteInput
}

export function hayCambios(f: FormularioTramite, original: FormularioTramite): boolean {
  return Object.keys(entradaDesde(f, original)).length > 0
}

// --- Explicaciones en español ---------------------------------------------------------------------

/**
 * Lo que falta para publicar; repite las reglas del servidor para avisar al instante. La ficha de
 * GOV.CO es obligatoria para trámites y OPA, no para consultas (Decreto 2106).
 */
export function faltaParaPublicar(f: FormularioTramite): string[] {
  const falta: string[] = []
  if (f.resumen.trim() === '') falta.push('Falta un resumen de qué es.')
  if (f.type !== 'consultas' && f.url_ficha_gov_co.trim() === '') {
    falta.push('Falta la dirección de la ficha en GOV.CO (obligatoria para trámites y OPA).')
  }
  if (f.modalidad === '') falta.push('Falta la modalidad (en línea, parcialmente en línea o presencial).')
  if (f.tiene_costo === '') falta.push('Falta decir si tiene costo o es gratuito.')
  if (f.tiempo_solucion_dias.trim() === '') falta.push('Falta el tiempo de solución, en días.')
  if (f.canal_inicio === '') falta.push('Falta el canal por el que se inicia.')
  if (f.consulta_estado.trim() === '') falta.push('Falta cómo se consulta el estado.')
  if (limpiarFilas(f.requisitos).length === 0) falta.push('Falta al menos un requisito.')
  return falta
}

const esWeb = (s: string): boolean => /^https?:\/\/\S+$/i.test(s.trim())

/** Errores que se pueden detectar sin preguntar al servidor, dichos como los diría quien edita. */
export function problemasLocales(f: FormularioTramite): string[] {
  const p: string[] = []
  const nombre = f.nombre.trim()
  if (nombre.length < NOMBRE_MIN) p.push(`El nombre necesita al menos ${NOMBRE_MIN} letras.`)
  if (nombre.length > NOMBRE_MAX) p.push(`El nombre no puede pasar de ${NOMBRE_MAX} letras.`)
  const direcciones: Array<[string, string]> = [
    ['la dirección de la ficha en GOV.CO', f.url_ficha_gov_co],
    ['la dirección donde se inicia', f.url_inicio],
    ['la dirección del manual en línea', f.url_manual_tramite_en_linea],
    ['la dirección del calendario', f.url_calendario],
    ['la dirección de pago', f.costo_url_pago],
  ]
  for (const [nombreCampo, valor] of direcciones) {
    if (valor.trim() !== '' && !esWeb(valor)) p.push(`Revise ${nombreCampo}: debe empezar por http:// o https://.`)
  }
  const tiempo = f.tiempo_solucion_dias.trim()
  if (tiempo !== '' && !/^\d+$/.test(tiempo)) p.push('El tiempo de solución debe ser un número entero de días, de cero en adelante.')
  if ((f.categoria_slug.trim() === '') !== (f.categoria_nombre.trim() === '')) {
    p.push('La categoría necesita el nombre y su identificador; llene los dos o deje los dos vacíos.')
  }
  return p
}

// --- Mensajes del servidor en palabras de quien edita -----------------------------------------------

const NOMBRE_LISTA: Record<string, string> = {
  requisitos: 'Requisito',
  pasos: 'Paso',
  documentos: 'Documento',
  solicitantes: 'Tipo de solicitante',
  medios_resultado: 'Medio de entrega',
  puntos_atencion: 'Lugar de atención',
  normativa: 'Norma',
  'costo.cuentas': 'Cuenta del costo',
  canales_consulta_estado: 'Canal de consulta',
}

const NOMBRE_CAMPO: Record<string, string> = {
  nombre: 'Nombre',
  titulo: 'Título',
  descripcion: 'Descripción',
  tipo: 'Tipo',
  url: 'Dirección web',
  url_pago: 'Dirección de pago',
  correo: 'Correo',
  numero: 'Número',
  anio: 'Año',
  direccion: 'Dirección',
  horario: 'Horario',
  telefono: 'Teléfono',
  latitud: 'Latitud',
  longitud: 'Longitud',
  orden: 'Número de orden',
  paso: 'Paso',
  canal: 'Canal',
  modalidad: 'Modalidad',
  tiene_costo: 'Costo',
  tiempo_solucion_dias: 'Tiempo de solución',
  url_ficha_gov_co: 'Dirección de la ficha en GOV.CO',
  url_inicio: 'Dirección de inicio',
  consulta_estado: 'Consulta del estado',
  canal_inicio: 'Dónde se inicia',
  dependencia_id: 'Dependencia',
  titular: 'Titular',
  banco: 'Banco',
  valor: 'Valor',
  moneda: 'Moneda',
  formato: 'Formato',
  municipio: 'Municipio',
  departamento: 'Departamento',
  articulos: 'Artículos',
}

/** `requisitos.0.descripcion` → «Requisito 1 · Descripción». */
export function rutaLegible(ruta: string): string {
  const partes = ruta.split('.')
  const etiquetas: string[] = []
  let acumulado = ''
  for (let i = 0; i < partes.length; i += 1) {
    const parte = partes[i]!
    if (/^\d+$/.test(parte)) continue
    const siguienteEsNumero = /^\d+$/.test(partes[i + 1] ?? '')
    const clave = acumulado === '' ? parte : `${acumulado}.${parte}`
    const lista = NOMBRE_LISTA[clave] ?? NOMBRE_LISTA[parte]
    if (siguienteEsNumero && lista) {
      etiquetas.push(`${lista} ${Number(partes[i + 1]) + 1}`)
      acumulado = ''
    } else if (i === partes.length - 1) {
      const campo = NOMBRE_CAMPO[parte] ?? parte.replaceAll('_', ' ')
      return etiquetas.length > 0 ? `${etiquetas.join(', ')} · ${campo}` : campo
    } else {
      acumulado = clave
    }
  }
  return etiquetas.join(', ')
}

/** Cambia el nombre técnico del campo dentro del mensaje por el que lee la persona. */
export function mensajeLegible(ruta: string, mensaje: string): string {
  const legible = `«${rutaLegible(ruta)}»`
  return mensaje.replaceAll(ruta, legible).replaceAll(ruta.replaceAll('_', ' '), legible)
}

/** Todos los motivos de un rechazo `errors: { ruta: [mensaje] }`, dichos en español sencillo. */
export function motivosLegibles(errores: Record<string, unknown>): string[] {
  const motivos: string[] = []
  for (const [ruta, mensajes] of Object.entries(errores)) {
    if (!Array.isArray(mensajes)) continue
    for (const m of mensajes) if (typeof m === 'string' && m !== '') motivos.push(ruta === 'tramite' ? m : mensajeLegible(ruta, m))
  }
  return motivos
}
