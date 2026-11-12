/**
 * Lógica pura de la pantalla de trámites: pasar entre lo que entrega la API y lo que edita el
 * formulario, decidir qué se envía y explicar en español lo que falta para publicar.
 *
 * Principio: **solo viaja lo que cambió**. Un trámite del catálogo oficial trae datos que esta
 * pantalla no edita (pagos y cuentas por requisito, canales…); si se reenviaran todos los campos
 * cualquier descuido los borraría. Por eso cada fila de una lista conserva sus claves ocultas y una
 * lista que no se tocó no se envía.
 */
import type { TramiteInput, TramitePanelItem } from '@/types/api'

/** Una fila de una lista editable: los campos que se ven y los que la pantalla no toca. */
export type Fila = Record<string, unknown>

export interface FormularioTramite {
  nombre: string
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
  producto_final: string
  observaciones_resultado: string
  /** `''` = sin dato, `si`, `no`. */
  fecha_cualquiera: string
  cuando_se_puede_realizar: string
  url_calendario: string
  palabras_relacionadas: string
  costo_tipo_valor: string
  costo_valor: string
  costo_moneda: string
  costo_url_pago: string
  costo_descripcion: string
  costo_cuentas: Fila[]
  categoria_slug: string
  categoria_nombre: string
  requisitos: Fila[]
  documentos: Fila[]
  momentos: Fila[]
  audiencias: Fila[]
  perfiles: Fila[]
  medios_resultado: Fila[]
  puntos_atencion: Fila[]
  normativa: Fila[]
  cuentas: Fila[]
  canales_consulta_estado: Fila[]
  seg_telefono: Fila[]
  seg_email: Fila[]
  seg_web: Fila[]
  seg_presencial: Fila[]
  publicado: boolean
}

export const NOMBRE_MIN = 3
export const NOMBRE_MAX = 200

const texto = (v: unknown): string => (v === null || v === undefined ? '' : String(v))
const copia = <T>(v: T): T => JSON.parse(JSON.stringify(v ?? null)) as T
const filas = (v: unknown): Fila[] => (Array.isArray(v) ? (copia(v) as Fila[]) : [])
const filasDeTexto = (v: unknown): Fila[] => (Array.isArray(v) ? v.map((x) => ({ valor: texto(x) })) : [])

export function formularioVacio(): FormularioTramite {
  return {
    nombre: '', resumen: '', url_ficha_gov_co: '', modalidad: '', tiene_costo: '', tiempo_solucion_dias: '', canal_inicio: '',
    url_inicio: '', url_manual_tramite_en_linea: '', consulta_estado: '', resultado: '', producto_final: '', observaciones_resultado: '',
    fecha_cualquiera: '', cuando_se_puede_realizar: '', url_calendario: '', palabras_relacionadas: '',
    costo_tipo_valor: '', costo_valor: '', costo_moneda: '', costo_url_pago: '', costo_descripcion: '', costo_cuentas: [],
    categoria_slug: '', categoria_nombre: '',
    requisitos: [], documentos: [], momentos: [], audiencias: [], perfiles: [], medios_resultado: [], puntos_atencion: [],
    normativa: [], cuentas: [], canales_consulta_estado: [], seg_telefono: [], seg_email: [], seg_web: [], seg_presencial: [],
    publicado: false,
  }
}

