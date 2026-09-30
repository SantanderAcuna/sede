<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import BaseTimeline from '@/components/base/BaseTimeline.vue';
import StatusBadge from '@/components/domain/StatusBadge.vue';
import { usePqrsdDetail } from '@/composables/usePqrsd';

const route = useRoute();
const id = computed(() => route.params.id as string);

const { data, isLoading, isError } = usePqrsdDetail(id);

const timelineEvents = computed(() =>
  data.value?.trazabilidad.map((t) => ({
    id: t.id,
    fecha: t.fecha,
    titulo: t.accion,
    detalle: t.detalle,
    actor: t.actor,
  })) ?? []
);
</script>

<template>
  <div class="space-y-6">
    <div v-if="isLoading" class="text-sm text-slate-500 py-8 text-center">Cargando radicado…</div>

    <div v-else-if="isError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
      No se pudo cargar el radicado. Verificá tu conexión e intentá de nuevo.
    </div>

    <template v-else-if="data">
      <header>
        <h1 class="text-2xl font-bold text-slate-900">{{ data.radicado }}</h1>
        <div class="mt-2 flex items-center gap-2">
          <StatusBadge :estado="data.estado" />
          <span class="text-sm text-slate-500 capitalize">{{ data.tipo }}</span>
        </div>
      </header>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-2xl bg-white ring-1 ring-slate-200 p-6 space-y-4">
          <h2 class="font-semibold text-slate-900">Detalle</h2>

          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-slate-500">Asunto</p>
              <p class="font-medium text-slate-900">{{ data.asunto }}</p>
            </div>
            <div>
              <p class="text-slate-500">Dependencia</p>
              <p class="font-medium text-slate-900">{{ data.dependencia }}</p>
            </div>
            <div>
              <p class="text-slate-500">Canal</p>
              <p class="font-medium text-slate-900 capitalize">{{ data.canal }}</p>
            </div>
            <div>
              <p class="text-slate-500">Días restantes</p>
              <p class="font-medium tabular-nums" :class="data.diasRestantes < 0 ? 'text-red-600' : 'text-slate-900'">
                {{ data.diasRestantes }}
              </p>
            </div>
            <div>
              <p class="text-slate-500">Fecha recepción</p>
              <p class="font-medium text-slate-900">{{ data.fechaRecepcion }}</p>
            </div>
            <div>
              <p class="text-slate-500">Fecha vencimiento</p>
              <p class="font-medium text-slate-900">{{ data.fechaVencimiento }}</p>
            </div>
          </div>

          <hr class="border-slate-100" />

          <div class="text-sm">
            <p class="text-slate-500 mb-1">Ciudadano</p>
            <p class="font-medium text-slate-900">{{ data.ciudadano.nombre }}</p>
            <p class="text-slate-600">{{ data.ciudadano.documento }}</p>
            <p class="text-slate-600">{{ data.ciudadano.email }}</p>
          </div>

          <div v-if="data.descripcion" class="text-sm">
            <p class="text-slate-500 mb-1">Descripción</p>
            <p class="text-slate-700 whitespace-pre-wrap">{{ data.descripcion }}</p>
          </div>
        </div>

        <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-6">
          <h2 class="font-semibold text-slate-900 mb-4">Trazabilidad</h2>
          <BaseTimeline v-if="timelineEvents.length" :events="timelineEvents" />
          <p v-else class="text-sm text-slate-500">Sin eventos registrados.</p>
        </div>
      </div>
    </template>
  </div>
</template>
