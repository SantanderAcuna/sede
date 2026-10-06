<script setup lang="ts">
/**
 * Perfil del usuario autenticado.
 *
 * Vista de operación: información de la cuenta y permisos del usuario activo.
 * Se carga al montar el componente.
 */
import { ref, onMounted, computed } from 'vue'
import { perfil as perfilApi } from '@/services/auth'
import type { RespuestaPerfil } from '@/services/auth'
import { esErrorApi } from '@/services/http'
import BaseBadge from '@/components/base/BaseBadge.vue'

const datos = ref<RespuestaPerfil | null>(null)
const cargando = ref(true)
const error = ref<string | null>(null)

/** Iniciales del usuario para el avatar. */
const iniciales = computed(() => {
  if (!datos.value) return ''
  return (datos.value.email.split('@')[0] ?? '')
    .split(/[._-]/)
    .map((p) => p.charAt(0).toUpperCase())
    .slice(0, 2)
    .join('')
})

/** Nombre para mostrar: la parte local del email formateada. */
const nombreDisplay = computed(() => {
  if (!datos.value) return ''
  return (datos.value.email.split('@')[0] ?? '')
    .replace(/[._-]/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase())
})

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
  <div class="max-w-3xl mx-auto">
    <!-- Loading skeleton -->
    <div v-if="cargando" class="space-y-6 animate-pulse">
      <div class="flex items-center gap-5">
        <div class="h-20 w-20 rounded-full bg-slate-200" />
        <div class="space-y-2">
          <div class="h-6 w-48 bg-slate-200 rounded" />
          <div class="h-4 w-64 bg-slate-200 rounded" />
        </div>
      </div>
      <div class="h-48 bg-slate-100 rounded-xl" />
      <div class="h-32 bg-slate-100 rounded-xl" />
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5"
    >
      <FaIcon icon="circleXmark" class="h-5 w-5 text-red-500 mt-0.5 shrink-0" aria-hidden="true" />
      <div>
        <p class="font-medium text-red-800">No se pudo cargar el perfil</p>
        <p class="mt-1 text-sm text-red-600">{{ error }}</p>
      </div>
    </div>

    <!-- Perfil completo -->
    <div v-else-if="datos" class="space-y-5">
      <!-- Hero: avatar + datos principales -->
      <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[var(--color-gov-blue)] to-[var(--color-gov-blue-dark)] p-6 text-white">
        <!-- Patrón decorativo sutil -->
        <div class="absolute inset-0 opacity-10" aria-hidden="true">
          <svg class="h-full w-full" viewBox="0 0 200 200" preserveAspectRatio="none">
            <defs>
              <pattern id="patron" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                <circle cx="20" cy="20" r="1.5" fill="white" />
              </pattern>
            </defs>
            <rect width="200" height="200" fill="url(#patron)" />
          </svg>
        </div>

        <div class="relative flex items-center gap-5">
          <!-- Avatar -->
          <div
            class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-2xl font-bold tracking-tight ring-2 ring-white/30 shrink-0"
            :aria-label="`Avatar de ${nombreDisplay}`"
          >
            {{ iniciales }}
          </div>

          <div class="min-w-0 flex-1">
            <h1 class="text-xl font-bold tracking-tight">{{ nombreDisplay }}</h1>
            <p class="mt-0.5 text-white/80 text-sm">{{ datos.email }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-2">
              <BaseBadge
                :variant="datos.estado === 'activo' ? 'success' : 'neutral'"
                dot
              >
                {{ datos.estado === 'activo' ? 'Cuenta activa' : datos.estado }}
              </BaseBadge>
              <BaseBadge
                v-if="datos.mfa_habilitado"
                variant="info"
              >
                <FaIcon icon="shieldHalved" class="h-3 w-3" aria-hidden="true" />
                Doble factor
              </BaseBadge>
              <BaseBadge
                v-else
                variant="neutral"
              >
                <FaIcon icon="shield" class="h-3 w-3" aria-hidden="true" />
                Sin 2FA
              </BaseBadge>
            </div>
          </div>
        </div>
      </section>

      <!-- Grid de información -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <!-- Cuenta -->
        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <header class="flex items-center gap-2 mb-4">
            <div class="h-8 w-8 rounded-lg bg-[var(--color-gov-blue-50)] flex items-center justify-center">
              <FaIcon icon="user" class="h-4 w-4 text-[var(--color-gov-blue)]" aria-hidden="true" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">Cuenta</h2>
          </header>
          <dl class="space-y-3">
            <div>
              <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Correo electrónico</dt>
              <dd class="mt-0.5 text-sm text-slate-900">{{ datos.email }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">ID de usuario</dt>
              <dd class="mt-0.5 text-sm text-slate-900 font-mono">#{{ datos.id }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Estado</dt>
              <dd class="mt-0.5">
                <BaseBadge
                  :variant="datos.estado === 'activo' ? 'success' : 'warning'"
                  size="sm"
                >
                  {{ datos.estado }}
                </BaseBadge>
              </dd>
            </div>
          </dl>
        </section>

        <!-- Seguridad -->
        <section class="rounded-xl border border-slate-200 bg-white p-5">
          <header class="flex items-center gap-2 mb-4">
            <div class="h-8 w-8 rounded-lg bg-[var(--color-gov-blue-50)] flex items-center justify-center">
              <FaIcon icon="lock" class="h-4 w-4 text-[var(--color-gov-blue)]" aria-hidden="true" />
            </div>
            <h2 class="text-sm font-semibold text-slate-900">Seguridad</h2>
          </header>
          <dl class="space-y-3">
            <div>
              <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Doble factor</dt>
              <dd class="mt-0.5 flex items-center gap-1.5 text-sm">
                <FaIcon
                  :icon="datos.mfa_habilitado ? 'circleCheck' : 'circleXmark'"
                  :class="datos.mfa_habilitado ? 'text-emerald-500' : 'text-slate-400'"
                  class="h-4 w-4"
                  aria-hidden="true"
                />
                <span :class="datos.mfa_habilitado ? 'text-slate-900' : 'text-slate-500'">
                  {{ datos.mfa_habilitado ? 'Habilitado' : 'No configurado' }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Autenticación</dt>
              <dd class="mt-0.5 text-sm text-slate-900 flex items-center gap-1.5">
                <FaIcon icon="mobileScreen" class="h-4 w-4 text-slate-400" aria-hidden="true" />
                Token Sanctum
              </dd>
            </div>
          </dl>
        </section>
      </div>

      <!-- Roles y permisos -->
      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <header class="flex items-center gap-2 mb-4">
          <div class="h-8 w-8 rounded-lg bg-[var(--color-gov-blue-50)] flex items-center justify-center">
            <FaIcon icon="user-shield" class="h-4 w-4 text-[var(--color-gov-blue)]" aria-hidden="true" />
          </div>
          <h2 class="text-sm font-semibold text-slate-900">Roles y permisos</h2>
        </header>

        <ul class="space-y-4">
          <li
            v-for="rol in datos.roles"
            :key="rol.id"
            class="flex items-start gap-4 rounded-lg border border-slate-100 bg-slate-50/50 p-4"
          >
            <div class="h-10 w-10 rounded-lg bg-[var(--color-gov-blue-light)] flex items-center justify-center shrink-0">
              <FaIcon icon="idCardClip" class="h-5 w-5 text-[var(--color-gov-blue)]" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <p class="font-medium text-slate-900">{{ rol.nombre }}</p>
                <BaseBadge
                  :variant="rol.nombre === 'super-admin' ? 'gov' : 'neutral'"
                  size="sm"
                >
                  rol
                </BaseBadge>
              </div>
              <div class="mt-2 flex flex-wrap gap-1.5">
                <span
                  v-for="permiso in rol.permisos"
                  :key="permiso"
                  class="inline-flex items-center rounded bg-slate-200 px-2 py-0.5 text-xs font-mono text-slate-600"
                >
                  {{ permiso }}
                </span>
                <span
                  v-if="rol.permisos.length === 0"
                  class="inline-flex items-center gap-1 text-xs text-slate-400"
                >
                  <FaIcon icon="infinity" class="h-3 w-3" aria-hidden="true" />
                  Acceso total (sin restricciones)
                </span>
              </div>
            </div>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>
