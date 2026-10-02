# Guía para agentes y personas — `sitio/`

Este archivo describe las reglas de **este** proyecto para trabajar en el sitio
público. No es el marcador genérico de Nuxt: el proyecto tiene sus propias
convenciones y quien trabaje aquí debe conocerlas **antes** de escribir la primera
línea.

---

## 1. Antes de tocar nada

1. Leer `../plan.md` — el plan de construcción y el origen de las decisiones.
2. Leer `../contract/openapi.yaml` — la fuente de verdad del intercambio HTTP.
3. Leer `../docs/adr/` — las decisiones ya tomadas, con su motivo. **Si vas a
   contradecir una, dilo antes de hacerlo**, no después.
4. Leer este archivo entero. Tiene 15 minutos de lectura y ahorra días.

---

## 2. Cómo está construido el CSS

**Cuatro hojas, y cada una tiene su dueño.**

| Hoja | Quién manda | Se toca |
|---|---|---|
| `public/govco/bootstrap.min.css` | Bootstrap 5.0.2, vendorizado | **Nunca.** Es de un tercero |
| `public/govco/all.css` | Kit UI gov.co 9.2, vendorizado | **Nunca.** Ver §3 |
| `app/assets/css/tokens.css` | Nosotros | Sí, es la capa de valores |
| `app/assets/css/sitio.css` | Nosotros | Sí, es la capa de reglas |

**El orden de carga está en `nuxt.config.ts` y es arquitectura, no preferencia:**

```
bootstrap.min.css  →  all.css  →  tokens.css  →  sitio.css
        ↑                              ↑
   el andamiaje              los valores        las reglas
```

Cada hoja posterior puede sobreescribir a las anteriores **por orden de
aparición**, sin necesidad de `!important`. Eso es lo que hace que la arquitectura
funcione, y romper el orden la rompe entera.

### ¿Por qué las dos vendorizadas no se tocan?

`all.css` es **byte a byte igual al del CDN del Ministerio** —verificado con
`cmp`— y `bootstrap.min.css` lleva su `sha384` comprobado contra el que publican
los ejemplos del propio Kit. Esa identidad es lo que permite actualizar el Kit
sustituyendo un archivo y saber que no hemos divergido.

**Y hay una razón más fuerte:** el Kit **no distribuye Bootstrap, pero depende de
él**. Su rejilla, su acordeón, su carrusel y su `input-group` son de Bootstrap. Si
recompiláramos Bootstrap desde Sass —que es lo que recomienda la metodología
ITCSS— su salida podría divergir de lo que el Kit espera, y lo notaríamos en
producción.

---

## 3. Reglas que no se negocian

| Regla | Por qué |
|---|---|
| **Un color no se escribe en crudo fuera de `tokens.css`** | Se declara una vez y se consume por `var()`. Escribirlo dos veces es cómo se llega a tener el mismo azul en dos formas |
| **Nunca editar las hojas de `public/govco/`** | Ver §2 |
| **Un componente consume tokens de nivel 3, o del 2 si no hay 3. Nunca del 1** | Un componente acoplado al valor no se puede re-tematizar; acoplado al papel, sí |
| **Nada de `<main>` en las páginas** | La disposición ya lo pone. Un segundo rompe la región principal y el salto al contenido |
| **`<script setup lang="ts">`, cero `any`** | Lo verifica `vue-tsc` en el CI |
| **`:key` en todo `v-for`; `v-if` y `v-for` nunca juntos** | Lo verifica el linter del proyecto |
| **Un solo `<h1>` por página** | Estructura del documento; hay una prueba que lo verifica |
| **Nada de contenido institucional inventado** | Ver §7 |

---

## 4. Cómo se declara un token

`tokens.css` tiene **tres bloques**, y cada uno responde a una pregunta distinta:

```
:root {  NIVEL 1 · Primitivos   — ¿qué color/es la paleta?          }
:root {  NIVEL 2 · Semánticos   — ¿qué papel cumple?                }
:root {  NIVEL 3 · Componentes  — ¿qué necesita este componente?    }
```

Los tres bloques de `:root` son **deliberados**: cada nivel es autocontenido y se
lee de un vistazo. Fusionarlos daría 800 líneas donde el nivel se pierde. Está
declarado como excepción en `.stylelintrc.json`.

**Para añadir un token:**

