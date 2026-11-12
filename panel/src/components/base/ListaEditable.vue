<script setup lang="ts">
/**
 * Lista de filas que se agregan, se llenan, se ordenan y se quitan (requisitos, documentos, teléfonos…).
 *
 * Es un componente dirigido por una descripción de campos, para que todas las listas del panel se
 * vean y se manejen igual. Cada fila es un grupo con nombre («Requisito 2»), todos los controles
 * tienen etiqueta visible y los botones dicen a qué fila se refieren. Los datos de la fila que no
 * aparecen en los campos se conservan al editar (la fuente oficial trae más de lo que se muestra).
 */
import { computed, nextTick, ref, useId } from 'vue'

export interface OpcionCampo {
  valor: string
  etiqueta: string
}

export interface CampoLista {
  clave: string
  etiqueta: string
  tipo: 'texto' | 'textarea' | 'numero' | 'decimal' | 'url' | 'correo' | 'select' | 'casilla' | 'multi' | 'lista'
  opciones?: OpcionCampo[]
  ayuda?: string
  requerido?: boolean
  /** Se muestra dentro de «Más datos (opcional)». */
  avanzado?: boolean
  /** Solo para `tipo: 'lista'`. */
  campos?: CampoLista[]
  etiquetaItem?: string
  /** Solo para `tipo: 'lista'`: la fila nueva. */
  nuevoItem?: () => Record<string, unknown>
  /** Solo para `casilla`: valor cuando la fila no lo trae. */
  porDefecto?: boolean
  /** Ancho en la rejilla de 2 columnas. */
  ancho?: 'completo' | 'medio'
}

type Fila = Record<string, unknown>

const props = withDefaults(
  defineProps<{
    modelValue: Fila[]
    campos: CampoLista[]
    /** Nombre de una fila en singular: «Requisito». */
    etiquetaItem: string
    /** Ruta de los errores del servidor: `requisitos` → `requisitos.0.tipo`. */
    prefijoError?: string
    errores?: Record<string, string | undefined>
    /** Fila nueva. */
    nuevo?: () => Fila
    textoAgregar?: string
    textoVacio?: string
    max?: number
    /** Campo cuyo valor encabeza la fila: «Requisito 2 · Cédula». */
    campoTitulo?: string
    /** Nombre de la fila en plural y en minúsculas, para los avisos («requisitos»). */
    plural?: string
  }>(),
  {
    prefijoError: '',
    errores: () => ({}),
    nuevo: () => ({}),
    textoAgregar: undefined,
    textoVacio: undefined,
    max: 100,
    campoTitulo: undefined,
    plural: undefined,
  },
)

const emit = defineEmits<{ 'update:modelValue': [value: Fila[]] }>()

const uid = useId()
const raiz = ref<HTMLElement | null>(null)
const botonAgregar = ref<HTMLButtonElement | null>(null)
const aviso = ref('')

const agregar = computed(() => props.textoAgregar ?? `Agregar ${props.etiquetaItem.toLowerCase()}`)
const puedeAgregar = computed(() => props.modelValue.length < props.max)

function nombreFila(fila: Fila, i: number): string {
  const base = `${props.etiquetaItem} ${i + 1}`
  const extra = props.campoTitulo ? String(fila[props.campoTitulo] ?? '').trim() : ''
  return extra === '' ? base : `${base} · ${extra.length > 40 ? `${extra.slice(0, 40)}…` : extra}`
}

function emitir(lista: Fila[]): void {
  emit('update:modelValue', lista)
}

function cambiar(i: number, clave: string, valor: unknown): void {
  const lista = props.modelValue.map((f, j) => (j === i ? { ...f, [clave]: valor } : f))
  emitir(lista)
}

