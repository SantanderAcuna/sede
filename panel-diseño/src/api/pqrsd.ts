import { client, type Page } from './client';
import type { Pqrsd, PqrsdEstado, PqrsdTipo } from '@/types/pqrsd';

export interface PqrsdListParams {
  page?: number; pageSize?: number;
  search?: string; estado?: PqrsdEstado; tipo?: PqrsdTipo;
  dependencia?: string; from?: string; to?: string;
}

export const pqrsdApi = {
  list: (params: PqrsdListParams) =>
    client.get<Page<Pqrsd>>('/pqrsd', { params }).then((r) => r.data),
  get: (id: string) => client.get<Pqrsd>(`/pqrsd/${id}`).then((r) => r.data),
  create: (payload: Partial<Pqrsd>) => client.post<Pqrsd>('/pqrsd', payload).then((r) => r.data),
  asignar: (id: string, funcionarioId: string) =>
    client.patch<Pqrsd>(`/pqrsd/${id}/asignar`, { funcionarioId }).then((r) => r.data),
  responder: (id: string, payload: { mensaje: string; adjuntos?: string[] }) =>
    client.post<Pqrsd>(`/pqrsd/${id}/responder`, payload).then((r) => r.data),
};
