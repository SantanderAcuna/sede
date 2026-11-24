# La bitácora de auditoría es de solo inserción

Estado: implementada en la entrega 0D, tarea D6 (2026-11-18). Tabla: `audit_log`.

## Qué impide, y cómo

| Barrera | Qué hace | Dónde vive |
|---|---|---|
| Permisos | `sede_app` (la aplicación, Horizon y el planificador) solo puede `INSERT` y `SELECT` | `App\Support\RolesBaseDeDatos::aplicar()`. Corre en cada despliegue (`bd:sincronizar-roles`): un `REVOKE` suelto en una migración lo desharía el siguiente `GRANT … ON ALL TABLES` |
| Trigger | `UPDATE`, `DELETE` y `TRUNCATE` se rechazan para **cualquiera**, también el dueño (`sede`) | Migración `2026_11_19_600000_bitacora_de_solo_insercion` |

`audit_log.cambios` es `jsonb`. `jsonb` ordena las claves de un objeto por longitud y luego por orden alfabético: el orden dentro de `cambios` puede diferir del de escritura, sin efecto semántico.

## Retención

**No hay depuración automática.** El plazo legal de conservación no está definido (Ley 594 de 2000 y la tabla de retención documental; se consulta con jurídica). Mientras tanto la tabla solo crece.

## Si algún día hay que depurar (procedimiento manual, una persona, con registro)

1. Decidir y **dejar por escrito** qué se depura, con qué criterio y quién lo autorizó.
2. Hacer una copia de la base antes (`docs/copias-de-seguridad.md`).
3. Con el rol dueño (`sede`) en una transacción, desactivar el trigger, depurar y reactivarlo:

```sql
BEGIN;
ALTER TABLE audit_log DISABLE TRIGGER USER;
DELETE FROM audit_log WHERE created_at < '<fecha de corte>';
ALTER TABLE audit_log ENABLE TRIGGER USER;
COMMIT;
```

`sede_app` no puede hacerlo aunque se lo propusiera: no tiene el permiso y no es el dueño de la tabla.

## Pruebas

`tests/Feature/Esquema/BitacoraDeSoloInsercionTest.php`: el trigger rechaza `UPDATE`, `DELETE` y `TRUNCATE` incluso al dueño; `sede_app` no tiene esos permisos y `aplicar()` ejecutado dos veces no se los devuelve. Las pruebas que necesitan una bitácora vacía usan el mismo procedimiento (dentro de su transacción, que se deshace).
