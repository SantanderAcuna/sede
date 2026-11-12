<script setup lang="ts">
/**
 * Editor de texto enriquecido, mínimo a propósito (Tiptap sobre ProseMirror, licencia MIT).
 *
 * Solo conoce lo que el sitio sabe mostrar: párrafos, títulos de nivel 2 y 3, negrita,
 * cursiva, listas, cita, enlaces (https, http, mailto, tel) e imágenes propias. Todo lo
 * demás —scripts, estilos, tablas, iframes, imágenes de otros sitios— no existe en su
 * esquema, así que se descarta aunque se pegue. Es la **primera** capa de defensa: el
 * servidor sanea siempre al guardar (control principal) y el sitio al mostrar.
 *
 * Los botones llevan texto visible (no solo iconos): quien no conoce el sistema no tiene
 * que adivinar qué hace cada uno. El enlace y la imagen se piden en paneles de la propia
 * página, no en `prompt()` ni en ventanas que atrapan el foco.
 */
import { computed, onBeforeUnmount, ref, useId, watch } from 'vue'
import { Editor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'

import SubirImagen from './SubirImagen.vue'
import { subirImagenContenido } from '@/services/contenido'

interface Props {
  modelValue: string | null | undefined
  label?: string
  hint?: string
  error?: string
  required?: boolean
  disabled?: boolean
}
const props = withDefaults(defineProps<Props>(), { label: undefined, hint: undefined, error: undefined })
const emit = defineEmits<{ 'update:modelValue': [valor: string] }>()

const id = useId()
const etiquetaId = `${id}-etiqueta`
const ayudaId = `${id}-ayuda`
const errorId = `${id}-error`
const describedBy = computed(() => [props.hint ? ayudaId : '', props.error ? errorId : ''].filter(Boolean).join(' ') || undefined)

const URL_PERMITIDA = /^(https?:\/\/|mailto:|tel:)/i
/** Solo las imágenes que sube este mismo panel. */
const RUTA_IMAGEN = '/api/v1/contenido/imagenes/'

const ImagenPropia = Image.extend({
  parseHTML() {
    return [{ tag: `img[src^="${RUTA_IMAGEN}"]` }]
  },
}).configure({ inline: false, allowBase64: false })

const CLASES_AREA = 'min-h-48 max-h-[32rem] overflow-y-auto px-4 py-3 text-sm text-slate-900 focus:outline-none [&_h2]:mt-4 [&_h2]:text-xl [&_h2]:font-semibold [&_h3]:mt-3 [&_h3]:text-lg [&_h3]:font-semibold [&_p]:my-2 [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_blockquote]:my-2 [&_blockquote]:border-l-4 [&_blockquote]:border-slate-300 [&_blockquote]:pl-4 [&_blockquote]:italic [&_a]:text-gov-blue [&_a]:underline [&_img]:my-2 [&_img]:max-h-72 [&_img]:max-w-full [&_img]:rounded'

/** Atributos del área editable: nombre, descripción y estado de error para lectores de pantalla. */
function atributos(): Record<string, string> {
  const a: Record<string, string> = { role: 'textbox', 'aria-multiline': 'true', 'aria-labelledby': etiquetaId, class: CLASES_AREA }
  if (describedBy.value) a['aria-describedby'] = describedBy.value
  if (props.error) a['aria-invalid'] = 'true'
  if (props.required) a['aria-required'] = 'true'
  return a
}

const editor = new Editor({
  content: props.modelValue ?? '',
  editable: !props.disabled,
  extensions: [
    StarterKit.configure({
      heading: { levels: [2, 3] },
      strike: false,
      code: false,
      codeBlock: false,
      horizontalRule: false,
      underline: false,
      trailingNode: false,
      link: {
        openOnClick: false,
        autolink: false,
        linkOnPaste: false,
        isAllowedUri: (url: string) => URL_PERMITIDA.test(url.trim()),
        HTMLAttributes: { target: null, rel: 'noopener noreferrer nofollow' },
      },
    }),
    ImagenPropia,
  ],
  editorProps: { attributes: atributos() },
  onUpdate: ({ editor: e }) => {
    emit('update:modelValue', e.isEmpty ? '' : e.getHTML())
    version.value++
  },
  onSelectionUpdate: () => { version.value++ },
})

/** Fuerza a recalcular el estado de los botones cuando cambia la selección o el contenido. */
const version = ref(0)
const activo = (nombre: string, atributos?: Record<string, unknown>) => { void version.value; return editor.isActive(nombre, atributos) }

watch(() => props.modelValue, (nuevo) => {
  const actual = editor.isEmpty ? '' : editor.getHTML()
  if ((nuevo ?? '') !== actual) editor.commands.setContent(nuevo ?? '', { emitUpdate: false })
})
watch(() => props.disabled, (d) => editor.setEditable(!d))
watch([describedBy, () => props.error, () => props.required], () => editor.setOptions({ editorProps: { attributes: atributos() } }))
onBeforeUnmount(() => editor.destroy())

interface Boton { nombre: string; etiqueta: string; ejecutar: () => void; activo?: () => boolean; deshabilitado?: () => boolean }

const botones = computed<Boton[]>(() => {
  void version.value
  // Con el ratón (el botón no toma el foco) se enfoca el texto; con el teclado el foco se
  // queda en el botón y la persona no pierde su lugar en la barra.
  const c = () => (barra.value?.contains(document.activeElement) ? editor.chain() : editor.chain().focus())
  return [
    { nombre: 'negrita', etiqueta: 'Negrita', ejecutar: () => c().toggleBold().run(), activo: () => activo('bold') },
    { nombre: 'cursiva', etiqueta: 'Cursiva', ejecutar: () => c().toggleItalic().run(), activo: () => activo('italic') },
    { nombre: 'titulo', etiqueta: 'Título', ejecutar: () => c().toggleHeading({ level: 2 }).run(), activo: () => activo('heading', { level: 2 }) },
    { nombre: 'subtitulo', etiqueta: 'Subtítulo', ejecutar: () => c().toggleHeading({ level: 3 }).run(), activo: () => activo('heading', { level: 3 }) },
    { nombre: 'lista', etiqueta: 'Lista con viñetas', ejecutar: () => c().toggleBulletList().run(), activo: () => activo('bulletList') },
    { nombre: 'numerada', etiqueta: 'Lista numerada', ejecutar: () => c().toggleOrderedList().run(), activo: () => activo('orderedList') },
    { nombre: 'cita', etiqueta: 'Cita', ejecutar: () => c().toggleBlockquote().run(), activo: () => activo('blockquote') },
    { nombre: 'enlace', etiqueta: 'Enlace', ejecutar: abrirEnlace, activo: () => activo('link') },
    { nombre: 'imagen', etiqueta: 'Imagen', ejecutar: abrirImagen, activo: () => panel.value === 'imagen' },
    { nombre: 'deshacer', etiqueta: 'Deshacer', ejecutar: () => c().undo().run(), deshabilitado: () => !editor.can().undo() },
    { nombre: 'rehacer', etiqueta: 'Rehacer', ejecutar: () => c().redo().run(), deshabilitado: () => !editor.can().redo() },
  ]
})

// ── Enlace ────────────────────────────────────────────────────────────────────
const barra = ref<HTMLElement | null>(null)
const nombreActivo = ref('negrita')
const panel = ref<'' | 'enlace' | 'imagen'>('')
const urlEnlace = ref('')
const errorEnlace = ref('')

/** Cierra el panel abierto y devuelve el foco al texto. */
function cerrarPanel() {
  panel.value = ''
  editor.commands.focus()
}

/** Patrón ARIA «toolbar»: una sola parada con Tab y flechas/Inicio/Fin entre botones. */
function alTeclearEnBarra(e: KeyboardEvent) {
  const teclas = ['ArrowRight', 'ArrowLeft', 'Home', 'End']
  if (!teclas.includes(e.key) || !barra.value) return
  const botonesDom = Array.from(barra.value.querySelectorAll<HTMLButtonElement>('button:not([disabled])'))
  if (botonesDom.length === 0) return
  const actual = botonesDom.indexOf(document.activeElement as HTMLButtonElement)
  let destino: number
  if (e.key === 'ArrowRight') destino = (actual + 1) % botonesDom.length
  else if (e.key === 'ArrowLeft') destino = (actual - 1 + botonesDom.length) % botonesDom.length
  else if (e.key === 'Home') destino = 0
  else destino = botonesDom.length - 1
  e.preventDefault()
  botonesDom[destino]?.focus()
}

function abrirEnlace() {
  if (panel.value === 'enlace') { panel.value = ''; return }
  urlEnlace.value = (editor.getAttributes('link').href as string | undefined) ?? ''
  errorEnlace.value = ''
  panel.value = 'enlace'
}
function aplicarEnlace() {
  const url = urlEnlace.value.trim()
  if (!URL_PERMITIDA.test(url)) {
    errorEnlace.value = 'Escriba una dirección completa que empiece por https://, http://, mailto: o tel:.'
    return
  }
  editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
  panel.value = ''
}
function quitarEnlace() {
  editor.chain().focus().extendMarkRange('link').unsetLink().run()
  panel.value = ''
}

// ── Imagen ────────────────────────────────────────────────────────────────────
const imagenElegida = ref<File | null>(null)
const altImagen = ref('')
const errorImagen = ref('')
const subiendo = ref(false)
const selector = ref<InstanceType<typeof SubirImagen> | null>(null)

function abrirImagen() {
  if (panel.value === 'imagen') { panel.value = ''; return }
  imagenElegida.value = null
  altImagen.value = ''
  errorImagen.value = ''
  panel.value = 'imagen'
}
async function insertarImagen() {
  errorImagen.value = ''
  if (!imagenElegida.value) { errorImagen.value = 'Elija primero una imagen.'; return }
  if (altImagen.value.trim() === '') { errorImagen.value = 'Escriba la descripción de la imagen: la necesita quien no puede verla.'; return }

  subiendo.value = true
  try {
    const subida = await subirImagenContenido(imagenElegida.value, altImagen.value.trim())
    editor.chain().focus().setImage({ src: subida.url, alt: subida.alt }).run()
    panel.value = ''
    selector.value?.reiniciar()
  } catch {
    errorImagen.value = 'No se pudo subir la imagen. Revise que sea PNG, JPG o WebP de hasta 5 MB y vuelva a intentarlo.'
  } finally {
    subiendo.value = false
  }
}

defineExpose({ editor })
</script>

<template>
  <div>
    <span :id="etiquetaId" data-etiqueta class="block text-sm font-medium text-slate-700">
      {{ label }}<span v-if="required" aria-hidden="true" class="text-red-600"> *</span>
    </span>
    <p v-if="hint" :id="ayudaId" class="mb-1 text-xs text-slate-600">{{ hint }}</p>

    <div
      class="mt-1 rounded-lg border bg-white focus-within:ring-2"
      :class="error ? 'border-red-300 focus-within:ring-red-200' : 'border-slate-300 focus-within:border-gov-blue focus-within:ring-gov-blue/30'"
    >
      <div ref="barra" role="toolbar" aria-label="Formato del texto" class="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50 p-2" @keydown="alTeclearEnBarra">
        <button
          v-for="b in botones"
          :key="b.nombre"
          type="button"
          :aria-label="b.etiqueta"
          :aria-pressed="b.activo ? (b.activo() ? 'true' : 'false') : undefined"
          :disabled="disabled || b.deshabilitado?.()"
          :tabindex="b.nombre === nombreActivo ? 0 : -1"
          class="min-h-10 rounded-md border px-3 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue disabled:cursor-not-allowed disabled:opacity-40"
          :class="b.activo?.() ? 'border-gov-blue bg-gov-blue text-white' : 'border-slate-300 bg-white text-slate-800 hover:bg-slate-100'"
          @mousedown.prevent
          @focus="nombreActivo = b.nombre"
          @click="b.ejecutar()"
        >
          <span aria-hidden="true">{{ b.etiqueta }}</span>
        </button>
      </div>

      <div v-if="panel === 'enlace'" class="space-y-2 border-b border-slate-200 bg-slate-50 p-3">
        <label :for="`${id}-url`" class="block text-sm font-medium text-slate-700">Dirección del enlace</label>
        <input
          :id="`${id}-url`"
          v-model="urlEnlace"
          type="url"
          inputmode="url"
          placeholder="https://"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-gov-blue focus:outline-none focus:ring-2 focus:ring-gov-blue/30"
          @keydown.enter.prevent="aplicarEnlace"
          @keydown.esc.prevent="cerrarPanel"
        >
        <p v-if="errorEnlace" data-error-enlace role="alert" class="text-sm font-medium text-red-700">{{ errorEnlace }}</p>
        <div class="flex flex-wrap gap-2">
          <button type="button" data-accion="aplicar-enlace" class="min-h-10 rounded-lg bg-gov-blue px-4 text-sm font-medium text-white hover:bg-gov-blue-dark" @click="aplicarEnlace">Aplicar enlace</button>
          <button type="button" data-accion="quitar-enlace" class="min-h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-800 hover:bg-slate-100" @click="quitarEnlace">Quitar enlace</button>
          <button type="button" data-accion="cancelar-panel" class="min-h-10 rounded-lg px-4 text-sm font-medium text-slate-700 hover:bg-slate-100" @click="cerrarPanel">Cancelar</button>
        </div>
      </div>

      <div v-if="panel === 'imagen'" class="space-y-3 border-b border-slate-200 bg-slate-50 p-3" @keydown.esc.prevent="cerrarPanel">
        <SubirImagen ref="selector" v-model:alt="altImagen" pedir-alt @seleccionada="imagenElegida = $event" />
        <p v-if="errorImagen" role="alert" class="text-sm font-medium text-red-700">{{ errorImagen }}</p>
        <div class="flex flex-wrap gap-2">
          <button type="button" data-accion="insertar-imagen" :disabled="subiendo" class="min-h-10 rounded-lg bg-gov-blue px-4 text-sm font-medium text-white hover:bg-gov-blue-dark disabled:opacity-60" @click="insertarImagen">
            {{ subiendo ? 'Subiendo…' : 'Insertar imagen' }}
          </button>
          <button type="button" data-accion="cancelar-panel" class="min-h-10 rounded-lg px-4 text-sm font-medium text-slate-700 hover:bg-slate-100" @click="cerrarPanel">Cancelar</button>
        </div>
      </div>

      <EditorContent :editor="editor" />
    </div>

    <p v-if="error" :id="errorId" role="alert" class="mt-1 text-sm font-medium text-red-700">{{ error }}</p>
  </div>
</template>
