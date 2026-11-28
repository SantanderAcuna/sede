# Decisiones tomadas sin tu autorización — construcción del panel de gestión de la Sede

Este archivo registra cada decisión que tomé por mi cuenta mientras construía (tú dijiste: «si hay una decisión por tomar o autorización, tómala y regístrala»). Para cada una: qué decidí, por qué, qué alternativas descarté y cómo se revierte. Las decisiones se agregan en orden; al final de cada fase quedan las de esa fase.

Formato: **HECHO** (verificado), **INFERENCIA** (deducción mía), **DECISIÓN** (lo que hice).

---

## Fase 1 — Cimientos del editor

### D-01 · Editor: Tiptap (MIT) en lugar de CKEditor 5 / TinyMCE
- **Contexto:** pediste CKEditor 5 o TinyMCE, «el más seguro», y respondiste «mit» a la pregunta de licencia.
- **HECHO:** `@tiptap/core`, `vue-3`, `starter-kit`, `extension-link`, `extension-image` y `pm` son MIT (npm, 3.33.1). `npm audit`: 0 vulnerabilidades. Aviso histórico CVE-2025-14284 (`javascript:` en enlaces) corregido en 2.10.4. TinyMCE tuvo CVE-2026-47759..47762 (sanitizador, severidad alta). CKEditor 5 es GPL-2+ o comercial, incompatible con «mit».
- **DECISIÓN:** Tiptap. Esquema mínimo; la seguridad real está en el saneador del servidor.
- **Revertir:** el componente `EditorTexto.vue` es el único que importa Tiptap.

### D-02 · Saneador de HTML: `symfony/html-sanitizer` con lista blanca estricta
- **DECISIÓN:** elementos permitidos: h2, h3, p, br, strong, em, ul, ol, li, blockquote, a (href/target/rel), img (src/alt). Esquemas de enlace: https, http, mailto, tel. Se fuerza `rel="noopener noreferrer nofollow"`. Se eliminan caracteres BiDi y nulos. Imágenes: solo `/api/v1/contenido/imagenes/<uuid>` (relativa o con el dominio de la sede) y con `alt` no vacío.
- **Por qué no HTMLPurifier:** Symfony 8 ya está en el proyecto y su API es más pequeña; 37 pruebas de corpus (OWASP, mXSS, `javascript:` con tabulaciones/entidades, SVG, `data-*`, BiDi) pasan.
- **Riesgo conocido:** `symfony/html-sanitizer` tuvo avisos en 2026 sobre atributos de URL; por eso hay prueba de cada variante y un segundo saneado al mostrar (Fase 2).

### D-03 · Imágenes del editor: reutilizan `FilesMediaService` (no se crea almacenamiento nuevo)
- Tu observación («ya tenemos el file media polimórfico») era correcta. Solo se añadió una puerta delgada: validación estricta + re-codificación con GD + `alt` obligatorio + colección `contenido`.
- **DECISIÓN:** el propietario polimórfico es **el usuario que sube** (la noticia puede no existir aún al insertar la imagen). Límites: PNG/JPG/WEBP, 5 MB, 4000 px por lado (frena «bombas de descompresión»). GIF y SVG excluidos.
- **DECISIÓN:** lectura pública `GET /api/v1/contenido/imagenes/{uuid}` con el límite `imagenes-publicas`, `Cache-Control: public, max-age=86400, immutable` (el uuid es único por archivo) y solo sirve la colección `contenido`.
- **Pendiente conocido (se limpia en Fase 7):** imágenes subidas y no usadas quedan huérfanas; no hay limpieza automática todavía.

### D-04 · Permiso para subir imágenes del editor
- **DECISIÓN:** puede subir quien tenga `noticias.gestionar`, `politicas.gestionar` o `entidad.gestionar`. No se creó un permiso nuevo para no ampliar el modelo de permisos sin necesidad.

