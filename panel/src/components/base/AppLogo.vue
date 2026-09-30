<script setup lang="ts">
/**
 * Logo institucional centralizado.
 * Usa el escudo real importado desde assets (con fallback a /public).
 * variant="light" para fondos oscuros, "dark" para fondos claros.
 */
import { ref } from 'vue';
import logoUrl from '@/assets/images/logo-alcaldia.png';

withDefaults(
  defineProps<{ variant?: 'light' | 'dark'; size?: number; showText?: boolean }>(),
  { variant: 'dark', size: 40, showText: true }
);

const src = ref(logoUrl);
function onError() {
  // Fallback a la copia en /public si el bundling fallara en local
  src.value = '/logo-alcaldia.png';
}
</script>

<template>
  <div class="flex items-center gap-3">
    <div
      class="grid place-items-center rounded-xl shrink-0 overflow-hidden bg-white ring-1"
      :class="variant === 'light' ? 'ring-white/25' : 'ring-line'"
      :style="{ width: size + 'px', height: size + 'px', padding: size * 0.14 + 'px' }"
    >
      <img
:src="src" alt="Escudo Alcaldía Distrital de Santa Marta"
           class="w-full h-full object-contain" @error="onError" />
    </div>
    <div v-if="showText" class="leading-tight min-w-0">
      <p class="font-bold" :class="variant === 'light' ? 'text-white' : 'text-ink'">SGDI</p>
      <p class="text-xs truncate" :class="variant === 'light' ? 'text-white/80' : 'text-ink-muted'">
        Alcaldía Distrital de Santa Marta
      </p>
    </div>
  </div>
</template>
