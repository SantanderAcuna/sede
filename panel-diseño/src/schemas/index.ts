import * as yup from 'yup';

const soloDigitos = /^\d+$/;

export const cedulaValidator = yup
  .string()
  .required('La cédula es obligatoria')
  .matches(soloDigitos, 'Solo dígitos')
  .min(6, 'Mínimo 6 dígitos')
  .max(12, 'Máximo 12 dígitos');

export const emailValidator = yup.string().required('El correo es obligatorio').email('Correo inválido');

export const loginSchema = yup.object({
  username: yup.string().required('Usuario requerido').min(3, 'Mínimo 3 caracteres'),
  password: yup.string().required('Contraseña requerida').min(8, 'Mínimo 8 caracteres'),
});

export const mfaSchema = yup.object({
  code: yup
    .string()
    .required('Código requerido')
    .matches(/^\d{6}$/, 'Debe ser un código de 6 dígitos'),
});

export const pqrsdCreateSchema = yup.object({
  tipo: yup
    .mixed<'peticion' | 'queja' | 'reclamo' | 'sugerencia' | 'denuncia' | 'felicitacion'>()
    .oneOf(['peticion', 'queja', 'reclamo', 'sugerencia', 'denuncia', 'felicitacion'])
    .required('Seleccione el tipo'),
  asunto: yup.string().required('Asunto requerido').min(10, 'Mínimo 10 caracteres').max(200),
  descripcion: yup.string().required('Descripción requerida').min(20, 'Mínimo 20 caracteres'),
  ciudadano: yup.object({
    documento: cedulaValidator,
    nombre: yup.string().required('Nombre requerido').min(3),
    email: emailValidator,
    telefono: yup.string().matches(/^[+]?[\d\s-]{7,15}$/, { message: 'Teléfono inválido', excludeEmptyString: true }),
  }),
  dependencia: yup.string().required('Seleccione dependencia'),
  canal: yup
    .mixed<'web' | 'presencial' | 'telefono' | 'email' | 'redes'>()
    .oneOf(['web', 'presencial', 'telefono', 'email', 'redes'])
    .required(),
});

export const usuarioSchema = yup.object({
  documento: cedulaValidator,
  nombre: yup.string().required('Nombre requerido').min(3),
  email: emailValidator,
  dependencia: yup.string().required('Seleccione dependencia'),
  roles: yup.array(yup.string().required()).min(1, 'Asigne al menos un rol'),
  mfaEnabled: yup.boolean().default(true),
});

export type LoginInput = yup.InferType<typeof loginSchema>;
export type MfaInput = yup.InferType<typeof mfaSchema>;
export type PqrsdCreateInput = yup.InferType<typeof pqrsdCreateSchema>;
export type UsuarioInput = yup.InferType<typeof usuarioSchema>;
