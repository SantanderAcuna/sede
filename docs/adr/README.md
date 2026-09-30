# Registro de Decisiones de Arquitectura (ADR)

> Formato: registro de decisiones. Cada entrada indica contexto, decisión,
> estado, consecuencias y la evidencia que la respalda.
>
> Base normativa: `docs/GUIA Maestra Sede Electronica Colombia.md` §6.3 paso 1.4
> («Aprobar la arquitectura: ADR») y `GUIA-MAESTRA-COMPLETA.md` (stack de casa).

---

## ADR-0001 — Stack de la Sede Electrónica

**Estado:** Aceptada

**Contexto.** El expediente `docs/` exige una sede con SPA, API REST, PostgreSQL
y autenticación mediante los Servicios Ciudadanos Digitales (§6.3 paso 1.4). La
guía de casa `GUIA-MAESTRA-COMPLETA.md` (reglas absolutas 2 y 3) obliga a
desacoplar backend y frontend en carpetas independientes, sin Inertia ni Blade,
con Laravel en el backend y Vue 3 + TypeScript en el frontend.

**Decisión.** Monorepo con `backend/` (Laravel 13, PHP 8.5, API REST) y
`frontend/` (Vue 3 + TypeScript + Vite, SPA). PostgreSQL 18 como base de datos,
Redis 7 para caché/sesión/colas, MinIO como almacenamiento compatible con S3.

**Consecuencias.** Ambas partes se acoplan únicamente al contrato OpenAPI. La
verificación de extremo a extremo requiere ambos servidores activos. El
cumplimiento de WCAG 2.1 AA es responsabilidad del frontend, que renderiza todo
el HTML público.

**Evidencia.** `composer show laravel/framework` → v13.34.0; `php -v` → 8.5.10.

---

## ADR-0002 — Kit UI gov.co v5 vendorizado localmente (corrige la URL del expediente)

**Estado:** Aceptada

**Contexto.** La `Sección 6 · Implementación paso a paso.md` (§6.12.2 y §6.12.6)
indica consumir el Kit desde
`https://cdn.www.gov.co/layout/v5/all.css` y `.../layout/v5/script.js`. La
verificación directa muestra que **esas rutas no sirven CSS ni JavaScript**:

```
$ curl -sSI https://cdn.www.gov.co/layout/v5/all.css
HTTP/2 200
content-type: text/html
content-length: 1908          <-- HTML de la aplicación BDC, no CSS
x-cache: Error from cloudfront
```

La base real del bundle v5 se obtiene del propio HTML de la Biblioteca Digital
de Componentes (`https://cdn.www.gov.co/v5/`, que referencia
`../layout-govco-v5/all.css`):

```
$ curl -sS -o /dev/null -w "%{http_code} %{content_type} %{size_download}\n" \
    https://cdn.www.gov.co/layout-govco-v5/all.css
200 text/css 266316

$ curl -sS -o /dev/null -w "%{http_code} %{content_type} %{size_download}\n" \
    https://cdn.www.gov.co/layout-govco-v5/script.js
200 text/javascript 111624
```

**Decisión.** Vendorizar el Kit completo desde el repositorio oficial
(`gitlab.com/govco/layout-govco`, rama `v5`) en `frontend/public/govco/`
— `all.css`, `script.js` y `assets/{fonts,icons,images}` — preservando la
estructura relativa que el CSS espera (`assets/fonts/...`, `assets/icons/...`).
La CDN queda configurable como alternativa, con la URL correcta.

**Consecuencias.** El sitio no depende de un tercero para renderizar su capa
visual (atributo de calidad «disponibilidad» y titularidad, art. 14 inc. 2 del
DL 2106/2019). Se incorporan ~7,8 MB de assets al repositorio (768 iconos SVG,
fuentes Nunito Sans y Verdana). El CSS del expediente (`docs/`) debe corregirse:
se anota en la sección 6.

**Evidencia.** `frontend/public/govco/` (266 316 B de `all.css`); los 21 tokens
`--govcolor-*` del CSS real coinciden exactamente con la tabla §2.2.1 de
`Sección 2 · Diseño.md`.

