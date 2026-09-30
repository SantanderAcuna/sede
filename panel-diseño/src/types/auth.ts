export type Permiso =
  | 'pqrsd.read' | 'pqrsd.write' | 'pqrsd.assign' | 'pqrsd.respond'
  | 'tramites.read' | 'tramites.write'
  | 'cms.read' | 'cms.write' | 'cms.publish'
  | 'usuarios.read' | 'usuarios.write'
  | 'auditoria.read' | 'reportes.read'
  | 'config.read' | 'config.write'
  | 'admin.full';

export type RolNombre = 'SuperAdmin' | 'Coordinador' | 'Editor' | 'Funcionario' | 'Lectura';

export interface Rol { id: string; nombre: RolNombre; permisos: Permiso[]; }

export interface User {
  id: string;
  documento: string;
  nombre: string;
  email: string;
  dependencia: string;
  roles: Rol[];
  mfaEnabled: boolean;
  activo: boolean;
}

export interface LoginPayload { username: string; password: string; }

export interface MfaChallenge {
  type: 'mfa-required';
  challengeId: string;
  method: 'totp' | 'sms';
}

export interface LoginResponse {
  type: 'session';
  token: string;
  refreshToken: string;
  expiresIn: number;
  user: User;
}
