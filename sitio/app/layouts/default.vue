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
import { menuPrincipal } from '~/config/sitemap'

// Componentes globales de la auditoría (D-01, D-02)
import BannerCookies from '~/components/BannerCookies.vue'
import ModalAvisoSalida from '~/components/ModalAvisoSalida.vue'
import { useAvisoSalida } from '~/composables/useAvisoSalida'
import { useMetadatosComparticion } from '~/composables/useMetadatosComparticion'
import GaleriaAplicacionesGovco from '~/components/govco/GaleriaAplicacionesGovco.vue'

/*
 * Metadatos de compartición y URL canónica (D-29). Se declaran aquí porque la
 * disposición envuelve todas las páginas y se ejecuta también en el servidor,
 * que es donde los lee un rastreador.
 */
useMetadatosComparticion()

// Estado del modal de aviso de salida a sitio externo
const { visible, enlacePendiente, origenDelAviso, confirmarNavegacion, cancelarNavegacion } =
  useAvisoSalida()

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
/*
 * El orden no es estético: lo fija el Anexo 2 §4.1.2.
 *
 * Los tres menús mínimos obligatorios son **Transparencia, Servicios a la
 * Ciudadanía y Participa**, y las opciones adicionales «deberán estar ubicadas
 * después de los tres menús mínimos obligatorios». PQRSD, Normativa y Noticias
 * son adicionales, así que van detrás de Participa.
 */
/*
 * El menú se toma de `config/sitemap.ts`, que es la fuente única de rutas,
 * rótulos y estado de publicación del sitio.
 *
 * Antes esta lista se escribía aquí, y el mapa del sitio y el `sitemap.xml`
 * llevaban cada uno la suya: tres listas que podían —y llegaron a— discrepar en
 * nombres y en qué se anuncia al buscador. Con el menú derivado de la
 * configuración, añadir una sección es tocar un solo fichero y los tres
 * artefactos quedan de acuerdo sin que nadie tenga que acordarse (RF-B1-010).
 */
const menu: MenuPrincipal = menuPrincipal

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

/**
 * Aplicaciones de la galería de la cabecera (RF-B3-068).
 *
 * Las tres que el Kit UI dibuja para la cabecera de la sede: Portal GOV.CO,
 * Carpeta Ciudadana y CIIU.
 *
 * **Los destinos son los oficiales de cada servicio**, y no un dominio
 * aproximado: la Carpeta Ciudadana es de los Servicios Ciudadanos Digitales
 * (`carpetaciudadana.gov.co`, no el SECOP, que es contratación) y el CIIU lo
 * publica el DANE (`dane.gov.co`, no la DIAN, que es impuestos). Un enlace
 * institucional que lleva a otro organismo es peor que no tenerlo: el ciudadano
 * cree estar en el sitio correcto y no lo está.
 *
 * Las tres son dominios de confianza en `useAvisoSalida`, así que no disparan el
 * aviso de salida: son extensiones del ecosistema del Estado, no una salida a un
 * tercero.
 */
const aplicacionesGaleria = [
  {
    id: 'portal-govco',
    nombre: 'Portal del Estado Colombiano',
    enlace: 'https://www.gov.co',
    icono: 'govco' as const,
  },
  {
    id: 'carpeta-ciudadana',
    nombre: 'Carpeta Ciudadana',
    enlace: 'https://carpetaciudadana.gov.co',
    icono: 'carpeta' as const,
  },
  {
    id: 'ciiu',
    nombre: 'CIIU — Clasificación Industrial (DANE)',
    enlace: 'https://www.dane.gov.co',
    icono: 'ciiu' as const,
  },
]
</script>

