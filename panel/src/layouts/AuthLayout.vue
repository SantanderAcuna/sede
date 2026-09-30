<script setup lang="ts">
import AppLogo from '@/components/base/AppLogo.vue';
</script>

<template>
  <div class="min-h-screen grid lg:grid-cols-2 bg-surface-bg">
    <!-- Panel de marca · color plano GOV.CO (sin degradados) -->
    <div class="hidden lg:flex flex-col justify-between p-12 bg-gov-blue-dark text-white">
      <AppLogo variant="light" :size="52" />

      <div class="space-y-3">
        <h1 class="text-4xl font-bold leading-tight">Sistema de Gestión Distrital Inteligente</h1>
        <p class="text-white/90 text-lg max-w-md">
          Administración eficiente, transparente y centrada en el ciudadano. Cumplimiento GOV.CO, Decreto 620 y MIPG.
        </p>
        <div class="flex flex-wrap gap-2 pt-2">
          <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-white/10 ring-1 ring-white/20">GOV.CO</span>
          <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-white/10 ring-1 ring-white/20">WCAG 2.1 AA</span>
          <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-white/10 ring-1 ring-white/20">MFA TOTP</span>
        </div>
      </div>

      <p class="text-xs text-white/70">© {{ new Date().getFullYear() }} Alcaldía de Santa Marta · NIT 891.780.009-4</p>
    </div>

    <!-- Panel del formulario -->
    <div class="flex items-center justify-center p-6 sm:p-12">
      <div class="w-full max-w-md">
        <!-- Marca compacta visible en móvil (sin panel lateral) -->
        <div class="flex lg:hidden mb-8">
          <AppLogo variant="dark" :size="44" />
        </div>
        <slot />
      </div>
    </div>
  </div>
</template>
