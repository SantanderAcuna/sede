# Archivo 0007 (no es el `ADR-0007` del registro): guarda del esquema de la base de datos, con línea base y trinquete

> **Estado:** PROPUESTA (entrega 0D, tarea D1; pasa a ACEPTADA cuando José apruebe el cierre de la fase)
> **Fecha:** 2026-11-18
> **Decisor:** José (aprobó la ficha 0D y el plan de robustez, 2026-11-18)
> **Tags:** base de datos, pruebas, integridad

## Contexto

La auditoría del 2026-11-18 (PostgreSQL 16 con las 35 tablas de las migraciones) midió defectos de diseño que ninguna prueba vigilaba: 5 asociaciones polimórficas sin clave foránea posible, 9 columnas JSON, 38 fechas sin zona, 5 claves foráneas sin índice, 14 columnas de enumeración sin `CHECK` y 3 claves candidatas de 3 columnas o más. Corregirlos va por tareas (D2–D10); sin guarda, cualquier migración nueva puede volver a introducirlos.

## Decisión

`backend/tests/Support/InspectorEsquema.php` consulta el catálogo del esquema `public` y devuelve los defectos como `categoria|objeto`. `GuardaEsquemaTest` los compara con `esquema.linea-base.json`:

1. Un defecto que no está en la línea base **rompe la CI**.
2. Una entrada de la línea base que **ya no existe** también la rompe: la lista solo baja, y quien corrige algo la quita.
3. Las excepciones permanentes (`permanente: true`) exigen una razón: tablas de Spatie y Sanctum, la bitácora (`audit_log`) y la clave de los modelos de Spatie.

`InspectorEsquemaTest` prueba el detector con una tabla que trae cada defecto a propósito, y comprueba que corregirlo lo hace desaparecer.

## Consecuencias

- La deuda queda medida y declarada (74 entradas, 7 permanentes); las tareas D2–D10 la bajan.
- La detección de enumeraciones es por **nombre de columna** (`tipo`, `estado`, `modalidad`, `canal`, …): no ve una enumeración con otro nombre. Se completa con la revisión de las fichas de cada fase.
- La clave candidata compuesta se señala para revisión de 5FN (criterio de Date y Fagin, 1992); las de 2 columnas son binarias y no se señalan.
- Alternativa descartada: revisar el esquema a mano en cada PR (depende de la memoria de quien programa).
