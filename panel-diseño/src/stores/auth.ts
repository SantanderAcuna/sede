import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authApi } from '@/api/auth';
import type { User, Permiso, LoginPayload, MfaChallenge } from '@/types/auth';

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null);
  const refreshToken = ref<string | null>(null);
  const user = ref<User | null>(null);
  const mfaChallenge = ref<MfaChallenge | null>(null);

  const isAuthenticated = computed(() => Boolean(token.value && user.value));
  const permisos = computed<Set<Permiso>>(
    () => new Set(user.value?.roles.flatMap(r => r.permisos) ?? [])
  );
  const can = (p: Permiso) => permisos.value.has('admin.full') || permisos.value.has(p);

  async function login(payload: LoginPayload) {
    const resp = await authApi.login(payload);
    if (resp.type === 'mfa-required') {
      mfaChallenge.value = resp;
      return resp;
    }
    setSession(resp);
    return resp;
  }

  async function verifyMfa(code: string) {
    if (!mfaChallenge.value) throw new Error('Sin reto MFA activo');
    const resp = await authApi.verifyMfa(mfaChallenge.value.challengeId, code);
    setSession(resp);
    return resp;
  }

  function setSession(resp: { token: string; refreshToken: string; user: User }) {
    token.value = resp.token;
    refreshToken.value = resp.refreshToken;
    user.value = resp.user;
    mfaChallenge.value = null;
  }

  function logout() {
    token.value = null;
    refreshToken.value = null;
    user.value = null;
    mfaChallenge.value = null;
  }

  return { token, refreshToken, user, mfaChallenge, isAuthenticated, permisos, can, login, verifyMfa, logout };
}, { persist: { paths: ['token', 'refreshToken', 'user'] } });
