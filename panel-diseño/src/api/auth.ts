import { client } from './client';
import type { LoginPayload, LoginResponse, MfaChallenge, User } from '@/types/auth';

export const authApi = {
  login: (payload: LoginPayload) =>
    client.post<LoginResponse | MfaChallenge>('/auth/login', payload).then((r) => r.data),
  verifyMfa: (challengeId: string, code: string) =>
    client.post<LoginResponse>('/auth/mfa/verify', { challengeId, code }).then((r) => r.data),
  logout: () => client.post('/auth/logout'),
  me: () => client.get<User>('/auth/me').then((r) => r.data),
};
