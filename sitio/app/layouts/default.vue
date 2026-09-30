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
 * No se inventa: sale de los criterios del expediente.
 *
 *  - **§4.1 del Anexo 2 de la Resolución 2893 de 2020**, que el propio anexo
 *    declara «de obligatorio cumplimiento», fija tres botones: **Transparencia y
 *    acceso a información pública**, **Servicios a la ciudadanía** y
 *    **Participa**. Añade que la autoridad puede poner más, y aquí se añaden dos
 *    —Inicio y Noticias—, que son secciones de la sede según el mismo anexo.
 *  - **§4.1.2.3** desdobla Participa en **seis subcategorías**, con estos nombres
 *    exactos. Faltaban: el menú sólo mostraba dos, y por eso no se veía lo que la
 *    norma pide.
 *  - **FUN-012** limita el menú a **siete opciones** principales y a **dos
 *    niveles**. Aquí hay cinco y un solo nivel de despliegue.
 *  - **FUN-013** obliga a que las tres secciones estén visibles.
 *
 * Las subcategorías de Participa apuntan a `/participa/<slug>`, que resuelve una
 * única página contra la lista de slugs válidos. Seis archivos casi idénticos
 * serían seis sitios donde equivocarse.
 */
const menu: MenuPrincipal = [
  { etiqueta: 'Inicio', ruta: '/' },
  { etiqueta: 'Transparencia y acceso a información pública', ruta: '/transparencia' },
  { etiqueta: 'Servicios a la Ciudadanía', ruta: '/servicios' },
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