---

## ADR-0003 — Bootstrap 5.0.2 + tokens gov.co en lugar de Tailwind

**Estado:** Aceptada

**Contexto.** La guía de casa lista `tailwindcss` entre las dependencias del
frontend. Sin embargo, el criterio **CAG-33** es bloqueante: «El código debe
construirse sobre **Bootstrap 5.0.2** y los CSS variables `--govcolor-*`
declarados en `src/all.css`». La Resolución MinTIC 1519/2020 Anexo 2 impone el
uso del Kit.

**Decisión.** No se instala Tailwind. La capa visual es Bootstrap 5.0.2 + Kit UI
9.2 (`all.css`) + variables `--govcolor-*`, con una hoja propia mínima para
tematización por entidad. Los iconos usan la fuente `govco-font` y los 768 SVG
oficiales; se descarta FontAwesome.

**Consecuencias.** Se evita el conflicto entre el preflight de Tailwind y el
reset de Bootstrap, que alteraría el contraste y el espaciado validados por el
Kit. Toda utilidad de layout se expresa con clases de Bootstrap 5.

**Evidencia.** `all.css` declara `Bootstrap v5.0.2`; tabla §2.2.1 de la
documentación (21 tokens).

---

## ADR-0004 — Componentes gov.co como componentes Vue propios

**Estado:** Aceptada

**Contexto.** El Kit UI 9.2 define 24 componentes (8 transversales, 19 generales
y 4 de formulario) cuyo marcado y comportamiento son la base de la conformidad
WCAG y de los criterios CAG-01 a CAG-34.

**Decisión.** Cada componente se encapsula como componente Vue 3
(`<script setup lang="ts">`) que **reproduce el marcado oficial** y delega el
comportamiento en el `script.js` del Kit cuando existe, en lugar de
reimplementarlo. Se descartan `@tanstack/vue-table` y `@tanstack/vue-form` para
no duplicar los componentes de tablas y formularios del Kit. Se conservan
Pinia, Vue Router 4, TanStack Query, Zod, Axios y vue3-toastify para estado,
enrutado, datos de servidor y validación.

**Consecuencias.** Menor riesgo de regresión de accesibilidad y una única
fuente de verdad visual. El coste es una capa de envoltura por componente.

---

## ADR-0005 — Integraciones externas mediante puertos y adaptadores

**Estado:** Aceptada

**Contexto.** §5.4 del expediente enumera 14 integraciones obligatorias (gov.co
OIDC, PSE, firma electrónica, correo certificado, SMS, captcha, AGN,
interoperabilidad SCD). La mayoría figura como **(a confirmar)** y no existen
credenciales productivas disponibles en este entorno.

**Decisión.** Cada integración se define como una **interfaz** en
`app/Contracts/Integraciones/` con tres implementaciones: `live` (cliente real),
`sandbox` (entorno de pruebas del proveedor) y `mock` (determinista, para
desarrollo y pruebas). El driver activo se selecciona por configuración
(`config/sede.php` → `integraciones`). Ninguna clase de dominio conoce al
proveedor concreto.

**Consecuencias.** Los flujos completos son ejecutables y verificables hoy
(incluida la radicación, el pago con webhook idempotente y la notificación con
acuse). El paso a producción exige únicamente credenciales y el cambio de
driver, sin tocar el dominio. **Riesgo declarado:** los comportamientos del
proveedor real deben validarse durante la Fase 3.

---

## ADR-0006 — La entidad titular y sus datos institucionales

**Estado:** Aceptada

**Contexto.** **FUN-014** exige que el pie de página publique los datos
completos de la entidad (nombre, dirección, código postal, teléfono, línea
gratuita, línea anticorrupción, correo de atención y correo de notificaciones
judiciales), y **SEG-006** exige las cinco políticas obligatorias.

