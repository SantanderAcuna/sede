<script setup lang="ts">
defineProps<{ title?: string; subtitle?: string }>();
</script>

<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-2xl font-bold text-slate-900">{{ title }}</h1>
      <p v-if="subtitle" class="text-sm text-slate-500">{{ subtitle }}</p>
    </header>
    <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-8 text-center text-slate-500">
      <p class="text-sm">Vista en construcción · scaffold listo para implementar.</p>
    </div>
  </div>
</template>
