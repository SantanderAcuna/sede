# Copias de seguridad de la base de datos

Estado: implementadas en la rama de B1 (Módulo 01); **no hay copia hecha ni restaurada en staging hasta que se ejecute la verificación de §7**. Este documento dice qué existe, qué NO cubre y cómo se comprueba.

Dónde corre cada comando: **[SERVIDOR]** dentro de `ssh -t` al servidor de staging, como `ops`; **[PC]** tu terminal; **[CI]** GitHub Actions.

## 1. Qué hace

| Pieza | Dónde | Qué hace |
|---|---|---|
| Agenda | `backend/routes/console.php` | 01:00 `backup:clean`, 01:30 `backup:run --only-db`, 06:00 `backup:monitor`, **hora de Bogotá fijada en cada tarea** (la zona de la aplicación es UTC) |
| Configuración | `backend/config/backup.php` | Destino, cifrado, retención, vigilancia y avisos |
| Disco `copias` | `backend/config/filesystems.php` | Carpeta privada, sin URL, que lanza excepción si no puede escribir |
| Volumen `/copias` | `compose.yaml`, `docker/app/Dockerfile` | Volumen propio, separado del almacenamiento de la aplicación; sólo lo montan `scheduler` y `copia` |
| `pg_dump` 18 | `docker/app/Dockerfile` | Cliente de PostgreSQL de la misma serie que el servidor; la construcción falla si no es la 18 |
| Quién ejecuta | servicio `scheduler` | Es el único proceso con la agenda (`schedule:work`) |
| Vía manual | servicio `copia` (perfil `copias`) | `docker compose --profile copias run --rm copia` |

El orden de la agenda es deliberado: se limpia **antes** de copiar para que la limpieza no borre la copia recién hecha por un límite de espacio, y se vigila **después**, cuando la de la noche ya debió terminar.

Nota de zona horaria: `config/app.php` fija `'timezone' => 'UTC'` y no lee `APP_TIMEZONE`, aunque la plantilla de entorno la declare (`APP_TIMEZONE=America/Bogota` no tiene efecto). Por eso cada tarea de copia lleva `->timezone('America/Bogota')`. Se descubrió en la verificación de staging: `schedule:list` anunciaba la copia de las 01:30 «en 8 horas» a las 12:12 de Bogotá, es decir, a las 01:30 UTC.

## 2. Qué NO cubre (leer antes de confiar en esto)

1. **Está en el mismo servidor.** El volumen `copias` protege contra errores lógicos, migraciones fallidas y borrados accidentales. **No** protege contra la pérdida del servidor. Una copia fuera del servidor (depósito de objetos u otro destino) sigue pendiente y debe existir **antes** de producción real. Cambiar el destino es una línea: `BACKUP_DISK=s3` en `deploy/valores-<entorno>.env` más las llaves del depósito (T-12).
2. **Sólo la base.** Los archivos subidos (`FILESYSTEM_DISK=local`) no entran: la agenda usa `--only-db`. Quitar esa bandera los incluiría (`config/backup.php` ya declara las carpetas), pero la decisión es de T-12.
3. **Sin aviso por correo hasta que haya destinatario.** `BACKUP_NOTIFICACION_CORREO` está vacío y `MAIL_MAILER=log`. Un fallo de copia queda sólo en los registros del servicio `scheduler`. Mientras tanto, alguien debe mirar `backup:list` (§6).
4. **Retención de partida, no definitiva.** Son los valores por defecto del paquete (7 días completas, 16 diarias, 8 semanales, 4 mensuales, 2 anuales). El plazo que corresponde a la Entidad (tabla de retención documental) está pendiente.
5. **El techo de 5000 MB es el valor por defecto del paquete, no un dato medido.** Hay que ajustarlo al tamaño real de la base. La limpieza borra las copias más antiguas al superarlo.

## 3. La frase de cifrado

Cada copia es un zip cifrado con AES-256 usando `BACKUP_PASSPHRASE`, el mismo secreto que ya exigen el despliegue y `secretos-requeridos.txt` (vive sólo en `/etc/sede/<entorno>.secretos.env`).

