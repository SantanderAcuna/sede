<script setup lang="ts">
import { ref, computed, h } from 'vue';
import { useRouter } from 'vue-router';
import { createColumnHelper, type ColumnDef } from '@tanstack/vue-table';
import DataTable from '@/components/base/DataTable.vue';
import StatusBadge from '@/components/domain/StatusBadge.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import { usePqrsdList } from '@/composables/usePqrsd';
import type { Pqrsd } from '@/types/pqrsd';

const router = useRouter();
const search = ref('');

const params = computed(() => ({ search: search.value || undefined }));
const { data, isLoading, isError } = usePqrsdList(params);
const rows = computed(() => data.value?.items ?? []);

const col = createColumnHelper<Pqrsd>();
const columns = [
  col.accessor('radicado', { header: 'Radicado', cell: (i) => h('span', { class: 'font-mono text-xs' }, i.getValue()) }),
  col.accessor('tipo', { header: 'Tipo' }),
  col.accessor('asunto', { header: 'Asunto' }),
  col.accessor('dependencia', { header: 'Dependencia' }),
  col.accessor('diasRestantes', { header: 'Días', cell: (i) => h('span', { class: 'tabular-nums' }, i.getValue()) }),
  col.accessor('estado', { header: 'Estado', cell: (i) => h(StatusBadge, { estado: i.getValue() }) }),
] as ColumnDef<Pqrsd, unknown>[];
</script>

<template>
  <div class="space-y-5">
    <header class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">PQRSD</h1>
        <p class="text-sm text-slate-500">Peticiones, Quejas, Reclamos, Sugerencias y Denuncias · Ley 1755</p>
      </div>
      <BaseButton variant="primary">
        <template #icon-left><FaIcon icon="plus" /></template>
        Nueva PQRSD
      </BaseButton>
    </header>

    <div v-if="isError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
      Error al cargar las PQRSD. Verificá tu conexión e intentá de nuevo.
    </div>

    <div class="flex items-center gap-3">
      <div class="relative flex-1 max-w-md">
        <FaIcon icon="magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
        <input
          v-model="search"
          type="search"
          placeholder="Buscar por radicado, ciudadano, asunto…"
          class="w-full h-10 pl-9 pr-3 rounded-lg border border-slate-300 focus:border-gov-blue focus:ring-2 focus:ring-gov-blue/30 focus:outline-none text-sm"
        />
      </div>
    </div>

    <DataTable
      :data="rows"
      :columns="columns"
      :loading="isLoading"
      empty="No hay PQRSD que coincidan con los filtros."
      @row-click="(r: Pqrsd) => router.push({ name: 'pqrsd-detail', params: { id: r.id } })"
    />
  </div>
</template>
