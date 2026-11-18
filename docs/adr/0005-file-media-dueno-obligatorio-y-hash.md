# Archivo 0005 (no es el `ADR-0005` del registro): `file_media` con dueño obligatorio (`model_id`) y hash del contenido

> **Estado:** PROPUESTA (entrega 0B, tarea T8, de la Fase 0; pasa a ACEPTADA cuando José apruebe la fase)
> **Fecha:** 2026-11-17
> **Decisor:** José (decisión D-0.5 de la ficha de la Fase 0)
> **Tags:** base de datos, archivos, migraciones

## Contexto

`file_media.model_id` admitía NULL y el índice único `[model_type, model_id, collection, filename]` lo
incluye. En PostgreSQL los NULL no chocan entre sí, así que las filas sin `model_id` eludían la
unicidad. Las lecturas reales (imagen de banners y noticias, documento de las políticas, borrado de una
política) buscaban por `model_uuid`, no por el id que cubre ese índice. Y no se guardaba ningún hash del
contenido de los archivos.

Medido en staging el 2026-11-17: 16 filas (4 `Banner`, 12 `Noticia`), 0 con `model_id` nulo, 0
incoherentes, 0 huérfanas.

## Decisión

1. `model_id` pasa a **NOT NULL**. La migración lo rellena desde `model_uuid` con el dueño real
   (por la tabla de su modelo) y **se detiene sin cambiar nada** si queda una fila sin dueño o con un
   `model_id` que contradice a su `model_uuid`: lista cada fila (uuid, tipo, colección, ruta) y no
   borra ni inventa. Todo va en una transacción. También se detiene, listando las filas, si una fila con
   `model_id` apunta a un dueño que no existe o de un tipo que no es un modelo propio, y si dos filas
   chocarían en el índice único al asignarles dueño (antes salía el error opaco de PostgreSQL).
2. Las lecturas pasan a `model_type` + `model_id` (`Banner::imagenPrincipal`, `Noticia::imagenPrincipal`,
   `Politica::documentoOficial`, el borrado de una política y `FilesMediaService::getByCollection`).
   `model_type` se conserva en cada una: dos modelos pueden tener el mismo id.
3. `FilesMediaService::getByCollection` de un objeto sin id devuelve vacío (antes, sin id ni uuid, no
   filtraba por dueño y devolvía los archivos de todos los modelos de ese tipo), y `upload` de un
   modelo sin id **falla antes de escribir en disco**.
4. Nueva columna `hash_sha256` (`char(64)`, nullable). `upload` la calcula; los archivos que ya
   existían se rellenan con `php artisan archivos:calcular-hash`, que lee cada disco, es idempotente,
   lista los archivos ausentes y termina con error si hay alguno. Una migración no debe leer discos. El
   despliegue lo ejecuta tras sembrar (tope de 5 minutos; si algo falla, avisa y **no** vuelve atrás) y la
   agenda lo repite cada noche a las 03:00 de Bogotá para completar lo subido en la ventana de despliegue.
5. **Expandir–contraer, en tres lanzamientos separados** (orden aprobado por José, 2026-11-17).
   `volver_atras` repone la imagen anterior pero **no deshace migraciones**, así que cada lanzamiento
   debe poder retroceder al anterior sin perder datos:

   | Lanzamiento | Contenido | Punto de retroceso |
   |---|---|---|
   | 1 | T8: lee por `model_id` y sigue escribiendo `model_id` y `model_uuid` | Versión anterior a T8, que lee por `model_uuid` y ve todo |
   | 2 | A: deja de escribir `model_uuid` y lo quita de `FileMediaResource`, del contrato y de `$fillable`; el contrato declara `model_id` como entero requerido; guardia que compara el recurso con el esquema | T8, que lee por `model_id` y ve todo |
   | 3 | B: migración que borra la columna `model_uuid` y su índice, tras repetir la comprobación de coherencia | A, que no la toca |

   A **no** viaja con T8: si lo hiciera, el retroceso caería en una versión que lee por `model_uuid` y
   no vería lo escrito sin él. A no entra a `develop` mientras T8 no esté desplegado en staging, y B
   espera a que A se haya desplegado y estabilizado. Al llegar a B, las pruebas de la migración de T8
   (que insertan filas con `model_uuid`) deberán revertir antes la migración de borrado o retirarse con
   la columna. Queda por decidir si la respuesta de la API conserva el uuid del dueño calculado desde
   `model_id` o lo pierde (hoy ningún cliente lo lee).

## Consecuencias

- Antes de promover a staging o producción: **copia manual `backup:run`** justo antes (D-0.5), repetir
  la consulta de coherencia, migrar y ejecutar `archivos:calcular-hash`.
- Retroceso: `down()` devuelve `model_id` a nullable y quita `hash_sha256`; los valores rellenados de
  `model_id` se conservan. Para volver a un estado anterior a los datos, restaurar la copia previa.
- Una instalación con filas sin dueño no migra hasta que una persona decida qué hacer con ellas: es a
  propósito.

## Evidencia

`tests/Feature/Operacion/FileMediaModelIdTest.php` (12), `tests/Feature/Api/V1/FileMediaPorModelIdTest.php`
(8), `tests/Feature/Operacion/CalcularHashArchivosTest.php` (3).