### D-05 · Botones del editor con texto visible, sin iconos
- **Por qué:** pediste que personas que no conocen el sistema lo entiendan. «Negrita», «Cursiva», «Título», «Subtítulo»… se entienden sin adivinar. Además evita registrar iconos nuevos.
- Enlace e imagen se piden en **paneles dentro de la página** (no `prompt()` ni ventanas que atrapan el foco).
- Solo existen h2 y h3 (el h1 es el de la página).

### D-06 · Botón nativo «Choose File» reemplazado
- **DECISIÓN:** `SubirImagen.vue` oculta el input nativo (`sr-only`, sigue accesible por teclado) y usa una etiqueta en español. Valida tipo y tamaño en el cliente (el servidor vuelve a validar siempre). Ya se usa en el formulario de noticias.

### D-07 · Margen derecho para el botón de accesibilidad
- Autorizaste el margen. **DECISIÓN:** `pr-14` (56 px) en el área principal: el botón mide 44 px.

### D-08 · CSP modo «aplicar» tras variable de entorno, apagado
- **DECISIÓN:** `SEDE_CSP_MODO` (por defecto `informe`; solo `aplicar` exacto cambia). Cualquier otro valor deja `informe` y avisa. La política es una sola; solo cambia el nombre de la cabecera.
- **Hallazgo propio:** el contenedor nginx es de solo lectura (`read_only: true`), así que no se podía editar el snippet. Se genera `/var/run/sede-csp.conf` (tmpfs) al arrancar y `default.conf` incluye ese archivo. La guarda del CI se actualizó.
- Prueba con nginx real: ambos modos, valores raros, idempotencia, `blob:` solo en el panel.
- **Staging y producción no cambian** mientras la variable no se defina como `aplicar`.

