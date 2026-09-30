<script setup lang="ts">
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import AuthLayout from '@/layouts/AuthLayout.vue';

const route = useRoute();
const layout = computed(() => route.meta.layout ?? 'blank');
</script>

<template>
  <!-- Las rutas de panel usan AdminLayout como componente padre (router).
       Las rutas de login/mfa se envuelven aquí con AuthLayout. -->
  <AuthLayout v-if="layout === 'auth'">
    <RouterView />
  </AuthLayout>
  <RouterView v-else />
</template>
