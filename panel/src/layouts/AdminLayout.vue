<script setup lang="ts">
/**
 * Disposición del panel autenticado: menú lateral, cabecera y contenido.
 *
 * El estado del menú (plegado y grupos abiertos) vive **aquí** y no en un
 * almacén: este componente es su único consumidor, y crear un almacén para un
 * solo lector es una indirección que después nadie recuerda por qué existe. Se
 * conserva entre recargas porque plegar el menú y perderlo al navegar molesta.
 */
import { computed, ref, watch } from 'vue'
import { RouterView, RouterLink, useRoute } from 'vue-router'

import CommandPalette from '@/components/feedback/CommandPalette.vue'
import AppLogo from '@/components/base/AppLogo.vue'
import AccessibilityBar from '@/components/base/AccessibilityBar.vue'

interface ItemNavegacion {
  label: string
  ruta: string
  icono: string
}

interface GrupoNavegacion {
  id: string
  label: string
  items: ItemNavegacion[]
}

const grupos: GrupoNavegacion[] = [
  {
    id: 'principal',
    label: 'Principal',
    items: [{ label: 'Dashboard', ruta: '/', icono: 'gauge-high' }],
  },
  {
    id: 'atencion',
    label: 'Atención al ciudadano',
    items: [
      { label: 'PQRSD', ruta: '/pqrsd', icono: 'inbox' },
      { label: 'Trámites', ruta: '/tramites', icono: 'file-lines' },
      { label: 'Citas y turnos', ruta: '/citas', icono: 'calendar-check' },
      { label: 'Notificaciones', ruta: '/notificaciones', icono: 'bell' },
    ],
  },
  {
    id: 'servicios',
    label: 'Servicios al ciudadano',
    items: [
      { label: 'Sede Electrónica', ruta: '/sede', icono: 'globe' },
      { label: 'Carpeta Ciudadana', ruta: '/carpeta', icono: 'briefcase' },
      { label: 'Autenticación Digital', ruta: '/autenticacion', icono: 'key' },
    ],
  },
  {
    id: 'contenidos',
    label: 'Contenidos',
    items: [
      { label: 'CMS', ruta: '/cms', icono: 'pen-to-square' },
      { label: 'Portal Ciudadano', ruta: '/portal', icono: 'display' },
      { label: 'Transparencia', ruta: '/transparencia', icono: 'landmark' },
    ],
  },
  {
    id: 'documental',
    label: 'Gestión documental',
    items: [{ label: 'Gestión Documental', ruta: '/gestion-documental', icono: 'folder-open' }],
  },
  {
    id: 'integraciones',
    label: 'Integraciones',
    items: [
      { label: 'Interoperabilidad SIGMI', ruta: '/sigmi', icono: 'link' },
      { label: 'Conectores externos', ruta: '/integraciones', icono: 'plug' },
    ],
  },
  {
    id: 'admin',
    label: 'Administración',
    items: [
      { label: 'Usuarios y Roles', ruta: '/usuarios', icono: 'users' },
      { label: 'Auditoría', ruta: '/auditoria', icono: 'magnifying-glass' },
      { label: 'Reportes', ruta: '/reportes', icono: 'chart-line' },
      { label: 'Motor de asignación', ruta: '/asignacion', icono: 'sliders' },
      { label: 'Configuración', ruta: '/configuracion', icono: 'gear' },
    ],
  },
]

const CLAVE_INTERFAZ = 'sede.panel.interfaz'

interface EstadoInterfaz {
  plegado: boolean
  gruposAbiertos: Record<string, boolean>
}

