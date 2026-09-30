<script setup lang="ts">
/**
 * Disposición de todo el sitio público.
 *
 * Monta las piezas que el Kit UI marca como obligatorias en **todas** las
 * páginas (sección 2.5 del expediente): barra de accesibilidad, barra superior
 * del Estado, cabecera, menú de navegación, pie de página y volver arriba.
 *
 * El `<main>` vive aquí y no en cada página. El criterio CAG-08 exige un enlace
 * «Saltar al contenido principal» que apunte a `#contenido-principal`, y ese
 * destino tiene que existir en todas las páginas: si cada vista declarara su
 * propio `<main>`, bastaría con que una lo olvidara para romper el enlace en
 * esa página, y el fallo no se vería. Con el destino en la disposición, el
 * enlace no puede quedar huérfano.
 */
import type { MenuPrincipal } from '~/types/menu'
import type { NivelMigaDePan } from '~/components/govco/MigaDePanGovco.vue'

/**
 * El menú obligatorio de la Sede.
 *
 * Sale del **Anexo 2.1 — Guía de diseño gráfico para sedes electrónicas**, que
 * el propio anexo declara «de obligatorio cumplimiento» por el artículo 14 del
 * Decreto 2106 de 2019:
 *
 *  - **Mínimos obligatorios:** Transparencia y acceso información pública,
 *    Atención y Servicios a la Ciudadanía y Participa.
 *  - **«En total son 7 ítems de menú principales»**, y la autoridad puede añadir
 *    más «conforme lo permitan las posibilidades de diseño y usabilidad». Aquí
 *    se usan los siete: los tres obligatorios más Inicio, PQRSD, Normativa y
 *    Noticias, que son páginas del propio anexo.
 *  - **«Se recomienda que el MENÚ quede ESTÁTICO y se pueda notar cuál es el
 *    ítem de menú en el que me encuentro.»** El despliegue no se abre solo al
 *    pasar el ratón: se abre al pulsar, y el ítem activo se marca según la ruta.
 *  - **Opción 2 (megamenú):** hasta **4 secciones internas** por ítem, con sus
 *    subsecciones. Participa lleva las seis que fija el §4.1.2.3.
 */
const menu: MenuPrincipal = [
  { etiqueta: 'Inicio', ruta: '/' },
  { etiqueta: 'Transparencia y acceso información pública', ruta: '/transparencia' },
  {
    etiqueta: 'Atención y Servicios a la Ciudadanía',
    subsecciones: [
      {
        titulo: 'Atención y Servicios a la Ciudadanía',
        enlaces: [
          { etiqueta: 'Trámites y servicios', ruta: '/tramites' },
          { etiqueta: 'Canales de atención', ruta: '/atencion' },
          { etiqueta: 'Realizar una petición', ruta: '/realizar-una-peticion' },
          { etiqueta: 'Seguimiento de una solicitud', ruta: '/seguimiento' },
        ],
      },
    ],
  },
  { etiqueta: 'PQRSD', ruta: '/pqrsd' },
  { etiqueta: 'Normativa', ruta: '/normativa' },
  { etiqueta: 'Noticias', ruta: '/noticias' },
  {
    etiqueta: 'Participa',
    subsecciones: [
      {
        titulo: 'Participa',
        enlaces: [
          {
            etiqueta:
              'Participación para la identificación de problemas y diagnóstico de necesidades',
            ruta: '/participa/identificacion-de-problemas',
          },
          {
            etiqueta: 'Planeación y/o presupuesto participativo',
            ruta: '/participa/presupuesto-participativo',
          },
          {
            etiqueta:
              'Participación y consulta ciudadana de proyectos, normas, políticas o programas',
            ruta: '/participa/consulta-ciudadana',
          },
          { etiqueta: 'Colaboración e innovación abierta', ruta: '/participa/innovacion-abierta' },
          { etiqueta: 'Rendición de cuentas', ruta: '/participa/rendicion-de-cuentas' },
          { etiqueta: 'Control ciudadano', ruta: '/participa/control-ciudadano' },
        ],
      },
    ],
  },
]

/**
 * Recorrido de la miga de pan, derivado de la ruta.
 *
 * Se construye desde el propio menú en lugar de declararlo página a página: así
 * el nombre que aparece en la miga y el que aparece en el menú no pueden
 * divergir, y una página nueva hereda su miga sin que nadie tenga que acordarse
 * de escribirla. CAG-11 la exige en todas las secciones salvo la portada, y
 * derivarla es la única forma de que eso se cumpla por construcción.
 */
const ruta = useRoute()
const enrutador = useRouter()

/** El buscador general lleva siempre a la misma página de resultados. */
function alBuscar(termino: string): void {
  enrutador.push({ path: '/buscar', query: { q: termino } })
}

/** Todos los destinos del menú, en un solo nivel, para poder buscarlos. */
const destinosDelMenu = menu.flatMap((item) => [
  ...(item.ruta ? [{ ruta: item.ruta, etiqueta: item.etiqueta }] : []),
  ...(item.subsecciones ?? []).flatMap((sub) => sub.enlaces),
])

