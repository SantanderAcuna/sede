<script setup lang="ts">
/**
 * Disposición del panel autenticado: menú lateral, cabecera y contenido.
 *
 * El estado del menú (plegado y grupos abiertos) vive **aquí** y no en un
 * almacén: este componente es su único consumidor, y crear un almacén para un
 * solo lector es una indirección que después nadie recuerda por qué existe. Se
 * conserva entre recargas porque plegar el menú y perderlo al navegar molesta.
 *
 * El menú **filtra por permiso** (D-42) usando el almacén de sesión, y por
 * debajo de `lg` se convierte en un panel deslizante con su botón y su fondo
 * (D-33). La cabecera no muestra usuario ni notificaciones inventadas: sin
 * sesión real dice que no la hay.
 */
import { computed, ref, watch } from 'vue'
import { RouterView, RouterLink, useRoute, useRouter } from 'vue-router'

import CommandPalette from '@/components/feedback/CommandPalette.vue'
import AppLogo from '@/components/base/AppLogo.vue'
import AccessibilityBar from '@/components/base/AccessibilityBar.vue'
import { permisoDeRuta } from '@/config/permisos'
import { useSesionStore } from '@/stores/sesion'

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
      { label: 'Entidad', ruta: '/configuracion/entidad', icono: 'building' },
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
const menuUsuarioAbierto = ref(false)
const router = useRouter()

async function cerrarSesion(): Promise<void> {
  menuUsuarioAbierto.value = false
  await sesion.cerrarSesion()
  // Recarga dura del navegador para resetear TODO el estado del cliente:
  //   - Pinia (incluso con 1 después del logout, queremos resetear)
  //   - Cualquier cache del router
  //   - Cualquier cache del cache-buster
  // window.location.replace() evita que se pueda usar "back" para volver al panel.
  window.location.replace('/admin/acceso')
}

/**
 * Estado de sesión. Hoy siempre vacío (ver `src/stores/sesion.ts`), y por eso
 * la cabecera no puede mostrar un usuario ni el menú ofrecer módulos.
 */
const sesion = useSesionStore()

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

/**
 * Grupos e items que la sesión actual puede ver.
 *
 * El permiso no se repite aquí: se pregunta al mapa compartido por la ruta de
 * cada item, el mismo que usa el enrutador para autorizar la navegación. Con
 * dos listas separadas, el menú podría ofrecer un módulo que la guardia
 * bloquea, o esconder uno al que sí se tiene acceso.
 */
const gruposVisibles = computed(() =>
  grupos
    .map((grupo) => ({
      ...grupo,
      items: grupo.items.filter((item) => sesion.tienePermiso(permisoDeRuta(item.ruta))),
    }))
    .filter((grupo) => grupo.items.length > 0),
)

