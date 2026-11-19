# Archivo 0008 (no es el `ADR-0008` del registro): `file_media` sin dueño polimórfico, con `uso` y huella obligatoria

> **Estado:** PROPUESTA (entrega 0D, tareas D10 y D11; pasa a ACEPTADA cuando José apruebe el cierre de la fase)
> **Fecha:** 2026-11-19
> **Decisor:** José (aprobó las cuatro decisiones de D10 con las opciones recomendadas, 2026-11-19)
> **Tags:** base de datos, archivos, migraciones
> **Sustituye:** la decisión 2 del archivo 0005 («`model_type` se conserva en cada lectura») y la parte de `getByCollection` de su decisión 3. El resto del archivo 0005 sigue vigente.

## Contexto

Hasta D9, `file_media` era polimórfica: `model_type` + `model_id` (sin clave foránea posible) más `collection` y un JSON `metadata`. La guarda del esquema (archivo 0007) la señalaba con cuatro defectos: la clave compuesta `(model_type, model_id, collection, filename)`, la enumeración `collection` sin `CHECK`, el JSON `metadata` y la asociación polimórfica misma. D8 y D9 ya habían construido las relaciones explícitas que dicen quién usa cada archivo (`noticias.imagen_principal_uuid`, `banners.imagen_uuid`, `noticia_imagenes`, `politica_documentos`, todas `ON DELETE RESTRICT`) y habían movido el código a leerlas. Quedaba contraer: retirar lo viejo.

`collection` no se podía retirar a secas. Distingue una imagen de contenido sin uso (que la limpieza borra a los 7 días) de un documento sin uso (que no se borra nunca, Ley 594/2000, a la espera de jurídica): «sin uso» es justo lo que no tiene relación a la que preguntar.

## Decisión

1. **`file_media.uso`** (`varchar(32) NOT NULL`, `CHECK (uso IN ('imagen','contenido','documento'))`) reemplaza a `collection`. Los valores salen de los tres que el código real escribía (`imagen`, `contenido`, `documento-oficial`); si una base trajera otro, la migración se detiene y lo lista sin cambiar nada.
2. **El archivo no tiene dueño.** Se borran `model_type`, `model_id`, `collection` y `metadata`. Quién usa un archivo lo dicen las relaciones explícitas; quién lo subió, `subido_por` (solo se registra en `contenido`). El texto alternativo pertenece al uso de la imagen: vive en `noticias.imagen_principal_alt` y `banners.imagen_alt`.
3. **API.** La respuesta del archivo ya no trae `model_type`, `model_id` ni `metadata`, y conserva `collection`, calculada desde `uso` (`documento` sale como `documento-oficial`): el panel no cambia. La subida de documentos de política ya no acepta `metadata`. El parámetro `collection` de la ruta y de la petición sigue siendo un nombre de la API, no de la columna.
4. **Huella.** `hash_sha256` pasa a `NOT NULL` con `CHECK` de SHA-256 en hexadecimal minúscula. Si algún archivo no la tiene, la migración se detiene sin cambiar nada y lista las filas; el remedio es `archivos:calcular-hash` y repetir.
5. **Tres lanzamientos más, por separado** (mismo patrón que el archivo 0005, porque el despliegue migra antes de reemplazar los contenedores y `volver_atras` no deshace migraciones):

   | Lanzamiento | Migración | Código | Punto de retroceso |
   |---|---|---|---|
   | D10a (expandir) | Agrega `uso` con `CHECK`; `model_type`, `model_id` y `collection` pasan a opcionales; retira el índice único compuesto | Deja de leer y escribir lo viejo; `upload(file, uso, subidoPor)`; respuesta y contrato sin las columnas retiradas | D9, que lo ve todo |
   | D10b (contraer) | Borra las cuatro columnas y el disparador; huella obligatoria | Fuera `Resincronizador` y `mediateca:resincronizar` | D10a, que no las toca |
   | D10c | — | `archivos:verificar` nocturno (03:00 Bogotá): compara la huella de cada archivo con su disco, solo lee, falla si hay diferencias o ausentes; sale de la agenda `archivos:calcular-hash` | D10b |

