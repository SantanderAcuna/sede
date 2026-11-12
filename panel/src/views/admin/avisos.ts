/**
 * Aviso que una pantalla deja para la siguiente (p. ej. «Noticia eliminada» al volver a la
 * lista). Vive en memoria: se muestra una vez y se descarta; recargar la página lo borra.
 */
let pendiente: string | null = null

export function dejarAviso(texto: string): void {
  pendiente = texto
}

export function tomarAviso(): string | null {
  const texto = pendiente
  pendiente = null
  return texto
}
