<script setup lang="ts">
/**
 * Formulario de PQRSD.
 *
 * El formulario todavía no se puede presentar: depende del sistema de radicación
 * y del módulo de PQRSD, que aún no están integrados. Un formulario que se ve
 * pero no radica nada es peor que la ausencia de formulario: el ciudadano cree
 * haber presentado una petición —y con ella empiezan a correr términos legales—
 * cuando en realidad no ha llegado nada a la Entidad.
 *
 * Por eso la página declara lo que publicará, sin dibujar campos que no llevan a
 * ninguna parte. La descripción no es una promesa nueva: son las condiciones que
 * el plan ya fijó para este módulo (tipificación, aviso de privacidad y
 * autorización de tratamiento de datos, con acuse de recibo en minutos).
 *
 * Mientras tanto, los canales de atención que la Entidad ya tiene publicados
 * están en `/atencion`.
 */
useHead({ title: 'PQRSD · Sede Electrónica' })
</script>

<template>
  <SeccionEnPreparacion
    titulo="Peticiones, quejas, reclamos, sugerencias y denuncias (PQRSD)"
    proposito="Formulario para radicar una PQRSD en línea: tipo de solicitud, datos de contacto, aviso de privacidad y autorización del tratamiento de datos personales, con acuse de recibo y número de radicado para hacerle seguimiento."
  />
</template>
