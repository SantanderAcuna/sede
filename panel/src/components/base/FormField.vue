<script setup lang="ts">
import { computed, useId } from 'vue';

interface Props {
  modelValue: string | null | undefined;
  label?: string;
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'date' | 'search' | 'textarea';
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  error?: string;
  hint?: string;
  rows?: number;
  autocomplete?: string;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  rows: 3,
  label: undefined,
  placeholder: undefined,
  error: undefined,
  hint: undefined,
  autocomplete: undefined,
});
const emit = defineEmits<{ 'update:modelValue': [value: string | null]; blur: [] }>();

const id = useId();
const describedBy = computed(() => {
  const ids: string[] = [];
  if (props.hint) ids.push(`${id}-hint`);
  if (props.error) ids.push(`${id}-error`);
  return ids.join(' ') || undefined;
});

const inputCls = computed(() => [
  'w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400',
  'transition-colors focus:outline-none focus:ring-2',
  props.error
    ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
    : 'border-slate-300 focus:border-gov-blue focus:ring-gov-blue/30',
  props.disabled ? 'bg-slate-50 cursor-not-allowed' : '',
]);

function onInput(e: Event) {
  emit('update:modelValue', (e.target as HTMLInputElement).value);
}
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="id" class="text-sm font-medium text-slate-700">
      {{ label }}<span v-if="required" class="text-red-500 ml-0.5" aria-hidden="true">*</span>
    </label>

    <textarea
      v-if="type === 'textarea'"
      :id="id"
      :value="modelValue"
      :rows="rows"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :aria-invalid="Boolean(error) || undefined"
      :aria-describedby="describedBy"
      :class="inputCls"
      @input="onInput"
      @blur="emit('blur')"
    />
    <input
      v-else
      :id="id"
      :value="modelValue"
      :type="type"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :autocomplete="autocomplete"
      :aria-invalid="Boolean(error) || undefined"
      :aria-describedby="describedBy"
      :class="inputCls"
      @input="onInput"
      @blur="emit('blur')"
    />

    <p v-if="hint && !error" :id="`${id}-hint`" class="text-xs text-slate-500">{{ hint }}</p>
    <p v-if="error" :id="`${id}-error`" class="text-xs text-red-600 flex items-center gap-1" role="alert">
      <FaIcon icon="triangle-exclamation" class="h-3 w-3" />
      {{ error }}
    </p>
  </div>
</template>
