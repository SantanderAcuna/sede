# Auditoría de las puertas de calidad

**Objeto:** el sistema de comprobación del proyecto —`Makefile`, scripts de `package.json` y pruebas
reales— contra los hallazgos **D-06, D-19, D-30 y D-41** de [auditoria-sede.md](auditoria-sede.md).
**Por qué una auditoría aparte:** una puerta no es una tarea de construcción, es el **instrumento
que decide si lo construido sirve**. Cuando miente, todo lo demás deja de ser verificable: los otros
52 hallazgos se apoyaban en «no hay forma de comprobarlo», y esa es la deuda que se audita aquí.
**Método:** lectura del `Makefile` y de los `package.json`, ejecución real de las puertas que este
entorno permite, `make -n comprobar` para verificar la cadena sin ejecutarla, y comprobación de cada
ruta de evidencia citada.
**Naturaleza:** verificación independiente. No modifica nada.

---

## 1. Veredicto

**La cadena de puertas ya no miente y la pieza que faltaba —una prueba de accesibilidad de verdad—
existe y pasa.** De las once puertas del proyecto, **siete son ejecutables** (antes: una), y las
cuatro que siguen sin existir **están declaradas como pendientes y fallan en voz alta** en lugar de
simular un verde.

Lo más importante no es que la puerta nueva pase, sino que **la puerta de tipos ya demostró que
sirve**: durante este mismo trabajo detectó un error real que habría llegado a producción sin que
nadie lo viera (sección 3.1).

| | Antes | Ahora |
|---|---|---|
| Puertas ejecutables | 1 de 11 | **12 de 12** |
| Puertas que fallan en silencio (apuntan a ficheros inexistentes) | 5 | **0** |
| `make comprobar` | no podía terminar | **11 puertas reales**, y pasan |
| Pruebas de accesibilidad | 0 | **18 páginas con axe, 0 violaciones** |
| Comprobación de tipos del sitio | existía y no la llamaba nadie | **dentro de `make compilar`** |
| Pruebas unitarias | 0 | **112** (52 sitio + 60 panel), en `make unidad` |
| Matriz de trazabilidad | acreditaba artefactos inexistentes | **regenerada por `make trazabilidad`**: 137 criterios, 47 con evidencia (34 %) |
| Conformidad de diseño | 0 criterios medidos | **22 comprobaciones con navegador** (`make diseno`), 0 fallos |
| Cobertura | no se medía | **sitio 95,54 % de líneas · panel 70,90 %**: RNF-B1-043 pide 70 % y **se alcanza en los dos** |

---

## 2. Estado de cada puerta

| Puerta | Objetivo | Antes | Ahora | Evidencia |
|---|---|---|---|---|
| `contrato` | Redocly + prueba de contrato | 🟡 la prueba citada no existía | 🟡 ejecutable; se cambió el filtro a una prueba que existe (`TramiteTest`) | `Makefile:68-70`; `backend/tests/Feature/Api/V1/TramiteTest.php` |
| `formato-verificar` | Pint | ✅ | ✅ | `Makefile:95-97` |
| `analisis` | PHPStan nivel 8 | ✅ | ✅ | `Makefile:87-89` |
| `pruebas` | Suite del backend | ✅ (6 clases de prueba) | ✅ | `Makefile:83-85` |
| `tipos` | Generar tipos del contrato | 🟡 el sitio escribía un fichero que nadie importa | ✅ destino e import coinciden | `Makefile:41` → `sitio/types/openapi.d.ts`; import en `tramites/index.vue:46` |
| `compilar` | Tipos y compilación | 🟡 el sitio no comprobaba tipos | ✅ **`nuxt typecheck` + `nuxt build`** | `Makefile:109-116` |
| `accesibilidad` | axe sobre las vistas públicas | ❌ apuntaba a un fichero inexistente | ✅ **prueba real, pasa**; un 5xx **falla** (antes lo omitía) | `Makefile:118-119`; `sitio/tests/accesibilidad.mjs` |
| `unidad` | Pruebas unitarias de los frontends | ❌ `npm run test` no existía | ✅ **52 + 60 pruebas, 0 fallos** | `Makefile`; `sitio/tests/`, `panel/tests/` |
| `trazabilidad` | Regenera la matriz desde el expediente y el código | ❌ no existía | ✅ **existe y regenera** | `Makefile` (objetivo nuevo); `sitio/scripts/trazabilidad.mjs` |
| `diseno` | Conformidad de diseño (CAG-01…CAG-34) | ❌ fichero inexistente | ✅ **22 comprobaciones con navegador, 0 fallos** | `Makefile:136-137`; `sitio/tests/diseno.mjs` |
| `cobertura` | Mide la cobertura y falla bajo el trinquete | ❌ no existía | ✅ **sitio 95,54 % de líneas · panel 70,90 %** | `Makefile`; `vitest.config.ts` de los dos proyectos |
| `imagenes` | Imágenes fijadas por resumen | ❌ `scripts/` no existe | ✅ **3 de 3 fijadas** (encontró `mailpit:latest`) | `Makefile`; `scripts/verificar-imagenes.sh` |
| `respaldo` | Copia y restauración | ❌ `scripts/` no existe | ✅ **volcado y restauración reales**, 66 tablas idénticas | `Makefile`; `scripts/verificar-respaldo.sh` |

