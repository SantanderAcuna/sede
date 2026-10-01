<script setup lang="ts">
/**
 * Inicio del panel: tablero interno.
 *
 * **Arranca en cero y no publica cifras simuladas** (RF-B1-078). Aquí había un
 * recuento de PQRSD, de plazos por vencer, de trámites y un porcentaje de
 * cumplimiento del ITA, además de una insignia que daba el sistema por operativo
 * y un panel de salud con servicios marcados como correctos: todo inventado. Un
 * funcionario no puede distinguir esa maqueta de un dato real, y si el panel
 * llega a producción la Entidad publica indicadores falsos de su propio
 * cumplimiento.
 *
 * Mientras los módulos no estén conectados, cada hueco se declara vacío. No es
 * un tablero a medias: es el único estado que se puede afirmar sin mentir.
 */
import EmptyState from '@/components/feedback/EmptyState.vue'
import KpiCard from '@/components/base/KpiCard.vue'
import BaseBadge from '@/components/base/BaseBadge.vue'

const hoy = new Date().toLocaleDateString('es-CO', { dateStyle: 'long' })

/**
 * Indicadores del tablero. Se enumeran con el módulo que los alimentará para
 * que el estado vacío diga exactamente qué falta, y no un «sin datos» mudo que
 * obligue a adivinar si el sistema está roto o simplemente no existe todavía.
 */
const indicadores = [
  { label: 'PQRSD activas', fuente: 'el módulo de PQRSD' },
  { label: 'Por vencer', fuente: 'el módulo de PQRSD' },
  { label: 'Trámites SUIT', fuente: 'el catálogo de trámites' },
  { label: 'Cumplimiento ITA', fuente: 'el validador de publicación' },
]
</script>

<template>
  <div class="space-y-6">
    <header>
      <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
      <p class="text-sm text-slate-500">Tablero interno del sistema · {{ hoy }}</p>
    </header>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" aria-label="Indicadores">
      <!--
        Sin `:value` numérico ni `:delta`: no hay serie que comparar. La tarjeta
        conserva su forma para que el hueco sea visible, pero su contenido dice
        que no hay dato en vez de rellenarlo.
      -->
      <KpiCard
        v-for="indicador in indicadores"
        :key="indicador.label"
        :label="indicador.label"
        value="Sin datos"
        :hint="`${indicador.fuente} no está conectado`"
      />
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 rounded-2xl bg-white ring-1 ring-slate-200">
        <EmptyState
          title="Sin datos: PQRSD"
          subtitle="El módulo de PQRSD todavía no está conectado, así que no hay serie que dibujar. El tablero no estima ni interpola cifras."
        />
      </div>

      <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-5">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-slate-900">Salud del sistema</h2>
          <BaseBadge variant="neutral" dot>No disponible</BaseBadge>
        </div>
        <!--
          Antes este bloque daba por buenos servicios que todavía no existen,
          con un semáforo inventado. Un semáforo inventado es la peor forma del
          dato falso: parece verificado.
        -->
        <EmptyState
          title="Sin sondas conectadas"
          subtitle="Ninguno de los servicios que alimentarán este panel está monitorizado, así que no se publica ningún estado de servicio."
        />
      </div>
    </section>
  </div>
</template>