function cambiarNumero(i: number, clave: string, texto: string, decimal: boolean): void {
  const limpio = texto.trim()
  if (limpio === '') {
    const fila = { ...props.modelValue[i] }
    delete fila[clave]
    emitir(props.modelValue.map((f, j) => (j === i ? fila : f)))
    return
  }
  const n = Number(limpio)
  cambiar(i, clave, Number.isNaN(n) ? limpio : decimal ? n : Math.trunc(n))
}

function alternarMulti(i: number, clave: string, valor: string, marcado: boolean): void {
  const actual = Array.isArray(props.modelValue[i]?.[clave]) ? (props.modelValue[i]![clave] as string[]) : []
  cambiar(i, clave, marcado ? [...actual, valor] : actual.filter((v) => v !== valor))
}

async function agregarFila(): Promise<void> {
  if (!puedeAgregar.value) return
  emitir([...props.modelValue, { ...props.nuevo() }])
  aviso.value = `${props.etiquetaItem} ${props.modelValue.length + 1} agregado. Hay ${props.modelValue.length + 1}.`
  await nextTick()
  const grupos = raiz.value?.querySelectorAll<HTMLElement>(':scope > ul > li')
  const ultimo = grupos?.[grupos.length - 1]
  ultimo?.querySelector<HTMLElement>('input:not([type=checkbox]), select, textarea')?.focus()
}

async function quitar(i: number): Promise<void> {
  const nombre = nombreFila(props.modelValue[i]!, i)
  emitir(props.modelValue.filter((_, j) => j !== i))
  aviso.value = `${nombre} quitado.`
  await nextTick()
  botonAgregar.value?.focus()
}

async function mover(i: number, delta: -1 | 1): Promise<void> {
  const j = i + delta
  if (j < 0 || j >= props.modelValue.length) return
  const lista = [...props.modelValue]
  const [fila] = lista.splice(i, 1)
  lista.splice(j, 0, fila!)
  emitir(lista)
  aviso.value = `${props.etiquetaItem} movido a la posición ${j + 1} de ${lista.length}.`
  await nextTick()
  raiz.value
    ?.querySelectorAll<HTMLElement>(`:scope > ul > li`)[j]
    ?.querySelector<HTMLElement>(`[data-mover="${delta === -1 ? 'subir' : 'bajar'}"]:not([disabled])`)
    ?.focus()
}

const idCampo = (i: number, clave: string): string => `${uid}-${i}-${clave}`
const rutaError = (i: number, clave: string): string =>
  props.prefijoError === '' ? `${i}.${clave}` : `${props.prefijoError}.${i}.${clave}`
const errorDe = (i: number, clave: string): string | undefined => props.errores[rutaError(i, clave)]
const visibles = (avanzado: boolean): CampoLista[] => props.campos.filter((c) => Boolean(c.avanzado) === avanzado)
const hayAvanzados = computed(() => props.campos.some((c) => c.avanzado))
const texto = (v: unknown): string => (v === null || v === undefined ? '' : String(v))

const claseControl =
  'mt-1 w-full rounded-lg border bg-white px-3 text-sm text-slate-900 focus:outline-none focus:ring-2'
const claseBorde = (hayError: boolean): string =>
  hayError ? 'border-red-300 focus:border-red-500 focus:ring-red-200' : 'border-slate-300 focus:border-gov-blue focus:ring-gov-blue/30'
</script>