export function formularioDesde(t: TramitePanelItem): FormularioTramite {
  const costo = (t.costo ?? null) as Record<string, unknown> | null
  const seg = (t.seguimiento ?? null) as Record<string, unknown> | null
  const fecha = t.fecha_cualquiera
  return {
    nombre: texto(t.nombre),
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
    producto_final: texto(t.producto_final),
    observaciones_resultado: texto(t.observaciones_resultado),
    fecha_cualquiera: fecha === true ? 'si' : fecha === false ? 'no' : '',
    cuando_se_puede_realizar: texto(t.cuando_se_puede_realizar),
    url_calendario: texto(t.url_calendario),
    palabras_relacionadas: texto(t.palabras_relacionadas),
    costo_tipo_valor: texto(costo?.tipo_valor),
    costo_valor: texto(costo?.valor),
    costo_moneda: texto(costo?.moneda),
    costo_url_pago: texto(costo?.url_pago),
    costo_descripcion: texto(costo?.descripcion),
    costo_cuentas: filas(costo?.cuentas),
    categoria_slug: texto(t.categoria?.slug),
    categoria_nombre: texto(t.categoria?.nombre),
    requisitos: filas(t.requisitos),
    documentos: filas(t.documentos),
    momentos: filas(t.momentos),
    audiencias: filas(t.audiencias),
    perfiles: filasDeTexto(t.perfiles),
    medios_resultado: filasDeTexto(t.medios_resultado),
    puntos_atencion: filas(t.puntos_atencion),
    normativa: filas(t.normativa),
    cuentas: filas(t.cuentas),
    canales_consulta_estado: filas(t.canales_consulta_estado),
    seg_telefono: filas(seg?.telefono),
    seg_email: filas(seg?.email),
    seg_web: filas(seg?.web),
    seg_presencial: filas(seg?.presencial),
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
  // Un booleano suelto (p. ej. «obligatorio») no basta para que la fila cuente como llenada.
  const conDatos = Object.values(limpia).some((v) => typeof v !== 'boolean')
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

/** Todo lo que el formulario puede enviar, ya convertido a la forma de la API. */
function entradaCompleta(f: FormularioTramite): Entrada {
  const bloqueCosto = {
    ...(f.tiene_costo !== '' ? { tiene_costo: f.tiene_costo } : {}),
    tipo_valor: enTexto(f.costo_tipo_valor),
    valor: enTexto(f.costo_valor),
    moneda: enTexto(f.costo_moneda),
    url_pago: enTexto(f.costo_url_pago),
    descripcion: enTexto(f.costo_descripcion),
    cuentas: limpiarFilas(f.costo_cuentas),
  }
  const hayCategoria = f.categoria_slug.trim() !== '' || f.categoria_nombre.trim() !== ''
  return {
    nombre: f.nombre.trim(),
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
    producto_final: enTexto(f.producto_final),
    observaciones_resultado: enTexto(f.observaciones_resultado),
    fecha_cualquiera: f.fecha_cualquiera === 'si' ? true : f.fecha_cualquiera === 'no' ? false : null,
    cuando_se_puede_realizar: enTexto(f.cuando_se_puede_realizar),
    url_calendario: enTexto(f.url_calendario),
    palabras_relacionadas: enTexto(f.palabras_relacionadas),
    costo: bloqueCosto,
    categoria: hayCategoria ? { slug: f.categoria_slug.trim(), nombre: f.categoria_nombre.trim() } : null,
    requisitos: limpiarFilas(f.requisitos),
    documentos: limpiarFilas(f.documentos),
    momentos: limpiarFilas(f.momentos),
    audiencias: limpiarFilas(f.audiencias),
    perfiles: soloTextos(f.perfiles),
    medios_resultado: soloTextos(f.medios_resultado),
    puntos_atencion: limpiarFilas(f.puntos_atencion),
    normativa: limpiarFilas(f.normativa),
    cuentas: limpiarFilas(f.cuentas),
    canales_consulta_estado: limpiarFilas(f.canales_consulta_estado),
    seguimiento: {
      telefono: limpiarFilas(f.seg_telefono),
      email: limpiarFilas(f.seg_email),
      web: limpiarFilas(f.seg_web),
      presencial: limpiarFilas(f.seg_presencial),
    },
    publicado: f.publicado,
  }
}

/** ¿El bloque de costo está sin datos? Al crear no se envía vacío. */
const costoVacio = (c: unknown): boolean =>
  typeof c === 'object' && c !== null && Object.values(c).every((v) => vacio(v))
const seguimientoVacio = (s: unknown): boolean =>
  typeof s === 'object' && s !== null && Object.values(s).every((v) => vacio(v))

/**
 * Lo que se envía al servidor.
 *
 * - Al **editar** (`original` dado): solo los campos cuyo valor cambió. Un campo que se vació viaja
 *   como `null` y una lista que se vació, como `[]`.
 * - Al **crear** (`original` nulo): el nombre y lo que se llenó.
 */
export function entradaDesde(f: FormularioTramite, original: FormularioTramite | null): TramiteInput {
  const nuevo = entradaCompleta(f)
  const salida: Entrada = {}
  if (original === null) {
    for (const [k, v] of Object.entries(nuevo)) {
      if (k === 'nombre') salida[k] = v
      else if (k === 'costo') {
        if (!costoVacio(v)) salida[k] = v
      } else if (k === 'seguimiento') {
        if (!seguimientoVacio(v)) salida[k] = v
      } else if (k === 'publicado') {
        if (v === true) salida[k] = v
      } else if (!vacio(v) && v !== null) salida[k] = v
    }
    return salida as TramiteInput
  }
  const viejo = entradaCompleta(original)
  for (const [k, v] of Object.entries(nuevo)) {
    if (JSON.stringify(v) !== JSON.stringify(viejo[k])) salida[k] = v
  }
  return salida as TramiteInput
}

export function hayCambios(f: FormularioTramite, original: FormularioTramite): boolean {
  return Object.keys(entradaDesde(f, original)).length > 0
}

// --- Explicaciones en español ---------------------------------------------------------------------

/** Lo que falta para publicar; repite las reglas del servidor para avisar al instante. */
export function faltaParaPublicar(f: FormularioTramite): string[] {
  const falta: string[] = []
  if (f.url_ficha_gov_co.trim() === '') falta.push('Falta la dirección de la ficha en GOV.CO.')
  if (f.modalidad === '') falta.push('Falta la modalidad (en línea, parcialmente en línea o presencial).')
  if (f.tiene_costo === '') falta.push('Falta decir si el trámite tiene costo o es gratuito.')
  if (f.tiempo_solucion_dias.trim() === '') falta.push('Falta el tiempo de solución, en días.')
  if (f.canal_inicio === '') falta.push('Falta el canal por el que se inicia el trámite.')
  if (f.consulta_estado.trim() === '') falta.push('Falta cómo se consulta el estado del trámite.')
  if (limpiarFilas(f.requisitos).length === 0) falta.push('Falta al menos un requisito.')
  return falta
}

const esWeb = (s: string): boolean => /^https?:\/\/\S+$/i.test(s.trim())

/** Errores que se pueden detectar sin preguntar al servidor, dichos como los diría quien edita. */
export function problemasLocales(f: FormularioTramite): string[] {
  const p: string[] = []
  const nombre = f.nombre.trim()
  if (nombre.length < NOMBRE_MIN) p.push(`El nombre del trámite necesita al menos ${NOMBRE_MIN} letras.`)
  if (nombre.length > NOMBRE_MAX) p.push(`El nombre del trámite no puede pasar de ${NOMBRE_MAX} letras.`)
  const direcciones: Array<[string, string]> = [
    ['la dirección de la ficha en GOV.CO', f.url_ficha_gov_co],
    ['la dirección donde se inicia el trámite', f.url_inicio],
    ['la dirección del manual del trámite en línea', f.url_manual_tramite_en_linea],
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
  momentos: 'Paso',
  documentos: 'Documento',
  audiencias: 'Audiencia',
  perfiles: 'Perfil',
  medios_resultado: 'Medio de entrega',
  puntos_atencion: 'Lugar de atención',
  normativa: 'Norma',
  cuentas: 'Cuenta de recaudo',
  canales_consulta_estado: 'Canal de consulta',
  'costo.cuentas': 'Cuenta del costo',
  'seguimiento.telefono': 'Teléfono de seguimiento',
  'seguimiento.email': 'Correo de seguimiento',
  'seguimiento.web': 'Página de seguimiento',
  'seguimiento.presencial': 'Lugar de seguimiento',
  pago: 'Pago',
  canales: 'Canal',
}

const NOMBRE_CAMPO: Record<string, string> = {
  nombre: 'Nombre',
  titulo: 'Título',
  descripcion: 'Descripción',
  tipo: 'Tipo',
  url: 'Dirección web',
  url_pago: 'Dirección de pago',
  url_descarga: 'Dirección de descarga',
  formulario_url: 'Dirección del formulario',
  email: 'Correo',
  correo: 'Correo',
  numero: 'Número',
  anio: 'Año',
  direccion: 'Dirección',
  horario: 'Horario',
  telefono: 'Teléfono',
  latitud: 'Latitud',
  longitud: 'Longitud',
  orden: 'Número de orden',
  canal: 'Canal',
  cantidad: 'Cantidad',
  modalidad: 'Modalidad',
  tiene_costo: 'Costo',
  tiempo_solucion_dias: 'Tiempo de solución',
  url_ficha_gov_co: 'Dirección de la ficha en GOV.CO',
  url_inicio: 'Dirección de inicio',
  consulta_estado: 'Consulta del estado',
  canal_inicio: 'Dónde se inicia',
  titular: 'Titular',
  banco: 'Banco',
  entidad: 'Entidad',
  tipo_cuenta: 'Tipo de cuenta',
  numero_cuenta: 'Número de cuenta',
  valor: 'Valor',
  moneda: 'Moneda',
  formato: 'Formato',
  grupo: 'Grupo',
  municipio: 'Municipio',
  departamento: 'Departamento',
  articulos: 'Artículos',
  documento: 'Nombre del documento',
  formulario: 'Nombre del formulario',
}

/** `momentos.1.requisitos.0.tipo` → «Paso 2, Requisito 1 · Tipo». */
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
