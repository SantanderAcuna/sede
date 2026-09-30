<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterView, RouterLink, useRoute } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import CommandPalette from '@/components/feedback/CommandPalette.vue';
import AppLogo from '@/components/base/AppLogo.vue';
import AccessibilityBar from '@/components/base/AccessibilityBar.vue';

const ui = useUiStore();
const auth = useAuthStore();
const route = useRoute();
const paletteRef = ref<InstanceType<typeof CommandPalette> | null>(null);

interface NavItem { label: string; route: string; icon: string; permiso?: string; }
interface NavGroup { id: string; label: string; items: NavItem[]; }

const groups: NavGroup[] = [
  { id: 'principal', label: 'Principal', items: [
    { label: 'Dashboard', route: '/dashboard', icon: 'gauge-high' },
  ]},
  { id: 'atencion', label: 'Atención al ciudadano', items: [
    { label: 'PQRSD',          route: '/pqrsd',          icon: 'inbox' },
    { label: 'Trámites',       route: '/tramites',       icon: 'file-lines' },
    { label: 'Citas y turnos', route: '/citas',          icon: 'calendar-check' },
    { label: 'Notificaciones', route: '/notificaciones', icon: 'bell' },
  ]},
  { id: 'servicios', label: 'Servicios al ciudadano', items: [
    { label: 'Sede Electrónica',      route: '/sede',          icon: 'globe' },
    { label: 'Carpeta Ciudadana',     route: '/carpeta',       icon: 'briefcase' },
    { label: 'Autenticación Digital', route: '/autenticacion', icon: 'key' },
  ]},
  { id: 'contenidos', label: 'Contenidos', items: [
    { label: 'CMS',              route: '/cms',           icon: 'pen-to-square' },
    { label: 'Portal Ciudadano', route: '/portal',        icon: 'display' },
    { label: 'Transparencia',    route: '/transparencia', icon: 'landmark' },
  ]},
  { id: 'documental', label: 'Gestión documental', items: [
    { label: 'Gestión Documental', route: '/gestion-documental', icon: 'folder-open' },
  ]},
  { id: 'integraciones', label: 'Integraciones', items: [
    { label: 'Interoperabilidad SIGMI', route: '/sigmi',         icon: 'link' },
    { label: 'Conectores externos',     route: '/integraciones', icon: 'plug' },
  ]},
  { id: 'admin', label: 'Administración', items: [
    { label: 'Usuarios y Roles',    route: '/usuarios',      icon: 'users' },
    { label: 'Auditoría',           route: '/auditoria',     icon: 'magnifying-glass' },
    { label: 'Reportes',            route: '/reportes',      icon: 'chart-line' },
    { label: 'Motor de asignación', route: '/asignacion',    icon: 'sliders' },
    { label: 'Configuración',       route: '/configuracion', icon: 'gear' },
  ]},
];

const commands = computed(() =>
  groups.flatMap(g => g.items.map(i => ({ id: i.route, label: i.label, group: g.label, route: i.route, icon: i.icon })))
);

function isGroupOpen(id: string) { return ui.sidebarGroupsOpen[id] ?? true; }

const initials = computed(() => {
  const n = auth.user?.nombre ?? 'AD';
  return n.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase();
});

const breadcrumb = computed(() => {
  const segments = route.path.split('/').filter(Boolean);
  return segments.map((s, i) => ({
    label: s.charAt(0).toUpperCase() + s.slice(1).replace(/-/g, ' '),
    path: '/' + segments.slice(0, i + 1).join('/'),
  }));
});
</script>

<template>
  <div class="min-h-screen flex bg-slate-50 text-slate-900">
    <!-- Sidebar -->
    <aside
:class="['flex flex-col bg-white border-r border-slate-200 transition-[width] duration-200 shrink-0',
                    ui.sidebarCollapsed ? 'w-16' : 'w-64']"
           aria-label="Navegación principal">
      <div class="h-16 px-4 flex items-center gap-3 border-b border-slate-200">
        <AppLogo v-if="!ui.sidebarCollapsed" variant="dark" :size="38" />
        <AppLogo v-else variant="dark" :size="38" :show-text="false" />
      </div>

      <nav class="flex-1 overflow-y-auto py-3">
        <div v-for="group in groups" :key="group.id" class="px-2 mb-2">
          <button
