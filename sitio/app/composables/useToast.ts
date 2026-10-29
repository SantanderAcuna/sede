/**
 * Avisos emergentes (toast) del Kit UI de GOV.CO (RF-B3-064).
 *
 * Un toast confirma o avisa de algo **que acaba de ocurrir** sin quitarle el
 * sitio a la página: «guardamos su elección», «no se pudo enviar». Este
 * composable guarda la lista y `ContenedorToastsGovco` la dibuja; quien quiere
 * avisar sólo llama a `mostrar`.
 *
 * Tres tipos, los del Kit: informativa, positiva y negativa. Dos decisiones que
 * no son obvias:
 *
 *  - **El error no se cierra solo.** Un aviso de fallo que desaparece a los ocho
 *    segundos lo pierde quien tardó en mirar la pantalla o iba con lector de
 *    pantalla; se queda hasta que la persona lo cierra (`duracionMs: 0`). Las
 *    informativas y positivas sí caducan, y el componente pausa el reloj mientras
 *    el cursor o el foco están encima (WCAG 2.2.1).
 *  - **Un máximo de `MAXIMO_VISIBLES`.** Si se acumulan, el más antiguo sale: una
 *    pila de avisos tapa la página y nadie lee el tercero.
 *
 * **Sólo en el cliente.** El estado es del módulo, y un módulo se comparte entre
 * peticiones en el servidor; por eso `mostrar` no hace nada durante el SSR. Un
 * toast es la respuesta a un gesto de la persona, y los gestos no existen en el
 * servidor, así que no se pierde nada.
 */
import { readonly, ref } from 'vue'

export type TipoToast = 'informativa' | 'positiva' | 'negativa'

export interface Toast {
  id: number
  tipo: TipoToast
  titulo: string
  mensaje: string
  /** Milisegundos hasta cerrarse solo; 0 = se queda hasta que se cierre. */
  duracionMs: number
}

export interface OpcionesToast {
  mensaje: string
  tipo?: TipoToast
  titulo?: string
  duracionMs?: number
}

export const DURACION_POR_DEFECTO_MS = 8000
export const MAXIMO_VISIBLES = 3

const TITULO_POR_TIPO: Record<TipoToast, string> = {
  informativa: 'Información',
  positiva: 'Listo',
  negativa: 'No se pudo completar',
}

const avisos = ref<Toast[]>([])
let contador = 0

function mostrar(opciones: OpcionesToast): number {
  if (!import.meta.client) return -1

  const tipo = opciones.tipo ?? 'informativa'
  contador += 1
  const aviso: Toast = {
    id: contador,
    tipo,
    titulo: opciones.titulo ?? TITULO_POR_TIPO[tipo],
    mensaje: opciones.mensaje,
    duracionMs: opciones.duracionMs ?? (tipo === 'negativa' ? 0 : DURACION_POR_DEFECTO_MS),
  }
  avisos.value = [...avisos.value, aviso].slice(-MAXIMO_VISIBLES)
  return aviso.id
}

function cerrar(id: number): void {
  avisos.value = avisos.value.filter((aviso) => aviso.id !== id)
}

function limpiar(): void {
  avisos.value = []
}

export function useToast() {
  return { avisos: readonly(avisos), mostrar, cerrar, limpiar }
}
