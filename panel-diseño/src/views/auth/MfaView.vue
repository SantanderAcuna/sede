<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useForm } from '@tanstack/vue-form';
import { ValidationError } from 'yup';
import { mfaSchema } from '@/schemas';
import { useToast } from '@/composables/useToast';
import FormField from '@/components/base/FormField.vue';
import BaseButton from '@/components/base/BaseButton.vue';

const router = useRouter();
const toast = useToast();
const loading = ref(false);

function validateCode(value: string): string | undefined {
  try {
    mfaSchema.validateSyncAt('code', { code: value });
    return undefined;
  } catch (e) {
    return e instanceof ValidationError ? e.message : 'Código inválido';
  }
}

const form = useForm({
  defaultValues: { code: '' },
  onSubmit: async () => {
    loading.value = true;
    try {
      await new Promise((r) => setTimeout(r, 500));
      toast.success('Sesión iniciada correctamente');
      router.push({ name: 'dashboard' });
    } finally {
      loading.value = false;
    }
  },
});
</script>

<template>
  <form class="space-y-5" novalidate @submit.prevent.stop="form.handleSubmit()">
    <header class="space-y-1">
      <h1 class="text-2xl font-bold text-ink">Verificación en dos pasos</h1>
      <p class="text-sm text-ink-muted">Ingresa el código de 6 dígitos de tu app autenticadora.</p>
    </header>

    <form.Field name="code" :validators="{ onChange: ({ value }) => validateCode(value) }">
      <template #default="{ field }">
        <FormField
          label="Código MFA"
          placeholder="000000"
          autocomplete="one-time-code"
          required
          :model-value="field.state.value"
          :error="field.state.meta.errors?.[0] ?? undefined"
          @update:model-value="(v: string) => field.handleChange(v)"
          @blur="field.handleBlur"
        />
      </template>
    </form.Field>

    <BaseButton type="submit" block size="lg" :loading="loading">Verificar e ingresar</BaseButton>
  </form>
</template>
