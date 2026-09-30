import { describe, it, expect } from 'vitest';
import { loginSchema, mfaSchema, pqrsdCreateSchema } from '@/schemas';

describe('schemas Yup', () => {
  it('loginSchema valida usuario y password', async () => {
    await expect(loginSchema.validate({ username: 'admin', password: 'secret123' })).resolves.toBeTruthy();
    await expect(loginSchema.validate({ username: 'a', password: '123' })).rejects.toThrow();
  });

  it('mfaSchema acepta solo 6 dígitos', async () => {
    await expect(mfaSchema.validate({ code: '123456' })).resolves.toBeTruthy();
    await expect(mfaSchema.validate({ code: '12345' })).rejects.toThrow();
    await expect(mfaSchema.validate({ code: 'abcdef' })).rejects.toThrow();
  });

  it('pqrsdCreateSchema valida estructura completa', async () => {
    await expect(
      pqrsdCreateSchema.validate({
        tipo: 'peticion',
        asunto: 'Reparación de vía en barrio Pescaíto',
        descripcion: 'La vía principal del barrio presenta huecos profundos…',
        ciudadano: { documento: '12345678', nombre: 'Juan Pérez', email: 'juan@x.com', telefono: '3001234567' },
        dependencia: 'INFRAESTRUCTURA',
        canal: 'web',
      })
    ).resolves.toBeTruthy();
  });
});
