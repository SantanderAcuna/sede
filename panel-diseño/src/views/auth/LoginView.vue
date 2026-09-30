<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useForm } from '@tanstack/vue-form';
import { ValidationError } from 'yup';
import { loginSchema } from '@/schemas';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import FormField from '@/components/base/FormField.vue';
import BaseButton from '@/components/base/BaseButton.vue';

const router = useRouter();
const auth = useAuthStore();
const toast = useToast();
const loading = ref(false);

/** Valida un campo individual con Yup y devuelve el mensaje de error (o undefined). */
function validate(field: 'username' | 'password', value: string): string | undefined {
  try {
    loginSchema.validateSyncAt(field, { username: '', password: '', [field]: value });
    return undefined;
  } catch (e) {
    return e instanceof ValidationError ? e.message : 'Valor inválido';
  }
}

const form = useForm({
  defaultValues: { username: '', password: '' },
  onSubmit: async ({ value }) => {
    loading.value = true;
    try {
      // TODO: replace with await auth.login(value) when API is ready
      auth.mfaChallenge = { type: 'mfa-required', challengeId: 'demo', method: 'totp' };
      void value;
      router.push({ name: 'mfa' });
    } catch {
      toast.error('Credenciales inválidas');
    } finally {
      loading.value = false;
    }
  },
});
</script>

<template>
  <form class="space-y-5" novalidate @submit.prevent.stop="form.handleSubmit()">
    <header class="space-y-1">
      <h1 class="text-2xl font-bold text-ink">Iniciar sesión</h1>
      <p class="text-sm text-ink-muted">Acceso al panel administrativo SGDI</p>
    </header>

    <form.Field name="username" :validators="{ onChange: ({ value }) => validate('username', value) }">
      <template #default="{ field }">
        <FormField
          label="Usuario o cédula"
          autocomplete="username"
          required
          :model-value="field.state.value"
          :error="field.state.meta.errors?.[0] ?? undefined"
          @update:model-value="(v: string) => field.handleChange(v)"
          @blur="field.handleBlur"
        />
      </template>
    </form.Field>

    <form.Field name="password" :validators="{ onChange: ({ value }) => validate('password', value) }">
      <template #default="{ field }">
        <FormField
          label="Contraseña"
          type="password"
          autocomplete="current-password"
          required
          :model-value="field.state.value"
          :error="field.state.meta.errors?.[0] ?? undefined"
          @update:model-value="(v: string) => field.handleChange(v)"
          @blur="field.handleBlur"
        />
      </template>
    </form.Field>

    <div class="flex items-center justify-between text-sm">
      <label class="inline-flex items-center gap-2 text-ink-muted">
        <input type="checkbox" class="h-4 w-4 rounded border-line-strong text-gov-blue focus:ring-gov-blue" />
        Recordarme
      </label>
      <RouterLink to="/recuperar" class="text-gov-blue hover:underline">¿Olvidaste tu contraseña?</RouterLink>
    </div>

    <BaseButton type="submit" block size="lg" :loading="loading">Ingresar</BaseButton>

    <p class="text-xs text-center text-ink-soft">Sesión protegida con MFA TOTP (Decreto 1078).</p>
  </form>
</template>