function leerInterfaz(): EstadoInterfaz {
  const porDefecto: EstadoInterfaz = { plegado: false, gruposAbiertos: {} }
  try {
    const crudo = window.localStorage.getItem(CLAVE_INTERFAZ)
    if (crudo === null) return porDefecto
    const guardado = JSON.parse(crudo) as Partial<EstadoInterfaz>
    return {
      plegado: typeof guardado.plegado === 'boolean' ? guardado.plegado : porDefecto.plegado,
      gruposAbiertos:
        typeof guardado.gruposAbiertos === 'object' && guardado.gruposAbiertos !== null
          ? guardado.gruposAbiertos
          : porDefecto.gruposAbiertos,
    }
  } catch {
    // Almacenamiento no disponible o contenido corrupto: se sigue con los
    // valores por defecto. Una preferencia de interfaz nunca debe romper la vista.
    return porDefecto
  }
}

const interfaz = ref<EstadoInterfaz>(leerInterfaz())

watch(
  interfaz,
  (valor) => {
    try {
      window.localStorage.setItem(CLAVE_INTERFAZ, JSON.stringify(valor))
    } catch {
      // Sin persistencia se pierde la preferencia, no la funcionalidad.
    }
  },
  { deep: true },
)

const ruta = useRoute()
const paleta = ref<InstanceType<typeof CommandPalette> | null>(null)

/** Una ruta está activa cuando la actual es ella o cuelga de ella. */
function estaActiva(destino: string): boolean {
  return destino === '/' ? ruta.path === '/' : ruta.path.startsWith(destino)
}

function grupoAbierto(id: string): boolean {
  return interfaz.value.gruposAbiertos[id] ?? true
}

function alternarGrupo(id: string): void {
  interfaz.value.gruposAbiertos[id] = !grupoAbierto(id)
}

function alternarMenu(): void {
  interfaz.value.plegado = !interfaz.value.plegado
}

const comandos = computed(() =>
  grupos.flatMap((grupo) =>
    grupo.items.map((item) => ({
      id: item.ruta,
      label: item.label,
      group: grupo.label,
      route: item.ruta,
      icon: item.icono,
    })),
  ),
)

/**
 * Iniciales del avatar. La identidad real llega con el módulo de identidad; por
 * ahora se muestra el marcador de sesión, no un usuario inventado.
 */
const iniciales = computed(() => 'AD')

const migas = computed(() => {
  const segmentos = ruta.path.split('/').filter(Boolean)
  return segmentos.map((segmento, indice) => ({
    label: segmento.charAt(0).toUpperCase() + segmento.slice(1).replace(/-/g, ' '),
    ruta: '/' + segmentos.slice(0, indice + 1).join('/'),
  }))
})
</script>

