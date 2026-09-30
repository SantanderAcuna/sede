<script setup lang="ts">
defineProps<{
  title?: string;
  subtitle?: string;
  variant?: 'empty' | 'error';
}>();
</script>

<template>
  <div class="flex flex-col items-center justify-center text-center px-6 py-16">
    <div class="h-16 w-16 grid place-items-center rounded-full bg-slate-100 text-slate-400 mb-4">
      <slot name="icon">
        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
        </svg>
      </slot>
    </div>
    <h3 class="text-base font-semibold text-slate-900">{{ title ?? 'Sin datos' }}</h3>
    <p v-if="subtitle" class="mt-1 text-sm text-slate-500 max-w-md">{{ subtitle }}</p>
    <div v-if="$slots.actions" class="mt-4"><slot name="actions" /></div>
  </div>
</template>
