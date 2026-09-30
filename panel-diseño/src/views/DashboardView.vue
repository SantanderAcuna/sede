<script setup lang="ts">
import KpiCard from '@/components/base/KpiCard.vue';
import BaseBadge from '@/components/base/BaseBadge.vue';
</script>

<template>
  <div class="space-y-6">
    <header class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Dashboard ejecutivo</h1>
        <p class="text-sm text-slate-500">Resumen operativo del sistema · {{ new Date().toLocaleDateString('es-CO', { dateStyle: 'long' }) }}</p>
      </div>
      <BaseBadge variant="gov" dot>Sistema operativo</BaseBadge>
    </header>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <KpiCard label="PQRSD activas" :value="287" :delta="12" trend="up" hint="vs. mes anterior" variant="gov" />
      <KpiCard label="Por vencer" :value="34"  :delta="-8" trend="down" hint="próximas 48h" variant="warning" />
      <KpiCard label="Trámites SUIT" :value="1842" :delta="5" trend="up" hint="este mes" variant="success" />
      <KpiCard label="Cumplimiento ITA" value="94%" :delta="3" trend="up" hint="criterios MIPG" variant="gov" />
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 rounded-2xl bg-white ring-1 ring-slate-200 p-5">
        <h2 class="text-base font-semibold text-slate-900">Tendencia PQRSD (últimos 30 días)</h2>
        <p class="text-sm text-slate-500 mb-4">Recibidas vs. respondidas</p>
        <div class="h-64 grid place-items-center text-slate-400 text-sm bg-slate-50 rounded-lg">
          [Chart · integración con vue-chartjs]
        </div>
      </div>
      <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-5">
        <h2 class="text-base font-semibold text-slate-900">Salud del sistema</h2>
        <ul class="mt-3 space-y-3 text-sm">
          <li class="flex items-center justify-between"><span class="text-slate-600">API Gateway</span><BaseBadge variant="success" dot>OK</BaseBadge></li>
          <li class="flex items-center justify-between"><span class="text-slate-600">SIGMI Bus</span><BaseBadge variant="success" dot>OK</BaseBadge></li>
          <li class="flex items-center justify-between"><span class="text-slate-600">Conector RNEC</span><BaseBadge variant="warning" dot>Lento</BaseBadge></li>
          <li class="flex items-center justify-between"><span class="text-slate-600">Firma electrónica</span><BaseBadge variant="success" dot>OK</BaseBadge></li>
        </ul>
      </div>
    </section>
  </div>
</template>
