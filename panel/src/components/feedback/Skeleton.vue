<script setup lang="ts">
defineProps<{
  rows?: number;
  variant?: 'card' | 'table' | 'text';
}>();
</script>

<template>
  <div v-if="variant === 'card'" class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 animate-pulse">
    <div class="h-4 w-1/3 bg-slate-200 rounded" />
    <div class="mt-3 h-8 w-1/2 bg-slate-200 rounded" />
    <div class="mt-2 h-3 w-2/3 bg-slate-100 rounded" />
  </div>
  <div v-else-if="variant === 'table'" class="space-y-2 animate-pulse">
    <div v-for="i in (rows ?? 5)" :key="i" class="h-10 bg-slate-100 rounded" />
  </div>
  <div v-else class="space-y-2 animate-pulse">
    <div v-for="i in (rows ?? 3)" :key="i" class="h-3 bg-slate-200 rounded" :style="{ width: 100 - (i * 10) + '%' }" />
  </div>
</template>
