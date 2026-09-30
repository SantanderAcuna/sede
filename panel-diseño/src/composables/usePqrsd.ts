import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query';
import { computed, type MaybeRefOrGetter, toValue } from 'vue';
import { pqrsdApi, type PqrsdListParams } from '@/api/pqrsd';

export function usePqrsdList(params: MaybeRefOrGetter<PqrsdListParams>) {
  return useQuery({
    queryKey: ['pqrsd', 'list', computed(() => toValue(params))],
    queryFn: () => pqrsdApi.list(toValue(params)),
    placeholderData: prev => prev,
  });
}

export function usePqrsdDetail(id: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: ['pqrsd', 'detail', computed(() => toValue(id))],
    queryFn: () => pqrsdApi.get(toValue(id)),
    enabled: computed(() => Boolean(toValue(id))),
  });
}

export function useAsignarPqrsd() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, funcionarioId }: { id: string; funcionarioId: string }) =>
      pqrsdApi.asignar(id, funcionarioId),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['pqrsd'] }),
  });
}
