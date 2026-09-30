<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = withDefaults(defineProps<{
  storageKey?: string;
  side?: 'left' | 'right';
}>(), {
  storageKey: 'smr_panel_a11y',
  side: 'right',
});

const open    = ref(false);
const scale   = ref(1);
const contrast = ref(false);
const dark    = ref(false);
const spacing = ref(false);
const pct     = computed(() => Math.round(scale.value * 100) + '%');

function apply() {
  // Zoom — scale the entire viewport
  document.documentElement.style.zoom = scale.value === 1 ? '' : String(scale.value);

  const html = document.documentElement;
  html.classList.toggle('a11y-contrast', contrast.value);
  html.classList.toggle('a11y-dark',     dark.value);
  html.classList.toggle('a11y-spacing',  spacing.value);

  try {
    localStorage.setItem(props.storageKey, JSON.stringify({
      scale:    scale.value,
      contrast: contrast.value,
      dark:     dark.value,
      spacing:  spacing.value,
    }));
  } catch { /* storage unavailable */ }
}

onMounted(() => {
  try {
    const saved = JSON.parse(localStorage.getItem(props.storageKey) || '{}');
    if (saved.scale)    scale.value    = saved.scale;
    if (saved.contrast) contrast.value = true;
    if (saved.dark)     dark.value     = true;
    if (saved.spacing)  spacing.value  = true;
  } catch { /* no prior prefs */ }
  apply();
});

watch([scale, contrast, dark, spacing], apply);

const inc   = () => (scale.value = Math.min(1.5, +(scale.value + 0.1).toFixed(1)));
const dec   = () => (scale.value = Math.max(0.8, +(scale.value - 0.1).toFixed(1)));
const reset = () => { scale.value = 1; contrast.value = false; dark.value = false; spacing.value = false; };

// Close on outside click
const rootEl = ref<HTMLElement | null>(null);
function onDocClick(e: MouseEvent) {
  if (open.value && rootEl.value && !rootEl.value.contains(e.target as Node)) {
    open.value = false;
  }
}
onMounted(() => document.addEventListener('click', onDocClick, true));
onUnmounted(() => document.removeEventListener('click', onDocClick, true));
</script>

<template>
  <div
    ref="rootEl"
    class="fixed top-1/2 z-50 flex -translate-y-1/2 items-center"
    :class="side === 'left' ? 'left-0 flex-row-reverse' : 'right-0'"
  >
    <Transition name="a11y-panel">
      <div
        v-if="open"
        class="w-64 rounded-xl bg-white shadow-lg ring-1 ring-slate-200 p-4"
        :class="side === 'left' ? 'ml-2' : 'mr-2'"
        role="dialog"
        aria-label="Opciones de accesibilidad"
      >
        <!-- Header -->
        <div class="mb-3 flex items-center justify-between">
          <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <FaIcon icon="universal-access" class="text-gov-blue" aria-hidden="true" />
            Accesibilidad
          </h3>
          <button
            class="grid h-6 w-6 place-items-center rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
            aria-label="Cerrar panel"
            @click="open = false"
          >
            <FaIcon icon="xmark" aria-hidden="true" />
          </button>
        </div>

        <!-- Text size -->
        <div class="mb-3">
          <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">
            Tamaño de texto
          </span>
          <div class="flex items-center gap-2">
            <button
              class="flex flex-1 items-center justify-center gap-1 rounded-lg border border-slate-200 py-1.5 text-slate-700 hover:bg-slate-50 disabled:opacity-40 transition-colors"
              :disabled="scale <= 0.8"
              aria-label="Reducir texto"
              @click="dec"
            >
              <FaIcon icon="minus" class="text-[10px]" aria-hidden="true" />
              <span class="text-xs font-medium">A</span>
            </button>
            <span class="w-12 text-center text-sm font-semibold text-slate-700">{{ pct }}</span>
            <button
              class="flex flex-1 items-center justify-center gap-1 rounded-lg border border-slate-200 py-1.5 text-slate-700 hover:bg-slate-50 disabled:opacity-40 transition-colors"
              :disabled="scale >= 1.5"
              aria-label="Aumentar texto"
              @click="inc"
            >
              <span class="text-xs font-medium">A</span>
              <FaIcon icon="plus" class="text-[10px]" aria-hidden="true" />
            </button>
          </div>
        </div>

        <!-- High contrast -->
        <button
          class="mb-2 flex w-full items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-left transition-colors hover:bg-slate-50"
          role="switch"
          :aria-checked="contrast"
          @click="contrast = !contrast"
        >
          <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
            <FaIcon icon="circle-half-stroke" class="text-gov-blue" aria-hidden="true" />
            Alto contraste
          </span>
          <span
            class="relative h-5 w-9 shrink-0 rounded-full transition-colors duration-200"
            :class="contrast ? 'bg-gov-blue' : 'bg-slate-200'"
          >
            <span
              class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition-all duration-200"
              :class="contrast ? 'left-4' : 'left-0.5'"
            />
          </span>
        </button>

        <!-- Dark mode -->
        <button
          class="mb-2 flex w-full items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-left transition-colors hover:bg-slate-50"
          role="switch"
          :aria-checked="dark"
          @click="dark = !dark"
        >
          <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
            <FaIcon icon="moon" class="text-gov-blue" aria-hidden="true" />
            Modo oscuro
          </span>
          <span
            class="relative h-5 w-9 shrink-0 rounded-full transition-colors duration-200"
            :class="dark ? 'bg-gov-blue' : 'bg-slate-200'"
          >
            <span
              class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition-all duration-200"
              :class="dark ? 'left-4' : 'left-0.5'"
            />
          </span>
        </button>

        <!-- Text spacing -->
        <button
          class="mb-3 flex w-full items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-left transition-colors hover:bg-slate-50"
          role="switch"
          :aria-checked="spacing"
          @click="spacing = !spacing"
        >
          <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
            <FaIcon icon="sliders" class="text-gov-blue" aria-hidden="true" />
            Espaciado de texto
          </span>
          <span
            class="relative h-5 w-9 shrink-0 rounded-full transition-colors duration-200"
            :class="spacing ? 'bg-gov-blue' : 'bg-slate-200'"
          >
            <span
              class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition-all duration-200"
              :class="spacing ? 'left-4' : 'left-0.5'"
            />
          </span>
        </button>

        <!-- Reset -->
        <button
          class="flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-800 transition-colors"
          @click="reset"
        >
          <FaIcon icon="rotate-left" aria-hidden="true" />
          Restablecer todo
        </button>
      </div>
    </Transition>

    <!-- Trigger -->
    <button
      class="grid h-11 w-11 place-items-center bg-gov-blue text-white shadow-md transition-colors hover:bg-gov-blue-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue focus-visible:ring-offset-2"
      :class="side === 'left' ? 'rounded-r-xl' : 'rounded-l-xl'"
      :aria-expanded="open"
      :aria-label="open ? 'Cerrar opciones de accesibilidad' : 'Abrir opciones de accesibilidad'"
      @click="open = !open"
    >
      <FaIcon icon="universal-access" class="text-base" aria-hidden="true" />
    </button>
  </div>
</template>

<style scoped>
.a11y-panel-enter-active,
.a11y-panel-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.a11y-panel-enter-from,
.a11y-panel-leave-to {
  opacity: 0;
  transform: translateX(8px);
}
</style>