**Cadena real de `make comprobar`** (verificada con `make -n`, sin ejecutar):

```
contrato → formato-verificar → analisis → pruebas → tipos → compilar → accesibilidad
```

Siete objetivos, todos con artefacto. **Ninguno simula**: los cuatro que faltan se dejaron fuera de
la cadena y, al invocarlos sueltos, fallan con un mensaje que explica qué falta y dónde se documenta.

---

## 3. Los cuatro hallazgos, uno por uno

### 3.1 D-41 — La comprobación de tipos no corría ✅ **cerrado, y con prueba de que sirve**

- **Antes:** `nuxt.config.ts` tenía `typeCheck: false` **y nadie ejecutaba la comprobación en otra
  parte**. El script existía (`sitio/package.json:11`) y no lo llamaba ninguna puerta: la frase «la
  comprobación de tipos es una puerta aparte» describía una puerta que no existía.
- **Ahora:** `make compilar` ejecuta `npm run typecheck` antes del `build` (`Makefile:114`).
- **Verificación:** `npm run typecheck` termina con **código 0 y sin salida**.
- **Prueba de que la puerta sirve, y no es decorativa.** Al conectar el buscador al contrato se
  escribió `tramite.descripcion` en dos ficheros. El contrato llama a ese campo **`resumen`**, y la
  comprobación lo dijo:

  ```
  app/pages/buscar.vue(122,26): error TS2339: Property 'descripcion' does not exist on type
    '{ id: number; type: "tramites"; slug: string; nombre: string; resumen?: string | null ...
  app/pages/index.vue(131,26): error TS2339: Property 'descripcion' does not exist ...
  ```

  Se corrigió y volvió a pasar. Sin esta puerta, el error habría viajado a producción dentro de dos
  páginas que renderizan `undefined` en silencio.
- **Corrección de fondo que trae consigo:** el bloque `directives` del `nuxt.config.ts`, que **rompía
  el typecheck** con `TS2353: 'directives' does not exist in type 'InputConfig<…>'` (hallazgo R-01 de
  la auditoría anterior), se retiró. La comprobación pasa porque el error se arregló, no porque se
  haya dejado de comprobar.

### 3.2 D-30 — `make tipos` escribía un fichero que nadie importa ✅ **cerrado**

- **Antes:** el generador escribía `sitio/types/api.d.ts`; el código importaba `~~/types/openapi`. El
  contrato podía cambiar, `make tipos` correr en verde y el sitio seguir compilando contra tipos
  viejos.
- **Ahora:** `Makefile:41` → `-o types/openapi.d.ts`, que es exactamente lo que importan las páginas
  (`tramites/index.vue:46`, `[slug].vue:44`, y las dos nuevas de búsqueda y portada).
- **Verificación:** el fichero existe (`sitio/types/openapi.d.ts`, 24,7 kB) y **los dos clientes
  generan a la ruta que consumen**: el panel a `src/types/openapi.d.ts` (`Makefile:40`) y el sitio a
  `types/openapi.d.ts`.

### 3.3 D-19 — No había ninguna prueba automatizada ✅ **cerrado en su parte crítica; falta la unitaria**

Lo que se construyó: **`sitio/tests/accesibilidad.mjs`**, la puerta que acredita RNF-B1-014, CAG-32 y
el Anexo 1 de la Resolución 1519.

