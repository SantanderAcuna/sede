# Auditoría — Catálogo de Trámites y Ficha del Trámite

> **Sede Electrónica** — Alcaldía Distrital de Santa Marta
> **Carpeta:** `entidad-transparencia/ciudadano/`
> **Fecha de auditoría:** 2026-10-02 (actualizado tras scraping completo a SUIT)
> **Fuentes consultadas:** [GOV.CO](https://www.gov.co) · [SUIT — Función Pública](https://www.funcionpublica.gov.co/es/suit/buscador-de-tramites) (entidad 0043) · Guía de diseño gráfico para sedes electrónicas (Anexo 2.1) · `sede-electronica-doc/03-servicios-tramites/servicios-tramites.md` · código actual de la Sede

> **Actualización 2026-10-02 (segundo pase):** Se descubrió que el visor de SUIT **sí expone los datos** de cada trámite a través de la API `https://visorsuit.funcionpublica.gov.co/api/tramite?fi=XXXX`. Con esa ruta se scrapean los 124 trámites y se obtiene: nombre, propósito, **producto final**, normativa, requisitos por tipo (DOCUMENTO/PAGO/SOLICITUD/VERIFICACION_INST/FORMULARIO), **puntos de atención con coordenadas GPS**, **cuentas de recaudo**, **audiencias (perfiles)**, **canales de seguimiento** (telefónico, email, presencial) y **URL del trámite en línea**. La información ya está toda en [`informacion-por-tramite.md`](./informacion-por-tramite.md) y resumida en [`resumen-datos-recurrentes.md`](./resumen-datos-recurrentes.md). El §3.2 de esta auditoría se actualiza abajo para reflejar el nuevo estado.

---

## 1. Lo que esta auditoría responde

1. ¿Qué muestra hoy la ficha de un trámite en GOV.CO (`https://www.gov.co/ficha-tramites-y-servicios/Txxxx`) que **no** muestre hoy nuestra Sede?
2. ¿Qué hace falta en el backend, el contrato y la UI para que la ficha esté **a la altura del referente nacional** y del `modulo 03` de la elicitación?
3. ¿De dónde se puede sacar la información que falta sin inventar contenido institucional?
4. ¿Qué se puede entregar **ahora**, con lo que ya tenemos, y qué requiere una nueva ingesta?

---

## 2. Lo que GOV.CO publica en cada ficha de trámite

> GOV.CO es una **SPA Angular**. La ficha no se sirve como HTML rastreable: el HTML inicial es un `<app-root></app-root>` y los datos se cargan con JavaScript desde servicios internos. Esto limita el scraping a un navegador real, y obliga a que la Sede **traiga** los datos a su propia base en vez de pedirlos en tiempo real a GOV.CO.

Bloques que la ficha de GOV.CO muestra (referencia: recorrido manual + capturas del visor de SUIT y de la ficha `T6113` y equivalentes):

| Bloque | Pregunta que responde | Visibilidad pública |
|---|---|---|
| **Información general** | ¿De qué se trata el trámite? | Sí |
| **¿Qué necesito para hacer mi trámite?** | Requisitos y documentos | Sí |
| **¿Cómo hago mi trámite?** | Pasos / instrucciones | Sí |
| **¿Cuál es el horario y los puntos de atención?** | Dónde y cuándo se atiende | Sí |
| **¿Cuánto cuesta?** | Tarifa o gratuidad | Sí |
| **¿Cuál es la normativa relacionada con este trámite?** | Leyes / decretos / resoluciones | Sí |
| **¿Qué resultado obtengo luego de hacer mi trámite?** | Entregable / respuesta | Sí |
| **¿Cuál es el tiempo de respuesta?** | Plazo legal | Sí |
| **¿Quién puede realizarlo?** (perfiles) | Grupo objetivo | Sí |
| **Canales de atención** | Cómo se contacta a la Entidad | Sí |
| **Iniciar trámite en línea** | Botón / enlace al flujo | Sí |

Esto coincide con el §2.1–§2.5 del módulo 03 de la elicitación (`sede-electronica-doc/03-servicios-tramites/servicios-tramites.md`, RF-B1-021..095) y con el §5.1.3 de la **Resolución 2893 de 2023** sobre atributos obligatorios.

---

## 3. Lo que la Sede publica hoy vs. lo que debería

### 3.1 Estado del catálogo (página `/tramites`)

| Elemento | Estado actual | Cumple Anexo 2.1 |
|---|---|---|
| Listado paginado (6 por página) | ✅ | ✅ |
| Selector de grupo (Trámites/OPA/Consultas) | ✅ con filtro `type` (recién añadido) | ✅ |
| Buscador por texto | ✅ emite `buscar` al backend | ✅ |
| Filtro por categoría | ⚠️ backend acepta `categoria`, pero **la fuente SUIT no clasifica** los trámites en categorías, así que el desplegable siempre está vacío (no es bug, es ausencia de fuente) | ⚠️ |
| Recuento de resultados | ✅ | ✅ |
| Paginación | ✅ | ✅ |
| Estado vacío diferenciado (sin publicación vs. sin resultados) | ✅ | ✅ |
| Enlace a GOV.CO en la galería | ✅ `url_ficha_gov_co` | ✅ |
| Botón «Iniciar trámite en línea» cuando hay `url_inicio` | ✅ | ✅ |
| Loading skeleton | ✅ | ✅ |
| Error con reintento | ✅ | ✅ |

### 3.2 Estado de la ficha (página `/tramites/{slug}`)

Esta es la auditoría central. La ficha existe, está conectada al backend por slug, y respeta el envoltorio.

**Actualización 2026-10-02 (tras el scraping completo de SUIT):** la información que faltaba **sí está disponible en la API del visor de SUIT**. Se scrapeó los 124 trámites y la data está consolidada en [`informacion-por-tramite.md`](./informacion-por-tramite.md). Lo que falta ahora es **integrar esa data en la Sede**: el sembrador la conoce, el modelo de `Tramite` no la tiene, y la ficha no la dibuja. Eso es P0/P1, no un problema de fuente.

| Campo de la ficha (GOV.CO) | Atributo del contrato | Estado en backend (seed SUIT) | Estado en SUIT (scrapeado) | Acción para integrarlo en la Sede |
|---|---|---|---|---|
| **Información general** (qué es) | `resumen` | ✅ Sembrado desde `proposito` | ✅ Igual, con `productoFinal` adicional | OK |
| **¿Qué necesito?** (requisitos) | `requisitos[]` | ⚠️ Sembrado con un único requisito genérico | ✅ SUIT publica hasta 5 tipos de requisitos por trámite (DOCUMENTO, PAGO, SOLICITUD, VERIFICACION_INST, FORMULARIO) | **P0** — agregar `pasos[]` a `momentos[]` y migrar `requisitos[]` al sembrador con el modelo rico. Sustituir el placeholder. |
| **¿Cómo hago mi trámite?** (pasos) | `pasos[]` (definido en OpenAPI, no en el seed) | ❌ no existe en el modelo | ✅ SUIT publica `momentos[]` (1-3 por trámite) con orden, descripción y requisitos | **P0** — agregar columna, sembrar desde SUIT, dibujar bloque en la ficha. |
| **¿Cuál es el horario y los puntos de atención?** | `puntos_atencion[]` | ❌ no existe en el seed | ✅ SUIT publica 58 puntos únicos con dirección, teléfono, horario y coordenadas GPS | **P0** — agregar columna, sembrar, dibujar. (Ver [`resumen-datos-recurrentes.md`](./resumen-datos-recurrentes.md) §3.) |
| **¿Cuánto cuesta?** | `costo` (con `valor`, `moneda`, `cuentas[]`, `url_pago`) | ⚠️ Sembrado con `costo: null`; `tiene_costo` correcto | ✅ 20 trámites tienen pago, 26 cuentas bancarias únicas publicadas (Bancolombia, Davivienda, BBVA, Banco de Bogotá, etc.) | **P0** — sembrar el valor y las cuentas desde SUIT. La Sede **debe** mostrar las cuentas: son la ruta real de pago del ciudadano, no un botón falso. |
| **¿Cuál es la normativa relacionada?** | `normativa[]` | ❌ no existe en el seed | ✅ SUIT publica 255 normas únicas (Leyes, Decretos, Resoluciones, Acuerdos, Constitución) con número, año, artículos y URL al PDF cuando existe | **P0** — agregar columna, sembrar, dibujar. |
| **¿Qué resultado obtengo?** | `resultado` (string) | ❌ no existe en el seed | ✅ SUIT publica `productoFinal` (ej.: "Factura o formulario con sello de pago del impuesto") y `mediosResultadoList` (correo, presencial, etc.) | **P0** — agregar columna, sembrar, dibujar. |
| **¿Cuál es el tiempo de respuesta?** | `tiempo_solucion_dias` | ✅ Sembrado con conversión `TiempoEnDias::desde()` | ✅ Misma data | OK |
| **¿Quién puede realizarlo?** | `perfiles[]` (array de strings) | ❌ no existe en el seed | ✅ SUIT publica `tiposAudienciaList[]` con hasta 12 audiencias (Infancia, Juventud, Adulto mayor, Grupos étnicos, etc.) | **P0** — agregar columna, sembrar, dibujar. |
| **Canales de seguimiento** | `canales_consulta_estado[]` | ❌ no existe en el seed | ✅ SUIT publica `seguimientoTelefonicoList[]`, `seguimientoEmailList[]`, `seguimientoPresencial` y `seguimientoWeb` | **P0** — agregar columna, sembrar, dibujar. **Cambio de política**: el §2.3 del `[slug].vue` dice que la Sede no muestra "consulta de estado fingida". La realidad es que muchos de estos canales **sí están habilitados** y el ciudadano debe verlos. |
| **Botón Iniciar trámite en línea** | `url_inicio` | ✅ Sembrado | ✅ SUIT publica `urlTramiteEnLinea` y `urlManualTramiteEnLinea` | OK, pero revisar para tener `urlManual` también. |
| **Procedencia del dato** (auditoría) | `procedencia.fuente`, `procedencia.url`, `procedencia.obtenido_en`, `procedencia.nota`, `procedencia.origen_por_campo` | ✅ Sembrado | ✅ Misma data | OK |
| **Consulta del estado** | `consulta_estado` | ✅ Constante de la Sede | — | OK |
| **Botón de pago** | `costo.url_pago` | ❌ no existe pasarela contratada | — | OK por diseño — el botón que no cobra es peor que no tenerlo. La Sede **publica las cuentas de recaudo** para que el ciudadano pague por transferencia o PSE directamente, pero no simula una pasarela. |

### 3.3 Verificación en vivo

```
$ curl -s http://127.0.0.1:8000/api/v1/tramites/actualizacion-...-sisben \
    | jq '.data | {nombre, requisitos, normativa, resultado, perfiles, pasos, puntos_atencion, canales_consulta_estado}'

{
  "nombre": "Actualización de información en la base de datos del sistema de identificación y clasificación de potenciales beneficiarios de programas sociales – SISBEN",
  "requisitos": [
    { "descripcion": "Los que declara la ficha oficial del trámite en GOV.CO (SUIT).", "obligatorio": true }
  ],
  "normativa": null,
  "resultado": null,
  "perfiles": null,
  "pasos": null,
  "puntos_atencion": null,
  "canales_consulta_estado": null
}
```

> El ciudadano ve **«Los que declara la ficha oficial del trámite en GOV.CO (SUIT)»** como único requisito. Eso explica el hueco visible en la ficha: no es bug, es ausencia de datos que hoy no podemos inventar.

---

## 4. ¿Por qué hay huecos y por qué no es un bug? (REVISADO)

El `TramiteSeeder` lo decía en su propio encabezado (líneas 67–76):

> «`requisitos` y `documentos` no existen en ninguna interfaz pública de GOV.CO para fichas de SUIT. Se comprobó contra el propio portal —el servicio `GetDocumentacionRequeridaById` sólo existe para las fichas que no son de SUIT— y contra el visor de SUIT, que es una aplicación de una sola página sin interfaz de datos. Transcribirlos de memoria sería inventar requisitos oficiales».

**Pero esta afirmación debe revisarse.** En el segundo pase se descubrió que:

1. La **búsqueda** de SUIT (`dafpIndexerBT/tramite/index`) sí devuelve para cada trámite su **nombre, propósito y enlace al visor**.
2. El **visor** de SUIT (`visorsuit.funcionpublica.gov.co`) sí expone una **API REST** (`/api/tramite?fi=XXXX`) con todos los datos del trámite: nombre, propósito, **producto final**, **normativa**, **requisitos por tipo**, **puntos de atención con coordenadas**, **cuentas de recaudo**, **audiencias (perfiles)**, **canales de seguimiento**, **URL del trámite en línea**.

Esto significa que el problema **no es de la fuente**: es de la **integración**. La Sede puede:

| Opción | Costo | Calidad | Recomendación |
|---|---|---|---|
| **A.** Mejorar el `TramiteSeeder` para que ingiera los datos del visor (copia congelada con `procedencia = 'SUIT — Visor (entidad 0043)'`) | Bajo, único | Alta | **RECOMENDADA** — la Sede ya tiene la infraestructura del seeder y del contrato. Solo hay que agregar columnas y poblar. |
| **B.** Ingesta continua (cron + endpoint) | Medio, recurrente | Alta, pero se rompe si SUIT cambia la API | NO recomendado — dependencias de un sistema que no controlamos. |
| **C.** La Entidad carga en el panel | $0 recurrente, alto esfuerzo humano | Alta | Recomendable como **complemento** de A: el panel permite correcciones y la carga de actos administrativos propios. |
| **D.** DAFP expone una API oficial más rica | Lento, político | Alta, sostenible | Recomendable a futuro, fuera de este sprint. |

**Decisión recomendada:** implementar **A** + **C** en este sprint. El sembrador consume la data del visor (esta carpeta ya tiene la data scrapeada lista), la Entidad mantiene la opción de corregir por panel.

---

## 5. Lo que la Sede debe construir (ordenado por criticidad)

### P0 — Ingerir la data del visor de SUIT en el sembrador

El scraping ya está hecho (esta carpeta tiene los 124 trámites con todos los datos). Lo que falta:

- [ ] **P0.1** — **Migración: agregar columnas y tablas faltantes al modelo `Tramite`**:
  - `resultado` (text, nullable)
  - `normativa` (json, nullable) — array de `TramiteNorma`
  - `perfiles` (json, nullable) — array de strings
  - `pasos` (json, nullable) — array de `TramitePaso`
  - `puntos_atencion` (json, nullable) — array de `TramitePuntoAtencion`
  - `canales_consulta_estado` (json, nullable) — array de `TramiteCanalConsulta`
  - `costo_valor` (string, nullable)
  - `costo_moneda` (string, nullable)
  - `costo_tipo_valor` (string, nullable, enum: `fijo`/`smlv`/`avaluo_liquidacion`/`rango`)
  - `costo_url_pago` (string, nullable)
  - `costo_descripcion` (text, nullable)
  - `costo_cuentas` (json, nullable) — array de cuentas
  - `url_manual_tramite_en_linea` (string, nullable)
  - `momento_requisitos` (json, nullable) — array de momentos con sus requisitos por tipo
- [ ] **P0.2** — **Actualizar el `TramiteSeeder`** para que cargue los datos del archivo JSON scrapeado (`/tmp/suit_details.json` o persistir en el repo como `database/datos/tramites-0043-suit-visor.json`). Mantener la marca de procedencia `SUIT — Visor (entidad 0043)` para distinguirla de la marca del seeder anterior.
- [ ] **P0.3** — **Actualizar el `TramiteResource`** para que exponga las nuevas columnas en la API, y la capa `api/v1/tramites/{slug}` para que el ciudadano los vea.
- [ ] **P0.4** — **Actualizar la página `/tramites/[slug]`** para que dibuje los nuevos bloques:
  - «Información general» — el actual, ya muestra `resumen`; añadir `productoFinal` como `resultado`.
  - «¿Qué necesito?» — agrupar `momento_requisitos` por tipo de requisito (DOCUMENTO/PAGO/SOLICITUD/VERIFICACION_INST/FORMULARIO) con el mismo patrón de `requisitosPorTipo` que ya existe.
  - «¿Cómo hago mi trámite?» — los `momentos` con su descripción y orden.
  - «¿Dónde?» — `puntos_atencion` con dirección, horario, teléfono, mapa (coordenadas GPS disponibles).
  - «¿Cuánto cuesta?» — actualizar con valor, tipo de valor, y lista de cuentas de recaudo.
  - «Normativa» — tabla con tipo, número, año, artículos, URL.
  - «¿Qué resultado obtengo?» — `productoFinal` + medios de entrega.
  - «¿Quién puede hacerlo?» — chips por audiencia.
  - «Canales de seguimiento» — los que estén habilitados (teléfono, email, presencial, web).

### P1 — Agregar al panel la edición de los huecos restantes

- [ ] **P1.1** — En el panel (`panel/src/views/admin/`): vista de edición de un trámite, con campos para:
  - los mismos bloques de P0 (modo edición manual sobre los datos sembrados)
  - fechas de vigencia
  - horario de atención
  - dependencia responsable
- [ ] **P1.2** — Endpoint `PATCH /api/v1/panel/tramites/{slug}` (autorización por Policy, sólo roles `tramites-editor` o superior)
- [ ] **P1.3** — Sello «**Actualizado por la Entidad el {fecha}**» cuando un campo tenga `procedencia.fuente === 'entidad'`

### P2 — Datos abiertos y observabilidad

- [ ] **P2.1** — Exponer `GET /api/v1/tramites` como datos abiertos en `datos.gov.co`, ya con la procedencia
- [ ] **P2.2** — Tablero BI de seguimiento (RF-B1-093 del módulo 03): nº de solicitudes, tasa de abandono, tiempo promedio — no es bloque de ficha, es back-office

### P3 — Lo que SÍ es construcción de la Sede, sin esperar a nadie

- [ ] **P3.1** — Página individual de cada trámite con **todos los bloques que ya tengamos** visibles, aunque sean bloques de «dato ausente, vea GOV.CO».
- [ ] **P3.2** — Paginación del catálogo en **>= 10** por página (RNF-03-D01 del módulo 03). Hoy `TAMANO_PAGINA = 6` por una decisión de pliegue visual del Anexo; en una segunda iteración añadir un selector de tamaño de página.

---

## 6. Lo que YA funciona (no tocar)

| Cosa | Archivo | Por qué no tocar |
|---|---|---|
| Selector de grupo (Trámites/OPA/Consultas) con `type` al backend | `sitio/app/pages/tramites/index.vue` | recién hecho en este sprint, con su migración y test de la API |
| Estado vacío diferenciado (sin publicación / sin resultados) | `sitio/app/pages/tramites/index.vue` | cumple C-04 del módulo 03 |
| Botón «Ver en GOV.CO» siempre presente | `sitio/app/pages/tramites/index.vue` (galería) y `[slug].vue` (ficha) | cumple RF-B1-021 |
| Bloque «Costo» con explicación de `tipo_valor` | `[slug].vue` `costoCifra` | cumple el §3 «no inventar importes» |
| Bloque «Procedencia» al final de la ficha | `[slug].vue` | cumplimiento Ley 1712 Art.11.b |
| Sin botón de pago, sin consulta de estado fingida | `[slug].vue` | el documento lo explica en líneas 22–37 |
| 55 pruebas backend pasando | `backend/tests/` | no se ha tocado nada del flujo de auth, identidad, conformidad |

---

## 7. Información completa por trámite — qué debería tener cada uno (plantilla)

Para que la ficha sea útil y cumpla el Anexo 2.1, **cada trámite** debe tener, en su semilla o en la edición manual del panel, los siguientes campos. La Sede sólo publicará lo que la Entidad haya aprobado: un campo sin fuente es un campo ausente, no un campo en blanco.

```
1. Identificación
   - id (T-código SUIT)
   - slug
   - type (tramites | opa | consultas)
   - categoría (slug + nombre, opcional)

2. Información general
   - nombre
   - resumen
   - url_ficha_gov_co

3. Hacer el trámite
   - modalidad (en_linea | parcialmente_en_linea | presencial)
   - url_inicio (si en línea)
   - pasos[]: { orden, titulo, descripcion }

4. Requisitos (¿Qué necesito?)
   - documentos[]: { nombre, url?, formato?, obligatorio? }
   - verificacion_institucional[]: { descripcion }
   - solicitud[]: { descripcion, canal }
   - formulario[]: { descripcion, url? }
   - pago[]: { descripcion, monto?, cuenta? }

5. Resultado (¿Qué obtengo?)
   - resultado: string

6. Dirigido a (¿Quién puede hacerlo?)
   - perfiles[]: string[]

7. Costo
   - tiene_costo (gratuito | con_costo)
   - tipo_valor (fijo | smlv | avaluo_liquidacion | rango)
   - valor, moneda
   - url_pago
   - descripcion
   - cuentas[]: { entidad, tipo_cuenta, titular, numero_cuenta, codigo_recaudo }

8. Tiempo
   - tiempo_solucion_dias (entero)
   - nota: si la conversión fue desde otra unidad (la fuente publica «3 MES(ES)», la Sede convierte a 90 días y lo dice)

9. Puntos de atención (¿Dónde?)
   - puntos_atencion[]: { nombre, direccion, horario, telefono, municipio, departamento, latitud, longitud }

10. Normativa
    - normativa[]: { tipo (Ley|Decreto|Resolución|Acuerdo|...), numero, anio, articulos, url, url_descarga }

11. Consulta del estado
    - consulta_estado (URL interna de la Sede, fija)
    - canales_consulta_estado[]: { canal, habilitado, nombre, url, correo, telefono, horario }

12. Procedencia
    - fuente, url, obtenido_en, nota
    - origen_por_campo: por cada campo de arriba, de dónde salió (fuente | derivado | entidad | ausente)
    - derivados[]: { campo, regla }
    - faltantes[]: string[] con los campos que la fuente no declaró
```

De los 12 bloques, la Sede hoy **publica** los 5 primeros de forma parcial. El resto los publica **vacíos con un rótulo de «consulte GOV.CO»** o **no los dibuja**. Lo que hay que hacer es:

1. **Migración + seed** que extienda el `TramiteSeeder` con la información disponible (la fuente SUIT tiene poco, pero hay algo).
2. **Edición en el panel** para que la Entidad complete lo que la fuente no entrega.
3. **Bloque en la ficha** por cada campo nuevo, con el mismo principio: «si no hay dato, se dice que no hay dato, con su enlace a la ficha oficial».

---

## 8. Estructura de archivos esperada (en este directorio)

```
entidad-transparencia/ciudadano/
├── auditoria-tramites-ficha.md           ← este archivo
├── plantilla-ficha-tramite.md            ← checklist por trámite (de la sección 7)
├── catalogo-fuentes.md                   ← qué dice cada fuente (SUIT, GOV.CO, entidad)
├── informacion-por-tramite.md            ← los 124 trámites con su data real de SUIT (695 KB, 16 K líneas)
├── resumen-datos-recurrentes.md          ← cuentas bancarias, normas, puntos de atención agregados
└── huecos-por-tramite.md                 ← por cada uno, qué le falta para llegar a la ficha de GOV.CO
```

> El contenido de esta carpeta es **evidencia** del estado actual: la data scrapeada es la base para los siguientes sprints. Cuando el sembrador ingiera la data del visor (P0.2), los archivos `informacion-por-tramite.md` y `resumen-datos-recurrentes.md` se regenerarán a partir de la base, no del scrape.

---

## 9. Conclusión ejecutiva (REVISADA 2026-10-02)

- **Lo que ayer parecía un problema de fuente, es un problema de integración.** La auditoría original asumía que GOV.CO no exponía la ficha como dato. **Es GOV.CO el que no la expone, pero el visor de SUIT sí** (`visorsuit.funcionpublica.gov.co/api/tramite?fi=XXXX`): la Sede puede tener los 124 trámites con todos sus datos sin scraping de GOV.CO ni dependencia de la SPA.
- **Ya hay evidencia scrapeada** en esta carpeta: 124 trámites × ~10 bloques de información (normativa, cuentas, puntos de atención, audiencias, seguimiento, requisitos por tipo, etc.). 26 cuentas bancarias únicas, 255 normas únicas, 58 puntos de atención únicos. **No es trabajo pendiente de captura: ya está capturado.**
- **Lo que falta es P0: ingestar esa data en el sembrador y dibujar los nuevos bloques en la ficha.** Es una migración, no una investigación. Esta auditoría pasa de «la fuente no tiene los datos» a «la fuente los tiene, hay que integrarlos», que es un trabajo más corto y de calidad más alta.
- **Lo que NO cambia:** la Sede sigue sin inventar contenido. Todo lo que se publique viene de la fuente trazable (SUIT, panel de la Entidad, o acto administrativo). Lo único que cambia es la cantidad de fuente disponible: pasa de 9 campos a ~25 campos por trámite.

Esta auditoría está versionada. El próximo paso es la **P0.1 (migración)** y el siguiente es la **P0.4 (dibujo de los bloques en `[slug].vue`)**. Cuando ambos estén, los ciudadanos verán una ficha del nivel de la de GOV.CO, con datos oficiales y enlace a la ficha nacional como referencia.
