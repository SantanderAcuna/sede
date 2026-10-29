/**
 * Lógica pura de la pantalla de políticas: pasar entre lo que entrega la API y lo
 * que edita el formulario, y comprobar lo que se puede comprobar antes de enviar.
 *
 * Está fuera del componente para poder probarla sin montar nada. Que la política
 * quede **completa** lo decide el servidor (`Politica::problemas()`); aquí sólo se
 * evita enviar lo que el servidor rechazaría con un mensaje poco claro, como un
 * apartado sin párrafos.
 */
import axios from 'axios'
import type { PoliticaInput, PoliticaPanelItem } from '@/types/api'

export interface SeccionEditable {
  id: string
  titulo: string
  /** Un párrafo por bloque, separados por una línea en blanco. */
  texto: string
  /** Los apartados mínimos no se pueden quitar: la política quedaría incompleta. */
  obligatoria: boolean
}

export interface FormularioPolitica {
  nombre: string
  version: number
  vigente_desde: string
  acto_tipo: string
  acto_numero: string
  acto_fecha: string
  es_ejemplo: boolean
  secciones: SeccionEditable[]
}

/** «Uno\n\nDos» → ['Uno', 'Dos']. Descarta los bloques vacíos. */
export function parrafosDesdeTexto(texto: string): string[] {
  return texto
    .replace(/\r\n?/g, '\n')
    .split(/\n{2,}/)
    .map((bloque) => bloque.trim())
    .filter((bloque) => bloque !== '')
}

export function textoDesdeParrafos(parrafos: readonly string[]): string {
  return parrafos.join('\n\n')
}

export function formularioDesde(politica: PoliticaPanelItem): FormularioPolitica {
  return {
    nombre: politica.nombre,
    version: politica.version,
    vigente_desde: politica.vigente_desde ?? '',
    acto_tipo: politica.acto?.tipo ?? '',
    acto_numero: politica.acto?.numero ?? '',
    acto_fecha: politica.acto?.fecha ?? '',
    es_ejemplo: politica.es_ejemplo,
    secciones: politica.secciones.map((seccion) => ({
      id: seccion.id,
      titulo: seccion.titulo,
      texto: textoDesdeParrafos(seccion.parrafos),
      obligatoria: Object.prototype.hasOwnProperty.call(politica.componentes, seccion.id),
    })),
  }
}

/** Un campo de texto vacío es «sin dato» (`null`), no una cadena vacía. */
const nuloSiVacio = (valor: string): string | null => (valor.trim() === '' ? null : valor.trim())

/**
 * Lo que se envía a `PATCH /panel/politicas/{slug}`.
 *
 * Si el texto no cambió, **no se envía `secciones`**; así el servidor no lo toma por
 * una edición y no sube la versión. Si cambió, se envía y la versión sube sola salvo
 * que se haya cambiado también el número a mano.
 */
export function entradaDesde(formulario: FormularioPolitica, original: FormularioPolitica): PoliticaInput {
  const entrada: PoliticaInput = {
    nombre: formulario.nombre.trim(),
    version: formulario.version,
    vigente_desde: nuloSiVacio(formulario.vigente_desde),
    acto_tipo: nuloSiVacio(formulario.acto_tipo),
    acto_numero: nuloSiVacio(formulario.acto_numero),
    acto_fecha: nuloSiVacio(formulario.acto_fecha),
    es_ejemplo: formulario.es_ejemplo,
  }

  if (JSON.stringify(formulario.secciones) !== JSON.stringify(original.secciones)) {
    entrada.secciones = formulario.secciones.map((seccion) => ({
      id: seccion.id,
      titulo: seccion.titulo.trim(),
      parrafos: parrafosDesdeTexto(seccion.texto),
    }))
    // Texto distinto = versión distinta: si la persona no tocó el número, lo sube el servidor.
    if (formulario.version === original.version) delete entrada.version
  }

  return entrada
}

export function hayCambios(formulario: FormularioPolitica, original: FormularioPolitica): boolean {
  return JSON.stringify(formulario) !== JSON.stringify(original)
}

/** Lo que se puede decir antes de enviar. Lista vacía = se puede intentar guardar. */
export function problemasLocales(formulario: FormularioPolitica): string[] {
  const problemas: string[] = []

  if (formulario.nombre.trim() === '') problemas.push('El nombre de la política no puede estar vacío.')
  if (!Number.isInteger(formulario.version) || formulario.version < 1) {
    problemas.push('La versión debe ser un número entero desde 1.')
  }

  for (const seccion of formulario.secciones) {
    const nombre = seccion.titulo.trim() === '' ? seccion.id : seccion.titulo.trim()
    if (seccion.titulo.trim() === '') problemas.push(`El apartado «${nombre}» necesita un título.`)
    if (parrafosDesdeTexto(seccion.texto).length === 0) problemas.push(`El apartado «${nombre}» no tiene texto.`)
  }

  return problemas
}

/** Un identificador de apartado a partir de su título: `Garantías y límites` → `garantias-y-limites`. */
export function idDeApartado(titulo: string, existentes: readonly string[]): string {
  const base =
    titulo
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 70) || 'apartado'

  let candidato = base
  for (let n = 2; existentes.includes(candidato); n += 1) candidato = `${base}-${n}`
  return candidato
}

/**
 * Los motivos que explican un rechazo de la API: los de `errors` (los apartados
 * que faltan, un campo inválido…) o, si no los hay, el mensaje.
 */
export function motivosDeError(error: unknown, porDefecto: string): string[] {
  if (axios.isAxiosError(error) && error.response) {
    const cuerpo = error.response.data as { message?: unknown; errors?: unknown } | undefined
    const errores = cuerpo?.errors
    if (errores !== null && typeof errores === 'object') {
      const motivos = Object.values(errores as Record<string, unknown>)
        .flat()
        .filter((m): m is string => typeof m === 'string' && m !== '')
      if (motivos.length > 0) return motivos
    }
    if (typeof cuerpo?.message === 'string' && cuerpo.message !== '') return [cuerpo.message]
  }
  return [porDefecto]
}