const comandos = computed(() =>
  gruposVisibles.value.flatMap((grupo) =>
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
 * Iniciales del avatar, derivadas del nombre real. Sin sesión no hay nombre que
 * abreviar, así que se devuelve cadena vacía y la cabecera dice que no hay
 * sesión: unas iniciales fijas afirmarían una identidad que no existe.
 */
const iniciales = computed(() => {
  const nombre = sesion.usuario?.nombre
  if (!nombre) return ''
  return nombre
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte.charAt(0).toUpperCase())
    .join('')
})

/** Menú móvil: por debajo de `lg` la barra lateral se abre y se cierra. */
const menuMovilAbierto = ref(false)

/**
 * En móvil el menú se muestra siempre con texto, aunque en escritorio esté
 * plegado: una columna de iconos sueltos a pantalla completa no se entiende.
 */
const menuExpandido = computed(() => !interfaz.value.plegado || menuMovilAbierto.value)

// Elegir un destino cierra el menú móvil; si no, la barra seguiría tapando la
// pantalla y habría que cerrarla con un segundo toque que nadie espera.
watch(
  () => ruta.fullPath,
  () => {
    menuMovilAbierto.value = false
  },
)

/**
 * Miga de pan. El último nivel es la página actual: no se enlaza a sí mismo y
 * se marca con `aria-current="page"`. El nombre sale de `meta.titulo` cuando
 * existe, porque el segmento de la URL («gestion-documental») no es un nombre.
 */
const migas = computed(() => {
  const segmentos = ruta.path.split('/').filter(Boolean)
  return segmentos.map((segmento, indice) => {
    const esActual = indice === segmentos.length - 1
    return {
      label:
        esActual && ruta.meta.titulo
          ? ruta.meta.titulo
          : segmento.charAt(0).toUpperCase() + segmento.slice(1).replace(/-/g, ' '),
      ruta: '/' + segmentos.slice(0, indice + 1).join('/'),
      esActual,
    }
  })
})
</script>

<template>
  <div class="min-h-screen flex bg-slate-50 text-slate-900">
    <!--
      Primer elemento enfocable de la página. Sin él, quien navega con teclado
      tiene que tabular por todo el menú —hasta sesenta enlaces— antes de llegar
      al contenido. Se oculta con recorte y no con `display:none`, porque un
      elemento que no se pinta tampoco recibe el foco.
    -->
    <a class="salto-contenido" href="#contenido-principal">Saltar al contenido principal</a>

    <!-- Fondo del menú móvil: se pulsa para cerrarlo, como cualquier cajón. -->
    <div
      v-if="menuMovilAbierto"
      class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
      aria-hidden="true"
      @click="menuMovilAbierto = false"
    />

    <!-- Menú lateral -->
    <aside
      id="navegacion-principal"
      :class="[
        'flex flex-col bg-white border-r border-slate-200 shrink-0',
        'transition-transform lg:transition-[width] duration-200',
        // Por debajo de `lg` sale del flujo y se desliza desde la izquierda; a
        // partir de `lg` vuelve a ocupar su columna, como antes.
        'fixed inset-y-0 left-0 z-40 w-64 lg:static lg:z-auto',
        menuMovilAbierto ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        interfaz.plegado ? 'lg:w-16' : 'lg:w-64',
      ]"
      aria-label="Navegación principal"
    >
      <div class="h-16 px-4 flex items-center gap-3 border-b border-slate-200">
        <AppLogo variant="dark" :size="38" :show-text="menuExpandido" />
      </div>

      <nav class="flex-1 overflow-y-auto py-3">
        <!--
          Sin nada que mostrar se dice POR QUÉ, que no es lo mismo en los dos
          casos: no haber entrado y haber entrado sin permiso para ningún
          módulo. Un mensaje único mentiría en uno de los dos.
        -->
        <p v-if="gruposVisibles.length === 0 && !sesion.iniciada" class="px-4 py-6 text-xs text-slate-500">
          No hay módulos disponibles: no hay una sesión iniciada. El menú se mostrará cuando el
          módulo de identidad esté conectado.
        </p>
        <p v-else-if="gruposVisibles.length === 0" class="px-4 py-6 text-xs text-slate-500">
          Su cuenta no tiene permiso para ningún módulo del panel. Si cree que es un error,
          contacte con la entidad.
        </p>

        <div v-for="grupo in gruposVisibles" :key="grupo.id" class="px-2 mb-2">
          <button
            v-if="menuExpandido"
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
          <ul v-show="!menuExpandido || grupoAbierto(grupo.id)" class="space-y-0.5 mt-1">
            <li v-for="item in grupo.items" :key="item.ruta">
              <RouterLink
                :to="item.ruta"
                :title="menuExpandido ? undefined : item.label"
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
                <span v-if="menuExpandido" class="truncate">{{ item.label }}</span>
              </RouterLink>
            </li>
          </ul>
        </div>
      </nav>

      <!-- Plegar en escritorio. En móvil la barra siempre ocupa `w-64`, así que
           el control no tendría efecto y se oculta. -->
      <button
        type="button"
        class="hidden lg:flex h-10 border-t border-slate-200 text-xs text-slate-500 hover:bg-slate-50 items-center justify-center gap-2"
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
        <span v-if="menuExpandido">Colapsar</span>
      </button>
    </aside>

    <!-- Contenido -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-16 px-6 flex items-center gap-4 bg-white border-b border-slate-200">
        <!--
          Abre el menú lateral por debajo de `lg`. Declara qué controla y si
          está abierto, que es lo que necesita un lector de pantalla para no
          perderse: el destino real de un botón de menú no se adivina.
        -->
        <button
          type="button"
          class="lg:hidden h-9 w-9 grid place-items-center rounded-lg text-slate-600 hover:bg-slate-100"
          :aria-expanded="menuMovilAbierto"
          aria-controls="navegacion-principal"
          :aria-label="menuMovilAbierto ? 'Cerrar el menú de navegación' : 'Abrir el menú de navegación'"
          @click="menuMovilAbierto = !menuMovilAbierto"
        >
          <FaIcon :icon="menuMovilAbierto ? 'xmark' : 'bars'" class="h-5 w-5" aria-hidden="true" />
        </button>

        <nav class="hidden sm:flex items-center gap-1 text-sm text-slate-500 min-w-0" aria-label="Migas de pan">
          <!-- El último nivel es la página actual: no se enlaza a sí mismo y se
               marca con `aria-current="page"`. -->
          <template v-if="migas.length === 0">
            <span class="truncate text-slate-700" aria-current="page">Inicio</span>
          </template>
          <template v-else>
            <RouterLink to="/" class="hover:text-gov-blue">Inicio</RouterLink>
            <template v-for="miga in migas" :key="miga.ruta">
              <span class="text-slate-300" aria-hidden="true">/</span>
              <span v-if="miga.esActual" class="truncate text-slate-700" aria-current="page">
                {{ miga.label }}
              </span>
              <RouterLink v-else :to="miga.ruta" class="hover:text-gov-blue truncate">
                {{ miga.label }}
              </RouterLink>
            </template>
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

        <!--
          Campana sin punto de aviso. El punto rojo afirmaba notificaciones sin
          leer que nadie ha contado: mientras no exista el módulo no hay nada
          que notificar, y un indicador falso en una cabecera institucional se
          lee como una alerta real.
        -->
        <button
          type="button"
          class="h-9 w-9 grid place-items-center rounded-lg hover:bg-slate-100 text-slate-600"
          aria-label="Notificaciones"
        >
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
              d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM8 16a2 2 0 104 0H8z"
            />
          </svg>
        </button>

        <!--
          Menú de usuario. Muestra avatar con iniciales y un desplegable con
          opciones de perfil y cierre de sesión.
        -->
        <div class="relative" v-if="sesion.iniciada">
          <button
            type="button"
            class="flex items-center gap-2 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-lg py-1.5 px-1 transition-colors"
            aria-haspopup="true"
            :aria-expanded="menuUsuarioAbierto"
            @click="menuUsuarioAbierto = !menuUsuarioAbierto"
          >
            <div
              class="h-9 w-9 grid place-items-center rounded-full text-sm font-semibold bg-gov-blue text-white"
            >
              {{ iniciales }}
            </div>
            <div class="hidden sm:block min-w-0">
              <p class="text-sm font-semibold text-slate-900 truncate">
                {{ sesion.usuario?.nombre }}
              </p>
              <p class="text-[11px] text-slate-500 truncate">
                {{ sesion.usuario?.email }}
              </p>
            </div>
            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="menuUsuarioAbierto ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 1111.06 1.06l-4.25 4.5a.75.75 0 01-1.06 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
          </button>

          <!-- Dropdown -->
          <div
            v-if="menuUsuarioAbierto"
            class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-slate-100 z-50"
            role="menu"
          >
            <div class="px-4 py-3 border-b border-slate-100">
              <p class="text-sm font-medium text-ink">{{ sesion.usuario?.nombre }}</p>
              <p class="text-xs text-ink-muted mt-0.5">{{ sesion.usuario?.email }}</p>
            </div>
            <RouterLink
              to="/perfil"
              class="flex items-center gap-2 px-4 py-2 text-sm text-ink hover:bg-slate-50 transition-colors"
              role="menuitem"
              @click="menuUsuarioAbierto = false"
            >
              <FaIcon icon="user" class="h-4 w-4 text-slate-400" aria-hidden="true" />
              Mi perfil
            </RouterLink>
            <button
              type="button"
              class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
              role="menuitem"
              @click="cerrarSesion"
            >
              <FaIcon icon='right-from-bracket' class="h-4 w-4" aria-hidden="true" />
              Cerrar sesión
            </button>
          </div>

          <!-- Click outside to close -->
          <div
            v-if="menuUsuarioAbierto"
            class="fixed inset-0 z-40"
            @click="menuUsuarioAbierto = false"
          />
        </div>
      </header>

      <main id="contenido-principal" tabindex="-1" class="flex-1 overflow-y-auto p-6 focus:outline-none">
        <RouterView />
      </main>
    </div>

    <CommandPalette ref="paleta" :commands="comandos" />
    <AccessibilityBar />
  </div>
</template>
