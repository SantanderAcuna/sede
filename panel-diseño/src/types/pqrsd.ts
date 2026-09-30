export type PqrsdTipo = 'peticion' | 'queja' | 'reclamo' | 'sugerencia' | 'denuncia' | 'felicitacion';
export type PqrsdEstado = 'recibida' | 'asignada' | 'en-tramite' | 'respondida' | 'cerrada' | 'vencida';
export type PqrsdCanal = 'web' | 'presencial' | 'telefono' | 'email' | 'redes';
export type PqrsdSemaforo = 'verde' | 'amarillo' | 'rojo' | 'vencido';

export interface Pqrsd {
  id: string;
  radicado: string;
  tipo: PqrsdTipo;
  asunto: string;
  descripcion: string;
  ciudadano: { documento: string; nombre: string; email: string; telefono?: string; };
  canal: PqrsdCanal;
  dependencia: string;
  asignadoA?: string;
  estado: PqrsdEstado;
  semaforo: PqrsdSemaforo;
  diasRestantes: number;
  fechaRecepcion: string;
  fechaVencimiento: string;
  fechaRespuesta?: string;
  adjuntos?: { nombre: string; url: string; size: number }[];
  trazabilidad: TrazaEvento[];
}

export interface TrazaEvento {
  id: string;
  fecha: string;
  actor: string;
  accion: string;
  detalle?: string;
}
