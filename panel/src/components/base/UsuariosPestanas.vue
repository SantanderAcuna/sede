<script setup lang="ts">
/** Las dos pantallas de «Usuarios y roles»: se pasa de una a otra sin volver al menú. */
import { RouterLink } from 'vue-router'
import { useSesionStore } from '@/stores/sesion'

const sesion = useSesionStore()
</script>

<template>
  <nav aria-label="Usuarios y roles" class="border-b border-slate-200">
    <ul class="-mb-px flex gap-1">
      <li v-if="sesion.tienePermiso('usuarios.ver')">
        <RouterLink
          :to="{ name: 'usuarios' }"
          class="inline-flex h-11 items-center border-b-2 border-transparent px-4 text-sm font-medium text-slate-600 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue aria-[current=page]:border-gov-blue aria-[current=page]:text-gov-blue"
        >
          Usuarios
        </RouterLink>
      </li>
      <li v-if="sesion.tienePermiso('roles.ver')">
        <RouterLink
          :to="{ name: 'panel.roles' }"
          class="inline-flex h-11 items-center border-b-2 border-transparent px-4 text-sm font-medium text-slate-600 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-blue aria-[current=page]:border-gov-blue aria-[current=page]:text-gov-blue"
        >
          Roles y permisos
        </RouterLink>
      </li>
    </ul>
  </nav>
</template>
