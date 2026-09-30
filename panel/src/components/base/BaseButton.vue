<script setup lang="ts">
import { computed } from 'vue';

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline' | 'success';
type Size = 'sm' | 'md' | 'lg';

interface Props {
  variant?: Variant;
  size?: Size;
  type?: 'button' | 'submit' | 'reset';
  disabled?: boolean;
  loading?: boolean;
  block?: boolean;
  iconOnly?: boolean;
  ariaLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
  disabled: false,
  loading: false,
  block: false,
  iconOnly: false,
  ariaLabel: undefined,
});

defineEmits<{ (e: 'click', ev: MouseEvent): void }>();

const classes = computed(() => {
  const variants: Record<Variant, string> = {
    primary:   'bg-gov-blue text-white hover:bg-gov-blue-dark focus:ring-gov-blue/40',
    secondary: 'bg-white text-gov-blue ring-1 ring-gov-blue/30 hover:bg-gov-blue-light focus:ring-gov-blue/40',
    outline:   'bg-transparent text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 focus:ring-slate-400',
    ghost:     'bg-transparent text-slate-700 hover:bg-slate-100 focus:ring-slate-300',
    danger:    'bg-red-600 text-white hover:bg-red-700 focus:ring-red-400',
    success:   'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-400',
  };
  const sizes: Record<Size, string> = {
    sm: props.iconOnly ? 'h-8 w-8 text-sm'  : 'h-8 px-3 text-sm gap-1.5',
    md: props.iconOnly ? 'h-10 w-10 text-sm' : 'h-10 px-4 text-sm gap-2',
    lg: props.iconOnly ? 'h-12 w-12'         : 'h-12 px-5 text-base gap-2',
  };
  return [
    'inline-flex items-center justify-center rounded-lg font-medium select-none',
    'transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
    'disabled:opacity-50 disabled:cursor-not-allowed',
    variants[props.variant],
    sizes[props.size],
    props.block ? 'w-full' : '',
  ];
});
</script>

<template>
  <button
    :type="type"
    :class="classes"
    :disabled="disabled || loading"
    :aria-label="ariaLabel"
    :aria-busy="loading || undefined"
    @click="(e) => $emit('click', e)"
  >
    <svg v-if="loading" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
      <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
    </svg>
    <slot v-else name="icon-left" />
    <slot v-if="!iconOnly" />
    <slot v-if="!iconOnly" name="icon-right" />
  </button>
</template>
