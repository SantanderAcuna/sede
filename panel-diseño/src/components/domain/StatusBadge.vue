<script setup lang="ts">
import { computed } from 'vue';
import type { PqrsdEstado, PqrsdSemaforo } from '@/types/pqrsd';
import BaseBadge from '@/components/base/BaseBadge.vue';

const props = defineProps<{ estado?: PqrsdEstado; semaforo?: PqrsdSemaforo }>();

const estadoMap: Record<PqrsdEstado, { variant: 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'gov'; label: string }> = {
  'recibida':   { variant: 'gov',     label: 'Recibida' },
  'asignada':   { variant: 'info',    label: 'Asignada' },
  'en-tramite': { variant: 'warning', label: 'En trámite' },
  'respondida': { variant: 'success', label: 'Respondida' },
  'cerrada':    { variant: 'neutral', label: 'Cerrada' },
  'vencida':    { variant: 'danger',  label: 'Vencida' },
};

const semaforoMap: Record<PqrsdSemaforo, { variant: 'success' | 'warning' | 'danger'; label: string }> = {
  verde:    { variant: 'success', label: 'A tiempo' },
  amarillo: { variant: 'warning', label: 'Por vencer' },
  rojo:     { variant: 'danger',  label: 'Crítico' },
  vencido:  { variant: 'danger',  label: 'Vencido' },
};

const cfg = computed(() => props.estado
  ? estadoMap[props.estado]
  : props.semaforo ? semaforoMap[props.semaforo]
  : { variant: 'neutral' as const, label: '—' });
</script>

<template>
  <BaseBadge :variant="cfg.variant" dot>{{ cfg.label }}</BaseBadge>
</template>
