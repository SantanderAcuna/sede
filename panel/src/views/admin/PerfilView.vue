<script setup lang="ts">
/**
 * Perfil del usuario autenticado.
 *
 * Muestra los datos del usuario y sus roles/permisos.
 * Los datos se cargan al montar el componente.
 */
import { ref, onMounted } from 'vue'
import { perfil as perfilApi } from '@/services/auth'
import type { RespuestaPerfil } from '@/services/auth'
import { esErrorApi } from '@/services/http'

const datos = ref<RespuestaPerfil | null>(null)
const cargando = ref(true)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    datos.value = await perfilApi()
  } catch (e) {
    if (esErrorApi(e)) {
      error.value = e.message
    } else {
      error.value = 'No se pudo cargar el perfil.'
    }
  } finally {
    cargando.value = false
  }
})
</script>

<template>
  <div class="max-w-2xl">
    <header class="mb-8">
      <h1 class="text-2xl font-bold text-ink">Mi perfil</h1>
      <p class="mt-1 text-sm text-ink-muted">Datos de la cuenta autenticada</p>
    </header>

    <!-- Loading -->
    <div v-if="cargando" class="flex items-center gap-3 text-slate-500">
      <div class="h-5 w-5 border-2 border-slate-300 border-t-gov-blue rounded-full animate-spin" />
      <span>Cargando perfil…</span>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="flex items-start gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200"
    >
      <FaIcon icon="circle-exclamation" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
      <span>{{ error }}</span>
    </div>

    <!-- Perfil -->
    <div v-else-if="datos" class="space-y-6">
      <!-- Datos básicos -->
      <section class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-sm font-semibold text-ink mb-4">Datos de la cuenta</h2>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs font-medium text-ink-muted uppercase tracking-wide">Correo electrónico</dt>
            <dd class="mt-1 text-sm text-ink">{{ datos.email }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-ink-muted uppercase tracking-wide">Estado</dt>
            <dd class="mt-1">
              <span
                :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  datos.estado === 'activo'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-slate-100 text-slate-800',
                ]"
              >
                {{ datos.estado }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-ink-muted uppercase tracking-wide">Doble factor</dt>
            <dd class="mt-1 text-sm text-ink">
              {{ datos.mfa_habilitado ? 'Habilitado' : 'No habilitado' }}
            </dd>
          </div>
        </dl>
      </section>

      <!-- Roles -->
      <section class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-sm font-semibold text-ink mb-4">Roles y permisos</h2>
        <ul class="space-y-4">
          <li v-for="rol in datos.roles" :key="rol.id" class="flex items-start gap-3">
            <div class="mt-0.5 h-8 w-8 rounded-lg bg-gov-blue-light flex items-center justify-center shrink-0">
              <FaIcon icon="user-shield" class="h-4 w-4 text-gov-blue" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-ink">{{ rol.nombre }}</p>
              <div class="mt-1 flex flex-wrap gap-1">
                <span
                  v-for="permiso in rol.permisos"
                  :key="permiso"
                  class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
                >
                  {{ permiso }}
                </span>
                <span
                  v-if="rol.permisos.length === 0"
                  class="text-xs text-slate-400 italic"
                >
                  Sin permisos específicos (acceso total)
                </span>
              </div>
            </div>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>
