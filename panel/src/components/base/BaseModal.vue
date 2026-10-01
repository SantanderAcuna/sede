<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, useId, useSlots, watch } from 'vue';

const props = withDefaults(defineProps<{
  modelValue: boolean;
  title?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  closeOnBackdrop?: boolean;
}>(), { size: 'md', closeOnBackdrop: true, title: undefined });

const emit = defineEmits<{ (e: 'update:modelValue', v: boolean): void }>();

const slots = useSlots();

/**
 * Todo lo que puede recibir foco dentro del diálogo. Se calcula en cada Tab y
 * no una vez al abrir: el contenido puede cambiar (un error que añade un botón,
 * un paso que cambia el formulario) y una lista cacheada dejaría el foco fuera.
 */
const FOCALIZABLES =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

const panelRef = ref<HTMLElement | null>(null);

/** Quien abrió el diálogo: al cerrarlo el foco vuelve ahí, no al principio del documento. */
let disparador: HTMLElement | null = null;

/** El título necesita un id para poder nombrar el diálogo con `aria-labelledby`. */
const tituloId = useId();

/**
 * Un `role="dialog"` sin nombre accesible no se anuncia: quien usa lector de
 * pantalla oye «diálogo» y nada más. Cuando hay título propio se enlaza con
 * `aria-labelledby`; cuando no lo hay —o lo pone el hueco `header`— se usa un
 * nombre de respaldo, que es peor que el título real pero mucho mejor que nada.
 */
const usaTituloPropio = computed(() => Boolean(props.title) && !slots.header);
const ariaLabelledby = computed(() => (usaTituloPropio.value ? tituloId : undefined));
const ariaLabel = computed(() => (usaTituloPropio.value ? undefined : 'Diálogo'));

function close() { emit('update:modelValue', false); }

function listarFocalizables(): HTMLElement[] {
  const panel = panelRef.value;
  if (!panel) return [];
  return Array.from(panel.querySelectorAll<HTMLElement>(FOCALIZABLES));
}

function onKey(e: KeyboardEvent) {
  if (!props.modelValue) return;

  if (e.key === 'Escape') {
    e.preventDefault();
    close();
    return;
  }

  if (e.key !== 'Tab') return;

  // Trampa de foco: con el diálogo abierto, Tab no puede irse al contenido de
  // detrás. No es una preferencia estética — `aria-modal="true"` ya promete que
  // lo de fuera está inerte, y sin la trampa esa promesa es falsa.
  const panel = panelRef.value;
  if (!panel) return;

  const focalizables = listarFocalizables();
  if (focalizables.length === 0) {
    e.preventDefault();
    panel.focus();
    return;
  }

  const primero = focalizables[0];
  const ultimo = focalizables[focalizables.length - 1];
  const activo = document.activeElement as HTMLElement | null;

  if (e.shiftKey && (activo === primero || !panel.contains(activo))) {
    e.preventDefault();
    ultimo.focus();
  } else if (!e.shiftKey && activo === ultimo) {
    e.preventDefault();
    primero.focus();
  }
}

/** Guarda quién abrió el diálogo y mete el foco en él. */
async function abrir() {
  // El disparador se recuerda ANTES de mover el foco; después ya sería el
  // primer control del diálogo y el foco volvería a un elemento equivocado.
  const activo = document.activeElement;
  disparador = activo instanceof HTMLElement && activo !== document.body ? activo : null;
  await nextTick();
  const primero = listarFocalizables()[0];
  (primero ?? panelRef.value)?.focus();
}

/** Cierra devolviendo el foco a donde estaba. */
function cerrarYDevolverFoco() {
  if (disparador?.isConnected) disparador.focus();
  disparador = null;
}

onMounted(() => {
  window.addEventListener('keydown', onKey);
  // Un diálogo que ya nace abierto —montado con `modelValue: true`— también
  // tiene que recibir el foco: si no, el `watch` nunca se dispara y el foco se
  // queda donde estuviera, fuera del diálogo.
  if (props.modelValue) void abrir();
});
onUnmounted(() => window.removeEventListener('keydown', onKey));

watch(() => props.modelValue, (abierto) => {
  document.body.style.overflow = abierto ? 'hidden' : '';
  if (abierto) void abrir();
  else cerrarYDevolverFoco();
});

const sizes = { sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' };
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="ariaLabelledby"
        :aria-label="ariaLabel"
      >
        <div
class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
             @click="closeOnBackdrop && close()" />
        <div
          ref="panelRef"
          tabindex="-1"
          :class="['relative w-full bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 flex flex-col max-h-[90vh] focus:outline-none', sizes[size]]">
          <header v-if="title || $slots.header" class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <slot name="header"><h2 :id="tituloId" class="text-lg font-semibold text-slate-900">{{ title }}</h2></slot>
            <button
type="button"
                    class="h-8 w-8 grid place-items-center rounded-md text-slate-500 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue"
                    aria-label="Cerrar"
                    @click="close">
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M5.28 4.22a.75.75 0 00-1.06 1.06L8.94 10l-4.72 4.72a.75.75 0 101.06 1.06L10 11.06l4.72 4.72a.75.75 0 101.06-1.06L11.06 10l4.72-4.72a.75.75 0 00-1.06-1.06L10 8.94 5.28 4.22z" />
              </svg>
            </button>
          </header>
          <div class="px-6 py-5 overflow-y-auto"><slot /></div>
          <footer v-if="$slots.footer" class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 200ms ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