**Decisión.** La entidad titular es la **Alcaldía Distrital de Santa Marta**
(Distrito Turístico, Cultural e Histórico). Los datos se centralizan en
`backend/config/entidad.php`, obtenidos de las fuentes oficiales del dominio
`santamarta.gov.co`, y se exponen al frontend por `GET /api/v1/entidad`. Los
valores que requieren ratificación formal se listan en la clave
`datos_por_confirmar` y se señalan en la interfaz de administración.

**Evidencia.** `santamarta.gov.co` (pie de página y página de localización
física): NIT 891780009; Calle 14 No. 2-49, Palacio Municipal; línea de atención
(+57) 605 4351719; PBX (+57) 605 420 9600; línea gratuita 01-8000-955-532;
`atencionalciudadano@santamarta.gov.co`;
`notificacionesalcaldiadistrital@santamarta.gov.co`; horario L-V 8:00-12:00 y
14:00-18:00.

---

## ADR-0007 — Remapeo de puertos en el entorno de desarrollo

**Estado:** Aceptada

**Contexto.** Las convenciones canónicas de infraestructura fijan app `8000`,
nginx `80/443`, PostgreSQL `5432` y Redis `6379`. En la máquina de desarrollo el
puerto `8000` ya está ocupado por otro servicio.

**Decisión.** Los puertos **internos** de los contenedores permanecen intactos
(8000, 8080, 5432, 6379). En el host de desarrollo se publican nginx en
`8080/8443`, la API en `8001` y el servidor Vite en `5173`.

**Consecuencias.** Las convenciones de infraestructura se conservan en
producción. `compose.override.yaml` (solo desarrollo) contiene el remapeo.

---

## ADR-0008 — Omisión deliberada de Public-Key-Pins (HPKP)

**Estado:** Aceptada

**Contexto.** **SEG-012** reproduce el criterio del PDF oficial, que menciona
`Public-Key-Pins (HPKP)` entre las cabeceras a habilitar. HPKP fue **eliminada**
de Chrome (72, 2019) y Firefox por riesgo de denegación de servicio por
*pinning* hostil, y el propio expediente lo reconoce en §4.2.7 («Deprecada… la
práctica actual es omitirla»).

**Decisión.** No se envía `Public-Key-Pins`. Se habilitan `Content-Security-Policy`
(con nonce), `Strict-Transport-Security`, `X-Content-Type-Options`,
`X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` y `X-XSS-Protection: 0`,
más los flags `Secure`, `HttpOnly` y `SameSite` en las cookies.

**Consecuencias.** Desviación **documentada y justificada** del texto literal de
SEG-012, con evidencia de la práctica actual de OWASP. Debe registrarse en la
declaración de conformidad del acta de aceptación.

---

## ADR-0009 — CMS profesional con Filament 5 como superficie editorial

**Estado:** Aceptada

**Contexto.** El requisito del proyecto es que la sede se administre con un
**CMS profesional**, no con pantallas hechas a medida. La guía de casa (regla
absoluta 2) exige backend API REST y frontend SPA desacoplado, y no menciona una
superficie editorial.

**Decisión.** Se incorpora **Filament 5** (panel `/admin`, en español) como CMS
profesional: motor de contenidos con tipos y campos, flujo editorial
(borrador → revisión → programado → publicado → archivado), versionado con
restauración, biblioteca de medios, taxonomías, constructor de menús, bloques,
metadatos SEO, gestor de redirecciones 301, roles y permisos (Spatie), registro
de auditoría y publicación programada. El **frontend público sigue siendo la SPA
Vue** y se alimenta exclusivamente por la API REST: el panel es una superficie
interna, no el sitio.

**Consecuencias.** La regla absoluta 2 se mantiene para el sitio público (el
ciudadano nunca recibe HTML de Livewire). El panel aporta Livewire y Blade como
dependencias del backend. Filament 5.9 requiere Laravel `^13.0` y Livewire
`^4.4`, ambos compatibles con el stack elegido.

**Evidencia.** `filament/support v5.9.0` → `illuminate/contracts ^11.28|^12.0|^13.0`,
`livewire/livewire ^4.4.2`, `php ^8.2`.

---

## ADR-0010 — Notificaciones en vivo por SSE en lugar de Reverb

