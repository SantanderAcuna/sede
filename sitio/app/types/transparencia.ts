/**
 * Inventario de la sección de Transparencia y acceso a la información pública.
 *
 * **Por qué este módulo existe.** La Ley 1712 de 2014 obliga a todo sujeto
 * obligado a publicar de manera proactiva un conjunto mínimo de información, y
 * la Resolución MinTIC 1519 de 2020 —que derogó la 3564 de 2015, que es la que
 * el sitio anterior todavía cita— fija en su Anexo 2 §4.1.2 las categorías con
 * las que esa información se organiza. Este archivo declara **la estructura
 * completa de las nueve categorías** y, con ella, el inventario de los
 * documentos que la Entidad tiene hoy publicados, tomados del rastreo de su
 * sitio. Es el único sitio del que la sección carga su contenido: cuando la
 * Entidad ratifique, corrija o amplíe lo que aquí consta, se cambia aquí y las
 * dos páginas de la sección —`/transparencia` y `/transparencia/{slug}`— quedan
 * al día sin tocarlas.
 *
 * **Qué es contenido y qué es arquitectura.** La estructura —las nueve
 * categorías, sus apartados, su encabezado, qué publica cada una y sus estados
 * vacíos— es arquitectura de información y por eso está construida entera. El
 * **contenido** son los documentos: sus títulos y sus direcciones salen del
 * rastreo y **no se completan con nada**. Ninguna descripción, ningún resumen,
 * ningún dato de contacto y ninguna cifra se escribe aquí si no está en la
 * fuente; cuando algo falta, se declara ausente. En una sede electrónica un
 * documento inventado no es un hueco en una maqueta: es información oficial
 * falsa sobre la que el ciudadano decide y el auditor dictamina.
 *
 * **La numeración del origen no se reproduce.** La del sitio anterior está rota
 * —no publica los ítems 1.8, 1.9, 1.14 ni 6.1, repite tres veces el número 3.2,
 * y anida 4.7, 4.8 y 4.9 dentro de 4.6—, así que cada apartado se enuncia por
 * su nombre y las nueve categorías se ordenan por el número que les da la
 * norma. Los defectos concretos de cada categoría se declaran en `notaOrigen`,
 * a la vista, porque el ciudadano y el auditor tienen derecho a saber de dónde
 * sale lo que se publica y en qué estado está la fuente.
 *
 * **La fecha de publicación es obligatoria y aquí no se inventa.** El numeral
 * 2.4.1.e de la Resolución 1519 de 2020 exige que todo documento indique la
 * fecha de su publicación, y el Anexo 2 §4.1.2.1 que el listado vaya del más
 * reciente al más antiguo. El rastreo del sitio de la Entidad **no encontró una
 * sola fecha declarada** en ninguna de sus páginas, así que ningún documento de
 * este inventario la lleva: la ficha de cada uno dice «no consta en la fuente»,
 * que es la verdad, en vez de una fecha aproximada, que sería una invención. El
 * campo `fechaPublicacion` está declarado y el ordenador de `ordenarPorFecha`
 * ya está escrito y probado contra él: en cuanto la Entidad declare las fechas,
 * se escriben aquí y la sección se ordena sola como la norma manda.
 *
 * **La trampa de los datos abiertos.** En el material de investigación del
 * proyecto hay cuatro volcados del portal federado —`ds_Alcaldía_Distrital_de_
 * Santa_Marta.json` y sus hermanos— cuyos nombres prometen los datos abiertos
 * de la Entidad. **No lo son.** Verificados uno por uno: el que se llama
 * «Alcaldía Distrital de Santa Marta» devuelve 24 conjuntos de la Alcaldía de
 * Barranquilla y 7 de Santa Rosa de Cabal, y ninguno de Santa Marta; el que se
 * llama «Alcaldía de Santa Marta» no devuelve ninguno del Distrito; y el que se
 * llama «Santa Marta DTCH» viene vacío. Publicarlos habría sido publicar datos
 * de otro municipio como si fueran del Distrito. Por eso la categoría 7 se
 * construye con su estructura y su estado declarado, y **no** con esos ficheros.
 *
 * **Este módulo no importa nada y no toca la red.** Es datos y tipos. Las dos
 * páginas que lo leen —`/transparencia` y `/transparencia/{slug}`— hacen con él
 * lo que la norma pide: publicarlo, contarlo, buscarlo y declarar lo que falta.
 */

// ---------------------------------------------------------------------------
// Tipos
// ---------------------------------------------------------------------------

/**
 * El índice de las nueve categorías se declara en su propio módulo, ligero,
 * porque la disposición del sitio lo necesita para la miga de pan y no tiene
 * por qué cargar los 334 documentos del inventario para leer nueve rótulos. Se
 * reexporta desde aquí para que quien consuma la sección tenga una sola puerta
 * de entrada.
 */
import { CATEGORIAS_INDICE, type SlugCategoria } from './transparencia-indice'

export {
  CATEGORIAS_INDICE,
  ORDEN_CATEGORIAS,
  rutaDeCategoria,
  type CategoriaIndice,
  type SlugCategoria,
} from './transparencia-indice'


/**
 * De dónde se tomó un documento.
 *
 * La procedencia se publica a la vista en la ficha de cada documento. Un dato
 * del que no se dice de dónde sale parece un dato de la fuente, y no lo es: es
 * un dato del rastreo de un sitio que se está sustituyendo.
 */
export interface FuenteRastreo {
  /** Nombre legible de la fuente. */
  nombre: string
  /** Dirección exacta de la página de la que se tomó. */
  url: string
  /** Día del rastreo, en formato ISO (aaaa-mm-dd). */
  rastreado: string
}

/**
 * Un documento publicado en la sección.
 *
 * Un documento es un destino: un PDF, un archivo de hoja de cálculo, una página
 * del sitio de la Entidad o un sistema externo al que la Entidad remite. Aquí
 * se guarda lo que la fuente declara —su título y su dirección— y nada más.
 */
export interface DocumentoTransparencia {
  /** Rótulo con el que la fuente publica el documento. */
  titulo: string
  /** Destino absoluto. Los relativos del origen se resuelven contra su dominio. */
  url: string
  /**
   * Apartado del origen del que cuelga el documento, tal como lo rotula la
   * fuente. Es parte de la procedencia: dice en qué punto del índice anterior
   * estaba publicado.
   */
  apartadoOrigen: string
  /**
   * Fecha de publicación en formato ISO (aaaa-mm-dd).
   *
   * **Su ausencia es un dato, no un olvido.** El numeral 2.4.1.e de la
   * Resolución 1519 de 2020 exige que todo documento indique la fecha de su
   * publicación; ninguna de las páginas rastreadas de la Entidad la declara, y
   * la Sede no la inventa: cuando el campo falta, la ficha dice «no consta en la
   * fuente». Escribir aquí una fecha la convertiría en un dato oficial.
   */
  fechaPublicacion?: string
  /**
   * Aviso sobre el destino del enlace, cuando el propio enlace del origen dice
   * algo que el ciudadano necesita saber antes de pulsarlo: que lleva a otro
   * municipio, a otro nivel de gobierno, a un sistema sin filtrar por entidad o
   * a un documento que no corresponde al apartado. No es una opinión sobre la
   * Entidad: es una descripción del destino, verificable en su dirección.
   */
  aviso?: string
}

/** Un grupo de documentos dentro de un apartado. */
export interface GrupoTransparencia {
  /**
   * Encabezado del grupo, si la fuente lo declara. Es `null` cuando los
   * documentos cuelgan directamente del apartado y no hay nada que los agrupe:
   * un rótulo inventado para una lista suelta sería información de más.
   */
  titulo: string | null
  documentos: readonly DocumentoTransparencia[]
}

/** Un apartado de una categoría: lo que la norma manda publicar. */
export interface ApartadoTransparencia {
  /** Nombre del apartado, tal como lo enuncia el índice de la norma. */
  titulo: string
  /**
   * Qué se publica en este apartado cuando la Entidad no ha publicado nada.
   * Es la razón por la que el apartado existe, no una excusa por su vacío, y
   * sólo se declara en los apartados que hoy están vacíos.
   */
  faltante?: string
  grupos: readonly GrupoTransparencia[]
}

/** El portal federado y cómo hay que consultarlo. */
export interface FuenteFederada {
  portal: string
  url: string
  instruccion: string
}

/**
 * El estado de una categoría.
 *
 * Se distinguen cuatro y no dos porque cada uno dice una cosa distinta al
 * ciudadano: `publicada` es que la Entidad publica lo que la norma pide;
 * `parcial` es que publica algo pero no todo, y `motivo` dice qué falta;
 * `declarada` es que la categoría está construida y su fuente identificada pero
 * no hay nada que publicar todavía; y `ausente` es que la Entidad no la publica
 * en absoluto. Confundir los cuatro es lo que hace que una sección incompleta
 * parezca completa.
 */
export type EstadoCategoria = 'publicada' | 'parcial' | 'declarada' | 'ausente'

/** Una de las nueve categorías de información mínima obligatoria. */
export interface CategoriaTransparencia {
  /** Orden que fija la norma: de 1 a 9. */
  numero: number
  /** Segmento de ruta de la categoría dentro de la sección. */
  slug: SlugCategoria
  /** Nombre de la categoría, tal como lo enuncia la norma. */
  nombre: string
  /**
   * Qué publica la categoría. Se compone de los apartados que la norma enumera
   * para ella: no añade ninguna afirmación sobre la Entidad que no esté ya en
   * el nombre de sus apartados.
   */
  publica: string
  /** Norma que ordena la categoría, para poder citarla. */
  base: string
  estado: EstadoCategoria
  /** Qué falta y por qué, cuando el estado no es `publicada`. */
  motivo?: string
  /** Defecto comprobado del origen, cuando lo hay. */
  notaOrigen?: string
  /** Ruta de esta misma Sede que cubre la categoría, si ya existe. */
  rutaSede?: string
  /** Rótulo del enlace a esa ruta. */
  rutaSedeTexto?: string
  /** Portal federado que hay que consultar, cuando la categoría lo tiene. */
  federada?: FuenteFederada
  apartados: readonly ApartadoTransparencia[]
}

// ---------------------------------------------------------------------------
// Constantes normativas
// ---------------------------------------------------------------------------

/**
 * La norma que rige la publicación de esta sección **hoy**.
 *
 * El sitio de la Entidad todavía cita la Resolución 3564 de 2015, que la 1519
 * derogó en su artículo 8. Una sede electrónica no puede remitir a una norma
 * derogada: se cita la vigente y se dice cuál sustituye, para que quien llegue
 * con la referencia antigua entienda por qué no la encuentra.
 */
