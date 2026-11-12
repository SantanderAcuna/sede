<script setup lang="ts">
/**
 * Ficha de un elemento del catálogo — `/tramites/{slug}`.
 *
 * **Qué publica.** La ficha propia de la Sede: qué es, quién puede solicitarlo, requisitos, cómo se
 * hace (pasos), documentos, costo y cuentas, dónde se atiende (con georreferenciación), qué norma lo
 * faculta, por dónde se consulta el estado y qué entrega. Rotula sus atributos uno por uno (Guía
 * §5.1.3) y enlaza la **ficha oficial de GOV.CO** como referencia nacional (integración, RF-B1-021),
 * no como destino principal.
 *
 * **De dónde sale.** De `GET /tramites/{slug}` del contrato, que devuelve la ficha por partes
 * relacionales. Esta página no inventa nada: un bloque que la Entidad no declara no se dibuja.
 *
 * **Lo que NO hace, y por qué.** No dibuja un botón de pago ni finge la consulta del estado: el
 * expediente electrónico y la pasarela son la Fase B (flujo transaccional de 4 etapas). El stepper
 * de las 4 etapas se muestra como guía del proceso (RF-B2-028); su cableado llega con esa fase.
 */
import type { components } from '~~/types/openapi'

type Ficha = components['schemas']['TramiteFichaDetalle']

interface RespuestaFicha {
  data: Ficha
}

const route = useRoute()
const slug = computed(() => String(route.params.slug ?? ''))

const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')
const traer = useRequestFetch()

const { data, error } = await useAsyncData<RespuestaFicha>(
  () => `tramite-${slug.value}`,
  () => traer<RespuestaFicha>(`${urlApi}/tramites/${encodeURIComponent(slug.value)}`),
)

// Un slug que no existe es un 404 de verdad, no una página vacía.
if (error.value || !data.value?.data) {
  throw createError({ statusCode: 404, statusMessage: 'El trámite no existe.', fatal: true })
}

const ficha = computed<Ficha>(() => data.value!.data)

useHead(() => ({
  title: `${ficha.value.nombre} · Trámites y servicios · Sede Electrónica`,
  meta: [{ name: 'description', content: ficha.value.resumen ?? ficha.value.nombre }],
}))

// ---------------------------------------------------------------------------
// Etiquetas y derivados de presentación
// ---------------------------------------------------------------------------

const NOMBRE_TIPO: Record<string, string> = {
  tramites: 'Trámite',
  opa: 'Otro procedimiento administrativo (OPA)',
  consultas: 'Consulta de acceso a información pública',
}
const ETIQUETA_MODALIDAD: Record<string, string> = {
  en_linea: 'En línea',
  parcialmente_en_linea: 'Parcialmente en línea',
  presencial: 'Presencial',
}
const ETIQUETA_SOLICITANTE: Record<string, string> = {
  ciudadano: 'Ciudadano',
  extranjeros: 'Extranjeros',
  organizaciones: 'Organizaciones',
  instituciones: 'Instituciones',
}

const textoModalidad = computed(() =>
  ficha.value.modalidad ? (ETIQUETA_MODALIDAD[ficha.value.modalidad] ?? ficha.value.modalidad) : null,
)
const textoCosto = computed(() => {
  if (ficha.value.tiene_costo == null) return null
  return ficha.value.tiene_costo === 'con_costo' ? 'Con costo' : 'Gratuito'
})
const textoDuracion = computed(() => {
  const d = ficha.value.tiempo_solucion_dias
  if (d == null) return null
  if (d === 0) return 'Inmediato'
  return `${d} día${d === 1 ? '' : 's'} hábil${d === 1 ? '' : 'es'}`
})

/** Las 4 etapas del flujo GOV.CO (RF-B2-028), presentacionales en esta fase. */
const ETAPAS = [
  { titulo: 'Inicio', detalle: 'Consulte la ficha: requisitos, costo, tiempo y dónde iniciarlo.' },
  { titulo: 'Hago mi solicitud', detalle: 'Reúna los requisitos y radique su solicitud.' },
  { titulo: 'Procesan mi solicitud', detalle: 'La dependencia revisa y tramita su solicitud.' },
  { titulo: 'Respuesta', detalle: 'Recibe el resultado por el medio definido.' },
]

const requisitos = computed(() => ficha.value.requisitos ?? [])
const pasos = computed(() => ficha.value.pasos ?? [])
const documentos = computed(() => ficha.value.documentos ?? [])
const puntos = computed(() => ficha.value.puntos_atencion ?? [])
const normas = computed(() => ficha.value.normativa ?? [])
const canales = computed(() => (ficha.value.canales_consulta_estado ?? []).filter((c) => c.habilitado))
const solicitantes = computed(() => ficha.value.solicitantes ?? [])
const cuentas = computed(() => ficha.value.costo?.cuentas ?? [])
const medios = computed(() => ficha.value.medios_resultado ?? [])

