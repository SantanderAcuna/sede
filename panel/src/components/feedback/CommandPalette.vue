<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';

interface Command { id: string; label: string; group: string; route?: string; icon?: string; action?: () => void; }

const props = defineProps<{ commands: Command[] }>();
const open = ref(false);
const query = ref('');
const activeIdx = ref(0);
const inputRef = ref<HTMLInputElement | null>(null);
const panelRef = ref<HTMLElement | null>(null);
const router = useRouter();

/** Quien abrió la paleta (el botón «Buscar…» o el atajo): al cerrarla vuelve ahí. */
let disparador: HTMLElement | null = null;

/** Igual que en `BaseModal`: se recalcula en cada Tab porque la lista filtra. */
const FOCALIZABLES =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase();
  if (!q) return props.commands;
  return props.commands.filter(c => c.label.toLowerCase().includes(q) || c.group.toLowerCase().includes(q));
});

const grouped = computed(() => {
  const g: Record<string, Command[]> = {};
  filtered.value.forEach(c => { (g[c.group] ??= []).push(c); });
  return g;
});

function listarFocalizables(): HTMLElement[] {
  const panel = panelRef.value;
  if (!panel) return [];
  return Array.from(panel.querySelectorAll<HTMLElement>(FOCALIZABLES));
}

/**
 * La paleta se comporta como un diálogo modal (`aria-modal="true"`): mientras
 * está abierta, Tab no puede escaparse al contenido de detrás. Sin esta trampa
 * la promesa de `aria-modal` es falsa y el foco se pierde por la página.
 */
function trampaDeFoco(e: KeyboardEvent) {
  const panel = panelRef.value;
  if (!panel) return;

  const focalizables = listarFocalizables();
  if (focalizables.length === 0) {
    e.preventDefault();
    panel.focus();
    return;
  }

  const primero = focalizables.at(0);
  const ultimo = focalizables.at(-1);
  const activo = document.activeElement as HTMLElement | null;

  if (e.shiftKey && (activo === primero || !panel.contains(activo))) {
    e.preventDefault();
    ultimo!.focus();
  } else if (!e.shiftKey && activo === ultimo) {
    e.preventDefault();
    primero!.focus();
  }
}

function onKey(e: KeyboardEvent) {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); open.value = !open.value; return; }
  if (!open.value) return;
  if (e.key === 'Escape') { e.preventDefault(); open.value = false; return; }
  if (e.key === 'Tab') { trampaDeFoco(e); return; }
  if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx.value = Math.min(activeIdx.value + 1, filtered.value.length - 1); }
  if (e.key === 'ArrowUp')   { e.preventDefault(); activeIdx.value = Math.max(activeIdx.value - 1, 0); }
  if (e.key === 'Enter')     { e.preventDefault(); execute(filtered.value[activeIdx.value]); }
}

function execute(c?: Command) {
  if (!c) return;
  open.value = false; query.value = ''; activeIdx.value = 0;
  if (c.route) router.push(c.route);
  else c.action?.();
}

watch(open, async (v) => {
  if (v) {
    const activo = document.activeElement;
    disparador = activo instanceof HTMLElement && activo !== document.body ? activo : null;
    await nextTick();
    inputRef.value?.focus();
    return;
  }
  if (disparador?.isConnected) disparador.focus();
  disparador = null;
});
watch(query, () => activeIdx.value = 0);

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));

defineExpose({ open: () => { open.value = true; } });
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-24" role="dialog" aria-modal="true" aria-label="Búsqueda rápida">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="open = false" />
        <div ref="panelRef" tabindex="-1" class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 overflow-hidden focus:outline-none">
          <div class="flex items-center px-4 border-b border-slate-200">
            <svg class="h-5 w-5 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M9 3a6 6 0 104.47 10.03l3.25 3.25a.75.75 0 101.06-1.06l-3.25-3.25A6 6 0 009 3zM4.5 9a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0z" clip-rule="evenodd" />
            </svg>
            <input
ref="inputRef" v-model="query" type="text"
                   placeholder="Buscar comando, módulo, ciudadano… (⌘K)"
                   class="flex-1 px-3 py-4 text-sm bg-transparent border-0 focus:outline-none focus:ring-0 text-slate-900 placeholder-slate-400" />
            <kbd class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 text-xs font-mono bg-slate-100 text-slate-600 rounded border border-slate-200">Esc</kbd>
          </div>
          <div class="max-h-96 overflow-y-auto py-2">
            <p v-if="filtered.length === 0" class="px-4 py-8 text-center text-sm text-slate-500">Sin coincidencias</p>
            <template v-for="(items, group) in grouped" v-else :key="group">
              <div class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ group }}</div>
              <button
v-for="c in items" :key="c.id" type="button"
                      :class="['w-full flex items-center gap-3 px-4 py-2.5 text-left text-sm transition-colors',
                               filtered.indexOf(c) === activeIdx ? 'bg-gov-blue-light text-gov-blue-dark' : 'hover:bg-slate-50 text-slate-700']"
                      @mouseenter="activeIdx = filtered.indexOf(c)"
                      @click="execute(c)">
                <!--
                  El icono se DIBUJA, no se imprime. Antes esto era
                  `{{ c.icon }}` y la paleta leía literalmente «gauge-high» en
                  cada comando (D-34): el nombre de un icono no es su contenido.
                -->
                <span class="h-7 w-7 grid place-items-center rounded-md bg-slate-100 text-slate-500">
                  <FaIcon v-if="c.icon" :icon="c.icon" class="h-3.5 w-3.5" aria-hidden="true" />
                  <span v-else class="text-xs" aria-hidden="true">›</span>
                </span>
                <span class="flex-1">{{ c.label }}</span>
                <span class="text-xs text-slate-400" aria-hidden="true">↵</span>
              </button>
            </template>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 200ms ease, transform 200ms ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