6. **Disparador transitorio** (`file_media_completar_uso`, solo entre D10a y D10b). Durante el despliegue de D10a el código anterior sigue atendiendo mientras migra, y solo escribe `collection`: sin ayuda, su insert fallaría contra `uso NOT NULL` (comprobado con una transacción revertida). El disparador rellena `uso` desde `collection` y también `collection` desde `uso`, de modo que volver a D9 después de subir archivos con el código nuevo no deja filas que D9 no entienda (D9 valida las imágenes del editor por `collection`). D10b lo borra.
7. **Retroceso de D10b** (`down()`): reconstruye `model_type`/`model_id` desde la noticia, el banner o la política que usa el archivo (o desde `subido_por` en `contenido`), `collection` desde `uso` y `metadata.alt` desde el alt de la noticia o del banner. Los archivos **sin uso** no tienen un dueño que se pueda conocer: quedan en NULL, que D10a admite; no se inventa. El alt de las imágenes de `contenido`, que nada leía, no se reconstruye.

## Consecuencias

- Antes de promover D10a: **copia manual** de la base (`docker compose --profile copias run --rm copia`, `docs/copias-de-seguridad.md`). Antes, las 5 consultas de solo lectura de la propuesta de D10 en staging (colecciones reales, huellas, desfases de imagen y alt, metadatos distintos de `alt`).
- La migración D9 (`mediateca_resincronizar`) ya no depende de ninguna clase de la aplicación: su lógica de resincronización vive dentro de ella, para que una base nueva siga migrando después de retirar `Resincronizador`.
- Las pruebas de migraciones anteriores (D8, D9 y las del archivo 0005) revierten D10b y D10a en su preparación (`RevierteUsoDeArchivos`); los fixtures y el factory escriben `uso` y la huella.
- La línea base de la guarda del esquema baja en cuatro defectos de `file_media`. Quedan `disk` (permanente: lo fija la configuración) y el resto de las tablas.
- `archivos:calcular-hash` queda sin filas que completar tras D10b: sale de la agenda y lo reemplaza `archivos:verificar` (D10c). El comando se conserva (el despliegue lo corre, sin efecto, y es el remedio que nombra D10b). **HIPÓTESIS:** con el volumen de hoy (decenas de archivos) leer todos los discos cuesta segundos; el de producción no está medido, y si la lectura pasara de 30 minutos coincidiría con la limpieza de las 03:30, que podría borrar un archivo sin uso que la verificación aún no leyó y reportarlo como ausente.
- **INFERENCIA no verificable desde el repositorio:** que ningún consumidor externo de la API lea `model_type`, `model_id` o `metadata`. Los dos clientes propios (panel y sitio) no los leen (`grep` en `panel/src` y `sitio/src`; los tipos generados no se versionan).
- Alternativas descartadas: derivar el uso de las relaciones (no distingue lo que no tiene relación, que es lo que se limpia); un solo lanzamiento (las subidas fallarían durante la ventana del despliegue); conservar `metadata` en otra parte (el panel no la envía y solo se escribía `alt`, que ya vive en su uso).

## Evidencia

`tests/Feature/Esquema/MediatecaUsoMigracionTest.php` (9), `MediatecaContraerMigracionTest.php` (8), `tests/Feature/Api/V1/FileMediaPorUsoTest.php`, `FileMediaSinModelUuidTest.php`, `tests/Feature/Operacion/VerificarArchivosTest.php` (D10c) y `AgendaDeHuellasTest.php`; la prueba de deriva del contrato (`FileMediaItem` sin las claves retiradas) y la línea base de `GuardaEsquemaTest`. Comprobado de punta a punta en una base aparte (D9 con datos de las seis clases de archivo → D10a → D10b → retroceso de ambas): dueños, colecciones y alt idénticos a los de partida.