function enlaceMapa(lat: number | null | undefined, lon: number | null | undefined): string | null {
  if (lat == null || lon == null) return null
  return `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}#map=17/${lat}/${lon}`
}

const tieneInicioEnLinea = computed(
  () => ficha.value.modalidad !== 'presencial' && !!ficha.value.url_inicio,
)
</script>

<template>
  <div class="container py-5 ficha-tramite">
    <nav class="migas" aria-label="Ruta">
      <NuxtLink to="/">Inicio</NuxtLink>
      <span aria-hidden="true">›</span>
      <NuxtLink to="/tramites">Trámites y servicios</NuxtLink>
      <span aria-hidden="true">›</span>
      <span aria-current="page">{{ ficha.nombre }}</span>
    </nav>

    <p class="tipo-elemento mt-2">{{ NOMBRE_TIPO[ficha.type] ?? 'Trámite' }}</p>
    <h1 class="mb-2">{{ ficha.nombre }}</h1>

    <ul class="chips" role="list">
      <li v-if="ficha.dependencia" class="chip chip-fuerte">{{ ficha.dependencia.nombre }}</li>
      <li v-if="textoModalidad" class="chip">{{ textoModalidad }}</li>
      <li v-if="textoCosto" class="chip">{{ textoCosto }}</li>
      <li v-if="textoDuracion" class="chip">Duración: {{ textoDuracion }}</li>
    </ul>

    <p v-if="ficha.resumen" class="resumen mt-2">{{ ficha.resumen }}</p>

    <!-- Acciones principales. -->
    <div class="acciones mt-3">
      <a
        v-if="tieneInicioEnLinea"
        class="btn btn-govco"
        :href="ficha.url_inicio!"
        rel="external"
      >
        Iniciar en línea<span class="solo-lectores">: {{ ficha.nombre }}</span>
      </a>
      <a
        v-if="ficha.url_ficha_gov_co"
        class="btn btn-govco outline-btn-govco"
        :href="ficha.url_ficha_gov_co"
        rel="external"
      >
        Ficha oficial en GOV.CO
      </a>
    </div>

    <!-- Stepper de las 4 etapas (guía del proceso). -->
    <section class="bloque" aria-labelledby="etapas-titulo">
      <h2 id="etapas-titulo" class="h5">¿Cómo es el proceso?</h2>
      <ol class="etapas" role="list">
        <li v-for="(e, i) in ETAPAS" :key="e.titulo" class="etapa">
          <span class="etapa-numero" aria-hidden="true">{{ i + 1 }}</span>
          <div>
            <p class="etapa-titulo mb-1">{{ e.titulo }}</p>
            <p class="etapa-detalle mb-0">{{ e.detalle }}</p>
          </div>
        </li>
      </ol>
    </section>

    <!-- ¿Quién puede solicitarlo? -->
    <section v-if="solicitantes.length > 0" class="bloque" aria-labelledby="solicitantes-titulo">
      <h2 id="solicitantes-titulo" class="h5">¿Quién puede solicitarlo?</h2>
      <ul class="pildoras" role="list">
        <li v-for="s in solicitantes" :key="s" class="pildora">
          {{ ETIQUETA_SOLICITANTE[s] ?? s }}
        </li>
      </ul>
    </section>

    <!-- Requisitos. -->
    <section v-if="requisitos.length > 0" class="bloque" aria-labelledby="requisitos-titulo">
      <h2 id="requisitos-titulo" class="h5">¿Qué necesita?</h2>
      <ul class="lista-requisitos" role="list">
        <li v-for="(r, i) in requisitos" :key="i" class="requisito">
          <p class="mb-1">
            {{ r.descripcion }}
            <span v-if="r.obligatorio === false" class="etiqueta-opcional">Opcional</span>
          </p>
          <p v-if="r.nota" class="requisito-nota mb-1">{{ r.nota }}</p>
          <a v-if="r.url" class="requisito-enlace" :href="r.url" rel="external">Ver más</a>
        </li>
      </ul>
    </section>

    <!-- Cómo se hace (pasos). -->
    <section v-if="pasos.length > 0" class="bloque" aria-labelledby="pasos-titulo">
      <h2 id="pasos-titulo" class="h5">¿Cómo se hace?</h2>
      <ol class="lista-pasos">
        <li v-for="p in pasos" :key="p.orden" class="paso">
          <p class="paso-titulo mb-1">{{ p.titulo }}</p>
          <p v-if="p.descripcion" class="mb-0">{{ p.descripcion }}</p>
        </li>
      </ol>
    </section>

    <!-- Documentos. -->
    <section v-if="documentos.length > 0" class="bloque" aria-labelledby="documentos-titulo">
      <h2 id="documentos-titulo" class="h5">Documentos</h2>
      <ul class="lista-simple" role="list">
        <li v-for="(d, i) in documentos" :key="i">
          <a v-if="d.url" :href="d.url" rel="external">{{ d.nombre }}</a>
          <span v-else>{{ d.nombre }}</span>
          <span v-if="d.formato" class="documento-formato">({{ d.formato }})</span>
        </li>
      </ul>
    </section>

    <!-- Costo. -->
    <section v-if="ficha.tiene_costo === 'con_costo'" class="bloque" aria-labelledby="costo-titulo">
      <h2 id="costo-titulo" class="h5">Costo</h2>
      <p v-if="ficha.costo?.descripcion" class="mb-2">{{ ficha.costo.descripcion }}</p>
      <p v-else-if="ficha.costo?.valor" class="mb-2">
        Valor: {{ ficha.costo.valor }} {{ ficha.costo.moneda ?? '' }}
      </p>
      <div v-if="cuentas.length > 0" class="cuentas">
        <h3 class="h6">Cuentas de recaudo</h3>
        <ul class="lista-simple" role="list">
          <li v-for="(c, i) in cuentas" :key="i">
            <strong>{{ c.banco }}</strong>
            <span v-if="c.tipo"> · {{ c.tipo }}</span>
            <span v-if="c.numero"> · N.º {{ c.numero }}</span>
            <span v-if="c.titular"> · {{ c.titular }}</span>
          </li>
        </ul>
      </div>
      <p class="aviso-pago mt-2">
        La Sede no cobra en línea: el pago se hace por los canales y cuentas oficiales que la Entidad
        indica.
      </p>
    </section>

    <!-- Puntos de atención (con georreferenciación). -->
    <section v-if="puntos.length > 0" class="bloque" aria-labelledby="puntos-titulo">
      <h2 id="puntos-titulo" class="h5">¿Dónde se atiende?</h2>
      <ul class="lista-puntos" role="list">
        <li v-for="(p, i) in puntos" :key="i" class="punto">
          <p class="punto-nombre mb-1">{{ p.nombre }}</p>
          <p class="mb-1">{{ p.direccion }}</p>
          <p v-if="p.horario" class="punto-dato mb-1">Horario: {{ p.horario }}</p>
          <p v-if="p.telefono" class="punto-dato mb-1">Teléfono: {{ p.telefono }}</p>
          <a
            v-if="enlaceMapa(p.latitud, p.longitud)"
            class="punto-mapa"
            :href="enlaceMapa(p.latitud, p.longitud)!"
            rel="external"
          >
            Ver en el mapa<span class="solo-lectores">: {{ p.nombre }}</span>
          </a>
        </li>
      </ul>
    </section>

    <!-- Consulta del estado. -->
    <section v-if="canales.length > 0" class="bloque" aria-labelledby="canales-titulo">
      <h2 id="canales-titulo" class="h5">¿Cómo consultar el estado?</h2>
      <ul class="lista-simple" role="list">
        <li v-for="(c, i) in canales" :key="i">
          <span>{{ c.nombre ?? c.canal }}</span>
          <a v-if="c.url" :href="c.url" rel="external"> — abrir</a>
          <span v-if="c.telefono"> · {{ c.telefono }}</span>
          <span v-if="c.correo"> · {{ c.correo }}</span>
        </li>
      </ul>
    </section>

    <!-- Resultado. -->
    <section v-if="ficha.resultado || medios.length > 0" class="bloque" aria-labelledby="resultado-titulo">
      <h2 id="resultado-titulo" class="h5">¿Qué obtengo?</h2>
      <p v-if="ficha.resultado" class="mb-2">{{ ficha.resultado }}</p>
      <p v-if="ficha.observaciones_resultado" class="observaciones mb-2">
        {{ ficha.observaciones_resultado }}
      </p>
      <p v-if="medios.length > 0" class="mb-0">
        Se entrega por: <span>{{ medios.join(', ') }}</span>
      </p>
    </section>

    <!-- Normas. -->
    <section v-if="normas.length > 0" class="bloque" aria-labelledby="normas-titulo">
      <h2 id="normas-titulo" class="h5">¿Qué norma lo regula?</h2>
      <ul class="lista-simple" role="list">
        <li v-for="(n, i) in normas" :key="i">
          {{ n.tipo }}<span v-if="n.numero"> {{ n.numero }}</span><span v-if="n.anio"> de {{ n.anio }}</span>
          <span v-if="n.articulos"> — {{ n.articulos }}</span>
          <a v-if="n.url" :href="n.url" rel="external"> (ver)</a>
        </li>
      </ul>
    </section>

    <!-- Referencia nacional. -->
    <p v-if="ficha.url_ficha_gov_co" class="pie-govco mt-4">
      Esta ficha también está publicada en el registro nacional:
      <a :href="ficha.url_ficha_gov_co" rel="external">consultar en GOV.CO</a>.
    </p>
  </div>
