# Entorno local en Docker

La misma pila que se despliega, para revisar cambios y correr pruebas **antes** de pasarlos a `develop`.

```bash
make local          # genera .env y certificados de prueba (una vez), levanta, migra con el dueño y siembra
make local-abajo    # detiene la pila, conserva los datos
make local-limpio   # detiene y BORRA los datos locales
```

- Sitio y API: <https://localhost:8443> (certificado autofirmado: el navegador pide aceptarlo).
- Correo de prueba: <http://localhost:8025>.
- `.env` y los certificados se crean con `scripts/local.sh` **sin pisar** los que ya existan; están en
  `.gitignore` y no salen de la máquina. Los secretos son aleatorios y propios de esta máquina.
- La aplicación corre con el rol `sede_app` y la base usa TLS, como en producción. El servicio
  `migrar` es el único que lleva la clave del dueño `sede` (ver ADR-0004).
- Sólo el entorno y los certificados: `bash scripts/local.sh preparar`.

## Si algo falla

```bash
docker compose ps                         # qué servicio no llegó a «healthy»
docker compose logs --tail=80 <servicio>  # postgres | migrar | app | nginx | sitio
```

- Puertos ocupados (PostgreSQL o Redis ya corren en tu máquina):
  `POSTGRES_PUERTO_LOCAL=5433 REDIS_PUERTO_LOCAL=6380 make local`.
- Empezar de cero (borra los datos locales y los secretos locales): `make local-limpio && rm .env`.
- `make local` construye primero la imagen del panel (`sede-panel:dev`) y la pasa a la de nginx
  como contexto con nombre; la primera vez tarda varios minutos.

## Pruebas de roles con autenticación real

La imagen oficial de PostgreSQL **confía** en `127.0.0.1`: contra ese origen cualquier clave entra, y
`test_la_clave_se_comprueba_de_verdad` se omite (y lo dice). Para que autentique, apunta la prueba a
una dirección que no sea el bucle local:

```bash
cd backend
DB_HOST=$(hostname -I | awk '{print $1}') php vendor/bin/phpunit tests/Feature/Operacion/RolesBaseDeDatosTest.php
```

## Registro de secretos (claves de los roles)

```bash
python3 deploy/agregar-secretos-roles.py   # añade lo que falte a .accesos.local.env y pass.md; no pisa nada ni imprime valores
```

## Verificar el esquema `public` de staging (antes y después de desplegar)

```bash
ssh ops@<IP-de-staging>
docker compose -f /opt/sede/staging/compose.yaml --env-file /opt/sede/staging/.env \
  exec -T postgres psql -U sede -d sede -c '\dn+' -c '\du+' -c '\l+ sede'
```

Esperado **después** del despliegue de la entrega 0A: en `public`, ningún privilegio `C` (CREATE) para
`=` (PUBLIC); `sede_app` y `sede_lectura` con `U` (USAGE); roles sin `Superuser`/`Create role`/`Create DB`.
