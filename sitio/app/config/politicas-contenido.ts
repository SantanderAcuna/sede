/**
 * El contenido de las cinco políticas (T-20, H-21).
 *
 * **Aquí NO hay texto legal, y es a propósito.** Las políticas son obligaciones
 * jurídicas de la Entidad: las redacta y las adopta por acto administrativo
 * Jurídica —y, para datos personales, el Oficial de Protección de Datos—. TIC no
 * las escribe; TIC les da el sitio donde se publican, con versión, vigencia y acto,
 * y con una prueba que impide publicar una a medias.
 *
 * Una política vale `null` hasta que se entrega su documento. Mientras sea
 * `null` la página dice que está en preparación y el `sitemap.xml` no la anuncia:
 * `publicada` se **deriva** de aquí, no se escribe a mano (regresión R-03: el
 * mapa del sitio ya anunció páginas vacías una vez).
 *
 * Para publicar una política, se sustituye su `null` por un `DocumentoPolitica`
 * completo. `tests/politicas-contenido.test.ts` falla si falta un componente
 * mínimo, el acto administrativo, la versión o la vigencia.
 */

/** Una sección del documento: el `id` es el componente mínimo que cubre. */
export interface SeccionPolitica {
  id: string
  titulo: string
  parrafos: string[]
}

/** Lo que Jurídica entrega para una política. */
export interface DocumentoPolitica {
  /**
   * Versión entera, creciente. En la política de cookies **es** `VERSION_POLITICA`
   * (`useConsentimientoCookies`): subir una sin la otra deja a la ciudadanía con un
   * consentimiento dado a un texto que ya no existe (AC-20.8).
   */
  version: number
  /** Fecha de entrada en vigor, `AAAA-MM-DD`. */
  vigenteDesde: string
  /** El acto administrativo que adopta la política (AC-20.6). */
  acto: { tipo: string; numero: string; fecha: string }
  secciones: SeccionPolitica[]
}

/**
 * Los componentes mínimos de cada política: la lista de comprobación contra la que
 * se verifica el documento antes de publicarlo.
 *
 * **Confirmar con Jurídica.** El expediente habla de «6 componentes mínimos» para los
 * términos y condiciones pero no los enumera uno a uno; aquí van los siete que
 * enumera la auditoría (AC-20.2). Los de privacidad son los de AC-20.3, los de
 * derechos de autor salen de RF-B2-010, y los de cookies del propósito que la
 * página ya declara. Si Jurídica cambia la lista, se cambia aquí y la prueba la
 * aplica.
 */
export const COMPONENTES_MINIMOS: Record<string, { id: string; rotulo: string }[]> = {
  'terminos-y-condiciones-de-uso': [
    { id: 'condiciones-y-alcance', rotulo: 'Condiciones y alcance de uso' },
    { id: 'limites-de-uso', rotulo: 'Límites de uso' },
    { id: 'derechos-del-usuario', rotulo: 'Derechos del usuario' },
    { id: 'deberes-del-usuario', rotulo: 'Deberes del usuario' },
    { id: 'responsabilidad-de-la-entidad', rotulo: 'Responsabilidad de la Entidad' },
    { id: 'contacto', rotulo: 'Contacto' },
    { id: 'privacidad-y-seguridad', rotulo: 'Referencia a la política de privacidad y seguridad' },
  ],
  'seguridad-y-privacidad': [
    { id: 'categorias-de-datos', rotulo: 'Categorías de datos' },
    { id: 'finalidades', rotulo: 'Finalidades' },
    { id: 'derechos-arco', rotulo: 'Derechos de acceso, rectificación, supresión y revocatoria' },
    { id: 'responsable', rotulo: 'Contacto del responsable' },
    { id: 'reclamos', rotulo: 'Mecanismo de reclamo' },
  ],
  'proteccion-y-tratamiento-de-datos-personales': [
    { id: 'categorias-de-datos', rotulo: 'Categorías de datos' },
    { id: 'finalidades', rotulo: 'Finalidades' },
    { id: 'derechos-arco', rotulo: 'Derechos de acceso, rectificación, supresión y revocatoria' },
    { id: 'responsable', rotulo: 'Contacto del responsable' },
    { id: 'reclamos', rotulo: 'Mecanismo de reclamo' },
  ],
  'uso-de-cookies': [
    { id: 'cookies-que-usa', rotulo: 'Qué cookies usa el sitio' },
    { id: 'finalidad', rotulo: 'Finalidad de cada una' },
    { id: 'duracion', rotulo: 'Duración' },
    { id: 'consentimiento', rotulo: 'Cómo se administra el consentimiento' },
  ],
  'derechos-de-autor-y-uso-sobre-contenidos': [
    { id: 'titularidad', rotulo: 'Titularidad de los contenidos' },
    { id: 'reutilizacion', rotulo: 'Condiciones de reutilización' },
    { id: 'licencia', rotulo: 'Licencia que los acompaña' },
    { id: 'como-citar', rotulo: 'Cómo citarlos' },
  ],
}

/**
 * Los documentos entregados. Todos `null`: ninguna política tiene aún su texto
 * adoptado (D-4 sin resolver: faltan los responsables y la fecha de entrega).
 */
export const DOCUMENTOS_POLITICAS: Record<string, DocumentoPolitica | null> = {
  'terminos-y-condiciones-de-uso': null,
  'seguridad-y-privacidad': null,
  'proteccion-y-tratamiento-de-datos-personales': null,
  'uso-de-cookies': null,
  'derechos-de-autor-y-uso-sobre-contenidos': null,
}

/** ¿Tiene esta política su documento publicado? */
export function politicaPublicada(slug: string): boolean {
  return (DOCUMENTOS_POLITICAS[slug] ?? null) !== null
}

const FECHA = /^\d{4}-\d{2}-\d{2}$/
const EN_PREPARACION = /en preparaci[oó]n/i

/**
 * Qué le falta a un documento para poder publicarse. Lista vacía = completo.
 *
 * Es una función pura y no sólo una prueba para poder ejercitarla con documentos
 * de ejemplo —buenos y malos— y comprobar que de verdad detecta lo que dice
 * detectar, antes de que llegue el primer documento real.
 */
export function problemasDelDocumento(slug: string, documento: DocumentoPolitica): string[] {
  const problemas: string[] = []

  if (!Number.isInteger(documento.version) || documento.version < 1) problemas.push('versión inválida')
  if (!FECHA.test(documento.vigenteDesde)) problemas.push('vigencia sin fecha AAAA-MM-DD')
  if (documento.acto.tipo.trim() === '') problemas.push('acto sin tipo')
  if (documento.acto.numero.trim() === '') problemas.push('acto sin número')
  if (!FECHA.test(documento.acto.fecha)) problemas.push('acto sin fecha AAAA-MM-DD')

  const porId = new Map(documento.secciones.map((seccion) => [seccion.id, seccion]))
  for (const componente of COMPONENTES_MINIMOS[slug] ?? []) {
    const seccion = porId.get(componente.id)
    if (!seccion) problemas.push(`falta «${componente.rotulo}»`)
    else if (seccion.titulo.trim() === '' || seccion.parrafos.join(' ').trim() === '') {
      problemas.push(`«${componente.rotulo}» está vacío`)
    }
  }

  const texto = documento.secciones.flatMap((seccion) => [seccion.titulo, ...seccion.parrafos]).join('\n')
  if (EN_PREPARACION.test(texto)) problemas.push('contiene «en preparación»')

  return problemas
}