</template>

<style scoped>
.migas {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  font-size: 0.9375rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}
.migas a {
  color: var(--govcolor-cobalt, #0943b5);
}

.tipo-elemento {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--govcolor-cobalt, #0943b5);
}

.chips {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem 0.5rem;
}
.chip {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.7rem;
  border: 1px solid var(--govcolor-silver, #b9b9b9);
  border-radius: 1rem;
  font-size: 0.875rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
  background-color: var(--govcolor-white-smoke, #f4f4f4);
}
.chip-fuerte {
  border-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-cobalt, #0943b5);
  background-color: var(--govcolor-white, #ffffff);
  font-weight: 700;
}

.resumen {
  font-size: 1.0625rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}

.acciones {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.acciones .btn {
  min-height: 2.75rem;
  display: inline-flex;
  align-items: center;
}

.bloque {
  margin-top: 2rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--govcolor-silver, #b9b9b9);
}

/* Etapas. */
.etapas {
  list-style: none;
  margin: 0.5rem 0 0;
  padding: 0;
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
}
.etapa {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}
.etapa-numero {
  flex-shrink: 0;
  width: 2rem;
  height: 2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background-color: var(--govcolor-cobalt, #0943b5);
  color: var(--govcolor-white, #ffffff);
  font-weight: 700;
}
.etapa-titulo {
  font-weight: 700;
}
.etapa-detalle {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.9375rem;
}

.pildoras {
  list-style: none;
  margin: 0.5rem 0 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem 0.5rem;
}
.pildora {
  padding: 0.25rem 0.7rem;
  border: 1px solid var(--govcolor-cobalt, #0943b5);
  border-radius: 1rem;
  color: var(--govcolor-cobalt, #0943b5);
  font-size: 0.875rem;
}

.lista-requisitos {
  list-style: none;
  margin: 0.5rem 0 0;
  padding: 0;
}
.requisito {
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--govcolor-white-smoke, #f4f4f4);
}
.etiqueta-opcional {
  margin-left: 0.4rem;
  padding: 0.05rem 0.5rem;
  border-radius: 0.75rem;
  background-color: var(--govcolor-white-smoke, #f4f4f4);
  font-size: 0.75rem;
  color: var(--govcolor-matterhorn, #4c4c4c);
}
.requisito-nota {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.9375rem;
}
.requisito-enlace,
.punto-mapa {
  display: inline-flex;
  align-items: center;
  min-height: 2.75rem;
  color: var(--govcolor-cobalt, #0943b5);
  font-weight: 700;
}

.lista-pasos {
  margin: 0.5rem 0 0;
  padding-left: 1.25rem;
}
.paso {
  margin-bottom: 0.75rem;
}
.paso-titulo {
  font-weight: 700;
}

.lista-simple {
  list-style: none;
  margin: 0.5rem 0 0;
  padding: 0;
}
.lista-simple li {
  padding: 0.35rem 0;
}
.lista-simple a {
  color: var(--govcolor-cobalt, #0943b5);
}
.documento-formato {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.875rem;
}

.cuentas {
  margin-top: 0.75rem;
}
.aviso-pago,
.observaciones {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.9375rem;
}

.lista-puntos {
  list-style: none;
  margin: 0.5rem 0 0;
  padding: 0;
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}
.punto {
  border: 1px solid var(--govcolor-silver, #b9b9b9);
  border-radius: 0.5rem;
  padding: 0.875rem 1rem;
}
.punto-nombre {
  font-weight: 700;
}
.punto-dato {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.9375rem;
}

.pie-govco {
  color: var(--govcolor-matterhorn, #4c4c4c);
  font-size: 0.9375rem;
}
.pie-govco a {
  color: var(--govcolor-cobalt, #0943b5);
}

.solo-lectores {
  position: absolute;
  overflow: hidden;
  clip-path: inset(50%);
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  border: 0;
  white-space: nowrap;
}

a:focus-visible,
.btn:focus-visible {
  outline: 3px solid var(--govcolor-cobalt, #0943b5);
  outline-offset: 2px;
}

@media (min-width: 48rem) {
  .etapas {
    grid-template-columns: repeat(2, 1fr);
  }
  .lista-puntos {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