- **Perderla hace inservibles todas las copias.** No hay forma de recuperarlas sin ella.
- **Rotarla invalida las copias anteriores**: las ya hechas se abren sólo con la frase vieja. Por eso `deploy/rotar-secretos.py --aplicacion` **ya no la rota por defecto**; hace falta `--frase-copias`, y entonces conserva la anterior en `.accesos.local.env` como `BACKUP_PASSPHRASE_<ENTORNO>_ANTERIOR_<fecha>`. Consérvala mientras existan copias hechas con ella.
- **Si la frase está vacía el paquete no cifra y no avisa.** Por eso el despliegue la exige no vacía; `config/backup.php` la lee de `BACKUP_PASSPHRASE` (no de `BACKUP_ARCHIVE_PASSWORD`, que es la que el paquete busca por defecto y el proyecto no define).

## 4. Cómo se comprueba que sigue encajando

- **[CI] Backend**: `tests/Feature/Operacion/CopiasDeSeguridadTest.php` (destino y vigilancia en el mismo disco, cifrado desde `BACKUP_PASSPHRASE`, avisos sólo con destinatario, agenda en su orden).
- **[CI] Pila**: `deploy/pruebas/guarda-copias.sh` (serie de `pg_dump` = serie del servidor, quién monta `/copias`, rotación de la frase) y `deploy/pruebas/prueba-rotar-secretos.py`.
- **Construcción de la imagen**: `RUN pg_dump --version` falla si no es la serie 18.

Lo que **ninguna** de esas pruebas demuestra, y se verifica en el servidor (§7): que `pg_dump` exista en la imagen desplegada, que `/copias` sea escribible por el usuario sin privilegios y que una copia **se pueda restaurar**.

## 5. Cambiar el destino a un depósito de objetos

1. Crear el depósito privado y las llaves (T-12), y ponerlas en `/etc/sede/<entorno>.secretos.env` (`S3_ACCESS_KEY`, `S3_SECRET_KEY`).
2. En `deploy/valores-<entorno>.env`: `BACKUP_DISK=s3` y los `S3_*` del depósito.
3. Redesplegar. `backup:monitor` y `backup:run` miran el mismo disco (lo comprueba la prueba de CI).
4. Repetir la verificación de §7 contra el nuevo destino.

## 6. Operación diaria

**[SERVIDOR]** (compose: `docker compose -f /opt/sede/<entorno>/compose.yaml --env-file /opt/sede/<entorno>/.env`, con `sudo`):

```bash
# Qué copias hay y qué antigüedad tienen
sudo docker compose -f /opt/sede/staging/compose.yaml --env-file /opt/sede/staging/.env exec scheduler php artisan backup:list

# Hacer una copia ahora (por ejemplo antes de una migración delicada)
sudo docker compose -f /opt/sede/staging/compose.yaml --env-file /opt/sede/staging/.env exec scheduler php artisan backup:run --only-db

# Comprobar la salud (antigüedad máxima 1 día)
sudo docker compose -f /opt/sede/staging/compose.yaml --env-file /opt/sede/staging/.env exec scheduler php artisan backup:monitor
```

## 7. Prueba de restauración (nunca contra la base viva)

Una copia que no se ha restaurado no existe. Esta prueba restaura en una base **de prueba** (`sede_restauracion`) y la elimina. No toca la base `sede`.

Notas que importan:
- **`unzip` no abre estas copias**: el zip es AES-256 y `unzip` responde «need PK compat. v5.1». Se extrae con PHP (`ZipArchive`), que ya está en la imagen. Con la frase equivocada `extractTo` devuelve `false`.
- Se restaura el SQL con `psql` (incluido en `postgresql18-client`).

**[SERVIDOR]**, paso 1: elegir la copia más reciente y extraerla a una carpeta temporal del volumen de almacenamiento (el resto del sistema de archivos del contenedor es de sólo lectura):