export const NORMA_VIGENTE =
  'Resolución 1519 de 2020 del Ministerio de Tecnologías de la Información y las Comunicaciones'

/** La norma que la anterior derogó, y que el sitio de la Entidad aún cita. */
export const NORMA_DEROGADA =
  'Resolución 3564 de 2015 del Ministerio de Tecnologías de la Información y las Comunicaciones'

/** De dónde salió el inventario de documentos de esta sección. */
export const FUENTE_ORIGEN: FuenteRastreo = {
  nombre: 'Sitio anterior de la Alcaldía Distrital de Santa Marta',
  url: 'https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica',
  rastreado: '2026-09-30',
}

// ---------------------------------------------------------------------------
// Inventario
// ---------------------------------------------------------------------------
export const CATEGORIAS: readonly CategoriaTransparencia[] = [
  {
    ...CATEGORIAS_INDICE['informacion-de-las-entidades'],
    publica:
      'La Entidad por dentro: estructura orgánica y funciones, mapas y cartas descriptivas de los procesos, directorios —institucional, de servidores públicos, de entidades y de agremiaciones—, los mecanismos para presentar solicitudes, quejas y reclamos, el calendario de actividades, las preguntas frecuentes, las autoridades que la vigilan, la carta de trato digno y el Modelo Integrado de Planeación y Gestión.',
    base: 'Ley 1712 de 2014, artículo 9; Resolución 1519 de 2020, Anexo 2, §4.1.2.',
    estado: 'publicada',
    notaOrigen: 'El origen numera esta categoría de 1.1 a 1.16, pero no publica los ítems 1.8, 1.9 ni 1.14, de modo que la serie salta. La Sede no reproduce esa numeración ni rellena los huecos: enuncia cada apartado por su nombre.',
    apartados: [
      {
        titulo: 'Misión, visión, funciones y deberes',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Misión y Visión',
                url: 'https://www.santamarta.gov.co/nuestra-alcaldia-distrital',
                apartadoOrigen: 'Misión, visión, funciones y deberes',
              },
              {
                titulo: 'Funciones',
                url: 'https://www.santamarta.gov.co/funciones',
                apartadoOrigen: 'Misión, visión, funciones y deberes',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Estructura orgánica - Organigrama',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Estructura orgánica - Organigrama',
                url: 'https://www.santamarta.gov.co/organigrama',
                apartadoOrigen: 'Estructura orgánica - Organigrama',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Mapas y Cartas descriptivas de los procesos',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Sistema Integrado de Gestion Procesos y Procedimientos',
                url: 'https://www.santamarta.gov.co/sistema-integrado-de-gestion',
                apartadoOrigen: 'Mapas y Cartas descriptivas de los procesos',
              },
              {
                titulo: 'Mapa de Procesos',
                url: 'https://www.santamarta.gov.co/documentos/mapa-de-procesos-alcaldia-distrital-de-santa-marta',
                apartadoOrigen: 'Mapas y Cartas descriptivas de los procesos',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Directorio Institucional',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Datos de contacto',
                url: 'https://www.santamarta.gov.co/localizacion-fisica-sucursales-horarios-y-dias-de-atencion-al-publico',
                apartadoOrigen: 'Directorio Institucional',
              },
              {
                titulo: 'Puntos de Atención al Ciudadano',
                url: 'https://www.santamarta.gov.co/puntos-atencion-al-ciudadano',
                apartadoOrigen: 'Directorio Institucional',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Directorio de servidores públicos, empleados o contratistas',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Directorio de servidores públicos, empleados o contratistas',
                url: 'https://www.santamarta.gov.co/documentos/directorio-alcaldia-de-santa-marta',
                apartadoOrigen: 'Directorio de servidores públicos, empleados o contratistas',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Directorio de entidades',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Directorio de entidades',
                url: 'https://www.santamarta.gov.co/entidades',
                apartadoOrigen: 'Directorio de entidades',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Directorio de Agremiaciones o Asociaciones',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Directorio de Agremiaciones o Asociaciones',
                url: 'https://www.santamarta.gov.co/agremiaciones',
                apartadoOrigen: 'Directorio de Agremiaciones o Asociaciones',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Mecanismo de presentación directa de solicitudes, quejas y reclamos',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Contactenos',
                url: 'https://www.santamarta.gov.co/pqrsd',
                apartadoOrigen: 'Mecanismo de presentación directa de solicitudes, quejas y reclamos',
              },
              {
                titulo: 'Manual para presentar quejas y reclamos',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/MANUAL%20PQRSD%20(1).pdf',
                apartadoOrigen: 'Mecanismo de presentación directa de solicitudes, quejas y reclamos',
              },
              {
                titulo: 'Manual de atención de PQRSD',
                url: 'https://www.santamarta.gov.co/sites/default/files/Manual-de-atencion-de-PQRSD.pdf',
                apartadoOrigen: 'Mecanismo de presentación directa de solicitudes, quejas y reclamos',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Calendario de actividades y eventos',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Calendario de Actividades',
                url: 'https://www.santamarta.gov.co/calendario',
                apartadoOrigen: 'Calendario de actividades y eventos',
              },
              {
                titulo: 'Capacitaciones 2020',
                url: 'https://www.santamarta.gov.co/documentos/capacitaciones-2020-secretaria-general',
                apartadoOrigen: 'Calendario de actividades y eventos',
              },
              {
                titulo: 'Cronograma de Actividades Bienestar Social 2021',
                url: 'https://www.santamarta.gov.co/documentos/cronograma-de-actividades-bienestar-social-2021-secretaria-general-alcaldia-distrital-de',
                apartadoOrigen: 'Calendario de actividades y eventos',
              },
              {
                titulo: 'Cronograma de Capacitación de Bienestar Social - Vigencia 2021',
                url: 'https://www.santamarta.gov.co/documentos/cronograma-de-capacitacion-de-bienestar-social-vigencia-2021-secretaria-general-alcaldia',
                apartadoOrigen: 'Calendario de actividades y eventos',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Preguntas Frecuentes',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Preguntas Frecuentes',
                url: 'https://www.santamarta.gov.co/preguntas-frecuentes',
                apartadoOrigen: 'Preguntas Frecuentes',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Entes y autoridades que lo vigilan',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Entes y autoridades que lo vigilan',
                url: 'https://www.santamarta.gov.co/entes-de-control',
                apartadoOrigen: 'Entes y autoridades que lo vigilan',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Carta al trato digno',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Carta al trato digno',
                url: 'https://www.santamarta.gov.co/carta-al-trato-digno',
                apartadoOrigen: 'Carta al trato digno',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Modelo Integrado de Planeación y Gestión – MIPG',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Modelo Integrado de Planeación y Gestión – MIPG',
                url: 'https://www.santamarta.gov.co/sites/default/files/acta-001-primer-comite-mipg-2024.pdf',
                apartadoOrigen: 'Modelo Integrado de Planeación y Gestión – MIPG',
              },
              {
                titulo: 'Acta 002 Comité Institucional de Gestión y Desempeño MIPG 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/acta_002_comite_mipg_2024_.pdf',
                apartadoOrigen: 'Modelo Integrado de Planeación y Gestión – MIPG',
              },
              {
                titulo: 'Acta 003 Comité Institucional de Gestión y Desempeño MIPG 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/tercer-comite-institucional-de-gestion-y-desempeno-MIPG.pdf',
                apartadoOrigen: 'Modelo Integrado de Planeación y Gestión – MIPG',
              },
              {
                titulo: 'Acta 004 Comité Institucional de Gestión y Desempeño MIPG 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/cuarto-comite-institucional-de-gestion-y-desempeno-MIPG.pdf',
                apartadoOrigen: 'Modelo Integrado de Planeación y Gestión – MIPG',
              },
              {
                titulo: 'Acta 001 Comité Institucional de Gestión y Desempeño - MIPG 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/Acta_001_Comite_Institucional_Gestion_Desempeno_MIPG_2025.pdf',
                apartadoOrigen: 'Modelo Integrado de Planeación y Gestión – MIPG',
              },
              {
                titulo: 'Acta 002 Comité Institucional de Gestión y Desempeño - MIPG 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/Acta_002_Comite_Institucional_Gestion_Desempeno_MIPG_2025.pdf',
                apartadoOrigen: 'Modelo Integrado de Planeación y Gestión – MIPG',
              },
              {
                titulo: 'Acta 003 Comité Institucional de Gestión y Desempeño - MIPG 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/acta_003_comite_institucional_gestion_desempeno_mipg_2025.pdf',
                apartadoOrigen: 'Modelo Integrado de Planeación y Gestión – MIPG',
              },
              {
                titulo: 'Acta 004 Comité Institucional de Gestión y Desempeño - MIPG 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/acta_004_comite_institucional_gestion_desempeno_mipg_2025.pdf',
                apartadoOrigen: 'Modelo Integrado de Planeación y Gestión – MIPG',
              },
              {
                titulo: 'Acta 001 Comité Institucional de Gestión y Desempeño - MIPG 2026',
                url: 'https://www.santamarta.gov.co/sites/default/files/acta_001_comite_institucional_gestion_desempeno_mipg_2026.pdf',
                apartadoOrigen: 'Modelo Integrado de Planeación y Gestión – MIPG',
              },
            ],
          },
        ],
      },
    ],
  },
  {
    ...CATEGORIAS_INDICE['normativa'],
    publica:
      'Las normas que rigen a la Entidad y las que la Entidad expide: decretos, gaceta distrital, acuerdos, avisos públicos de licitación, resoluciones, edictos, notificaciones por aviso, actos administrativos, políticas, lineamientos y manuales, y los dos sistemas nacionales de consulta normativa, SUIN y SUCOP.',
    base: 'Ley 1712 de 2014, artículo 9, literal d); Resolución 1519 de 2020, Anexo 2, §4.1.2.',
    estado: 'publicada',
    notaOrigen: 'El origen cuelga «Políticas, lineamientos y manuales» de «Decretos», que no es su sitio, y salta del 2.1.1 al 2.1.5 sin publicar los ítems intermedios. La Sede separa ambos apartados y enuncia cada uno por su nombre. Su encabezado «Actos administrativos» no lleva a ninguna parte en el origen: allí el destino es un «#» y aquí se declara ausente en vez de copiarse.',
    apartados: [
      {
        titulo: 'Decretos',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Decretos',
                url: 'https://www.santamarta.gov.co/documentos?tid=Decretos&title=',
                apartadoOrigen: 'Decretos',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Decreto Único Reglamentario',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Decreto Único Reglamentario',
                url: 'https://www.santamarta.gov.co/sites/default/files/decreto_1083_de_2015_sector_de_funcion_publica.pdf',
                apartadoOrigen: 'Decreto Único Reglamentario',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Políticas, lineamientos y manuales',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Protocolo para la Prevención, Atención y Protección frente a todas las formas de Violencia contra las Mujeres y Basadas en Género',
                url: 'https://www.santamarta.gov.co/sites/default/files/protocolo_para_la_prevencion_atencion_y_proteccion_frente_a_todas_las_formas_de_violencia_contra_las_mujeres_y_basadas_en_genero.pdf',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Manual de Contratación 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/MANUAL%20DE%20CONTRATACION%202019.pdf',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Manual de Supervision 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/MANUAL%20SUPERVISION%20E%20INTERVENTOR%C3%8DA%202019.pdf',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Guía de implementación de la política de integridad',
                url: 'https://www.santamarta.gov.co/documentos/guia-de-implementacion-de-la-politica-de-integridad',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Código de integridad',
                url: 'https://www.santamarta.gov.co/sites/default/files/Codigo-de-integridad-2024.pdf',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Política de Administración del Riesgo',
                url: 'https://www.santamarta.gov.co/documentos/politica-de-administracion-del-riesgo',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Política de Seguridad y Salud en el Trabajo',
                url: 'https://www.santamarta.gov.co/sites/default/files/Politica_Seguridad_Salud_Trabajo.pdf',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Política de Prevención del Acoso Laboral, Sexual, Discriminación y Violencia LGBTIQ en el Entorno Laboral',
                url: 'https://www.santamarta.gov.co/sites/default/files/Politica_Prevencion_Acoso_Laboral_Sexual_Discriminacion_Violencia_LGBTIQ_Entorno_Laboral.pdf',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Política de Prevención y Control de Alcohol, Tabaco y Drogas',
                url: 'https://www.santamarta.gov.co/sites/default/files/Politica_Prevencion_Control_Alcohol_Tabaco_Drogas.pdf',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Política de Seguridad Vial',
                url: 'https://www.santamarta.gov.co/sites/default/files/Politica_Seguridad_Vial.pdf',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Política para el Control de Emergencias',
                url: 'https://www.santamarta.gov.co/sites/default/files/Politica_Control_Emergencias.pdf',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Reglamento de Higiene y Seguridad Industrial',
                url: 'https://www.santamarta.gov.co/sites/default/files/Reglamento_Higiene_Seguridad_Industrial.pdf',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Procedimiento de transferencia documental',
                url: 'https://www.santamarta.gov.co/documentos/procedimiento-de-transferencia-documental-gestion-documental-de-la-alcaldia-distrital',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Programa de Almacenacimiento y Realmacenamiento',
                url: 'https://www.santamarta.gov.co/documentos/programa-de-almacenacimiento-y-realmacenamiento-proceso-de-preservacion-largo-plazo',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Programa de capacitacion y sensibilizacion',
                url: 'https://www.santamarta.gov.co/documentos/programa-de-capacitacion-y-sensibilizacion-proceso-de-preservacion-largo-plazo-gestion',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Programa de inspección y mantenimiento',
                url: 'https://www.santamarta.gov.co/documentos/programa-de-inspeccion-y-mantenimiento-gestion-documental-de-la-alcaldia-distrital',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Programa de monitoreo y control de condiciones ambientales',
                url: 'https://www.santamarta.gov.co/documentos/programa-de-monitoreo-y-control-de-condiciones-ambientales-gestion-documental-de-la',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Programa de prevencion de emergencias y atención de desastres',
                url: 'https://www.santamarta.gov.co/documentos/programa-de-prevencion-de-emergencias-y-atencion-de-desastres-proceso-de-preservacion',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
              {
                titulo: 'Programa de saneamiento ambiental: limpieza, desinfección, desratización y desinfección',
                url: 'https://www.santamarta.gov.co/documentos/programa-de-saneamiento-ambiental-limpieza-desinfeccion-desratizacion-y-desinfeccion',
                apartadoOrigen: 'Políticas, lineamientos y manuales',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Gaceta Distrital',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Gaceta Distrital',
                url: 'https://www.santamarta.gov.co/documentos?tid=Gaceta&title=',
                apartadoOrigen: 'Gaceta Distrital',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Acuerdos',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Acuerdos',
                url: 'https://www.santamarta.gov.co/documentos?tid=Acuerdos&title=',
                apartadoOrigen: 'Acuerdos',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Aviso Público de licitación',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Aviso Público de licitación',
                url: 'https://www.santamarta.gov.co/documentos?tid=Aviso+P%C3%BAblico+de+Licitaci%C3%B3n&title=',
                apartadoOrigen: 'Aviso Público de licitación',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Resoluciones',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Resoluciones',
                url: 'https://www.santamarta.gov.co/documentos?tid=Resoluciones&title=',
                apartadoOrigen: 'Resoluciones',
              },
              {
                titulo: 'Resolución adopción Manual de contratación y supervisión 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/RESOLUCI%C3%92N%20ADOPTA%20MANUEL%20DE%20CONTRATACI%C3%92N%202019.pdf',
                apartadoOrigen: 'Resoluciones',
              },
              {
                titulo: 'Resolución No 942 del 03 de noviembre de 2020',
                url: 'https://www.santamarta.gov.co/documentos/resolucion-no-942-del-03-de-noviembre-de-2020',
                apartadoOrigen: 'Resoluciones',
              },
              {
                titulo: 'Resolución No 118 del 05 de Abril de 2021',
                url: 'https://www.santamarta.gov.co/documentos/resolucion-no-118-del-05-de-abril-de-2021',
                apartadoOrigen: 'Resoluciones',
              },
              {
                titulo: 'Resolución No 119 del 05 de abril de 2021',
                url: 'https://www.santamarta.gov.co/documentos/resolucion-no-119-del-05-de-abril-de-2021',
                apartadoOrigen: 'Resoluciones',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Edictos',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Edictos',
                url: 'https://www.santamarta.gov.co/documentos?tid=Edictos&title=',
                apartadoOrigen: 'Edictos',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Notificacióon por aviso',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Notificacióon por aviso',
                url: 'https://www.santamarta.gov.co/documentos?tid=Notificaci%C3%B3n+por+Aviso&title=',
                apartadoOrigen: 'Notificacióon por aviso',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Actos administrativos',
        faltante:
          'Los actos administrativos que la Entidad expide. El sitio anterior abre este apartado con un enlace que no lleva a ninguna parte —un «#»—, así que aquí no se copia ningún destino y el apartado se declara vacío en vez de fingir un contenido que no existe.',
        grupos: [
        ],
      },
      {
        titulo: 'Sistema Único de Información Normativa SUIN',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Sistema Único de Información Normativa SUIN',
                url: 'https://www.suin-juriscol.gov.co/legislacion/normatividad.html',
                apartadoOrigen: 'Sistema Único de Información Normativa SUIN',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Participación ciudadana en la expedición de normas a través el SUCOP',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Participación ciudadana en la expedición de normas a través el SUCOP',
                url: 'https://www.sucop.gov.co/',
                apartadoOrigen: 'Participación ciudadana en la expedición de normas a través el SUCOP',
              },
            ],
          },
        ],
      },
    ],
  },
  {
    ...CATEGORIAS_INDICE['contratacion'],
    publica:
      'La actividad contractual de la Entidad: el Plan Anual de Adquisiciones, la publicación de la información contractual, las convocatorias, las ofertas de empleo, la publicación de la ejecución de los contratos y el manual de contratación, adquisición y compras.',
    base: 'Ley 1712 de 2014, artículos 9, literales e) y f), y 10; Resolución 1519 de 2020, Anexo 2, §4.1.2.',
    estado: 'parcial',
    motivo: 'Cinco de los seis apartados enlazan sistemas externos y ninguno enlaza el Plan Anual de Adquisiciones de la vigencia en curso: el destino que el origen publica para ese apartado es el archivo de 2019, mientras que la categoría de planeación publica planes anuales de adquisiciones hasta 2026.',
    notaOrigen: 'El origen repite el mismo número —3.2— en tres apartados distintos.',
    apartados: [
      {
        titulo: 'Plan Anual de Adquisiciones',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Plan Anual de Adquisiciones',
                url: 'https://www.contratos.gov.co/consultas/consultarArchivosPAA2019.do',
                aviso: 'El origen enlaza el Plan Anual de Adquisiciones de la vigencia 2019, cinco vigencias por detrás del último que publica la categoría de planeación.',
                apartadoOrigen: 'Plan Anual de Adquisiciones',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Publicación de la información contractual',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Publicación de la información contractual',
                url: 'https://community.secop.gov.co/Public/Tendering/ContractNoticeManagement/Index?currentLanguage=es-CO&Page=login&Country=CO&SkinName=CCE',
                aviso: 'El origen enlaza la entrada general del sistema electrónico de contratación pública, sin filtro por entidad.',
                apartadoOrigen: 'Publicación de la información contractual',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Convocatorias',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Convocatorias',
                url: 'https://www.santamarta.gov.co/documentos?tid=Convocatorias&title=',
                apartadoOrigen: 'Convocatorias',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Ofertas de empleo',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Ofertas de empleo',
                url: 'https://www.cnsc.gov.co/index.php/828-a-979-y-982-a-986-de-2018-municipios-priorizados-para-el-post-conflicto',
                aviso: 'El origen enlaza una convocatoria de la Comisión Nacional del Servicio Civil de 2018 sobre municipios priorizados para el posconflicto.',
                apartadoOrigen: 'Ofertas de empleo',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Publicación de la ejecución de los contratos',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Publicación de la ejecución de los contratos',
                url: 'http://siaobserva.auditoria.gov.co/',
                aviso: 'El origen enlaza el sistema de observación de la Auditoría General de la República, ente de control nacional.',
                apartadoOrigen: 'Publicación de la ejecución de los contratos',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Manual de contratación, adquisición y/o compras',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Manual de contratación, adquisición y/o compras',
                url: 'https://www.santamarta.gov.co/contratacion',
                apartadoOrigen: 'Manual de contratación, adquisición y/o compras',
              },
            ],
          },
        ],
      },
    ],
  },
  {
    ...CATEGORIAS_INDICE['planeacion-presupuesto-e-informes'],
    publica:
      'El presupuesto de ingresos, gastos e inversión y su ejecución; los planes de la Entidad; los proyectos de inversión; los informes de empalme; la información pública y relevante —estrategias, informes de PQRSD y del plan anticorrupción, instrumentos de gestión documental—; los informes de gestión, evaluación y auditoría; los de la oficina de control interno; el informe sobre defensa pública y prevención del daño antijurídico, y los estados financieros.',
    base: 'Ley 1712 de 2014, artículo 9, literal b), y artículo 11, literal e); Resolución 1519 de 2020, Anexo 2, §4.1.2.',
    estado: 'publicada',
    notaOrigen: 'El origen publica los encabezados 4.7, 4.8 y 4.9 anidados dentro del apartado 4.6, por dos defectos de su marcado: una etiqueta «<liclass>» escrita sin el espacio que separa el nombre del atributo, y el encabezado de 4.9 compuesto por un enlace suelto en vez de por un elemento de lista. La Sede restituye los cuatro apartados a su nivel.',
    apartados: [
      {
        titulo: 'Presupuesto general de ingresos, gastos e inversión',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Presupuesto 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/decreto_no._897_liquidacion_presupuesto_2025.pdf',
                apartadoOrigen: 'Presupuesto general de ingresos, gastos e inversión',
              },
              {
                titulo: 'Presupuesto 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/acuerdo_009_de_13_dic_2023_presupuesto_2024.pdf',
                apartadoOrigen: 'Presupuesto general de ingresos, gastos e inversión',
              },
              {
                titulo: 'Presupuesto 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/Decreto_365_del_27_de_Dic_2022_Liqui_PPTO_2023.pdf',
                apartadoOrigen: 'Presupuesto general de ingresos, gastos e inversión',
              },
              {
                titulo: 'Presupuesto 2022',
                url: 'https://www.santamarta.gov.co/documentos/acuerdo-no-015-del-27-de-diciembre-de-2021',
                apartadoOrigen: 'Presupuesto general de ingresos, gastos e inversión',
              },
              {
                titulo: 'Presupuesto 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/acuerdo_017_de_30_nov_2020.pdf',
                apartadoOrigen: 'Presupuesto general de ingresos, gastos e inversión',
              },
              {
                titulo: 'Presupuesto 2020',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Decreto_507-Liquidaci%C3%B3n_Ppto_Vigencia_2020.pdf',
                apartadoOrigen: 'Presupuesto general de ingresos, gastos e inversión',
              },
              {
                titulo: 'Presupuesto 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Dcreto%20de%20Liquidaci%C3%B2n%20PPTO%202019.pdf',
                apartadoOrigen: 'Presupuesto general de ingresos, gastos e inversión',
              },
              {
                titulo: 'Presupuesto 2018',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/DEC.%20361%20%2822dic2017%29Liquidaci%C3%B2n%20de%20Presupuesto%202018.pdf',
                apartadoOrigen: 'Presupuesto general de ingresos, gastos e inversión',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Ejecución presupuestal',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Ejecución presupuestal Gastos Primer Semestre 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/ejecucion_presupuestal_de_gastos_primer_semestre_2025.xls',
                apartadoOrigen: 'Ejecución presupuestal',
              },
              {
                titulo: 'Ejecución presupuestal ingresos Primer Semestre 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/ejecucion_presupuestal_de_ingresos_primer_semestre_2025.xls',
                apartadoOrigen: 'Ejecución presupuestal',
              },
              {
                titulo: 'Ejecución presupuestal Gastos 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/ejecucion_presupuestal_de_gastos_vigencia_2024_firmada.pdf',
                apartadoOrigen: 'Ejecución presupuestal',
              },
              {
                titulo: 'Ejecución presupuestal ingresos 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/ejecucion_presupuestal_de_ingresos_vigencia_2024_firmada.pdf',
                apartadoOrigen: 'Ejecución presupuestal',
              },
              {
                titulo: 'Ejecución presupuestal 2023 (corte septiembre)',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Ejecucion_Presupuestal_de_Gastos_2023_corte_septiembre.pdf',
                apartadoOrigen: 'Ejecución presupuestal',
              },
              {
                titulo: 'Ejecución presupuestal 2022',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Ejecucion_de_gastos_Firmada_2022.pdf',
                apartadoOrigen: 'Ejecución presupuestal',
              },
              {
                titulo: 'Ejecución presupuestal 2021',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Ejecucion_de_gastos_Firmada_2021.pdf',
                apartadoOrigen: 'Ejecución presupuestal',
              },
              {
                titulo: 'Ejecución presupuestal 2020',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Ejecucion_de_gastos_Firmada_2020.pdf',
                apartadoOrigen: 'Ejecución presupuestal',
              },
              {
                titulo: 'Ejecución presupuestal 2018',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/DEC.%20361%20%2822dic2017%29Liquidaci%C3%B2n%20de%20Presupuesto%202018.pdf',
                apartadoOrigen: 'Ejecución presupuestal',
              },
              {
                titulo: 'Ejecución presupuestal 2017',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/INFORME%20EJECUCION%20DE%20INGRESOS%20Y%20GASTOS%202017.pdf',
                apartadoOrigen: 'Ejecución presupuestal',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Planes',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Plan Institucional de Capacitación 2026',
                url: 'https://www.santamarta.gov.co/documentos/plan-institucional-de-capacitacion-2026',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Institucional de Capacitación 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/plan-institucional-de-capacitaciones-2025.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Institucional de Capacitación 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/PLAN-INSTITUCIONAL-DE-CAPACITACIONES-2023.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Desarrollo 2020-2023',
                url: 'https://www.santamarta.gov.co/plan-de-desarrollo-distrital-2020-2023',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Desarrollo 2016-2019',
                url: 'https://www.santamarta.gov.co/plan-de-desarrollo-distrital-2016-2019',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Ordenamiento Territorial',
                url: 'https://www.santamarta.gov.co/plan-de-ordenamiento-territorial',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Acción 2026',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-accion-2026',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Acción 2025',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-accion-distrital-2025',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de acción 2024',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-accion-2024',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de acción 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/PLAN_DE_ACCION_DISTRITAL_2023-C.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de acción 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/plan_de_accion_distrital_2022_-_santa_marta_d.t.c.h._vf.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de acción 2021',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2021/PLAN_DE_ACCION_2021.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de acción 2020',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/PLAN_DE_ACCION_2020.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de acción 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/PLAN%20DE%20ACCION%202019',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan 500 años',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/PLAN%20MAESTRO%20500%20A%c3%91OS%20FINAL.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de acción de Secretaría de Salud 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/PLAN%20DE%20ACCI%C3%93N%20EN%20SALUD%20-PAS%202019%20-%20TIC.xlsx',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan institucional de archivos de la entidad - Pinar 2026 - 2029',
                url: 'https://www.santamarta.gov.co/sites/default/files/1_-_plan_institucional_de_archivo_pinar.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan institucional de archivos de la entidad - Pinar',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/PLAN%20INSTITUCIONAL%20DE%20ARCHIVOS%20DE%20LA%20ENTIDAD%20_%20PINAR',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan anual de adquisiciones 2026',
                url: 'https://www.santamarta.gov.co/documentos/resolucion-no-010-del-06-enero-de-2026',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan anual de adquisiciones 2025',
                url: 'https://www.santamarta.gov.co/documentos/resolucion-004-del-08-enero-de-2025',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan anual de adquisiciones 2024',
                url: 'https://www.santamarta.gov.co/documentos/resolucion-011',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan anual de adquisiciones 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/res_007_de_10_ene_2023_-_plan_anual_de_adquisiciones.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan anual de adquisiciones 2021',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2021/PLAN_ANUAL_DE_ADQUISICIONES_2021.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan anual de adquisiciones 2020',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2020/PLAN%20ANUAL%20DE%20ADQUISICIONES.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de trabajo SST',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2020/PLAN%20DE%20TRABAJO%20SST%202020.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Inducción, capacitación y entrenamiento 2020',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2020/Plan%20de%20Induccion%20capacitacion%20y%20entrenamiento%202020.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Estratégico de Tecnologías de Información PETI 2024-2027 actualizado 2026',
                url: 'https://www.santamarta.gov.co/documentos/plan-estrategico-de-tecnologias-de-informacion-peti-2024-2027',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Estratégico de Tecnologías de Información PETI 2024-2027 actualizado 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/peti-2024-2027.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Estratégico de Tecnologías de Información PETI 2024 -2027',
                url: 'https://www.santamarta.gov.co/sites/default/files/peti_alcaldia_de_santa_marta-2024-2027.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Estratégico de Tecnologías de Información PETI 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/PETI_2023.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Estratégico de Tecnologías de Información PETI 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/peti_2022.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de tratamiento de riesgos de seguridad y privacidad de la información 2026',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-tratamiento-de-riesgos-de-seguridad-y-privacidad-de-la-informacion-2026',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de tratamiento de riesgos de seguridad y privacidad de la información 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/plan_para_el_tratamiento_de_riesgos_de_seguridad_y_privacidad_de_la_informacion.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de tratamiento de riesgos de seguridad y privacidad de la información 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/PLAN_DE_TRATAMIENTO_RIESGOS_SEGURIDAD_PRIVACIDAD_INFORMACION_2023.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de tratamiento de riesgos de seguridad y privacidad de la información 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/plan_de_tratamiento_de_riesgos_de_seguridad_y_privacidad_de_la_informacion_2022.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Seguridad y Privacidad de la Información 2024 - 2027 Actualizado 2026',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-seguridad-y-privacidad-de-la-informacion-2024-2027',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Seguridad y Privacidad de la Información 2024 - 2027',
                url: 'https://www.santamarta.gov.co/sites/default/files/plan_de_seguridad_y_privacidad_de_la_informacion_2024-2027.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Seguridad y Privacidad de la Información 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/PLAN_DE_SEGURIDAD_Y_PRIVACIDAD_DE_LA_INFORMACION_2023.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Seguridad y Privacidad de la Información 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/plan_de_seguridad_y_privacidad_de_la_informacion_2022.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan institucional de capcitación',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2020/PLAN%20INSTITUCIONAL%20CAPACITACION.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan institucional de bienestar e incentivos',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2020/PLAN%20INSTITUCIONAL%20BIENESTAR%20E%20INCENTIVOS.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Rendición de cuenta 2023',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2023/PLAN_DE_RENDICION_DE_CUENTAS_2023.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Rendición de cuenta 2021',
                url: 'https://www.santamarta.gov.co//portal/archivos/documentos/transparencia/2021/PLAN_RENDICION_DE_CUENTAS_2021.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de de Serivicio al ciudadano',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/PLAN%20ANTICORRUPCION%202019',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Antitrámite',
                url: 'https://www.santamarta.gov.co/',
                aviso: 'El origen enlaza la portada del sitio anterior, no el Plan Antitrámite.',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anticorrupción y de Atención al ciudadano 2025',
                url: 'https://www.santamarta.gov.co/documentos/plan-anticorrupcion-y-de-atencion-al-ciudadano-2025-paac',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anticorrupción y de Atención al ciudadano 2024',
                url: 'https://www.santamarta.gov.co/documentos/plan-anticorrupcion-y-atencion-al-ciudadano',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anticorrupción y de Atención al ciudadano 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/PLAN_ANTICORRUPCION_2023.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Declaración de bienes y rentas y conflicto de intereses',
                url: 'https://drive.google.com/drive/folders/1NHkMBR5i523_PGURumBWaT_H6xiiMqpG',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Matriz Mapa de Riesgo de Corrupción 2022.',
                url: 'https://www.santamarta.gov.co/documentos/matriz-mapa-riesgo-corrupcion-2022-alcaldia',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Matriz Mapa de Riesgo de Corrupción 2023.',
                url: 'https://www.santamarta.gov.co/documentos/matriz-mapa-de-riesgo-de-corrupcion-2023',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anticorrupción y de Atención al ciudadano 2022.',
                url: 'https://www.santamarta.gov.co/sites/default/files/plan_anticorrupcion_y_atencion_al_ciudadano_alcaldia_distrital_2022.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anticorrupción y de Atención al ciudadano 2021 (Modificado).',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2021/PLAN_ANTICORRUPCION_2021-modificado.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anticorrupción y de Atención al ciudadano 2021.',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2021/PLAN_ANTICORRUPCION_2021.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anticorrupción y de Atención al ciudadano 2020.',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2020/PLAN_ANTICORRUPCION_2020.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Nuevas decisiones o políticas adoptadas.',
                url: 'https://www.santamarta.gov.co/sala-prensa/noticias/pico-y-placa-y-restriccion-de-motos-se-mantendran-en-semana-santa',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan anual operativo de inversiones 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/POAI%202019.xlsx',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan anual operativo de inversiones 2020',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2020/POAI%202020.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Contratación 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/MANUAL%20DE%20CONTRATACION%202019.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Supervision',
                url: 'https://www.santamarta.gov.co/portal/archivos/MANUAL%20SUPERVISION%20E%20INTERVENTOR%C3%8DA%202019.pdf%0A',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Institucional Bienestar e Incentivos 2026',
                url: 'https://www.santamarta.gov.co/documentos/programa-de-bienestar-social-e-incentivos-2026',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Institucional Bienestar e Incentivos 2025',
                url: 'https://www.santamarta.gov.co/documentos/programa-de-bienestar-social-e-incentivos-2025',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Institucional Bienestar e Incentivos 2024',
                url: 'https://www.santamarta.gov.co/documentos/programa-de-bienestar-social-e-incentivos-2024',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Institucional Bienestar e Incentivos 2020',
                url: 'https://www.santamarta.gov.co/documentos/plan-institucional-bienestar-e-incentivos-2020-secretaria-general',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Institucional de Capacitación',
                url: 'https://www.santamarta.gov.co/documentos/plan-institucional-de-capacitacion-secretaria-general',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anual de Seguridad y Salud en el Trabajo 2026',
                url: 'https://www.santamarta.gov.co/documentos/plan-anual-de-seguridad-y-salud-en-el-trabajo-2026',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anual de Seguridad y Salud en el Trabajo 2025',
                url: 'https://www.santamarta.gov.co/documentos/plan-anual-de-seguridad-y-salud-en-el-trabajo-2025',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anual de Seguridad y Salud en el Trabajo 2024',
                url: 'https://www.santamarta.gov.co/documentos/plan-anual-de-seguridad-y-salud-en-el-trabajo-2024',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Trabajo SST 2020',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-trabajo-sst-2020-secretaria-general',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Induccion Capacitación y Entrenamiento 2020',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-induccion-capacitacion-y-entrenamiento-2020-secretaria-general',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Induccion Capacitación y Entrenamiento 2024',
                url: 'https://www.santamarta.gov.co/documentos/plan-institucional-de-capacitaciones-2024',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anual de Vacantes 2026',
                url: 'https://www.santamarta.gov.co/documentos/plan-anual-de-vacantes-2026',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anual de Vacantes 2021',
                url: 'https://www.santamarta.gov.co/documentos/plan-anual-de-vacantes-2021-secretaria-general-direccion-de-capital-humano',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Anual de Vacantes 2020',
                url: 'https://www.santamarta.gov.co/documentos/plan-anual-de-vacantes-2020-secretaria-general',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Inducción, capacitación y entrenamiento 2021',
                url: 'https://www.santamarta.gov.co/documentos/cronograma-de-induccion-y-capacitacion-de-sst-alcaldia-distrital-de-santa-marta-y-sus',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Trabajo SST 2021',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-trabajo-anual-de-seguridad-y-salud-en-el-trabajo-ano-2021-alcaldia-distrital-de',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Previsión de Recursos Humanos 2026',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-prevision-de-recursos-humanos-2026',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Estratégico de Talento Humano 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/Plan-estratategico-talento-humano-2025.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Estratégico de Talento Humano 2021',
                url: 'https://www.santamarta.gov.co/documentos/plan-estrategico-de-talento-humano-2021-secretaria-general-direccion-de-capital-humano',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Estratégico de Talento Humano 2020',
                url: 'https://www.santamarta.gov.co/documentos/plan-estrategico-de-talento-humano-2020-secretaria-general',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Preservacion digital a largo plazo',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-preservacion-digital-largo-plazo-gestion-documental-de-la-alcaldia-distrital',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de rendición de cuentas 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/plan_de_rendicion_de_cuentas_2022_vf.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de rendición de cuentas 2021',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-rendicion-de-cuentas-2021-alcaldia-distrital-de-santa-marta',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de Acción Distrital 2021',
                url: 'https://www.santamarta.gov.co/documentos/plan-de-accion-distrital-2021-documento-oficial',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan de rendición de cuentas',
                url: 'https://www.santamarta.gov.co/sites/default/files/plan_de_rendicion_de_cuentas_2022_vf.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Estudio investigaciones y otras publicaciones',
                url: 'https://www.santamarta.gov.co/estudios',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Plan Integral de Seguridad y Convivencia Ciudadana',
                url: 'https://www.santamarta.gov.co/sites/default/files/PISCC-DISTRITO-DE-SANTA-MARTA-1.pdf',
                apartadoOrigen: 'Planes',
              },
              {
                titulo: 'Informe de percepción ciudadana',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME-DE-PERCEPCION-CIUDADANA.pdf',
                apartadoOrigen: 'Planes',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Proyectos de Inversión',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Proyectos de inversión 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/seguimineto_y_avance_a_proyectos.xlsx',
                apartadoOrigen: 'Proyectos de Inversión',
              },
              {
                titulo: 'Proyectos de inversión 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/PROYECTOS%20REGISTRADOS%20Y%20ACTUALIZADOS%20%20%20VIGENCIA%202019%20(ada).docx',
                apartadoOrigen: 'Proyectos de Inversión',
              },
              {
                titulo: 'Anexo Proyectos PDET Santa Marta',
                url: 'https://www.santamarta.gov.co/documentos/anexo-proyectos-pdet-santa-marta-programa-de-desarrollo-con-enfoque-territorial',
                apartadoOrigen: 'Proyectos de Inversión',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Informes de empalme',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Informes de Empalme 2015 - 2016',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Informe%20Empalme%202015-2016.pdf',
                apartadoOrigen: 'Informes de empalme',
              },
              {
                titulo: 'Acta de Empalme 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2020/ACTA%20DE%20EMPALME%202016-2019.pdf',
                apartadoOrigen: 'Informes de empalme',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Información pública y/o relevante',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Estrategia Institucional de la Relación Estado -Ciudadano 2026',
                url: 'https://www.santamarta.gov.co/documentos/estrategia-institucional-de-la-relacion-estado-ciudadano-2026',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Estrategia de Participación Ciudadana vigencia 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/estrategia_de_participacion-ciudadana_vigencia_2025.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Estrategia Rendición de Cuentas 2025',
                url: 'https://www.santamarta.gov.co/documentos/estrategia-rendicion-de-cuentas-2025',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Estrategia Rendición de Cuentas 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/Estrategia-rendicion-de-cuentas-santa-marta-2024.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Estrategia de Racionalización de Trámites',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/ESTRATEGIA-RACIONALIZACION-DE-TRAMITES.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Estrategia Institucional de la Relación Estado - Ciudadano 2024 - 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/estrategia-institucional-de-la-relacion-estado-ciudadano-2024-2025.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Guia de lenguaje claro',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/MM-GAC-G-001-Guia-lenguaje-claro-servidores-publicos.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Guía de Lenguaje Claro 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/guia-de-lenguaje-claro-2024.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Metas objetivos e indicadores de gestión y desmepeño',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/PLAN%20DE%20ACCION%202019',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Participación en la formulación de políticas',
                url: 'https://www.santamarta.gov.co/participacion-en-politica-0',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe Semestral PQRSD Enero - Junio 2020',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_semestral_pqrsd_enero_junio_2020.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe Semestral PQRSD Julio - Diciembre 2020',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_semestral_pqrsd_julio_a_diciembre_2020.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe Semestral PQRSD Enero - Junio 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_semestral_pqrsd_enero-junio_2021.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe Semestral PQRSD Julio - Diciembre 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_seguimiento_pqrd_santa_alcaldia.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe Seguimiento PQRSD Julio - Diciembre 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_PQRDS_OCI_II_SEM.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe Semestral PQRSD Enero - Junio 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_PQRSD_PRIMER_SEMESTRE_DE_2023.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe Semestral PQRSD Julio - Diciembre 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME-ESTADO-PQRSD-2023.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe Semestral PQRSD Enero - Junio 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_gestion_pqrsd_primer_semestre_2025.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe De Seguimiento PACC Enero - Abril 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/primer_informe_de_segumiento_al_paac_2022.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe De Seguimiento PACC Mayo - Agosto 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_paac_segundo_cuatrimestre_2022.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe De Seguimiento PACC Septiembre - Diciembre 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/TERCER_CUATRIMETRE_INFORME_DE_PACC-2022.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe De Seguimiento PACC Enero - Abril 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/PRIMER_CUATRIMESTRE_INFORME_DE_PACC-2023.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe De Seguimiento PACC Mayo - Agosto 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/SEGUNDO_CUATRIMESTRE_MAYO_AGOSTO_PAAC-2023.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe De Seguimiento PACC Septiembre - Diciembre 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME-DE-SEGUMIENTO-PACC-SEPTIEMBRE-DICIEMBRE-2023.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe De Seguimiento PACC Enero - Abril 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_seguimiento_pacc_enero_-_abril_2024.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe De Seguimiento PACC Mayo - Agosto 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_seguimiento_pacc_mayo_-_agosto_2024.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Información mínima a publicar',
                url: 'https://www.santamarta.gov.co/101-informacion-minima-requerida-publicar',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Registros de activos de información',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/inventario-de-activos-de-informacion.xlsx',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Indice de información clasificada y reservada',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Esquema de publicación de información',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Programa de Gestión documental',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/PGD.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Tablas de Retención Documental',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Manual%20de%20Implementaci%C3%B3n%20de%20TRD%20ADSM%20(2).pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Registro de Publicaciones',
                url: 'https://www.santamarta.gov.co/documentos/',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Costos de publicación',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe de peticiones quejas y reclamos',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/INFORME_PQRDS_OCI_II_SEM.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Capacitación sobre atención al ciudadano y lenguaje claro en la función pública',
                url: 'https://www.santamarta.gov.co/documentos/capacitacion-sobre-atencion-al-ciudadano-y-lenguaje-claro-en-la-funcion-publica',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Informe Plan Anticorrupción Y Atención Al Ciudadano',
                url: 'https://www.santamarta.gov.co/sites/default/files/primer_informe_de_segumiento_al_paac_2022.pdf',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Encuesta para la racionalización o simplificación de trámites de la Alcaldía de Santa Marta',
                url: 'https://docs.google.com/forms/d/e/1FAIpQLSdYF66Ql6ChJQ_PBJ1klspQgII0o7DJC0HfAMb9jsjM7lFPRg/viewform',
                apartadoOrigen: 'Información pública y/o relevante',
              },
              {
                titulo: 'Sistema Integrado de Conservación (SIC) - Gestión Documental',
                url: 'https://www.santamarta.gov.co/documentos/sistema-integrado-de-conservacion-sic-gestion-documental-secretaria-general',
                apartadoOrigen: 'Información pública y/o relevante',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Informes de gestión, evaluación y auditoría',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Planes de mejoramiento Contraloría General de la República',
                url: 'https://www.santamarta.gov.co/documentos/planes-de-mejoaramiento-contraloria-general-de-la-republica',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Planes de mejoramiento con la Contralría Distrital de Santa Marta',
                url: 'https://www.santamarta.gov.co/sites/default/files/25043_formato-plan-de-mejoramiento_-_sia_observa.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informes enviado al Concejo',
                url: 'https://www.santamarta.gov.co/rendicion-de-cuentas-2017',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informes de rendición de cuenta a la contraloria',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/certificado_10907_20181231_12%20(1).pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informes de rendición de cuentas a los ciudadanos',
                url: 'https://www.santamarta.gov.co/documentos/informe-de-gestion-rendicion-de-cuentas-2020',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informes a organismos de inspección y vigilancia',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/certificado_10907_20181231_12%20(1).pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Acta de Empalme 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2020/ACTA%20DE%20EMPALME%202016-2019.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Gestión 2025',
                url: 'https://www.santamarta.gov.co/documentos/informe-de-gestion-vigencia-2025',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Gestión 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_gestion_2024.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Gestión 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_DE_GESTION_PRINCIPAL_VIGENCIA_2022.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Gestión 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_gestion_2021_vf.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Gestión 2016-2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/transparencia/2020/INFORME%20DE%20GESTION%202016-2019.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informes de auditoría interna',
                url: 'https://www.santamarta.gov.co/informes-de-auditoria-interna-0',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
            ],
          },
          {
            titulo: 'Informes de Austeridad del Gasto',
            documentos: [
              {
                titulo: 'Informe trimestre III 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_austeridad_del_gasto_iii_trimestre_2025_vs_iii_trimestre_2024.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe trimestre II 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_trimestre_ii_2025_.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe trimestre I 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_trimestre_i_2025_.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe trimestre III 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME-DE-AUSTERIDAD-DEL-GASTO-III-TRIMESTRE-2024.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe trimestre II 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME-DE-AUSTERIDAD-DEL-GASTO-II-TRIMESTRE-2024.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre I 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME-TRIMESTRE-I-2024.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre IV – 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME-DE-AUSTERIDAD-DEL-GASTO-CUARTO-TRIMESTRE-2023.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre III – 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_DE_AUSTERIDAD_DEL_GASTO_TERCER_TRIMESTRE-2023.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre II – 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_DE_AUSTERIDAD_DEL_GASTO_SEGUNDO_TRIMESTRE-2023.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre I – 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_DE_AUSTERIDAD_DEL_GASTO_PRIMER_TRIMESTRE-2023.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre IV – 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_DE_AUSTERIDAD_DEL_GASTO_CUARTO_TRIMESTRE-2022.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre III – 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_DE_AUSTERIDAD_DEL_GASTO_TERCER_TRIMESTRE-2022.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre II – 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_DE_AUSTERIDAD_DEL_GASTO_SEGUNDO_TRIMESTRE-2022.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre I – 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_DE_AUSTERIDAD_DEL_GASTO_PRIMER_TRIMESTRE_2022.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre IV – 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_DE_AUSTERIDAD_DEL_GASTO_CUARTO_TRIMESTRE-2021.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre III – 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_DE_AUSTERIDAD_DEL_GASTO_TERCER_TRIMESTRE_2021.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre II – 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_austeridad_del_gasto_segundo_trimestre_-_2021.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre I – 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_austeridad_del_gasto_trimestre_1_-_2021.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe Trimestre IV – 2020',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_austeridad_del_gasto_trimestre_4_-_2020.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Planes de Mejoramiento',
                url: 'https://www.santamarta.gov.co/plan-de-mejoramiento',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Rendición de Cuentas PDET Santa Marta-2025',
                url: 'https://www.santamarta.gov.co/documentos/informe-de-rendicion-de-cuentas-2025-construccion-de-paz-del-distrito-de-santa-marta',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Rendición de Cuentas PDET Santa Marta-2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_pdet_2024.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Rendición de Cuentas PDET Santa Marta-2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_rendicion_de_cuentas_construccion_de_paz_del_-distrito_de_santa_marta_magdalena.pdf',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Rendición de Cuentas PDET Santa Marta-2022',
                url: 'https://www.santamarta.gov.co/documentos/informe-de-rendicion-de-cuentas-pdet-santa-marta-2022',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Rendición de Cuentas PDET Santa Marta-2021',
                url: 'https://www.santamarta.gov.co/documentos/informe-plan-marco-de-implementacion-del-acuerdo-de-paz-ano-2021',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
              {
                titulo: 'Informe de Rendición de Cuentas PDET Santa Marta-2020',
                url: 'https://www.santamarta.gov.co/documentos/informe-de-rendicion-de-cuentas-pdet-santa-marta-programa-de-desarrollo-con-enfoque',
                apartadoOrigen: 'Informes de gestión, evaluación y auditoría',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Informes de la oficina de control interno',
        grupos: [
          {
            titulo: 'Soportes FURAG 2020',
            documentos: [
              {
                titulo: 'Resolución No. 756 del 24 de agosto del 2020',
                url: 'https://www.santamarta.gov.co/sites/default/files/resolucion-756-de-24-agosto-2020.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Resolución No. 118 del 5 de abril de 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/resolucion-118.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Política De Administración Del Riesgo, marzo 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/politica-de-administracion-de-riesgo.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Formato Evaluación De Gestión Por Dependencias Oficina De Control Interno Institucional',
                url: 'https://www.santamarta.gov.co/sites/default/files/formato-evaluacion-dependencia.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
            ],
          },
          {
            titulo: 'Soportes FURAG 2021',
            documentos: [
              {
                titulo: 'Resolución No. 545 del 9 de diciembre del 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/resolucion_del_comite_institucional_de_gestion_y_desempeno.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Plan Operativo Anual de Inversión 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/poai_2021.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Plan Indicativo - Alcaldía Distrital de Santa Marta 2020-2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/plan_indicativo_-_alcaldia_distrital_de_santa_marta_2020-2023.xlsx',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Marco Fiscal Distrito Santa Marta 2021 -2031',
                url: 'https://www.santamarta.gov.co/sites/default/files/marco_fiscal_distrito_santa_marta_2021_-2031.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Homologación Plan Indicativo Santa Marta 2020-2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/homologacion_plan_indicativo_santa_marta_2020-2023.xlsx',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
            ],
          },
          {
            titulo: 'Seguimiento al Plan de Desarrollo Distrital 2021',
            documentos: [
              {
                titulo: 'Primer Trimestre',
                url: 'https://www.santamarta.gov.co/sites/default/files/1_trimestre_2021_-_seguimiento_plan_de_desarrollo_distrital.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Segundo Trimestre',
                url: 'https://www.santamarta.gov.co/sites/default/files/2_trimestre_2021_-_seguimiento_plan_de_desarrollo_distrital.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Tercer Trimestre',
                url: 'https://www.santamarta.gov.co/sites/default/files/3_trimestre_2021_-_seguimiento_plan_de_desarrollo_distrital.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Cuarto Trimestre',
                url: 'https://www.santamarta.gov.co/sites/default/files/4_trimestre_2021_-_seguimiento_plan_de_desarrollo_distrital.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
            ],
          },
          {
            titulo: 'Soporte FURAG 2023',
            documentos: [
              {
                titulo: 'Informe Ejecutivo FURAG 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/infiorme_ejecutivo_furag_2023.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Presentación Resultados FURAG-IDI-2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/presentacion_resultados_furag-idi-2023.pptx',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
            ],
          },
          {
            titulo: 'Informes Pormenorizados',
            documentos: [
              {
                titulo: 'Informe Primer Semestre 2020',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_pormenorizado_de_control_interno_2020-i.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Segundo Semestre 2020',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_pormenorizado_segundo_semestre_2020.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Primer Semestre 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_pormenorizado_enero_junio_2021.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Segundo Semestre 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_pormenorizado_de_control_interno_2021-ii.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Primer Semestre 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_pormenorizado_de_control_interno_2022-i.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Segundo Semestre 2022',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_pormenorizado_de_control_interno_2022-II.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Primer Semestre 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/INFORME_PORMENORIZADO_PRIMER_SEMESTRE_2023.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Segundo Semestre 2023',
                url: 'https://www.santamarta.gov.co/documentos/informes-pormenorizados-segundo-semestre-2023',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Primer Semestre 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe-sci-parametrizado-primer-sem-2024.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Segundo Semestre 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe-segundo-semestre-2024.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Primer Semestre 2025',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_primer_semestre_2025.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
            ],
          },
          {
            titulo: 'Informes Derechos de Autor',
            documentos: [
              {
                titulo: 'Informe Derecho de Autor Alcaldía DTCH Vigencia 2025',
                url: 'https://www.santamarta.gov.co/documentos/informe-derecho-de-autor-alcaldia-dtch-vigencia-2025',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe de Software Legal Vigencia 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/certificado-software-legal.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Derecho de Autor Alcaldía DTCH Vigencia 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe-de%20derechos-de-autor-y-licencias-de-software-y-oficio.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
              {
                titulo: 'Informe Derecho de Autor Alcaldía DTCH Vigencia 2023',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_derecho_de_autor_alcaldia_dtch_vigencia_2023.pdf',
                apartadoOrigen: 'Informes de la oficina de control interno',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Informe sobre defensa pública y prevención del daño antijurídico',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Informe Sobre Defensa Pública Y Prevención Del Daño Antijurídico',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Informe%20de%20procesos%20judiciales%20.xlsx',
                apartadoOrigen: 'Informe sobre defensa pública y prevención del daño antijurídico',
              },
              {
                titulo: 'Informe De Gestión Del Comité De Conciliación Y Gestión Jurídica Del Distrito De Santa Marta - Ekogui',
                url: 'https://ekogui.defensajuridica.gov.co/Pages/NEW/index.aspx',
                apartadoOrigen: 'Informe sobre defensa pública y prevención del daño antijurídico',
              },
              {
                titulo: 'Informe De Gestión Del Comité De Conciliación Y Gestión Jurídica Del Distrito De Santa Marta (Primer Semestre 2026)',
                url: 'https://www.santamarta.gov.co/sites/default/files/2026-i_informe_de_gestion_del_comite_de_conciliacion_y_gestion_juridica_del_ditsrito_de_santa_marta.pdf',
                apartadoOrigen: 'Informe sobre defensa pública y prevención del daño antijurídico',
              },
              {
                titulo: 'Informe De Gestión Del Comité De Conciliación Y Gestión Jurídica Del Distrito De Santa Marta (Primer Semestre 2024)',
                url: 'https://www.santamarta.gov.co/sites/default/files/2024-i_informe_de_gestion_del_comite_de_conciliacion_y_gestion_juridica_del_ditsrito_de_santa_marta.pdf',
                apartadoOrigen: 'Informe sobre defensa pública y prevención del daño antijurídico',
              },
              {
                titulo: 'Informe De Gestión Del Comité De Conciliación Y Gestión Jurídica Del Distrito De Santa Marta (Segundo Semestre 2024)',
                url: 'https://www.santamarta.gov.co/sites/default/files/2024-ii_informe_de_gestion_del_comite_de_conciliacion_y_gestion_juridica_del_ditsrito_de_santa_marta.pdf',
                apartadoOrigen: 'Informe sobre defensa pública y prevención del daño antijurídico',
              },
              {
                titulo: 'Lineamientos A La Política Daño Antijurídico 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/LINEAMIENTOS_A_LA_POLITICA_DA%C3%91O_ANTIJURIDICO_2024.pdf',
                apartadoOrigen: 'Informe sobre defensa pública y prevención del daño antijurídico',
              },
              {
                titulo: 'Plan De Acción 2024 (Comité De Conciliación - Dirección Jurídica)',
                url: 'https://www.santamarta.gov.co/sites/default/files/PLAN_DE_ACCION_2024_(Comite_de_Conciliacion_-_Direcci%C3%B3n_Juridica).pdf',
                apartadoOrigen: 'Informe sobre defensa pública y prevención del daño antijurídico',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Estados Financieros',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Estados Financieros 2024 Primer Trimestre',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/ESTADOS-FINANCIEROS-CON-SUS-NOTAS-A-31-DE-MARZO-DE-2024.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2024 Segundo Trimestre',
                url: 'https://www.santamarta.gov.co/sites/default/files/estados_fiancieros_santa_marta_trimestre_junio_2024.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2024 Tercer Trimestre',
                url: 'https://www.santamarta.gov.co/sites/default/files/estados-financieros-tercer-trimestre-2024.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2024 cuarto Trimestre',
                url: 'https://www.santamarta.gov.co/sites/default/files/estados_financieros_2024.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
            ],
          },
          {
            titulo: 'Estados Financieros 2023',
            documentos: [
              {
                titulo: 'Estados Financieros 2023',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/ESTADOS-FINACIEROS-SANT-A-MARTA-2023.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2023 Primer Trimestre',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/estados-financieros-2023/PRIMER-TRIMESTRE-2023.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2023 Segundo Trimestre',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/estados-financieros-2023/SEGUNDO-TRIMESTRE-2023.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2023 Tercer Trimestre',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/estados-financieros-2023/TERCER-TRIMESTRE-2023.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2023 Cuarto Trimestre',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/estados-financieros-2023/CUARTO-TRIMESTRE-2023.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2022',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Estados_financieros_2022.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2021',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Estados_financieros_2021.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2020',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Estados_financieros_2020.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2019',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/ef_2019.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2018',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/NOTAS%20Y%20EF%202018.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
              {
                titulo: 'Estados Financieros 2017',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/Estados%20Financieros%20201712.pdf',
                apartadoOrigen: 'Estados Financieros',
              },
            ],
          },
        ],
      },
    ],
  },
  {
    ...CATEGORIAS_INDICE['tramites-y-servicios'],
    publica:
      'Los trámites y servicios que la Entidad presta y la normatividad que los rige.',
    base: 'Ley 1712 de 2014, artículo 11, literal b); Resolución 1519 de 2020, Anexo 2, §4.1.2.',
    estado: 'parcial',
    motivo: 'El origen publica un solo destino para el apartado de trámites y servicios, y el de normatividad de trámites no lleva a ninguna parte: allí es un «#». La Sede no copia un enlace muerto y declara el apartado como no publicado.',
    rutaSede: '/tramites',
    rutaSedeTexto: 'El catálogo de trámites y servicios de esta Sede',
    apartados: [
      {
        titulo: 'Trámites y Servicios',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Trámites y Servicios',
                url: 'https://www.santamarta.gov.co/tramites-y-servicios',
                apartadoOrigen: 'Trámites y Servicios',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Normatividad de trámites',
        faltante:
          'Las normas que regulan cada trámite de la Entidad: la que lo crea, la que fija sus requisitos, su costo y su plazo, y la que lo modifica. El sitio anterior abre este apartado con un enlace que no lleva a ninguna parte —un «#»—, así que aquí no se copia ningún destino y el apartado se declara vacío.',
        grupos: [
        ],
      },
    ],
  },
  {
    ...CATEGORIAS_INDICE['participa'],
    publica:
      'Los mecanismos, espacios e instancias de participación ciudadana y los informes de rendición de cuentas de la Entidad.',
    base: 'Ley 1712 de 2014, artículo 11, literal i); Resolución 1519 de 2020, Anexo 2, §4.1.2.',
    estado: 'publicada',
    notaOrigen: 'El origen abre esta categoría en el 6.2: no publica ningún 6.1.',
    rutaSede: '/participa',
    rutaSedeTexto: 'La sección Participa de esta Sede',
    apartados: [
      {
        titulo: 'Mecanismos, espacios o instancias de Participación',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Mecanismos, espacios o instancias de Participación',
                url: 'https://www.santamarta.gov.co/participacion-en-politica-0',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Construcción del Plan Anticorrupción y de Atención al Ciudadano (PAAC)',
                url: 'https://docs.google.com/forms/d/e/1FAIpQLSct3Ql8gvoPzS7RgRsmWlqjB8iXad0Jkh9sYxyBqt4Tv0ABXg/viewform',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Informe de Rendición de cuentas 2024',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_consolidado_pdet_2024_1.pdf',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Informe de Rendición de cuentas 2023',
                url: 'https://www.santamarta.gov.co/documentos/informe-de-gestion-ultima-rendicion-de-cuentas-diciembre-2023',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Informe de Rendición de cuentas 2022',
                url: 'https://www.santamarta.gov.co/documentos/informe-de-rendicion-de-cuentas-2022',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Informe de Rendición de cuentas 2021',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_gestion_2021_vf.pdf',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Informe de Rendición de cuentas 2020',
                url: 'https://www.santamarta.gov.co/documentos/rendicion-de-cuentas-2020',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Informe de Rendición de Cuentas 2019',
                url: 'https://www.santamarta.gov.co/documentos/rendicion-de-cuentas-2019',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Informe de Rendición de Cuentas 2018',
                url: 'https://www.santamarta.gov.co/documentos/rendicion-de-cuentas-2018',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Informe de Rendición de Cuentas 2017',
                url: 'https://www.santamarta.gov.co/documentos/rendicion-de-cuentas-2017',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Informe de Rendición de Cuentas 2016',
                url: 'https://www.santamarta.gov.co/documentos/rendicion-de-cuentas-2016',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Última Rendición de Cuentas Diciembre 2023',
                url: 'https://www.santamarta.gov.co/documentos/rendicion-cuentas-diciembre-2023',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Rendición de Cuentas 2015',
                url: 'https://www.santamarta.gov.co/documentos/rendicion-de-cuentas-2015',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Menu Participa',
                url: 'https://www.santamarta.gov.co/participa',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Informes de rendición de cuentas.',
                url: 'https://www.santamarta.gov.co/sites/default/files/informe_de_gestion_2021_vf.pdf',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Encuesta De Percepción Sobre Rendición Pública De Cuentas',
                url: 'https://docs.google.com/forms/d/e/1FAIpQLSf2GYic5IBOBWVVOBpG5SebwLBbltxbTN8p0Eu9Xhz3WDlORQ/viewform',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
              {
                titulo: 'Circular 2025151000000007-5 de 2025',
                url: 'https://www.santamarta.gov.co/circular-2025151000000007-5-de-2025-de-la-superintendencia-nacional-de-salud',
                apartadoOrigen: 'Mecanismos, espacios o instancias de Participación',
              },
            ],
          },
        ],
      },
    ],
  },
  {
    ...CATEGORIAS_INDICE['datos-abiertos'],
    publica:
      'Los conjuntos de datos que la Entidad publica en el portal federado de datos abiertos del Estado, en formatos abiertos y reutilizables.',
    base: 'Ley 1712 de 2014, artículo 11, literal k); Resolución 1519 de 2020, Anexo 2, §4.1.2, y Anexo 4.',
    estado: 'declarada',
    motivo: 'El origen de esta categoría contiene un único enlace, y no apunta a un catálogo de conjuntos de datos sino a la propia página de datos abiertos del sitio anterior. La Sede no publica aquí ningún conjunto: no ha verificado ninguno cuyo propietario sea la Alcaldía Distrital de Santa Marta. La categoría queda construida y su fuente federada declarada, para que el ciudadano sepa dónde consultarla y la Entidad sepa qué falta.',
    federada: {
      portal: 'Portal de Datos Abiertos del Estado colombiano',
      url: 'https://www.datos.gov.co',
      instruccion:
        'La fuente federada no se puede publicar desde aquí: hay que consultarla en el portal del Estado filtrando por entidad propietaria «Alcaldía Distrital de Santa Marta». Mientras ese filtro no devuelva conjuntos de la Entidad, esta categoría queda sin contenido y así se declara.',
    },
    apartados: [
    ],
  },
  {
    ...CATEGORIAS_INDICE['grupos-de-interes'],
    publica:
      'La información dirigida a grupos de interés determinados: niños, niñas y adolescentes; madres cabeza de hogar; población víctima; personas con discapacidad; adulto mayor, y etnias.',
    base: 'Ley 1712 de 2014, artículo 8, criterio diferencial de accesibilidad; Resolución 1519 de 2020, Anexo 2, §4.1.2.',
    estado: 'parcial',
    motivo: 'De los seis apartados, tres enlazan la portada del sitio anterior en vez de contenido del grupo, y la información para madres cabeza de hogar enlaza un informe de tecnologías de la información. La Sede publica esos destinos con el aviso a la vista, en vez de silenciarlos.',
    apartados: [
      {
        titulo: 'Información para niños, niñas y adolescentes',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Información para niños, niñas y adolescentes',
                url: 'http://portalninos.santamarta.gov.co//',
                apartadoOrigen: 'Información para niños, niñas y adolescentes',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Información para madres cabeza de hogar',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Información para madres cabeza de hogar',
                url: 'https://www.santamarta.gov.co/portal/archivos/documentos/INFORME%20TICS%20JULIO%20Y%20AGOSTO%20(1).pdf',
                aviso: 'El origen enlaza un informe de tecnologías de la información, no información dirigida a madres cabeza de hogar.',
                apartadoOrigen: 'Información para madres cabeza de hogar',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Información para población victima',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Información para población victima',
                url: 'https://www.santamarta.gov.co/victima',
                apartadoOrigen: 'Información para población victima',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Información para personas con discapacidad',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Información para personas con discapacidad',
                url: 'https://www.santamarta.gov.co/',
                aviso: 'El origen enlaza la portada del sitio anterior, no contenido de este apartado.',
                apartadoOrigen: 'Información para personas con discapacidad',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Información para el adulto mayor',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Información para el adulto mayor',
                url: 'https://www.santamarta.gov.co/',
                aviso: 'El origen enlaza la portada del sitio anterior, no contenido de este apartado.',
                apartadoOrigen: 'Información para el adulto mayor',
              },
            ],
          },
        ],
      },
      {
        titulo: 'Información para etnias',
        grupos: [
          {
            titulo: null,
            documentos: [
              {
                titulo: 'Información para etnias',
                url: 'https://www.santamarta.gov.co/',
                aviso: 'El origen enlaza la portada del sitio anterior, no contenido de este apartado.',
                apartadoOrigen: 'Información para etnias',
              },
            ],
          },
        ],
      },
    ],
  },
  {
    /**
     * La novena categoría. **La Entidad no la publica**: el índice del sitio
     * anterior se detiene en la octava, así que ésta es la categoría que falta
     * y la razón por la que esta sección se estaba incumpliendo. Se declara con
     * su encabezado, su estado y su vacío, y **sin apartados**: cuáles son los
     * suyos es una decisión de la Entidad sobre su propia naturaleza, y
     * inventarlos aquí sería publicar como obligación lo que nadie ha aprobado.
     */
    ...CATEGORIAS_INDICE['informacion-especifica-de-la-entidad'],
    publica:
      'La información mínima obligatoria que corresponde a la Entidad por su naturaleza propia, distinta de la que comparte con todos los sujetos obligados.',
    base: 'Resolución 1519 de 2020, Anexo 2, §4.1.2.',
    estado: 'ausente',
    motivo:
      'La Entidad no publica hoy esta categoría: el índice de transparencia de su sitio se detiene en la octava, de modo que la novena no existe en ninguna parte. La Sede la construye con su encabezado y su estado declarado, y la deja sin apartados, porque determinar cuáles son los suyos corresponde a la Entidad y no a esta Sede.',
    notaOrigen:
      'Esta es la única de las nueve categorías que el origen no publica en absoluto. No hay, por tanto, ningún documento que rastrear y ningún destino que enlazar: el vacío es del origen y se declara como tal.',
    apartados: [],
  },
]

// ---------------------------------------------------------------------------
// Auxiliares de lectura
// ---------------------------------------------------------------------------

/** Todos los documentos de un grupo de apartados, en el orden de la fuente. */
function documentosDeApartados(
  apartados: readonly ApartadoTransparencia[],
): DocumentoTransparencia[] {
  const salida: DocumentoTransparencia[] = []
  for (const apartado of apartados) {
    for (const grupo of apartado.grupos) {
      for (const documento of grupo.documentos) salida.push(documento)
    }
  }
  return salida
}

/** Todos los documentos de una categoría, en el orden de la fuente. */
export function documentosDe(categoria: CategoriaTransparencia): DocumentoTransparencia[] {
  return documentosDeApartados(categoria.apartados)
}

/** Cuántos documentos publica una categoría. */
export function totalDe(categoria: CategoriaTransparencia): number {
  return documentosDe(categoria).length
}

/** Si algún documento del inventario declara su fecha de publicación. */
export function hayFechaDeclarada(categoria: CategoriaTransparencia): boolean {
  return documentosDe(categoria).some((documento) => documento.fechaPublicacion !== undefined)
}

/**
 * Los documentos ordenados **del más reciente al más antiguo**, como exige el
 * Anexo 2 §4.1.2.1 de la Resolución 1519 de 2020.
 *
 * Mientras ningún documento declare su fecha —que es hoy el caso de las nueve
 * categorías— la función devuelve el orden de la fuente en lugar de fingir un
 * orden que no puede sostener: ordenar por una fecha que no existe sería
 * inventarse la cronología. En cuanto la Entidad declare las fechas, los
 * documentos con fecha van primero, de la más reciente a la más antigua, y los
 * que no la declaren quedan detrás, en el orden de la fuente, que es lo único
 * que se puede afirmar de ellos.
 */
export function ordenarPorFecha(
  documentos: readonly DocumentoTransparencia[],
): DocumentoTransparencia[] {
  const conFecha = documentos.filter((d) => d.fechaPublicacion !== undefined)
  if (conFecha.length === 0) return [...documentos]
  const sinFecha = documentos.filter((d) => d.fechaPublicacion === undefined)
  conFecha.sort((a, b) => (b.fechaPublicacion ?? '').localeCompare(a.fechaPublicacion ?? ''))
  return [...conFecha, ...sinFecha]
}

/** Una coincidencia de la búsqueda, con su sitio en la estructura. */
export interface CoincidenciaTransparencia {
  categoria: CategoriaTransparencia
  apartado: ApartadoTransparencia
  documento: DocumentoTransparencia
}

/**
 * Quita las tildes y baja a minúsculas.
 *
 * Se busca sobre el texto normalizado porque quien escribe «informacion» sin
 * tilde tiene que encontrar «Información», y quien escribe «Politica» tiene que
 * encontrar «Política». Sin esto, la mitad de las búsquedas de una sede
 * electrónica en castellano fallan por un acento.
 */
function normalizar(texto: string): string {
  return texto
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
}

/** El texto contra el que se busca un documento: su rótulo y su apartado. */
function textoBuscable(documento: DocumentoTransparencia, apartado: ApartadoTransparencia): string {
  return normalizar(`${documento.titulo} ${apartado.titulo}`)
}

/**
 * Busca un término en los documentos de una categoría.
 *
 * La búsqueda es **dentro de la sección** y no contra un buscador externo: es lo
 * que exige el criterio FUN-017 del expediente, y lo que impide que quien busca
 * un documento de una sede electrónica acabe en un buscador comercial.
 */
export function buscarEnCategoria(
  categoria: CategoriaTransparencia,
  termino: string,
): CoincidenciaTransparencia[] {
  const aguja = normalizar(termino.trim())
  if (aguja === '') return []
  const salida: CoincidenciaTransparencia[] = []
  for (const apartado of categoria.apartados) {
    for (const grupo of apartado.grupos) {
      for (const documento of grupo.documentos) {
        if (textoBuscable(documento, apartado).includes(aguja)) {
          salida.push({ categoria, apartado, documento })
        }
      }
    }
  }
  return salida
}

/** Busca un término en **toda** la sección, categoría por categoría. */
export function buscarEnSeccion(termino: string): CoincidenciaTransparencia[] {
  const salida: CoincidenciaTransparencia[] = []
  for (const categoria of CATEGORIAS) {
    salida.push(...buscarEnCategoria(categoria, termino))
  }
  return salida
}

/** La categoría de un segmento de ruta, o `undefined` si no existe. */
export function categoriaPorSlug(slug: string): CategoriaTransparencia | undefined {
  return CATEGORIAS.find((categoria) => categoria.slug === slug)
}

/** El rótulo del estado, para enseñarlo tal cual al ciudadano. */
export const ROTULO_ESTADO: Readonly<Record<EstadoCategoria, string>> = {
  publicada: 'Publicada por la Entidad',
  parcial: 'Publicada parcialmente',
  declarada: 'Sin contenido publicado',
  ausente: 'No publicada por la Entidad',
}