<template>
  <div class="disposicion-sitio">
    <!--
      **El enlace de salto va el primero, y aquí.** RF-B3-022 lo exige literal:
      «Primer Tab en cualquier página → aparece "Saltar al contenido principal"»,
      y `Sección 3:148,252` lo repite («poner… como primer enlace de la página»).

      Estaba dentro de la cabecera, y la cabecera se monta **después** de la barra
      superior y de la barra de accesibilidad: el primer tabulador era el enlace a
      GOV.CO y el atajo llegaba tras ocho o nueve paradas, que es justo lo que
      existe para evitar. Medido con navegador real antes de moverlo (auditoría,
      hallazgo R-P4). El destino sigue siendo `#contenido-principal`, que la
      disposición declara más abajo con `tabindex="-1"`.
    -->
    <a class="sr-only sr-only-focusable" href="#contenido-principal">
      Saltar al contenido principal
    </a>

    <!--
      **El botón circular de accesibilidad.** Flota fijo, centrado verticalmente
      en el lado derecho, e idéntico en todas las pantallas. Va justo después del
      enlace de salto —que sigue siendo el primer tabulable, como exige RF-B3-022—
      y **fuera** del envoltorio filtrable, para que los modos de contraste no lo
      inviertan y para que un `filter` no lo desancle de la ventana.
    -->
    <BotonAccesibilidad />

    <!--
      **Envoltorio del contenido que sí se filtra.** Los modos «colores
      invertidos» y «escala de grises» aplican aquí su `filter`, y no sobre
      `#__nuxt`, por dos motivos que se descubrieron midiendo:

        1. Un `filter` convierte al elemento en **bloque contenedor** de sus
           descendientes con `position: fixed`. Filtrar `#__nuxt` desanclaba todo
           lo flotante del sitio, y la única salida era teletransportarlo a
           `body`.
        2. Y teletransportar tenía un precio que no se ve hasta que se mide: Vue
           emite lo teletransportado **antes** del contenedor de la aplicación,
           así que el botón de accesibilidad quedaba por delante del enlace
           «Saltar al contenido principal» y rompía RF-B3-022 (el enlace de salto
           tiene que ser el primer tabulable).

      Filtrando sólo este envoltorio, el contenido se invierte y los controles
      flotantes conservan a la vez su posición y su orden en el documento. Y el
      filtro es **uno solo** para todo el bloque —y no uno por elemento— para que
      el desplegable del menú siga pintándose por encima del contenido: cada
      `filter` crea un contexto de apilamiento, y si cada bloque tuviera el suyo,
      el contenido posterior taparía los desplegables.

      `flex: 1` mantiene el pie abajo aunque la página tenga poco contenido.
    -->
    <div class="contenido-filtrable">
      <BarraSuperior />

      <!--
        El buscador general de la Sede va EN LA CABECERA, dentro del hueco que el
        propio componente reserva para él. Lo exige FUN-011 —«el encabezado debe
        incluir el logo de la entidad enlazado a inicio, buscador general…»— y
        además el Kit lo alinea a la derecha de la barra por su cuenta.

        Antes sólo estaba en la portada, así que desde cualquier otra página no
        había forma de buscar: había que volver al inicio primero.
      -->
      <CabeceraGovco>
        <template #buscador>
          <BuscadorGovco @buscar="alBuscar" />
        </template>

        <template #acciones>
          <!--
            La galería de aplicaciones. **El botón de accesibilidad ya no va aquí**:
            pasó a ser un círculo flotante fijo en el lado derecho, para estar siempre
            en el mismo sitio en cualquier pantalla. Su montaje está arriba, tras el
            enlace de salto.

            **El botón de login ahora está en la BarraSuperior** (la franja azul del
            Estado), en la esquina derecha, con un icono de usuario outline en blanco.
          -->
          <GaleriaAplicacionesGovco
            :aplicaciones="aplicacionesGaleria"
            etiqueta-boton="Abrir aplicaciones y servicios"
            ayuda-boton="Acceso al Portal GOV.CO, Carpeta Ciudadana y CIIU."
          />
        </template>
      </CabeceraGovco>

      <!--
        El buscador se pasa también al menú, en el hueco que el componente
        expone. En escritorio el componente lo oculta —el buscador ya está en la
        cabecera— y en móvil, con el menú desplegado, es la única forma de buscar
        sin cerrarlo: el Kit sitúa ahí su buscador y aquí se respeta (D-50).
      -->
      <MenuNavegacionGovco :items="menu" etiqueta-accesible="Menú principal de la Sede Electrónica">
        <template #buscador>
          <BuscadorGovco @buscar="alBuscar" />
        </template>
      </MenuNavegacionGovco>

      <MigaDePanGovco :niveles="migaDePan" />

      <!--
        `tabindex="-1"` permite que el enlace de salto mueva el foco aquí. Sin
        él, el navegador desplaza la vista pero deja el foco donde estaba, y
        quien navega con teclado sigue tabulando desde la cabecera: el salto no
        serviría de nada.
      -->
      <main id="contenido-principal" tabindex="-1">
        <slot />
      </main>

      <PiePaginaGovco />
    </div>

    <VolverArriba />

    <!-- Componentes de la auditoría: banner de cookies y aviso de salida -->
    <BannerCookies />
    <ModalAvisoSalida
      v-if="enlacePendiente"
      :visible="visible"
      :destino="enlacePendiente.url"
      :nombre-destino="enlacePendiente.nombre"
      :entidad-responsable="enlacePendiente.entidad"
      :origen="origenDelAviso"
      @confirmar="confirmarNavegacion"
      @cancelar="cancelarNavegacion"
    />

    <!--
      El panel de ajustes de accesibilidad. Se monta una sola vez por página y lo
      abren sus dos disparadores —el círculo flotante y el enlace del pie— porque
      la apertura es estado compartido, no un evento que se pase de uno a otro.
      Va también fuera del envoltorio filtrable: un `<dialog>` abierto con
      `showModal()` se dibuja en la capa superior, pero su contenido no debe
      heredar la inversión de colores del modo.
    -->
    <PanelAccesibilidad />
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