### D-09 · Dependencias locales
- El contenedor de trabajo no puede bajar `symfony/html-sanitizer` por Composer (lista de permitidos); lo instalé desde el commit exacto del `composer.lock`. El CI lo instala solo (verificado en el PR #567).

---


## Fase 2 — Noticias con cuerpo HTML

### D-10 · Migración que reemplaza la nota «sin HTML» de la tabla de noticias
- La migración original decía que el contenido era texto plano. La nueva (`2026_11_12_000000_noticias_contenido_a_html`) convierte cada noticia a `<p>…</p>` con escape; `down` devuelve a texto plano. Probada en local y en CI.
### D-11 · Saneado al escribir Y al leer; tope de 100 000 caracteres
- El saneador corre al guardar (control principal) y otra vez en `NoticiaResource`/`NoticiaPanelResource`, por si algo entró por otra vía (sembrado, SQL). Tope de 100 000 caracteres: evita cuerpos gigantes.
- `esHtml` = empieza con `<[a-z!/]`: así el texto plano heredado se sigue mostrando en párrafos.
### D-12 · Segundo saneado en el sitio, cargado de forma diferida
- `sanitize-html` se importa dinámicamente solo en la página de noticia (no engorda el resto). Se eliminó `parrafosDeNoticia` (ya no se parte por línea en blanco).
### D-13 · Barra del editor con patrón ARIA «toolbar»
- Una sola parada con Tab (roving tabindex), flechas/Inicio/Fin, `mousedown` no roba el foco, Escape cierra el panel y devuelve el foco al texto. Hallazgo real: con el ratón el botón quitaba el foco y rompía la escritura.

## Fase 3 — Políticas y Entidad

### D-14 · Políticas: los apartados siguen en texto plano (sin editor enriquecido)
- **Por qué:** el texto alimenta el ODT y el PDF/A oficiales, que se generan desde párrafos de texto; meter HTML obligaba a reescribir ambos generadores y abría superficie XSS en documentos legales. Tu pedido de WYSIWYG era explícito para noticias.
- **Qué se hizo:** tabla + vista de edición aparte; un bloque de texto por apartado (línea en blanco = párrafo).
- **Revertir/ampliar:** si quieres editor enriquecido en políticas, hay que convertir los generadores ODT/PDF a HTML; es una fase aparte.
### D-15 · Políticas: no hay «crear» ni «eliminar»
- Son cinco y fijas (el sitio y la validación dependen de sus slugs). La tabla lo explica.
### D-16 · Políticas: la vista de edición busca por slug en la lista (no hay endpoint individual)
- Evita tocar el contrato; son 5 registros.
### D-17 · Entidad: secciones visibles en una página, sin pestañas
- Las pestañas ocultaban campos y no tenían ARIA. Ahora: Identidad, Atención al público, Teléfonos y correos, Redes sociales, Políticas en el pie.
- **Corrección de un defecto real:** antes no se podía borrar un dato ni quitar todas las redes (solo se enviaba lo «con valor»). Ahora vaciar envía `null` y la lista vacía se guarda.
### D-18 · Entidad: no se edita el logo
- El sitio no lee `entidad.logo` (usa `sitio/public/logo-entidad.png`); un campo de subida habría sido un control que no hace nada, y tocar la identidad visual necesita tu autorización explícita (regla 6 del plan). La pantalla lo dice.
### D-19 · Entidad: las políticas del pie se eligen de las que existen
- Antes se escribía el slug a mano (riesgo de enlace roto, RF-01-012). Ahora un selector con la lectura pública de políticas.
### D-20 · Se eliminó `tests/entidad-view-logic.test.ts`
- Probaba una copia pegada de la lógica, no el componente; lo reemplazan `entidad-logica.test.ts` y `entidad-view.test.ts`.

### Hallazgos que no corregí (fuera de alcance de la fase)
- La miga de pan muestra «Configuracion» sin tilde y el menú lateral resalta a la vez «Políticas del sitio» y «Configuración».
- El sembrado local no trae políticas ni entidad por defecto (no es defecto del código; el sembrado se ejecuta aparte).

## Fase 4 — Trámites y servicios

### D-21 · Editar un trámite del catálogo oficial (SUIT) lo marca como «del panel»
- **Por qué:** la carga automática (`IngestaTramites`) solo reescribe trámites cuya procedencia sea SUIT; marcar la procedencia como el panel protege la corrección sin tocar la ingesta. Es la opción (c) del plan.
- **Efecto:** `procedencia.fuente` = «Alcaldía Distrital de Santa Marta (panel de gestión)», `origen_por_campo[campo]='entidad'`; se conservan la URL y demás datos de procedencia.
- **Revertir:** quitar `marcarProcedencia` en `TramitePanelService`.

### D-22 · Códigos y direcciones de los trámites creados en el panel
- Código `P0001`, `P0002`… (prefijo P, no choca con los `T…` de SUIT). Slug único y **inmutable** tras crear (un enlace compartido no puede romperse); el código tampoco cambia.

### D-23 · Borrador con solo el nombre; publicar exige ficha completa
- Publicar exige: dirección de ficha GOV.CO, modalidad, costo sí/no, tiempo en días, canal de inicio, cómo consultar el estado y ≥1 requisito (las reglas de `scopePublicados`). El panel muestra la lista «Antes de publicar» en vivo y el servidor la comprueba siempre.

### D-24 · Eliminar: solo los creados en el panel; los de SUIT solo se despublican
- DELETE responde 409 para SUIT (la ingesta los traería de vuelta). La tabla y el formulario no ofrecen «Eliminar» en ellos. Un SUIT corregido aquí sigue siendo `origen: fuente` con `modificado_en_panel: true` (hallazgo propio en la verificación en navegador: al principio aparecía como «panel» y ofrecía un botón que el servidor rechazaba).

### D-25 · Permiso nuevo `tramites.gestionar`
- Antes la ruta usaba `tramites.ver` (permiso de lectura). Un permiso de gestión explícito evita que quien solo mira pueda editar. Se siembra en `PermissionSeeder`; no se asigna a ningún rol automáticamente (solo el super-admin lo tiene vía Gate).
- **Pendiente para ti/Fase 5:** asignarlo a los roles que corresponda desde la pantalla de roles.

### D-26 · Validación tolerante con los datos reales, estricta con las direcciones
- Prueba nueva que abre y guarda **sin cambios los 123 trámites reales**. Reveló que mi primera validación rechazaba/descartaba datos reales (requisitos sin descripción, `canales`, `pago`, `cuentas` por requisito, `normativa.archivo`, municipio como objeto, textos más largos que el contrato, cuentas en dos formas, códigos de pago propios de SUIT). Se ajustaron reglas y se eliminó el `TramiteInput` antiguo y huérfano del contrato.
- **Hallazgo de datos (no corregido, es de la fuente):** hay direcciones web inválidas en SUIT (p. ej. `http://file:///C:/Users/...` en una norma, `http://por DAVIPLATA una vez tenga la liquidación` en un pago, `www.santamarta.gov.co` sin esquema). El panel las rechaza al guardar esa lista, con el campo exacto, para que se corrijan; no se silencian.

### D-27 · Solo viaja lo que cambió
- Cada fila conserva las claves que la pantalla no muestra (pagos/cuentas por requisito) y una lista sin tocar no se envía: así no se pierde nada de lo que trae SUIT.

### D-28 · Componente `ListaEditable` y texto de ayuda
- Una sola pieza dirigida por descripción de campos para las 14 listas (requisitos, pasos con requisitos anidados, documentos, audiencias, puntos de atención, normativa, cuentas, canales, seguimiento…). Campos poco usados van en «Más datos (opcional)». Errores del servidor se traducen a «Paso 2, Requisito 1 · Tipo» en lugar de `momentos.1.requisitos.0.tipo`.
- **Proceso, con franqueza:** escribí el componente antes de su prueba (la prueba pasó a la primera); el resto de la fase sí fue rojo → verde.

### D-29 · Texto plano en los campos del trámite
- Resumen, descripciones, etc. no admiten HTML (los muestra el sitio escapado). Sin editor enriquecido: no lo pediste para trámites y evita superficie XSS.

## Fase 5 — Usuarios, roles y permisos

### D-30 · Quién administra: `admin` y `super-admin`, por permiso `usuarios.gestionar`
- Tu decisión 5 («admin y superadmin administran»). El servidor exige `usuarios.gestionar`; el súper administrador lo tiene vía `Gate::before`. Como no existía un rol `admin`, `PermissionSeeder` lo crea **una sola vez** con todos los permisos del panel (si ya existe no se toca, para no deshacer ajustes hechos desde el panel). Cualquier otro rol con `usuarios.gestionar` también administra.
- Se agregó la regla «nadie otorga lo que no tiene» (D-31), así que un `admin` no se vuelve súper administrador.

### D-31 · Reglas contra la escalada de privilegios (las hace el servidor; la interfaz solo las anticipa)
1. Quien no es súper administrador solo asigna o quita roles cuyos permisos **tiene todos**, y **nunca** `super-admin` (403).
2. Solo un súper administrador modifica a otro súper administrador (403).
3. Nadie cambia **sus propios** roles ni su estado (403): evita auto-ascenso y auto-bloqueo.
4. No se puede desactivar ni quitar el rol al **único súper administrador activo** (422).
5. El rol `super-admin` no se edita ni se elimina desde el panel (403); el nombre está reservado.
6. Un rol con usuarios no se elimina (409). Un rol solo recibe permisos que quien lo edita posee; **quitar** un permiso ajeno también se rechaza.
7. Cada alta, cambio, bloqueo y cambio de rol queda en `audit_log` **sin la contraseña** (solo dice que cambió «clave»).
8. Desactivar/bloquear o cambiar la contraseña **cierra las sesiones abiertas** del usuario.
- Cubierto con 19 pruebas de backend (incluye intentos hostiles) y comprobado en el navegador (PATCH directo al rol súper administrador → 403).

### D-32 · Los usuarios no se eliminan: se desactivan o bloquean
- No hay `DELETE /panel/usuarios`. Borrar rompe la trazabilidad de la auditoría (quién hizo qué). Estados: activo, inactivo, bloqueado, pendiente (ya existían en la base).

### D-33 · Contraseñas
- Mínimo 12 caracteres con letras y números (criterio SEG-02). **No** se consulta HIBP (llamada externa; decisión tuya/Jurídica). Las pone el administrador como **temporal**; no hay envío de correo de restablecimiento (el correo saliente no está configurado en el proyecto).
- **MFA no se gestiona aquí.** El campo `mfa_habilitado` existe y se devuelve, pero activarlo/obligarlo requiere el flujo TOTP (T-10/D-3), que no es de esta fase. **Pendiente real:** hoy un administrador sin MFA puede crear usuarios.

### D-34 · Sin capa Repository para usuarios y roles
- Los modelos de Spatie ya son la persistencia y no hay varias fuentes de datos; un repositorio sería una capa vacía. Controlador → FormRequest → Servicio (con interfaz) → modelos. Contar usuarios por rol usa la tabla puente, porque la relación `users()` de Spatie exige configuración de guardas que aquí sobra.

### D-35 · Defecto encontrado y corregido: la auditoría reventaba al dar muchos permisos de una vez
- `PermissionAuditListener` guardaba la lista completa de permisos en `audit_log.recurso` (255 caracteres). Al sembrar el rol `admin` con 26 permisos fallaba. Ahora el texto se recorta y la lista completa va en `cambios`. Habría afectado a cualquier rol grande.

### D-36 · Nombres de rol y textos
- Nombres con letras (con tildes), números, espacios y guiones («redactor de noticias»). Los permisos se muestran con etiqueta y descripción en lenguaje llano, agrupados por tema; uno que exista en la base pero no en el catálogo aparece en «Otros» (nunca invisible).
- Una sola entrada de menú «Usuarios y roles» con dos pestañas (Usuarios / Roles y permisos).

### D-37 · Defecto propio encontrado en el navegador
- Al crear un usuario, el aviso decía «Cambios guardados» en lugar de «Usuario creado» (leía el estado después de guardar). Corregido con prueba en rojo primero.

## Fase 6 — Carrusel de la portada (banners)

### D-38 · Permiso `banners.gestionar` y quién lo recibe
- Permiso nuevo `banners.gestionar`. El sembrador lo concede al rol `admin` solo cuando el permiso se crea (no pisa cambios que ya hiciera quien administra). `super-admin` lo tiene por `Gate::before`. En staging, el rol `admin` existente lo recibe al correr el sembrador.

### D-39 · Modelo y estados
- Tabla `banners` (título ≤ 80, descripción ≤ 200, enlace ≤ 500, orden, activo, vigente_desde/hasta, borrado lógico). Estado calculado, no guardado: borrador (inactiva), programada (inicio futuro), publicada (activa y dentro de fechas), vencida (fin pasado). Sin límite de diapositivas (la Entidad decide; el carrusel tiene controles de pausa y navegación).
- La imagen reutiliza `FilesMediaService` (colección `imagen`; el texto alternativo va en `metadata.alt`). Sin imagen o sin texto alternativo no se puede publicar (Res. MinTIC 1519/2020).

### D-40 · Validación del enlace
- Solo una ruta del sitio (`/transparencia`) o `https://`. Se rechazan `javascript:`, `data:`, `http:` y `//dominio` (redirección abierta). Se valida en el servidor, en el panel y otra vez en la portada (defensa en capas).

### D-41 · Orden por botones, no arrastrando
- «Subir» / «Bajar» con anuncio para lector de pantalla y guardado inmediato; el servidor exige la lista completa sin repetidas ni desconocidas. Decidí no usar arrastrar y soltar por accesibilidad (teclado, lector de pantalla, táctil) y por no añadir dependencias.

### D-42 · Portada con respaldo
- La portada lee `GET /api/v1/banners`. Si la API falla, no hay ninguna publicada o llegan inválidas (sin alt, imagen ajena a la API), se muestran las diapositivas por defecto de siempre: el carrusel nunca queda vacío. Una diapositiva con enlace inválido se conserva sin enlace. No se tocó el diseño público (`CarruselGovco`).

### D-43 · Sin capa Repository y excepción de gitleaks
- Como en la Fase 5 (D-34), sin Repository: el modelo Eloquent es la persistencia. Se añadieron a la lista de excepciones de gitleaks los archivos de prueba de usuarios (contraseñas de prueba), con justificación por archivo.

### D-44 · Pendientes conscientes (no se corrigieron)
- Al cambiar la imagen de una diapositiva se borra la anterior; al eliminar la diapositiva (borrado lógico) la imagen se conserva a propósito, para poder recuperarla. No hay limpieza definitiva automática.
- La búsqueda pública no ignora tildes (preexistente).

## Fase 7 — Verificación transversal

### D-45 · Defecto de contraste preexistente corregido
- Matriz de 14 pantallas del panel × 6 anchos (320, 375, 768, 1024, 1440, 1920) con axe (WCAG 2.1 AA) y medición de desborde horizontal: único hallazgo, el texto «Acceso total (sin restricciones)» del perfil (`text-slate-400`, 2,56:1). Cambiado a `text-slate-600`. No toca el diseño público.

### D-46 · Defecto propio detectado por el CI
- La prueba `LimiteLecturasPublicasTest` fija la lista de lecturas públicas con cupo propio; la nueva `GET /banners` no estaba y el CI falló. Se añadió `v1.banners.listar` (la ruta ya usaba el limitador de lecturas). Lección: correr la suite completa de backend antes de abrir el PR, no solo los archivos nuevos.

### D-47 · Despliegue: migraciones y permisos
- Comprobado en `deploy/droplet/despliegue-remoto.sh`: el despliegue corre `migrate --force` y `db:seed --class=SedeSeeder`, que incluye `PermissionSeeder`. Por tanto en staging se crean la tabla de banners y el permiso `banners.gestionar`, y el rol `admin` lo recibe solo al crearse. No hizo falta ningún paso manual.

### D-48 · Datos de prueba en staging
- En staging quedan noticias de prueba de recorridos anteriores («Recorrido local con el panel real», autor `admin@planeacion.gov.co`). Decisión: **no las toqué** (no puedo iniciar sesión en staging ni borrar con credenciales, y eliminar datos ajenos al código no estaba autorizado). Quedan para que las elimines desde el panel; ya hay confirmación y borrado lógico.
- No se crearon diapositivas de prueba en staging: la portada muestra las de por defecto hasta que publiques una.

## Estado final
- Fases 1–7 completas en `develop` y promovidas a `staging` (#575, despliegue en verde). `main` no se tocó.
- Pendiente solo de tu lado: iniciar sesión en staging para el recorrido final, y decidir lo listado como «Pendientes conscientes» (MFA, tildes en la búsqueda, los 14 módulos sin construir).

---

## Reconstrucción del módulo 03 — Servicios y Trámites (desde cero, sin SUIT)

> Plan previo en `sede-electronica-doc/03-servicios-tramites/PLAN-servicios-tramites-desde-cero.md`.

### D-49 · «Desde cero» = arquitectura nueva, contenido conservado (Opción A, confirmada por ti)
- El módulo se reconstruyó con esquema, capas, panel y sitio nuevos. Los 123 trámites reales ya cargados se **conservaron** por carga única relacional (no se redigitaron ni se perdieron). Confirmaste la Opción A por chat.

### D-50 · Eliminación de la dependencia del pipeline del SUIT
- Borrados: `IngerirTramites`, `IngestaTramites(+Interface)`, `ReconciliacionSuit`, `Services/GovCo/*`, `FuenteFichaGovCo(+Interface)`, `MapeoFicha`, `FichaFuente`, `IngestaTramite` (modelo/repo/interface), `FuenteSuit`, la tabla `ingesta_tramites`, y sus pruebas/fakes (ingesta, banco-*, MapeoFicha*). Quitados del modelo: `procedencia_*`, `esDelPanel`, `FUENTE_PANEL`, `TramiteNoSePuedeEliminar`.
- `TramiteSeeder` se reescribió como **carga inicial relacional** desde el archivo congelado del proyecto (sin red) y es **idempotente**: si ya hay trámites, no re-siembra (no pisa ediciones del panel en cada despliegue).

### D-51 · Integración GOV.CO: obligatoria para publicar (corrige el marco del plan)
- Tras tu indicación «se debe cumplir la integración con gov.co»: la ficha de GOV.CO es **condición de publicación** para trámites y OPA (RF-B1-021, Decreto 2106). Las **consultas** no la exigen (Decreto 2106, RN-B2-002: no son trámites, se suprimen del SUIT). Lo que se eliminó es la *dependencia del pipeline* del SUIT, no el enlace a la ficha oficial.

### D-52 · Base relacional
- Tablas nuevas: `dependencias`, `tramite_categorias`, `tramite_pasos`, `tramite_requisitos`, `tramite_documentos`, `tramite_puntos_atencion`, `tramite_normas`, `tramite_canales`, `tramite_audiencias`, `tramite_cuentas`, `tramite_valoraciones`. Migración que convierte los 123 (JSON→filas) con `ConversorFichaJson`, asigna tema inicial por palabras del nombre (`TemaDeTramite`, revisable) y retira los bloques JSON y la procedencia. Soft delete en `tramites`.
- **Hallazgo (no inventado):** el catálogo de origen no traía dependencia por trámite, así que `dependencia_id` quedó en `null` en los 123. No se inventó: la faceta de dependencia se llena cuando la Entidad la asigne en el panel.

### D-53 · Presentación del sitio (tipo Montería)
- Lista `/tramites`: directorio con buscador, contador, **facetas** (tipo, modalidad, costo, tema, dependencia, tipo de solicitante) con contadores del servidor, grupos Trámites/OPA/Consultas, y tarjetas con chips iconizados cuyo nombre lleva a la **ficha propia**; GOV.CO como enlace secundario.
- Ficha `/tramites/[slug]`: ficha propia con **stepper de 4 etapas** (RF-B2-028), requisitos, puntos de atención con mapa (RF-B2-029), normas, y GOV.CO como referencia.
- **Verificado:** axe = 0 y sin desbordamiento horizontal en 320/375/768/1024/1440/1920, en lista y ficha, en build de producción con los 123 reales.
- Diseño público: el cambio se acota a las vistas de trámites (autorizado por ti). `tokens.css` y `public/govco/**` intactos. **Pendiente de tu OK:** regenerar la línea base de la puerta visual para estas vistas (regla del proyecto: no se sube sin tu autorización).

### D-54 · Panel
- Formulario conservado y adaptado a relacional: selector de **tipo** (crear OPA y consultas), selector de **dependencia** (endpoint nuevo `GET /panel/dependencias`), **solicitantes** como casillas, pasos y requisitos relacionales. Quitados el banner SUIT, el seguimiento, los perfiles y el producto final. **Todo elemento es borrable** (papelera). Lista: columna Tipo en vez de Origen.

### D-55 · Alcance: Fase A hecha; Fase B fuera (esquema preparado)
- **Fase A (hecha):** catálogo propio + presentación + panel + OPA/Consultas.
- **Pendiente de Fase A:** valoración FÁCIL/DIFÍCIL + encuesta 1–5 (RF-B3-065/B2-033/034/B1-094): la tabla `tramite_valoraciones` existe; falta el endpoint público y el componente en la ficha.
- **Fase B (fuera de este alcance):** flujo transaccional de 4 etapas (formulario, radicado idempotente, pago PSE, expediente SGDEA, Carpeta Ciudadana, autenticación SCD), que depende de los módulos 09 y 10. El esquema quedó preparado para recibirla sin rehacerse.

### D-56 · Estado y PR
- Commiteado en `feat/tramites-propios`: backend relacional, contrato + tipos, sitio y panel. Pruebas: backend 549 verde; panel 600 verde (vue-tsc limpio, eslint sin errores); contrato válido (Redocly) y deriva OK.
- **Pendiente:** abrir el PR a `develop` y la promoción a `staging`. En el PR, la **puerta visual fallará contra la línea base vieja** (las vistas de trámites cambiaron): es lo esperado y requiere tu OK para regenerar esa base.