**Estado:** Aceptada

**Contexto.** El catálogo de módulos de infraestructura contempla Reverb para
notificaciones en vivo. `laravel/reverb` no es instalable junto a Laravel 13:
sus versiones `v1.7.0–v1.12.0` exigen `guzzlehttp/psr7 ^2.6`, mientras Laravel
13 fija `guzzlehttp/psr7 3.1.0`.

**Decisión.** Las notificaciones en vivo del panel del ciudadano se sirven por
**Server-Sent Events** sobre la API (con *polling* de respaldo). Se reevalúa
Reverb cuando publique soporte para Laravel 13.

**Evidencia.** `composer require laravel/reverb` → conflicto de dependencias
reportado por Composer.

---

## ADR-0011 — El script global del Kit no se carga; Bootstrap aporta el comportamiento

**Estado:** Aceptada

**Contexto.** La `Sección 6` (§6.12.2, §6.12.6) indica incluir
`script.js` del Kit en todas las páginas. Ese archivo es un paquete de 111
funciones que se autoinicializa en `window.load` y llama a `addEventListener`
sobre el resultado de selectores como `.carrusel-govco` o
`.container-modal-govco`. En una página que no usa esos componentes el selector
devuelve `null` y el script lanza un error en **cada** carga.

Verificado en Chromium con Playwright sobre las seis vistas construidas, antes de
retirarlo:

```
Errores de consola:
  pageerror: Cannot read properties of null (reading 'addEventListener')
  pageerror: Cannot read properties of null (reading 'addEventListener')
  ... (uno por cada carga de página)
```

**Decisión.** No se carga `/govco/script.js` de forma global. El comportamiento
lo aporta Bootstrap 5.0.2 (menú desplegable, pestañas, modales) y los dos
componentes del Kit que sí se usan —barra de accesibilidad y «volver arriba»—
están implementados como componentes Vue (ADR-0004). Cuando se incorpore el
carrusel o los modales del Kit, su script se cargará bajo demanda en la vista que
los use.

**Consecuencias.** Cero errores de consola en las seis vistas verificadas. Se
evita que una auditoría de calidad o de accesibilidad registre errores de
JavaScript en todas las páginas.

**Evidencia.** `npm run test:shell` → «Errores de consola: ninguno».

---

## ADR-0012 — La barra de accesibilidad se implementa en Vue y no con el script oficial

**Estado:** Aceptada

**Contexto.** El script oficial de la barra de accesibilidad
(`src/transversal/barra-accesibilidad.js`) ajusta el tamaño de letra recorriendo
`document.querySelectorAll('body *')` y fijando `fontSize` **en línea** en cada
elemento. En una SPA eso tiene dos consecuencias: cualquier navegación reemplaza
nodos del DOM y el ajuste se pierde a mitad del recorrido del ciudadano, y el DOM
queda con un estilo en línea por elemento, lo que impide razonar sobre él y
dificulta las pruebas.

**Decisión.** Se conserva el **marcado y las clases oficiales** del Kit
(`barra-accesibilidad-govco`, botones `increase-font-size`, `decrease-font-size`
y `contrast`, clase `active` y `contrast-govco` sobre `body`), de modo que el
componente siga siendo el del Kit. El comportamiento lo gobierna un componente
Vue: la escala tipográfica se expone como variable CSS `--sede-escala` aplicada
al contenedor, y la preferencia se conserva en `localStorage`.

**Consecuencias.** El ajuste sobrevive a cualquier navegación y a cualquier
re-renderizado, no ensucia el DOM con estilos en línea y es verificable. El modo
de alto contraste se tematiza con los colores institucionales ya validados en
§2.2.1, porque el Kit solo estiliza `contrast-govco` para su página de ejemplo.

**Evidencia.** `npm run test:shell` comprueba los tres botones y el contraste
medido (21:1).

---

## ADR-0013 — Almacenamiento de objetos: S3 en lugar de una imagen concreta de MinIO

**Estado:** Aceptada

