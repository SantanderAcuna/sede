# Secretos del despliegue

Este documento describe qué credenciales existen, **dónde viven** y quién las
custodia. No contiene valores: los secretos se leen del gestor de secretos de la
entidad o del almacén de GitHub, nunca de aquí.

## Por qué no todo está en GitHub

`docker/README.md` ya lo dice para el `.env` de la entidad: la sede toma su
configuración de un **gestor de secretos**, y `APP_KEY` vive allí porque su
pérdida vuelve irrecuperables los datos cifrados de la base. GitHub Actions es
un almacén de secretos para la **canalización**, no el custodio del expediente.

De ahí la regla que gobierna lo que sigue:

> Un secreto se crea en GitHub solo si lo **consume una canalización**. Si su
> valor lo custodia la entidad o un tercero, se referencia, no se guarda.

## Lo que existe hoy

Ambos entornos —`staging` y `production`— tienen su propio juego de credenciales,
**distinto en cada uno**. Compartir la contraseña de la base entre entornos haría
que un incidente en `staging` alcanzara a producción.

| Secreto | Entorno | Quién lo genera | Por qué GitHub puede generarlo |
|---|---|---|---|
| `APP_KEY` | staging, production | La canalización | Clave de cifrado de Laravel; ambos extremos son de la sede |
| `DB_PASSWORD` | staging, production | La canalización | La base es de la sede |
| `REDIS_PASSWORD` | staging, production | La canalización | Redis es de la sede |
| `S3_ACCESS_KEY` | staging, production | La canalización | El almacenamiento es SeaweedFS self-hosted (`docker/s3/Dockerfile`) |
| `S3_SECRET_KEY` | staging, production | La canalización | Igual que el anterior |

Estos cinco **no son intercambiables ni decorativos**: `compose.yaml` los declara
obligatorios con `:?`, así que sin ellos la pila ni siquiera se resuelve. Las
credenciales de `S3_*` llegaron a estar horneadas en la imagen con los valores de
ejemplo (`sede_local`), de modo que la aplicación se autenticaba con una cosa y
SeaweedFS esperaba otra: la sede arrancaba, respondía y fallaba al guardar
cualquier documento con un 403 que no señalaba a su causa. Ahora se renderizan al
arrancar desde el entorno, en `docker/s3/entrypoint.sh`, y el servicio las recibe
solo a él —no por `env_file`—, porque no necesita ningún otro secreto de la sede.

Los valores generados quedaron guardados **fuera del control de versiones**, en
`.scratch/secretos-generados.env` (permisos `600`, ruta ignorada por
`.gitignore`). Ese archivo es un puente, no un destino: hay que trasladar
`APP_KEY` al gestor de secretos de la entidad y borrarlo. **Si se pierde
`APP_KEY`, los datos cifrados de la base —los secretos de doble factor, entre
otros— quedan irrecuperables aunque la copia de seguridad esté intacta.**

## Lo que deliberadamente NO se creó

Estos secretos no se inventan porque su valor es un **acuerdo con un tercero**, y
un valor inventado rompería la integración sin que ninguna puerta lo detectara:

| Secreto | De dónde sale |
|---|---|
| `SEDE_PAGOS_SECRETO` | Lo entrega la pasarela de pagos. Autentica sus notificaciones (FUN-039). |
| `SEDE_CORREO_CERTIFICADO_SECRETO` | Lo entrega el operador de correo certificado (FUN-042). |

Tampoco se creó ningún secreto con valor de relleno: un `cambie_este_secreto` en
producción es peor que la ausencia de la variable, porque pasa las validaciones
de presencia y falla solo en la verificación de firma, ya en caliente.

## Lo que falta para que exista un despliegue

No hay canalización de despliegue todavía —`entrega.yml` construye, escanea y
firma, pero no publica en servidor alguno—, y su diseño es decisión aparte.
Cuando se escriba, necesitará estos secretos, que solo la entidad puede aportar:

| Secreto | Contenido |
|---|---|
| `DEPLOY_HOST` | Nombre o IP del servidor de destino |
| `DEPLOY_USER` | Usuario de despliegue |
| `DEPLOY_PORT` | Puerto SSH (22 si no se dice otra cosa) |
| `DEPLOY_SSH_KEY` | Clave privada de despliegue, sin frase de paso |
| `DEPLOY_KNOWN_HOSTS` | Huella del servidor, para no aceptar cualquier anfitrión |

`DEPLOY_KNOWN_HOSTS` no es ceremonia: sin él, `ssh` acepta la identidad que le
presenten y el despliegue se vuelve vulnerable a interposición.

## Reglas de protección: bloqueadas por plan

Los entornos `staging` y `production` existen, pero **sin reglas de protección**.
GitHub rechaza con `422` revisores requeridos, temporizador de espera y política
de ramas cuando el repositorio es privado y el plan no es Pro o Team:

```
Failed to create the environment protection rule.
Please ensure the billing plan supports the required reviewers protection rule.
```

Esto importa porque `entrega.yml` declara que las **dos aprobaciones previas al
despliegue en producción** son un acto de la entidad. Con el plan actual esa
exigencia no puede automatizarse: o se sube de plan, o el repositorio pasa a
público, o la aprobación se ejerce fuera de GitHub y queda registrada en otro
sitio. Mientras tanto, los entornos sí cumplen una función real —acotan qué
trabajo ve cada juego de credenciales—, porque un trabajo debe declarar
`environment: production` para acceder a sus secretos.

## Operación

```bash
# Listar lo que hay (nunca muestra valores)
gh secret list --env production --repo SantanderAcuna/sede

# Rotar un secreto sin dejarlo en el historial del shell
printf '%s' "$NUEVO_VALOR" | gh secret set DB_PASSWORD --env production

# Regenerar el juego completo de un entorno no productivo
openssl rand -base64 32   # APP_KEY, con el prefijo base64:
openssl rand -hex 24      # contraseñas
```

La rotación de los secretos compartidos con la pasarela y el operador postal
exige coordinación con ellos; está descrita en `docker/README.md`, sección
«Rotación de secretos».