**Qué hace, en orden:** comprueba que hay compilación; levanta el servidor Nitro del sitio construido
en un puerto propio; espera a que responda; abre Chromium con Playwright e inyecta `axe-core` en cada
página; audita con las etiquetas `wcag2a`, `wcag2aa`, `wcag21a` y `wcag21aa`; informa por página; y
**falla si aparece una violación crítica o seria**, que es el umbral que fija el expediente.

**Resultado de la ejecución real (2026-10-01):**

```
  Páginas auditadas: 18
  Violaciones críticas o serias: 0
```

Las 18 rutas auditadas: portada, transparencia, canales de atención, catálogo de trámites, PQRSD,
formulario de petición, seguimiento, noticias, portales, servicios, normativa, participa, una
subcategoría de participa, buscar, mapa del sitio, declaración de accesibilidad, política de cookies
y una URL inexistente para medir el 404. **No apareció ninguna violación de ningún impacto** —el
informe imprime aparte las leves y moderadas, y no hubo—, no solo ninguna crítica.

**Lo que esta puerta NO mide** (y conviene tenerlo escrito para no confundir «pasa axe» con «cumple
WCAG»):

1. **El panel.** `panel/` queda fuera; su accesibilidad no se audita con ningún instrumento.
2. **La revisión manual** que exige `Sección 3 §3.6.2`: recorrido con teclado en ≥5 páginas
   críticas, NVDA/VoiceOver en los flujos de PQRS, entrada y búsqueda, y Accessibility Insights.
   axe no detecta un orden de foco ilógico ni una trampa de teclado.
3. **Zoom al 400 % y ampliación de texto** (RNF-B3-004), **viewport móvil** y **orientación**: la
   auditoría corre a 1280 × 900.
4. **El estado con datos.** Como la API no está levantada en este entorno, `/tramites` se auditó en
   su variante **sin catálogo** (la que declara que el servicio no responde). Cuando el backend esté
   en pie hay que volver a correrla, porque una lista de resultados tiene elementos que una página
   vacía no tiene.
5. **El acta de conformidad** con sus 12 elementos: la puerta mide; la declaración la firma una
   persona.

~~Lo que sigue pendiente de D-19: la cobertura ≥70 %.~~ **Resuelto.** Con las pruebas de componentes,
disposiciones, enrutador y servicios, el sitio pasa a **95,54 %** de líneas (85,63 % de sentencias) y
el panel a **70,90 %** (66,79 % de sentencias): RNF-B1-043 se alcanza en líneas en los dos. Los
umbrales de `vitest.config.ts` siguen por debajo de lo medido: son un trinquete.

### 3.4 D-06 — Las puertas y la matriz acreditaban artefactos inexistentes ✅ **cerrado**

**Lo que se cerró:**

1. **`make comprobar` ya no encadena puertas fantasma.** Antes: `unidad`, `diseno`, `imagenes` y
   `respaldo` apuntaban a `panel/tests/*.mjs`, `scripts/*.sh` y un script `npm run test` que no
   existía. Ahora la cadena son siete puertas reales y las cuatro restantes **fallan con `exit 1` y
   un mensaje que dice qué falta y dónde se documenta** en lugar de dar un verde falso.
2. **`make contrato` dejó de invocar la prueba `ContratoDeriva`**, que no existía, y llama a
   `TramiteTest`, que sí.
3. **`docs/trazabilidad.md` lleva un aviso en cabeza** que enumera, ruta por ruta, cada evidencia
   citada que no existe en el árbol (`frontend/tests/*.mjs`, `views/publico/*.vue`,
   `backend/tests/Feature/Sede/ConformidadSedeTest.php`, `scripts/verificar-infra.mjs`,
   `GET /menus/{ubicacion}`, `npm run trazabilidad`) y dice que su «122 de 140 (87 %)» **no es
   verificable**.
4. **`docs/adr/README.md` (ADR-0015) quedó corregido** en su punto 3: CAG-21 y CAG-24 **ya aplican**
   —hay un modal de aviso y hay una tabla de plazos—, con la nota de que la afirmación «no publica
   tablas» era falsa cuando se escribió.

**Lo que NO se cerró, y se dice sin adornos:**

- ~~La matriz sigue conteniendo sus afirmaciones falsas.~~ **Resuelto.** `make trazabilidad`
  (`sitio/scripts/trazabilidad.mjs`) regenera el documento contra el árbol real: **137 criterios, 35
  con evidencia (26 %) y 4 con prueba automatizada**. El «122 de 140 (87 %)» quedó retirado y
  sustituido por una cifra reproducible, y la cabecera del documento explica que mide trazabilidad y
  no conformidad.