**Contexto.** La guía de infraestructura del proyecto y la documentación del
expediente proponen MinIO como almacenamiento de objetos. Al preparar la pila se
comprobó que su imagen oficial **ya no es descargable**:

```
$ docker manifest inspect minio/minio:latest
errors:
denied: requested access to the resource is denied
unauthorized: authentication required

$ docker manifest inspect quay.io/minio/minio:latest
NO disponible
```

Lo mismo ocurre con otras etiquetas publicadas anteriormente y con
`bitnami/minio`. El registro exige credenciales, de modo que una pila que
dependiera de esa imagen dejaría de construirse.

**Decisión.** El contrato con el almacenamiento es **S3**, no un producto. La
aplicación usa `league/flysystem-aws-s3-v3` y selecciona el proveedor por
configuración. Para la pila local se adopta **SeaweedFS** (licencia Apache-2.0),
que expone la misma API S3 y verifica el mismo camino de código. En producción la
entidad puede usar MinIO con sus propias credenciales, Ceph o el almacenamiento
de la nube estatal.

**Consecuencias.** La pila se construye sin depender de un registro con acceso
restringido. El código de la aplicación no cambia al cambiar de proveedor: solo
la configuración. La prueba de integración del almacenamiento debe ejecutarse
contra el proveedor real antes del despliegue, porque las implementaciones de S3
difieren en los detalles (URLs firmadas, bloqueo de objetos, cifrado en reposo).

**Evidencia.** `docker/s3/s3.json` define la identidad local; `docker/README.md`
documenta la operación; `compose.yaml` declara el servicio `s3` en la red de
datos.

---

## ADR-0014 — Nginx como único punto de entrada y la SPA servida desde disco

**Estado:** Aceptada

**Contexto.** La sede tiene tres superficies: la API REST, el panel de
administración y la SPA pública. Servir cada una desde un proceso distinto
obligaría a exponer varios orígenes, lo que contradice el art. 14 inc. 1 del
Decreto Ley 2106 de 2019 —una sola sede por autoridad— y repartiría las cabeceras
de seguridad entre varios servidores.

**Decisión.** Un único contenedor `nginx` es el punto de entrada. Sirve la SPA
compilada y los activos del Kit gov.co desde el sistema de archivos, y delega en
la aplicación las rutas `/api`, `/admin`, `/livewire` y `/storage`. Es el único
servicio que publica puertos al exterior.

**Consecuencias.** Un solo origen y un solo lugar donde se aplican las cabeceras
de SEG-012 y el límite de tasa de primera línea. La SPA se sirve sin tocar PHP,
lo que reduce la latencia de las páginas públicas y la carga de la aplicación.
Como contrapartida, cualquier cambio en la SPA exige reconstruir su imagen y
reiniciar nginx.

---

## ADR-0015 — Desviaciones de diseño declaradas frente a los criterios CAG

**Estado:** Aceptada

**Contexto.** La `Sección 2 · Diseño.md` fija 34 criterios de aceptación de
diseño (CAG-01 a CAG-34). La puerta `frontend/tests/conformidad-diseno.mjs` mide
sobre el navegador real la tipografía, la paleta, el contraste, el carrusel, la
rejilla en los seis breakpoints, el área activa táctil y los componentes del Kit.
Algunos criterios no admiten cumplimiento literal en esta sede y varios más no
aplican porque el componente no existe en el producto. Afirmar que se cumplen
sería falso; callarlos dejaría la matriz de trazabilidad mintiendo por omisión.

**Decisión.**

1. **CAG-06 — botón de cambio de idioma: omitido.** §2.3.1 describe ese botón
   como *(opcional)* dentro de la barra superior, y **FUN-008** exige que la sede
   esté íntegramente en castellano. Un conmutador que no conmuta ninguna lengua
   sería un control falso y anunciaría una capacidad inexistente (WCAG 4.1.2). La
   barra superior conserva el logo GOV.CO y la galería de aplicaciones.

