<script setup lang="ts">
/**
 * Insignia de estado de un caso.
 *
 * Los estados viven **aquí** y no en un archivo de tipos de dominio: el panel
 * genera sus tipos desde `contract/openapi.yaml`, y declarar además un modelo
 * a mano crearía dos fuentes de verdad que se separan con el primer cambio.
 * Cuando el contrato modele PQRSD, estas uniones se sustituyen por el tipo
 * generado y este archivo sólo conserva la correspondencia con la insignia.
 */
import { computed } from 'vue'

import BaseBadge from '@/components/base/BaseBadge.vue'

type EstadoCaso = 'recibida' | 'asignada' | 'en-tramite' | 'respondida' | 'cerrada' | 'vencida'
type SemaforoPlazo = 'verde' | 'amarillo' | 'rojo' | 'vencido'

type Variante = 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'gov'

const props = defineProps<{ estado?: EstadoCaso; semaforo?: SemaforoPlazo }>()

const porEstado: Record<EstadoCaso, { variant: Variante; label: string }> = {
  recibida: { variant: 'gov', label: 'Recibida' },
  asignada: { variant: 'info', label: 'Asignada' },
  'en-tramite': { variant: 'warning', label: 'En trámite' },
  respondida: { variant: 'success', label: 'Respondida' },
  cerrada: { variant: 'neutral', label: 'Cerrada' },
  vencida: { variant: 'danger', label: 'Vencida' },
}

const porSemaforo: Record<SemaforoPlazo, { variant: Variante; label: string }> = {
  verde: { variant: 'success', label: 'A tiempo' },
  amarillo: { variant: 'warning', label: 'Por vencer' },
  rojo: { variant: 'danger', label: 'Crítico' },
  vencido: { variant: 'danger', label: 'Vencido' },
}

const configuracion = computed(() => {
  if (props.estado) return porEstado[props.estado]
  if (props.semaforo) return porSemaforo[props.semaforo]
  return { variant: 'neutral' as const, label: '—' }
})
</script>

<template>
  <BaseBadge :variant="configuracion.variant" dot>{{ configuracion.label }}</BaseBadge>
</template>
