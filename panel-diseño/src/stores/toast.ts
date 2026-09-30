import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'warning' | 'info';
export interface ToastItem { id: number; type: ToastType; message: string; }

let nextId = 1;

export const useToastStore = defineStore('toast', () => {
  const items = ref<ToastItem[]>([]);
  function push(type: ToastType, message: string, ttl = 4000) {
    const id = nextId++;
    items.value.push({ id, type, message });
    setTimeout(() => dismiss(id), ttl);
  }
  function dismiss(id: number) { items.value = items.value.filter(t => t.id !== id); }
  return { items, push, dismiss };
});
