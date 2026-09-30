<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  label: string;
  value: string | number;
  delta?: number;            // % cambio (positivo/negativo)
  trend?: 'up' | 'down' | 'flat';
  hint?: string;
  loading?: boolean;
  variant?: 'default' | 'gov' | 'success' | 'warning' | 'danger';
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  delta: undefined,
  trend: undefined,
  hint: undefined,
});

const accent = computed(() => ({
  default: 'bg-slate-100 text-slate-600',
  gov:     'bg-gov-blue-light text-gov-blue',
  success: 'bg-emerald-50 text-emerald-600',
  warning: 'bg-amber-50 text-amber-600',
  danger:  'bg-red-50 text-red-600',
}[props.variant]));

const trendCls = computed(() => {
  if (props.trend === 'up')   return 'text-emerald-600';
  if (props.trend === 'down') return 'text-red-600';
  return 'text-slate-500';
});
</script>

<template>
  <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-sm font-medium text-slate-600 truncate">{{ label }}</p>
        <div v-if="loading" class="mt-2 h-8 w-24 bg-slate-200 rounded animate-pulse" />
        <p v-else class="mt-1 text-3xl font-bold tabular-nums text-slate-900 tracking-tight">{{ value }}</p>
      </div>
      <div :class="['h-10 w-10 grid place-items-center rounded-xl shrink-0', accent]">
        <slot name="icon">
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M3 10a7 7 0 1114 0 7 7 0 01-14 0z" />
          </svg>
        </slot>
      </div>
    </div>
    <div v-if="delta !== undefined || hint" class="mt-3 flex items-center gap-2 text-xs">
      <span v-if="delta !== undefined" :class="['inline-flex items-center gap-0.5 font-medium', trendCls]">
        <svg v-if="trend === 'up'" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 5l5 6H5l5-6z" /></svg>
        <svg v-else-if="trend === 'down'" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 15l-5-6h10l-5 6z" /></svg>
        {{ delta > 0 ? '+' : '' }}{{ delta }}%
      </span>
      <span v-if="hint" class="text-slate-500">{{ hint }}</span>
    </div>
  </div>
</template>