/*
 * `sr-only` y `sr-only-focusable` sacan el elemento de la vista sin sacarlo del
 * árbol de accesibilidad, y lo devuelven a su sitio al recibir el foco. Son las
 * clases que pide CAG-08, pero **no las define ninguna hoja cargada**: `all.css`
 * no las trae y Bootstrap 5 las renombró a `visually-hidden`. Se implementan
 * aquí, en la disposición, porque aquí vive ahora el enlace.
 *
 * Se hace con `clip` y no con `display: none` a propósito: `display: none` lo
 * borraría también para quien usa lector de pantalla, y el enlace existe
 * precisamente para esa persona.
 */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/*
 * Al aparecer tiene que leerse, y ahí arriba compite con la barra azul del Estado
 * y con la blanca de la cabecera. Se queda en el flujo (`position: relative`, no
 * `fixed`) para que empuje la página hacia abajo mientras está visible, que es
 * como se comporta un enlace de salto y como lo espera quien lo usa.
 */
.sr-only-focusable:active,
.sr-only-focusable:focus {
  position: relative;
  z-index: 1100;
  display: block;
  width: auto;
  height: auto;
  padding: 0.75rem 1rem;
  margin: 0;
  overflow: visible;
  clip: auto;
  white-space: normal;
  background-color: var(--govcolor-white, #ffffff);
  color: var(--govcolor-cobalt, #0943b5);
  font-family: 'Verdana-Bold', Verdana, sans-serif;
  text-decoration: underline;
  outline: 0.125rem solid var(--govcolor-cobalt, #0943b5);
  outline-offset: -0.125rem;
}

#contenido-principal {
  flex: 1;
}

/*
 * El envoltorio del contenido filtrable. Reproduce la disposición en columna
 * que antes tenía `.disposicion-sitio`, para que sus hijos —barra superior,
 * cabecera, menú, migas, contenido y pie— se comporten exactamente igual que
 * cuando eran hijos directos. `flex: 1` es lo que hace que `#contenido-principal`
 * siga empujando el pie hasta abajo.
 */
.contenido-filtrable {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
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
