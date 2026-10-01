<script setup lang="ts">
/**
 * Participa — página de la sección.
 *
 * Enumera las **seis subcategorías** que fija el §4.1.2.3 del Anexo 2 de la
 * Resolución 2893 de 2020. El menú ya las despliega, y esta página las repite en
 * el cuerpo a propósito: quien llegue por un buscador o por un enlace directo
 * tiene que poder ver qué contiene la sección sin abrir el menú.
 */
useHead({ title: 'Participa · Sede Electrónica' })

const SUBCATEGORIAS = [
  {
    ruta: '/participa/identificacion-de-problemas',
    titulo: 'Identificación de problemas y diagnóstico de necesidades',
  },
  {
    ruta: '/participa/presupuesto-participativo',
    titulo: 'Planeación y/o presupuesto participativo',
  },
  {
    ruta: '/participa/consulta-ciudadana',
    titulo: 'Consulta ciudadana de proyectos, normas, políticas o programas',
  },
  { ruta: '/participa/innovacion-abierta', titulo: 'Colaboración e innovación abierta' },
  { ruta: '/participa/rendicion-de-cuentas', titulo: 'Rendición de cuentas' },
  { ruta: '/participa/control-ciudadano', titulo: 'Control ciudadano' },
]
</script>

<template>
  <div class="container py-5">
    <h1>Participa</h1>

    <p class="lead">
      Espacios y mecanismos con los que la ciudadanía interviene en las decisiones de la
      Alcaldía Distrital de Santa Marta.
    </p>

    <h2 class="h4 mt-5 mb-3">Mecanismos de participación</h2>

    <ul class="list-unstyled">
      <li v-for="subcategoria in SUBCATEGORIAS" :key="subcategoria.ruta" class="mb-2">
        <a :href="subcategoria.ruta">{{ subcategoria.titulo }}</a>
      </li>
    </ul>

    <h2 class="h4 mt-5 mb-3">Portales</h2>
    <p class="mb-0">
      <a href="/portales">Portales de programas transversales</a>
    </p>

    <p class="mt-4 mb-0">
      <a class="btn btn-outline-primary" href="/">Volver a la portada</a>
    </p>
  </div>
</template>