```bash
C="sudo docker compose -f /opt/sede/staging/compose.yaml --env-file /opt/sede/staging/.env exec -T scheduler"
$C sh -c 'rm -rf /app/storage/app/restauracion && mkdir -p /app/storage/app/restauracion && ls -1t /copias/*/*.zip | head -1'
$C php -r '
$ult = glob("/copias/*/*.zip"); rsort($ult); $zip = new ZipArchive();
if ($zip->open($ult[0]) !== true) { fwrite(STDERR, "No se pudo abrir\n"); exit(1); }
$zip->setPassword((string) getenv("BACKUP_PASSPHRASE"));
if (! $zip->extractTo("/app/storage/app/restauracion")) { fwrite(STDERR, "No se pudo extraer: frase equivocada o archivo dañado\n"); exit(1); }
for ($i = 0; $i < $zip->numFiles; $i++) { echo $zip->getNameIndex($i), "\n"; }'
```

Paso 2: restaurar en una base de prueba, contar tablas y eliminarla:

```bash
$C sh -c '
export PGPASSWORD="$DB_PASSWORD"
psql -h "$DB_HOST" -U "$DB_USERNAME" -d postgres -qc "DROP DATABASE IF EXISTS sede_restauracion" -c "CREATE DATABASE sede_restauracion"
f=$(ls /app/storage/app/restauracion/db-dumps/*.sql | head -1)
psql -h "$DB_HOST" -U "$DB_USERNAME" -d sede_restauracion -q -v ON_ERROR_STOP=1 -f "$f"
for b in sede_restauracion "$DB_DATABASE"; do
  printf "%s: " "$b"
  psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$b" -Atc "select count(*) from information_schema.tables where table_schema = '"'"'public'"'"'"
done
psql -h "$DB_HOST" -U "$DB_USERNAME" -d postgres -qc "DROP DATABASE sede_restauracion"
rm -rf /app/storage/app/restauracion'
```

Criterio de éxito: la extracción lista un `.sql`, `psql` termina sin error (`ON_ERROR_STOP`), el conteo de tablas de `sede_restauracion` es mayor que cero y coincide con el de la base viva (el bucle imprime ambos), y la base de prueba queda eliminada.

## 8. Criterios de aceptación de B1

| # | Criterio | Cómo se comprueba | Dónde |
|---|---|---|---|
| B1.1 | `pg_dump` existe en la imagen y es de la serie del servidor | Construcción + `guarda-copias.sh` + `pg_dump --version` desplegado | CI / servidor |
| B1.2 | La agenda hace limpiar → copiar → vigilar | `CopiasDeSeguridadTest` + `schedule:list` | CI / servidor |
| B1.3 | La copia sale cifrada con la frase del despliegue | `CopiasDeSeguridadTest` + intento de abrir con frase equivocada | CI / servidor |
| B1.4 | Copia y vigilancia miran el mismo disco | `CopiasDeSeguridadTest` | CI |
| B1.5 | Sólo `scheduler` y `copia` montan `/copias`, y no hay volúmenes anónimos de copias | `guarda-copias.sh` (CI) + en el servidor, por montajes y no con `ls` (la carpeta existe vacía en la imagen): `sudo docker inspect $(<compose> ps -q app) --format '{{range .Mounts}}{{.Name}} -> {{.Destination}}{{"\n"}}{{end}}'` no debe mostrar `/copias` | CI / servidor |
| B1.6 | Rotar secretos no invalida las copias por descuido | `prueba-rotar-secretos.py` | CI |
| B1.7 | Se hizo una copia real y se restauró en una base de prueba | §7 | servidor |
| B1.8 | Los límites (§2) están escritos y no se presentan como resueltos | este documento | revisión |

## Fuentes

- spatie/laravel-backup, instalación y programación: <https://spatie.be/docs/laravel-backup/v9/installation-and-setup> (la configuración base es la de la versión 10.3.3 instalada).
- Alpine 3.24 ofrece `postgresql18-client` (índice del repositorio `main`); es la base fijada en el Dockerfile.