1. ¿Es un valor de la paleta o una unidad? → **nivel 1**, con su fuente citada.
2. ¿Es un papel (superficie, texto, borde, estado)? → **nivel 2**, y **anota su
   contraste medido** si va a llevar texto encima.
3. ¿Sólo lo usa un componente? → **nivel 3**. Si lo acaban usando dos, se
   promueve al 2.

**Ejemplo real, y el motivo importa tanto como el valor:**

```css
/* Nivel 2 — el papel, con la medición al lado. */
--color-superficie-entidad: var(--alcaldia-azul-celeste-a);
--color-texto-sobre-entidad: var(--alcaldia-negro);
```

El texto va oscuro porque sobre `#00ADE7` el negro da **8,13:1** y el blanco
**2,59:1**. El Kit pone blanco porque su fondo por defecto es oscuro; sobre el
celeste de la Entidad incumpliría AA. **Esa cifra va en el archivo** para que
nadie tenga que volver a calcularla ni descubra el problema en producción.

---

## 5. Cómo se añade o modifica una regla

1. **¿Es un valor?** → `tokens.css`, no `sitio.css`.
2. **¿Es una regla?** → `sitio.css`, y **elige la sección que corresponda**. El
   archivo está dividido por secciones con encabezados de 80 caracteres.
3. **Escribe el porqué, no el qué.** El archivo tiene un 26 % de comentarios y no
   es casualidad: cada decisión no obvia lleva su motivo. Un comentario que
   describe lo que el código ya dice sobra; uno que explica **por qué** ahorra que
   alguien lo «arregle» mañana.

**El estilo de comentario del proyecto:**

```css
/* ============================================================================
   Título de la sección
   ============================================================================

   **La afirmación que importa, en negrita.**

   El problema, con la medición que lo demuestra. Y la decisión, con su motivo.

   Si el arreglo obvio no sirve, dilo: es lo que evita que se reintente.
   ============================================================================ */
```

**Si tu cambio corrige un defecto del Kit, dilo explícitamente.** El archivo
documenta veinte puntos donde el Kit incumple lo que él mismo publica, y cada uno
con su medición. Esa documentación es la que permite actualizar el Kit sin
perderlos.

---

## 6. La gobernanza, y cómo se verifica

### Stylelint

```bash
cd sitio && npx stylelint "app/assets/css/*.css"
```

Cinco reglas, y cada una protege algo concreto: `max-nesting-depth 3` (un
selector que depende de su contexto deja de ser reutilizable), `selector-max-id 0`
(un `#id` gana siempre y obliga a `!important`), `selector-max-specificity 0,3,0`
(que la siguiente persona no necesite `!important` para ganarte),
`declaration-no-important` (un `!important` casi siempre esconde un problema de
especificidad) y `no-duplicate-selectors` (dos bloques del mismo selector hacen
que **el orden de las secciones decida**, y nadie lo ve).

### Las dos excepciones declaradas

Están en `.stylelintrc.json`, **con su motivo escrito y acotadas por archivo**. No
son silencios:

1. **`tokens.css` puede repetir `:root`** — los tres niveles (ver §4).
2. **`sitio.css` tiene relajadas cuatro reglas**, porque contiene la **capa de
   modos de accesibilidad**: 41 `!important`, 51 de especificidad y 16 `#id`.

**La segunda merece explicación, porque parece un waiver y no lo es.** Un modo de
alto contraste, de dislexia o de detención de animaciones **tiene que ganarle a
todo lo ya pintado**, incluido el Kit gov.co, que es un tercero vendorizado y no
controlamos. Y `#__nuxt` es la raíz de la aplicación: no hay forma de estilarla
sin el `#id`. **Meterlos en `utilities/` sería clasificarlos mal: no son helpers,
son una capa de modo.**

> **Se intentó extraerlos a `modos-accesibilidad.css` y se descartó, con motivo.**
> Están **intercalados** con las secciones que sobreescriben, no en un bloque
> contiguo. Y la extracción **no aporta nada funcional** —las reglas funcionan
> donde están—: su único fin era que el linter dejara de marcarlas, y eso se
> consigue sin tocar la cascada. Mover 550 líneas intercaladas arriesgando el
> orden del que depende que el modo gane es un mal negocio. **No lo reintentes sin
> una razón nueva.**

### La captura visual — úsala siempre

**Es la única prueba que detecta que el diseño cambió.** `axe` verifica
contraste, foco y estructura, pero **no verifica que la página se siga viendo
igual**: un párrafo descolocado o una altura distinta no violan ninguna norma.