/**
 * El nombre de la sección a la que pertenece una ruta. Se busca el prefijo más
 * largo que coincida, para que `/participa/control-ciudadano` use el nombre de
 * esa subcategoría y no el de `/participa`, y se descarta el destino raíz para
 * que `/` no se lea como prefijo de todo.
 */
function nombreDeLaSeccion(destino: string): string {
  const candidatos = destinosDelMenu
    .filter((d) => d.ruta !== '/' && (destino === d.ruta || destino.startsWith(`${d.ruta}/`)))
    .sort((a, b) => b.ruta.length - a.ruta.length)
  return candidatos[0]?.etiqueta ?? ''
}

const migaDePan = computed<NivelMigaDePan[]>(() => {
  // En la portada no hay recorrido que mostrar.
  if (ruta.path === '/') return []

  const niveles: NivelMigaDePan[] = [{ etiqueta: 'Inicio', ruta: '/' }]

  const segmentos = ruta.path.split('/').filter(Boolean)
  segmentos.forEach((segmento, indice) => {
    const destino = `/${segmentos.slice(0, indice + 1).join('/')}`
    const delMenu = nombreDeLaSeccion(destino)
    // Si el menú no lo conoce —una subcategoría, un detalle— se deriva del
    // segmento, que es lo único que queda sin inventarse un nombre.
    const etiqueta =
      delMenu || (segmento.charAt(0).toUpperCase() + segmento.slice(1)).replace(/-/g, ' ')
    niveles.push({ etiqueta, ruta: destino })
  })

  return niveles
})
</script>

<template>
  <div class="disposicion-sitio">
    <BarraSuperior />
    <BarraAccesibilidad />

    <!--
      El buscador general de la Sede va EN LA CABECERA, dentro del hueco que el
      propio componente reserva para él. Lo exige FUN-011 —«el encabezado debe
      incluir el logo de la entidad enlazado a inicio, buscador general…»— y
      además el Kit lo alinea a la derecha de la barra por su cuenta.

      Antes sólo estaba en la portada, así que desde cualquier otra página no
      había forma de buscar: había que volver al inicio primero.
    -->
    <CabeceraGovco destino-contenido="#contenido-principal">
      <template #buscador>
        <BuscadorGovco @buscar="alBuscar" />
      </template>

      <!--
        «Iniciar Sesión» en la cabecera, como la dibuja el Anexo 2.1 en su
        página 6.

        **Lleva a la entrada que ya existe**, la del panel, en `/admin/acceso`.
        No se inventa una pantalla de acceso de ciudadano que no está construida:
        un botón que abre un formulario que no autentica es peor que no tenerlo,
        porque el ciudadano cree haber iniciado sesión. Cuando exista el módulo de
        identidad ciudadana, este enlace apuntará a su entrada.
      -->
      <template #acciones>
        <a class="enlace-sesion" href="/admin/acceso">Iniciar sesión</a>
      </template>
    </CabeceraGovco>

    <MenuNavegacionGovco :items="menu" etiqueta-accesible="Menú principal de la Sede Electrónica" />

    <MigaDePanGovco :niveles="migaDePan" />

    <!--
      `tabindex="-1"` permite que el enlace de salto mueva el foco aquí. Sin él,
      el navegador desplaza la vista pero deja el foco donde estaba, y quien
      navega con teclado sigue tabulando desde la cabecera: el salto no serviría
      de nada.
    -->
    <main id="contenido-principal" tabindex="-1">
      <slot />
    </main>

    <PiePaginaGovco />
    <VolverArriba />
  </div>
</template>

<style scoped>
/*
  El enlace de sesión de la cabecera. Se estiliza aquí y no se le pone una clase
  del Kit porque el Kit no trae un botón de sesión para la cabecera: el suyo vive
  en el módulo de inicio de sesión, que es otra pantalla. Se resuelve con el azul
  cobalto del Kit y un área de pulsación de 44 px de alto, que es el mínimo táctil
  que fija el propio Kit (CAG-23).
*/
.enlace-sesion {
  display: inline-flex;
  align-items: center;
  flex: none;
  min-height: 2.75rem;
  padding: 0 1rem;
  border: 0.125rem solid var(--govcolor-cobalt, #0943b5);
  border-radius: 1.5rem;
  color: var(--govcolor-cobalt, #0943b5);
  font-family: 'Nunito_Sans-SemiBold', system-ui, sans-serif;
  font-size: 0.9375rem;
  text-decoration: none;
  white-space: nowrap;
}

.enlace-sesion:hover {
  background-color: var(--govcolor-solitude, #e5ecf8);
}

.disposicion-sitio {
  display: flex;
  flex-direction: column;
  /* El pie queda abajo aunque la página tenga poco contenido, sin flotar a
     media altura. */
  min-height: 100vh;
}

#contenido-principal {
  flex: 1;
}

/*
  El foco no debe dibujar un recuadro alrededor de TODO el contenido al saltar:
  el destino es un ancla de teclado, no un control. El foco sigue yendo ahí —que
  es lo que lo hace útil— pero sin marco.
*/
#contenido-principal:focus {
  outline: none;
}
</style>
