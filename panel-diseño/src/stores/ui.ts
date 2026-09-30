import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
  const sidebarCollapsed = ref(false);
  const sidebarGroupsOpen = ref<Record<string, boolean>>({});
  const theme = ref<'light' | 'dark'>('light');
  const brand = ref<'govco' | 'caribe' | 'dorado'>('govco');
  const density = ref<'compacto' | 'comodo'>('comodo');

  function toggleSidebar() { sidebarCollapsed.value = !sidebarCollapsed.value; }
  function toggleGroup(name: string) {
    sidebarGroupsOpen.value[name] = !(sidebarGroupsOpen.value[name] ?? true);
  }
  return { sidebarCollapsed, sidebarGroupsOpen, theme, brand, density, toggleSidebar, toggleGroup };
}, { persist: true });