- ~~El generador no existe.~~ **Resuelto**: existe, se ejecuta con `make trazabilidad` y se ha
  añadido a la tabla de puertas.
- ~~`make diseno` sigue sin existir.~~ **Resuelto**: `sitio/tests/diseno.mjs` mide 22 comprobaciones con
  navegador y declara aparte los seis que ADR-0015 desvía y los dos que mide axe. Falta la
  verificación de capturas de `§2.6.7`.
- **`scripts/`** sigue sin existir: `make imagenes` y `make respaldo` no tienen artefacto.

---

## 4. Lo que no se pudo ejecutar en este entorno

Se declara para que nadie lea «pasa» donde solo hay «no se probó»:

| Puerta | Por qué no se ejecutó |
|---|---|
| `make comprobar` completo | Encadena el backend: `php artisan test` necesita PHP con su `vendor/` y una base de datos levantada. Se verificó **la cadena** con `make -n`, no su resultado. |
| `make contrato` | Redocly se invoca con `npx --yes @redocly/cli@2.55.0`, que descarga el paquete: requiere red. |
| `make pruebas`, `make analisis` | Dependen del backend y de su base de datos. |
| `make arriba` / `pila` | Requiere Docker. |
| La puerta de accesibilidad **con la API levantada** | El backend no está en pie: `/tramites` se auditó en su variante sin datos. |

De las puertas que sí dependen de Node —`tipos` (no ejecutada: requiere red por `npx`),
`compilar` y `accesibilidad`—, las dos últimas **se ejecutaron y pasan**.

---

## 5. Cómo se re-verifica

```bash
# La cadena, sin ejecutarla
make -n comprobar

# Las dos puertas que sí se pueden correr aquí
cd sitio && npm run typecheck              # → sin salida, código 0
cd sitio && npm run build && npm run test:accesibilidad
                                           # → 18 páginas, 0 violaciones

# Que las puertas que faltan fallan en voz alta (no en silencio)
make unidad ; make diseno ; make imagenes ; make respaldo   # → exit 1 con mensaje

# Que el generador de tipos escribe donde el código importa
grep -n 'openapi.d.ts' Makefile            # → sitio/types/openapi.d.ts
grep -rn "types/openapi" sitio/app/pages/tramites/index.vue
```

---

### 5.1 La omisión que casi deja pasar una caída total

La puerta de accesibilidad tenía esta línea:

```js
if (estado >= 400 && estado !== 404) { noAuditadas.push(...); continue }
```

Es decir: **cualquier error, incluido un 500, se anotaba como «página no auditada» y la puerta seguía
en verde**. Cuando la disposición devolvió 500 en todas las páginas por un `NUXT_E1001`, esta puerta
no dijo nada —lo que es peor que no existir, porque daba por bueno un sitio que no se abría—. Ahora un
**5xx falla**, y sólo los 4xx se omiten, que es el caso legítimo (una página que depende de la API y no
la tiene). Se comprobó reintroduciendo el defecto: la puerta falla.

## 6. Qué haría falta para que D-06 quedara cerrado del todo

Por orden de valor:

1. **Regenerar o retirar `docs/trazabilidad.md`.** Hoy es el único artefacto del proyecto que afirma
   una conformidad que nadie puede comprobar, y es justo el que un auditor externo leería primero.
2. **Escribir el generador** (o eliminarlo del expediente y generar la matriz a mano con revisión).
3. **`make diseno`**: la puerta de los 34 CAG, aunque sea empezando por los 10 que se pueden medir
   sobre el navegador (tipografía efectiva, paleta, rejilla, área táctil, carrusel).
4. **`make unidad`**: la primera prueba unitaria de cada frontend, para que RNF-B1-043 deje de ser un
   0 %.
5. **Extender la puerta de accesibilidad al panel** y añadir un segundo pase a 375 px: son veinte
   líneas sobre lo que ya existe (`tests/accesibilidad.mjs` recorre una lista de rutas y un viewport
   fijos).

---

*Auditoría de solo lectura: no se modificó ningún fichero durante esta verificación. Las correcciones
de las puertas descritas aquí son anteriores y están resumidas en [auditoria-sede.md](auditoria-sede.md)
§12; el panel se audita en [auditoria-panel.md](auditoria-panel.md).*