<template>
  <div ref="raiz" class="space-y-3">
    <p v-if="modelValue.length === 0" class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
      {{ textoVacio ?? `Aún no hay ${plural ?? etiquetaItem.toLowerCase() + 's'}.` }}
    </p>

    <ul v-else class="space-y-3">
      <li v-for="(fila, i) in modelValue" :key="i">
        <fieldset class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
          <legend class="px-1 text-sm font-semibold text-slate-800">{{ nombreFila(fila, i) }}</legend>

          <div class="grid gap-3 sm:grid-cols-2">
            <template v-for="campo in visibles(false)" :key="campo.clave">
              <div :class="campo.ancho === 'medio' ? '' : campo.tipo === 'textarea' || campo.tipo === 'multi' || campo.tipo === 'lista' ? 'sm:col-span-2' : ''">
                <!-- Casilla -->
                <label v-if="campo.tipo === 'casilla'" class="flex items-start gap-2 pt-6 text-sm text-slate-800">
                  <input
                    :id="idCampo(i, campo.clave)"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4"
                    :checked="fila[campo.clave] === undefined || fila[campo.clave] === null ? (campo.porDefecto ?? false) : fila[campo.clave] === true"
                    @change="cambiar(i, campo.clave, ($event.target as HTMLInputElement).checked)"
                  >
                  {{ campo.etiqueta }}
                </label>

                <!-- Varias opciones -->
                <fieldset v-else-if="campo.tipo === 'multi'">
                  <legend class="text-sm font-medium text-slate-700">{{ campo.etiqueta }}</legend>
                  <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1.5">
                    <label v-for="o in campo.opciones" :key="o.valor" class="flex items-center gap-2 text-sm text-slate-800">
                      <input
                        type="checkbox"
                        class="h-4 w-4"
                        :checked="Array.isArray(fila[campo.clave]) && (fila[campo.clave] as string[]).includes(o.valor)"
                        @change="alternarMulti(i, campo.clave, o.valor, ($event.target as HTMLInputElement).checked)"
                      >
                      {{ o.etiqueta }}
                    </label>
                  </div>
                  <p v-if="campo.ayuda" class="mt-1 text-xs text-slate-600">{{ campo.ayuda }}</p>
                </fieldset>

                <!-- Lista anidada -->
                <div v-else-if="campo.tipo === 'lista'">
                  <p class="text-sm font-medium text-slate-700">{{ campo.etiqueta }}</p>
                  <p v-if="campo.ayuda" class="text-xs text-slate-600">{{ campo.ayuda }}</p>
                  <div class="mt-2">
                    <ListaEditable
                      :model-value="Array.isArray(fila[campo.clave]) ? (fila[campo.clave] as Fila[]) : []"
                      :campos="campo.campos ?? []"
                      :etiqueta-item="campo.etiquetaItem ?? 'Elemento'"
                      :prefijo-error="rutaError(i, campo.clave)"
                      :errores="errores"
                      :nuevo="() => ({ ...(campo.nuevoItem?.() ?? {}) })"
                      campo-titulo="descripcion"
                      @update:model-value="cambiar(i, campo.clave, $event)"
                    />
                  </div>
                </div>

                <!-- Campo simple -->
                <template v-else>
                  <label :for="idCampo(i, campo.clave)" class="text-sm font-medium text-slate-700">
                    {{ campo.etiqueta }}<span v-if="campo.requerido" class="ml-0.5 text-red-600" aria-hidden="true">*</span>
                  </label>
                  <textarea
                    v-if="campo.tipo === 'textarea'"
                    :id="idCampo(i, campo.clave)"
                    rows="3"
                    :value="texto(fila[campo.clave])"
                    :aria-invalid="Boolean(errorDe(i, campo.clave)) || undefined"
                    :aria-describedby="errorDe(i, campo.clave) ? `${idCampo(i, campo.clave)}-error` : undefined"
                    :class="[claseControl, 'py-2', claseBorde(Boolean(errorDe(i, campo.clave)))]"
                    @input="cambiar(i, campo.clave, ($event.target as HTMLTextAreaElement).value)"
                  />
                  <select
                    v-else-if="campo.tipo === 'select'"
                    :id="idCampo(i, campo.clave)"
                    :value="texto(fila[campo.clave])"
                    :aria-invalid="Boolean(errorDe(i, campo.clave)) || undefined"
                    :aria-describedby="errorDe(i, campo.clave) ? `${idCampo(i, campo.clave)}-error` : undefined"
                    :class="[claseControl, 'h-10', claseBorde(Boolean(errorDe(i, campo.clave)))]"
                    @change="cambiar(i, campo.clave, ($event.target as HTMLSelectElement).value)"
                  >
                    <option value="">Seleccione…</option>
                    <option v-for="o in campo.opciones" :key="o.valor" :value="o.valor">{{ o.etiqueta }}</option>
                  </select>
                  <input
                    v-else
                    :id="idCampo(i, campo.clave)"
                    :type="campo.tipo === 'numero' || campo.tipo === 'decimal' ? 'number' : campo.tipo === 'correo' ? 'email' : campo.tipo === 'url' ? 'url' : 'text'"
                    :inputmode="campo.tipo === 'numero' ? 'numeric' : campo.tipo === 'decimal' ? 'decimal' : undefined"
                    :step="campo.tipo === 'decimal' ? 'any' : campo.tipo === 'numero' ? '1' : undefined"
                    :value="texto(fila[campo.clave])"
                    :aria-invalid="Boolean(errorDe(i, campo.clave)) || undefined"
                    :aria-describedby="errorDe(i, campo.clave) ? `${idCampo(i, campo.clave)}-error` : undefined"
                    :class="[claseControl, 'h-10', claseBorde(Boolean(errorDe(i, campo.clave)))]"
                    @input="
                      campo.tipo === 'numero' || campo.tipo === 'decimal'
                        ? cambiarNumero(i, campo.clave, ($event.target as HTMLInputElement).value, campo.tipo === 'decimal')
                        : cambiar(i, campo.clave, ($event.target as HTMLInputElement).value)
                    "
                  >
                  <p v-if="campo.ayuda" class="mt-1 text-xs text-slate-600">{{ campo.ayuda }}</p>
                  <p v-if="errorDe(i, campo.clave)" :id="`${idCampo(i, campo.clave)}-error`" class="mt-1 text-xs font-medium text-red-700">
                    {{ errorDe(i, campo.clave) }}
                  </p>
                </template>
              </div>
            </template>
          </div>

          <details v-if="hayAvanzados" class="mt-3">
            <summary class="cursor-pointer text-sm font-medium text-gov-blue">Más datos (opcional)</summary>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <template v-for="campo in visibles(true)" :key="campo.clave">
                <div :class="campo.tipo === 'textarea' || campo.tipo === 'multi' || campo.tipo === 'lista' ? 'sm:col-span-2' : ''">
                  <fieldset v-if="campo.tipo === 'multi'">
                    <legend class="text-sm font-medium text-slate-700">{{ campo.etiqueta }}</legend>
                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1.5">
                      <label v-for="o in campo.opciones" :key="o.valor" class="flex items-center gap-2 text-sm text-slate-800">
                        <input
                          type="checkbox"
                          class="h-4 w-4"
                          :checked="Array.isArray(fila[campo.clave]) && (fila[campo.clave] as string[]).includes(o.valor)"
                          @change="alternarMulti(i, campo.clave, o.valor, ($event.target as HTMLInputElement).checked)"
                        >
                        {{ o.etiqueta }}
                      </label>
                    </div>
                    <p v-if="campo.ayuda" class="mt-1 text-xs text-slate-600">{{ campo.ayuda }}</p>
                  </fieldset>
                  <template v-else>
                    <label :for="idCampo(i, campo.clave)" class="text-sm font-medium text-slate-700">{{ campo.etiqueta }}</label>
                    <select
                      v-if="campo.tipo === 'select'"
                      :id="idCampo(i, campo.clave)"
                      :value="texto(fila[campo.clave])"
                      :class="[claseControl, 'h-10', claseBorde(Boolean(errorDe(i, campo.clave)))]"
                      @change="cambiar(i, campo.clave, ($event.target as HTMLSelectElement).value)"
                    >
                      <option value="">Seleccione…</option>
                      <option v-for="o in campo.opciones" :key="o.valor" :value="o.valor">{{ o.etiqueta }}</option>
                    </select>
                    <textarea
                      v-else-if="campo.tipo === 'textarea'"
                      :id="idCampo(i, campo.clave)"
                      rows="2"
                      :value="texto(fila[campo.clave])"
                      :class="[claseControl, 'py-2', claseBorde(Boolean(errorDe(i, campo.clave)))]"
                      @input="cambiar(i, campo.clave, ($event.target as HTMLTextAreaElement).value)"
                    />
                    <input
                      v-else
                      :id="idCampo(i, campo.clave)"
                      :type="campo.tipo === 'numero' || campo.tipo === 'decimal' ? 'number' : campo.tipo === 'correo' ? 'email' : campo.tipo === 'url' ? 'url' : 'text'"
                      :step="campo.tipo === 'decimal' ? 'any' : campo.tipo === 'numero' ? '1' : undefined"
                      :value="texto(fila[campo.clave])"
                      :aria-invalid="Boolean(errorDe(i, campo.clave)) || undefined"
                      :aria-describedby="errorDe(i, campo.clave) ? `${idCampo(i, campo.clave)}-error` : undefined"
                      :class="[claseControl, 'h-10', claseBorde(Boolean(errorDe(i, campo.clave)))]"
                      @input="
                        campo.tipo === 'numero' || campo.tipo === 'decimal'
                          ? cambiarNumero(i, campo.clave, ($event.target as HTMLInputElement).value, campo.tipo === 'decimal')
                          : cambiar(i, campo.clave, ($event.target as HTMLInputElement).value)
                      "
                    >
                    <p v-if="campo.ayuda" class="mt-1 text-xs text-slate-600">{{ campo.ayuda }}</p>
                    <p v-if="errorDe(i, campo.clave)" :id="`${idCampo(i, campo.clave)}-error`" class="mt-1 text-xs font-medium text-red-700">
                      {{ errorDe(i, campo.clave) }}
                    </p>
                  </template>
                </div>
              </template>
            </div>
          </details>

          <div class="mt-3 flex flex-wrap items-center gap-2">
            <button
              type="button"
              data-mover="subir"
              :disabled="i === 0"
              :aria-label="`Subir ${nombreFila(fila, i)}`"
              class="inline-flex h-9 items-center rounded-lg bg-white px-3 text-sm font-medium text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue disabled:cursor-not-allowed disabled:opacity-40"
              @click="mover(i, -1)"
            >Subir</button>
            <button
              type="button"
              data-mover="bajar"
              :disabled="i === modelValue.length - 1"
              :aria-label="`Bajar ${nombreFila(fila, i)}`"
              class="inline-flex h-9 items-center rounded-lg bg-white px-3 text-sm font-medium text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue disabled:cursor-not-allowed disabled:opacity-40"
              @click="mover(i, 1)"
            >Bajar</button>
            <button
              type="button"
              data-quitar
              :aria-label="`Quitar ${nombreFila(fila, i)}`"
              class="inline-flex h-9 items-center rounded-lg bg-white px-3 text-sm font-medium text-red-700 ring-1 ring-slate-300 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600"
              @click="quitar(i)"
            >Quitar</button>
          </div>
        </fieldset>
      </li>
    </ul>

    <button
      v-if="puedeAgregar"
      ref="botonAgregar"
      type="button"
      data-agregar
      class="inline-flex h-10 items-center gap-2 rounded-lg bg-white px-4 text-sm font-medium text-gov-blue ring-1 ring-gov-blue hover:bg-gov-blue-light focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue focus-visible:ring-offset-2"
      @click="agregarFila"
    >
      <FaIcon icon="plus" class="h-4 w-4" aria-hidden="true" />
      {{ agregar }}
    </button>
    <p v-else class="text-sm text-slate-600">Se llegó al máximo de {{ max }}.</p>

    <p class="sr-only" role="status" aria-live="polite">{{ aviso }}</p>
  </div>
</template>
