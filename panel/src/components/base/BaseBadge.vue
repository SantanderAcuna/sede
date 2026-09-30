<script setup lang="ts">
import { computed } from 'vue';

type Variant = 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'gov';
type Size = 'sm' | 'md';

const props = withDefaults(defineProps<{ variant?: Variant; size?: Size; dot?: boolean }>(), {
  variant: 'neutral',
  size: 'sm',
  dot: false,
});

const cls = computed(() => {
  const v: Record<Variant, string> = {
    neutral: 'bg-slate-100 text-slate-700 ring-slate-200',
    info:    'bg-blue-50 text-blue-700 ring-blue-200',
    success: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    warning: 'bg-amber-50 text-amber-800 ring-amber-200',
    danger:  'bg-red-50 text-red-700 ring-red-200',
    gov:     'bg-gov-blue-light text-gov-blue-dark ring-gov-blue/20',
  };
  const s: Record<Size, string> = {
    sm: 'text-xs px-2 py-0.5 gap-1',
    md: 'text-sm px-2.5 py-1 gap-1.5',
  };
  return ['inline-flex items-center rounded-full font-medium ring-1 ring-inset', v[props.variant], s[props.size]];
});

const dotCls = computed(() => {
  const m: Record<Variant, string> = {
    neutral: 'bg-slate-500', info: 'bg-blue-500', success: 'bg-emerald-500',
    warning: 'bg-amber-500', danger: 'bg-red-500', gov: 'bg-gov-blue',
  };
  return ['inline-block h-1.5 w-1.5 rounded-full', m[props.variant]];
});
</script>

<template>
  <span :class="cls">
    <span v-if="dot" :class="dotCls" aria-hidden="true" />
    <slot />
  </span>
</template>