2. **CAG-18 — «máximo 5 elementos visibles» en los desplegables: umbral
   reexpresado.** La sede usa el `<select>` nativo, que aporta por sí solo el
   teclado, el lector de pantalla y el comportamiento del sistema. Sustituirlo
   por un *combobox* a medida sin implementar el patrón ARIA completo sería un
   retroceso de accesibilidad a cambio de una regla de presentación. Las listas
   reales de la sede van de 2 a 11 elementos. El criterio queda acotado así: por
   encima de **12 elementos** debe usarse el desplegable con filtro de búsqueda
   del Kit, y la puerta lo verifica.

3. **CAG-19, CAG-21, CAG-22, CAG-24 y CAG-25 — no aplican.** La sede no usa
   campos de calendario, no abre modales, no emite notificaciones *toast*, no
   publica tablas ni usa acordeones. La puerta lo comprueba como ausencia
   declarada: si alguno se incorpora, la verificación falla y obliga a
   satisfacer su criterio.

4. **La superficie semántica verde del Kit: no se adopta.** El *toast*
   de éxito del propio Kit combina `--govcolor-green` (#158361) con la superficie
   `#CDE6DF` y rinde **3,61:1**, por debajo del 4,5:1 que exige WCAG 1.4.3. La
   sede no reproduce esa combinación: usa borde y color de texto sobre blanco,
   que rinden 4,78:1 (verde) y 7,89:1 (rojo, sobre `#EECDD2` sería 5,35:1 pero
   tampoco es un token declarado). El mismo motivo llevó a no usar ningún fondo
   tintado inventado.

5. **CAG-33 — «solo los 21 tokens»: se reexpresa lo que el Kit deja en
   literales.** El bundle del Kit escribe `#3366cc`, `#4b4b4b`, `#E5ECF8`,
   `#004884`, `#E6EFFD`, `#B5C7E9`, `#CDE6DF` y `#EECDD2` como valores literales.
   Los componentes que la sede instancia —galería de aplicaciones, carrusel,
   botones de la barra de accesibilidad— se reexpresan con los tokens; el resto
   del bundle no se toca (§6.12.5 regla 1). La puerta mide la paleta **efectiva**
   de las vistas, no la del archivo.

6. **Los títulos del pie son `h2` y `h3`, no los `h4` y `h5` del ejemplo del
   Kit.** El Kit estiliza el pie sobre `h4` (Cobalt 22 px) y `h5` (Cobalt 20 px),
   pero en seis vistas el contenido termina en `h1` o `h2`: el salto a `h4`
   rompería el orden de encabezados y la auditoría lo marcaría. El elemento sigue
   el orden del documento y la apariencia del Kit se aplica por clase
   (`.titulo-pie-sede`, `.subtitulo-pie-sede`).

7. **El logo de la autoridad es el logotipo tipográfico de la entidad, no el
   marcador del Kit.** El Kit dibuja `.govco-logo-entidad` con
   `content: url(assets/images/Logo-v2-MinTIC.png)`: es el logo del Ministerio
   TIC puesto como ejemplo. Publicarlo en la sede del Distrito sería mostrar el
   logotipo de otro organismo. Mientras el Distrito no entregue su archivo
   oficial, el pie y la cabecera usan el nombre de la entidad con el tratamiento
   de marca del Kit. **Pendiente:** incorporar el logo oficial de la Alcaldía.

**Consecuencias.** La matriz de trazabilidad deja de listar criterios «pendientes»
que nunca se van a implementar y pasa a distinguir tres estados reales:
satisfecho, no aplica y desviación declarada. Cualquier cambio futuro que
incorpore un componente no aplicado rompe la puerta de conformidad, que es
exactamente la señal que se busca.

**Evidencia.** `frontend/tests/conformidad-diseno.mjs` — más de cincuenta
verificaciones, cada una con su criterio citado, entre ellas «La sede no abre
modales: CAG-21 no aplica», «La sede declara una sola lengua y no ofrece un
conmutador de idioma falso (CAG-06)» y «Ninguna lista nativa supera los 12
elementos que este ADR fija como umbral (CAG-18)». La matriz de trazabilidad
lista los 34 criterios de diseño con evidencia: `make trazabilidad`.
