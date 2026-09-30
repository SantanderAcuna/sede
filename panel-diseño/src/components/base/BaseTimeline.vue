<script setup lang="ts">
interface TimelineEvento {
  id: string;
  fecha: string;       // ISO
  titulo: string;
  detalle?: string;
  actor?: string;
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info';
}
defineProps<{ events: TimelineEvento[] }>();

const dotCls: Record<string, string> = {
  default: 'bg-slate-300',
  success: 'bg-emerald-500',
  warning: 'bg-amber-500',
  danger:  'bg-red-500',
  info:    'bg-gov-blue',
};
</script>

<template>
  <ol class="relative border-l border-slate-200 ml-3 space-y-6">
    <li v-for="ev in events" :key="ev.id" class="ml-4">
      <span
:class="['absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full ring-2 ring-white', dotCls[ev.variant ?? 'default']]"
            aria-hidden="true" />
      <time class="block text-xs text-slate-500">{{ new Date(ev.fecha).toLocaleString('es-CO') }}</time>
      <h3 class="text-sm font-semibold text-slate-900">{{ ev.titulo }}</h3>
      <p v-if="ev.detalle" class="text-sm text-slate-600 mt-0.5">{{ ev.detalle }}</p>
      <p v-if="ev.actor" class="text-xs text-slate-500 mt-0.5">por <span class="font-medium">{{ ev.actor }}</span></p>
    </li>
  </ol>
</template>