```bash
cd sitio
npm run build
PORT=4300 node .output/server/index.mjs &
BASE=http://127.0.0.1:4300 node scripts/captura-visual.mjs            # compara
BASE=http://127.0.0.1:4300 node scripts/captura-visual.mjs --guardar  # re-fija
```

Once vistas, en `tests/visual/referencia/`. **Están versionadas a propósito**: una
referencia que sólo existe en tu máquina no protege a nadie más.

> **Caso real, y la razón de que esto exista.** `stylelint --fix` aplicó
> `selector-not-notation` a la regla del modo de contraste. La notación moderna
> **no es equivalente**:
>
> ```
> :not(svg):not(path)   cada :not aporta (0,0,1)  →  suma (0,0,2)
> :not(svg, path)       un solo :not, toma el máximo  →  (0,0,1)
> ```
>
> **Baja la especificidad** de una regla que existe para ganar. `axe` seguía en
> cero, ninguna prueba se rompió, y el modo de contraste se repintaba distinto.
> **Sólo la captura visual lo vio.** Por eso `selector-not-notation` está
> desactivada: **una regla de estilo no puede ganarle a una de corrección.**

---

## 7. Lo que **no** se hace

| Anti-patrón | Por qué |
|---|---|
| Editar `public/govco/**` | Rompe la identidad byte a byte con el CDN del Ministerio |
| Escribir un color en crudo fuera de `tokens.css` | Es cómo se acaba con el mismo azul en tres formas |
| Recompilar Bootstrap desde Sass | El Kit depende de Bootstrap y no lo distribuye (ver §2) |
| Inventar contenido institucional | Paleta, misión, políticas, datos de contacto: **los aprueba la Entidad**. Donde no hay fuente, se declara ausente |
| Publicar datos que no sean de la Entidad | Ver §8 |
| `!important` fuera de la capa de modos | Esconde un problema de especificidad |
| Un `#id` como selector nuevo | Salvo `#__nuxt` y los anclajes de la propia página |

---

## 8. Dos trampas que ya nos costaron tiempo

**La primera, datos ajenos.** En `../investigacion/raw/` hay ficheros `ds_*.json`
que **no son los datos abiertos de Santa Marta**: el que lleva su nombre devuelve
24 conjuntos de Barranquilla y 7 de Santa Rosa de Cabal, y **ninguno del Distrito**.
Usarlos publicaría datos de otro municipio como si fueran de aquí. La fuente
federada se consulta en `datos.gov.co` **filtrando por entidad propietaria**.

**La segunda, el `git push` que parece funcionar.** En esta máquina, `git` necesita
`GIT_SSH_COMMAND="ssh -F /dev/null -o BatchMode=yes"` **en cada orden**, porque el
`/etc/ssh/ssh_config.d/` local es ilegible. Sin ella el push falla, y **el mensaje
de git no lo dice**: llega a parecer que se empujó cuando no. Si ves cambios que
no aparecen en el remoto, mira eso primero.

---

## 9. Puertas antes de dar algo por hecho

```bash
cd sitio
npm run typecheck                     # 0 errores
npm run build                         # sin errores
npx stylelint "app/assets/css/*.css"  # limpio
# con el build servido:
node scripts/captura-visual.mjs       # las 11 vistas iguales
```

**Y arranca el servidor con un puerto libre y mátalo al terminar.** Esta máquina
tiene historial de servidores huérfanos sirviendo compilaciones viejas, con
respuestas mezcladas de dos procesos a la vez — lo que produce verificaciones
falsas en las dos direcciones.

---

## 10. Lo que está pendiente de decisión

Escrito aquí para que nadie lo resuelva por su cuenta:

- **Bootstrap**: ¿recompilar desde Sass (rompe la identidad verificada) o
  mantenerlo vendorizado y reconfigurar por tokens? **Recomendado lo segundo.**
- **Tema oscuro**: los tokens están preparados para conmutarlo, pero **no hay tema
  oscuro institucional aprobado**. No se inventa.
- **Catálogo de componentes**: Storybook frente a Histoire (nativo de Vue).
- **`panel/`**: usa Tailwind, que ya es un sistema atómico con su propia
  configuración. **Meterle ITCSS encima sería pelearse con dos metodologías en el
  mismo repositorio.** Decidir antes de tocar.
