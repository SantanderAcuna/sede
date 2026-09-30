<script setup lang="ts">
/**
 * Portada del sitio público.
 *
 * Está construida sobre el Kit gov.co vendorizado (§9.4), con su barra superior
 * del Estado y su estructura, y no sobre la pantalla de bienvenida de la plantilla
 * de Nuxt, que era lo que estaba publicado en la dirección de la Entidad.
 *
 * Contenido: el mínimo veraz. El nombre y la naturaleza de la Entidad, el marco
 * normativo que obliga a tener sede electrónica, y accesos a lo que ya existe. No
 * se inventa la carta de trámites ni los servicios: eso depende del CMS y de la
 * migración, que son fases posteriores. Poner contenido de relleno en una sede
 * electrónica es peor que no poner nada, porque parece información oficial.
 *
 * Accesibilidad (§11): el sitio se rige por la resolución 1519 de 2020 y las
 * pautas WCAG 2.1 AA. Esta página cumple lo que le corresponde por sí misma: salto
 * al contenido, regiones con nombre, jerarquía de encabezados sin saltos y foco
 * visible. Lo demás se verifica con las herramientas del §11.6, no aquí.
 */
useHead({
  title: 'Sede Electrónica · Alcaldía Distrital de Santa Marta',
  meta: [
    {
      name: 'description',
      content:
        'Sede Electrónica de la Alcaldía Distrital de Santa Marta. Punto de acceso electrónico a los trámites, servicios y canales de atención de la Entidad.',
    },
  ],
})

const anioActual = new Date().getFullYear()
</script>

<template>
  <div>
    <!--
      Primer elemento enfocable de la página. Es un requisito, no un adorno: quien
      navega con teclado o con lector de pantalla no debería atravesar la cabecera
      entera en cada página para llegar al contenido (WCAG 2.4.1).
    -->
    <a class="saltar-a-contenido" href="#contenido">Saltar al contenido principal</a>

    <!-- Barra superior del Estado, con el marcado del propio Kit. -->
    <div class="barra-superior-govco">
      <a
        href="https://www.gov.co/"
        target="_blank"
        rel="noopener"
        aria-label="Portal del Estado Colombiano - GOV.CO"
      ></a>
    </div>

    <header class="barra-logos-govco">
      <div class="container">
        <p class="entidad">Alcaldía Distrital de Santa Marta</p>
        <p class="dependencia">Sede Electrónica</p>
      </div>
    </header>

    <main id="contenido" tabindex="-1">
      <div class="container py-5">
        <h1>Sede Electrónica</h1>

        <p class="lead">
          Punto de acceso electrónico de la Alcaldía Distrital de Santa Marta,
          conforme al artículo 14 del Decreto Ley 2106 de 2019.
        </p>

        <div class="row mt-4">
          <div class="col-md-8">
            <h2>Estado del sitio</h2>
            <p>
              El sitio está en construcción. Los trámites, los servicios y la
              atención en línea se irán publicando aquí a medida que se integren con
              los sistemas de la Entidad.
            </p>

            <h2>Accesos</h2>
            <ul class="list-unstyled">
              <li class="mb-2">
                <!-- La barra final importa: el punto de entrada sirve el panel bajo
                     el prefijo /admin/. -->
                <a class="btn btn-outline-primary" href="/admin/">
                  Panel de administración
                </a>
                <span class="text-muted ms-2">Para funcionarios y autogestión</span>
              </li>
              <li class="mb-2">
                <a class="btn btn-outline-primary" href="/health">
                  Estado del servicio
                </a>
                <span class="text-muted ms-2">Comprobación técnica</span>
              </li>
            </ul>
          </div>

          <aside class="col-md-4" aria-labelledby="titulo-ayuda">
            <h2 id="titulo-ayuda" class="h5">Ayuda</h2>
            <p class="small mb-0">
              Si encuentra un problema de acceso o de accesibilidad en esta sede,
              repórtelo a la Entidad para que se corrija.
            </p>
          </aside>
        </div>
      </div>
    </main>

    <footer class="barra-inferior-govco">
      <div class="container py-4">
        <p class="mb-1">Alcaldía Distrital de Santa Marta</p>
        <p class="mb-0 small">
          Sede Electrónica · {{ anioActual }}
        </p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/*
  El enlace de salto se oculta hasta que recibe el foco. No se usa `display: none`
  ni `visibility: hidden`, porque eso lo sacaría del orden de tabulación y dejaría
  de existir para quien lo necesita.
*/
.saltar-a-contenido {
  position: absolute;
  left: -9999px;
  top: 0;
  z-index: 1000;
  padding: 0.75rem 1rem;
  background: #ffffff;
  color: var(--govcolor-cobalt, #0943b5);
  font-weight: 600;
  text-decoration: underline;
}

.saltar-a-contenido:focus {
  left: 0;
}

/* El foco tiene que verse siempre: es la única señal de dónde está uno. */
a:focus-visible,
button:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 2px;
}

.entidad {
  margin: 0;
  font-weight: 700;
}

.dependencia {
  margin: 0;
  color: var(--govcolor-matterhorn, #4c4c4c);
}
</style>
