<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue';

const props = withDefaults(defineProps<{
  modelValue: boolean;
  title?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  closeOnBackdrop?: boolean;
}>(), { size: 'md', closeOnBackdrop: true, title: undefined });

const emit = defineEmits<{ (e: 'update:modelValue', v: boolean): void }>();

function close() { emit('update:modelValue', false); }
function onKey(e: KeyboardEvent) { if (e.key === 'Escape' && props.modelValue) close(); }

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));

watch(() => props.modelValue, (v) => {
  document.body.style.overflow = v ? 'hidden' : '';
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
        :aria-label="title"
      >
        <div
class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
             @click="closeOnBackdrop && close()" />
        <div :class="['relative w-full bg-white rounded-2xl shadow-2xl ring-1 ring-slate-200 flex flex-col max-h-[90vh]', sizes[size]]">
          <header v-if="title || $slots.header" class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <slot name="header"><h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2></slot>
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