v-if="!ui.sidebarCollapsed" type="button"
                  class="w-full flex items-center justify-between px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-700"
                  :aria-expanded="isGroupOpen(group.id)"
                  @click="ui.toggleGroup(group.id)">
            {{ group.label }}
            <svg
:class="['h-3 w-3 transition-transform', isGroupOpen(group.id) ? 'rotate-90' : '']"
                 viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path d="M7 5l6 5-6 5V5z" />
            </svg>
          </button>
          <ul v-show="ui.sidebarCollapsed || isGroupOpen(group.id)" class="space-y-0.5 mt-1">
            <li v-for="item in group.items" :key="item.route">
              <RouterLink
:to="item.route"
                          :title="ui.sidebarCollapsed ? item.label : undefined"
                          :class="['group relative flex items-center gap-2.5 px-2 py-2 rounded-lg text-sm transition-all',
                                   $route.path.startsWith(item.route)
                                     ? 'bg-gov-blue-light text-gov-blue-dark font-semibold'
                                     : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900']">
                <span :class="['absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 rounded-full transition-all',
                               $route.path.startsWith(item.route) ? 'bg-gov-blue' : 'bg-transparent group-hover:bg-gov-blue/40']"
                      aria-hidden="true" />
                <FaIcon :icon="item.icon"
                        :class="['w-4 h-4 shrink-0 transition-colors',
                                 $route.path.startsWith(item.route) ? 'text-gov-blue' : 'text-slate-400 group-hover:text-gov-blue']"
                        aria-hidden="true" />
                <span v-if="!ui.sidebarCollapsed" class="truncate">{{ item.label }}</span>
              </RouterLink>
            </li>
          </ul>
        </div>
      </nav>

      <button
type="button"
              class="h-10 border-t border-slate-200 text-xs text-slate-500 hover:bg-slate-50 flex items-center justify-center gap-2"
              @click="ui.toggleSidebar()">
        <svg
:class="['h-4 w-4 transition-transform', ui.sidebarCollapsed ? '' : 'rotate-180']"
             viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path d="M13 5l-6 5 6 5V5z" />
        </svg>
        <span v-if="!ui.sidebarCollapsed">Colapsar</span>
      </button>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-16 px-6 flex items-center gap-4 bg-white border-b border-slate-200">
        <nav class="flex items-center gap-1 text-sm text-slate-500 min-w-0" aria-label="Migas de pan">
          <RouterLink to="/" class="hover:text-gov-blue">Inicio</RouterLink>
          <template v-for="b in breadcrumb" :key="b.path">
            <span class="text-slate-300">/</span>
            <RouterLink :to="b.path" class="hover:text-gov-blue truncate">{{ b.label }}</RouterLink>
          </template>
        </nav>

        <div class="flex-1" />

        <button
type="button"
                class="hidden md:flex items-center gap-2 px-3 h-9 rounded-lg ring-1 ring-slate-200 text-sm text-slate-500 hover:bg-slate-50"
                @click="paletteRef?.open()">
          <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M9 3a6 6 0 104.47 10.03l3.25 3.25a.75.75 0 101.06-1.06l-3.25-3.25A6 6 0 009 3z" clip-rule="evenodd" />
          </svg>
          Buscar…
          <kbd class="ml-2 text-[10px] font-mono px-1.5 py-0.5 bg-slate-100 rounded">⌘K</kbd>
        </button>

        <button
type="button"
                class="relative h-9 w-9 grid place-items-center rounded-lg hover:bg-slate-100 text-slate-600"
                aria-label="Notificaciones">
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM8 16a2 2 0 104 0H8z" />
          </svg>
          <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white" />
        </button>

        <div class="flex items-center gap-2 pl-3 border-l border-slate-200">
          <div class="h-9 w-9 grid place-items-center rounded-full bg-gov-blue text-white text-sm font-semibold">{{ initials }}</div>
          <div class="hidden sm:block min-w-0">
            <p class="text-sm font-semibold text-slate-900 truncate">{{ auth.user?.nombre ?? 'Administrador' }}</p>
            <p class="text-[11px] text-slate-500 truncate">{{ auth.user?.roles[0]?.nombre ?? 'Sesión activa' }}</p>
          </div>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto p-6">
        <RouterView />
      </main>
    </div>

    <CommandPalette ref="paletteRef" :commands="commands" />
    <AccessibilityBar />
  </div>
</template>