<template>
  <div class="min-h-screen flex bg-slate-50 text-slate-900">
    <!-- Menú lateral -->
    <aside
      :class="[
        'flex flex-col bg-white border-r border-slate-200 transition-[width] duration-200 shrink-0',
        interfaz.plegado ? 'w-16' : 'w-64',
      ]"
      aria-label="Navegación principal"
    >
      <div class="h-16 px-4 flex items-center gap-3 border-b border-slate-200">
        <AppLogo variant="dark" :size="38" :show-text="!interfaz.plegado" />
      </div>

      <nav class="flex-1 overflow-y-auto py-3">
        <div v-for="grupo in grupos" :key="grupo.id" class="px-2 mb-2">
          <button
            v-if="!interfaz.plegado"
            type="button"
            class="w-full flex items-center justify-between px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-700"
            :aria-expanded="grupoAbierto(grupo.id)"
            @click="alternarGrupo(grupo.id)"
          >
            {{ grupo.label }}
            <svg
              :class="['h-3 w-3 transition-transform', grupoAbierto(grupo.id) ? 'rotate-90' : '']"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path d="M7 5l6 5-6 5V5z" />
            </svg>
          </button>
          <ul v-show="interfaz.plegado || grupoAbierto(grupo.id)" class="space-y-0.5 mt-1">
            <li v-for="item in grupo.items" :key="item.ruta">
              <RouterLink
                :to="item.ruta"
                :title="interfaz.plegado ? item.label : undefined"
                :class="[
                  'group relative flex items-center gap-2.5 px-2 py-2 rounded-lg text-sm transition-all',
                  estaActiva(item.ruta)
                    ? 'bg-gov-blue-light text-gov-blue-dark font-semibold'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                ]"
              >
                <span
                  :class="[
                    'absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 rounded-full transition-all',
                    estaActiva(item.ruta)
                      ? 'bg-gov-blue'
                      : 'bg-transparent group-hover:bg-gov-blue/40',
                  ]"
                  aria-hidden="true"
                />
                <FaIcon
                  :icon="item.icono"
                  :class="[
                    'w-4 h-4 shrink-0 transition-colors',
                    estaActiva(item.ruta)
                      ? 'text-gov-blue'
                      : 'text-slate-400 group-hover:text-gov-blue',
                  ]"
                  aria-hidden="true"
                />
                <span v-if="!interfaz.plegado" class="truncate">{{ item.label }}</span>
              </RouterLink>
            </li>
          </ul>
        </div>
      </nav>

      <button
        type="button"
        class="h-10 border-t border-slate-200 text-xs text-slate-500 hover:bg-slate-50 flex items-center justify-center gap-2"
        :aria-expanded="!interfaz.plegado"
        aria-label="Plegar o desplegar el menú"
        @click="alternarMenu()"
      >
        <svg
          :class="['h-4 w-4 transition-transform', interfaz.plegado ? '' : 'rotate-180']"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path d="M13 5l-6 5 6 5V5z" />
        </svg>
        <span v-if="!interfaz.plegado">Colapsar</span>
      </button>
    </aside>

    <!-- Contenido -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-16 px-6 flex items-center gap-4 bg-white border-b border-slate-200">
        <nav class="flex items-center gap-1 text-sm text-slate-500 min-w-0" aria-label="Migas de pan">
          <RouterLink to="/" class="hover:text-gov-blue">Inicio</RouterLink>
          <template v-for="miga in migas" :key="miga.ruta">
            <span class="text-slate-300">/</span>
            <RouterLink :to="miga.ruta" class="hover:text-gov-blue truncate">
              {{ miga.label }}
            </RouterLink>
          </template>
        </nav>

        <div class="flex-1" />

        <button
          type="button"
          class="hidden md:flex items-center gap-2 px-3 h-9 rounded-lg ring-1 ring-slate-200 text-sm text-slate-500 hover:bg-slate-50"
          @click="paleta?.open()"
        >
          <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
              fill-rule="evenodd"
              d="M9 3a6 6 0 104.47 10.03l3.25 3.25a.75.75 0 101.06-1.06l-3.25-3.25A6 6 0 009 3z"
              clip-rule="evenodd"
            />
          </svg>
          Buscar…
          <kbd class="ml-2 text-[10px] font-mono px-1.5 py-0.5 bg-slate-100 rounded text-slate-600">⌘K</kbd>
        </button>

        <button
          type="button"
          class="relative h-9 w-9 grid place-items-center rounded-lg hover:bg-slate-100 text-slate-600"
          aria-label="Notificaciones"
        >
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
              d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM8 16a2 2 0 104 0H8z"
            />
          </svg>
          <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white" />
        </button>

        <div class="flex items-center gap-2 pl-3 border-l border-slate-200">
          <div
            class="h-9 w-9 grid place-items-center rounded-full bg-gov-blue text-white text-sm font-semibold"
          >
            {{ iniciales }}
          </div>
          <div class="hidden sm:block min-w-0">
            <p class="text-sm font-semibold text-slate-900 truncate">Administrador</p>
            <p class="text-[11px] text-slate-500 truncate">Sesión activa</p>
          </div>
        </div>
      </header>

      <main id="contenido-principal" class="flex-1 overflow-y-auto p-6">
        <RouterView />
      </main>
    </div>

    <CommandPalette ref="paleta" :commands="comandos" />
    <AccessibilityBar />
  </div>
</template>
