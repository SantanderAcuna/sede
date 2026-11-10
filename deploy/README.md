# Despliegue

Cómo se publica la Sede en el servidor y qué hay que hacer a mano.

## Las piezas

| Archivo | Dónde termina | Para qué |
|---|---|---|
| `droplet/preparar.sh` | Se ejecuta **en** el servidor, una vez | Prepara la máquina: docker, cortafuegos, fail2ban, la cuenta de despliegue y su llave restringida |
| `droplet/certificado.sh` | En el servidor | Emite y renueva el certificado del origen |
| `droplet/despliegue-remoto.sh` | **`/usr/local/bin/desplegar.sh`** | Despliega una versión, **renderiza su entorno** y vuelve atrás si algo falla |
| `publicar-entorno.sh` | Se ejecuta desde tu máquina | Instala los **secretos** en `/etc/sede/<entorno>.secretos.env` y publica el arranque de `/opt/sede/<entorno>/` |
| `plantilla.env` | Dentro de la imagen de la app | La plantilla con la que se renderiza cada `.env`, en el servidor |
| `valores-<entorno>.env` | Dentro de la imagen de la app | Los valores **no secretos** de cada entorno (dominio, base, integraciones) |
| `secretos-requeridos.txt` | — | Los **nombres** de los secretos (ni un valor): la canalización comprueba con ella que nada falte y que ningún secreto viaje en la imagen |
| `pruebas/guarda-entorno.sh` | Canalización | Que las cuatro piezas del entorno encajen |
| `pruebas/prueba-render-entorno.sh` | Canalización | El renderizador real del servidor, con sus caminos de fallo |

La canalización (`.github/workflows/despliegue.yml`) sólo puede ejecutar **un**
comando en el servidor: `desplegar.sh <entorno> <versión>`. La llave de
despliegue está restringida a eso (`command="sudo -n /usr/local/bin/desplegar.sh"`
en `authorized_keys`), así que no puede copiar archivos ni ejecutar nada más. La
cuenta de despliegue **no** pertenece al grupo `docker`: el guion corre con `sudo`
(regla en `/etc/sudoers.d/20-deploy`, que conserva `SSH_ORIGINAL_COMMAND`).

## El orden, la primera vez

```bash
# 1. Preparar el servidor (una vez). `preparar.sh` instala también el guion de
#    despliegue desde el repositorio, así que se copian los dos juntos. Para
#    instalar la llave de la canalización, pasa LLAVE_DESPLIEGUE=<ruta pública>.
scp deploy/droplet/preparar.sh deploy/droplet/despliegue-remoto.sh ops@<servidor>:/tmp/
ssh -t ops@<servidor> 'sudo bash /tmp/preparar.sh'

# 2. Actualizar SOLO el guion de despliegue más adelante (ver la trampa, más abajo).
scp deploy/droplet/despliegue-remoto.sh ops@<servidor>:/tmp/
ssh ops@<servidor> 'sudo install -m 755 -o root -g root /tmp/despliegue-remoto.sh /usr/local/bin/desplegar.sh'

# 3. Publicar el entorno: instala los SECRETOS en el servidor y deja el arranque de
#    /opt/sede/<entorno>. Exige los valores en `.accesos.local.env`, que no se versiona.
#    Desde el ADR-0019 esto NO hay que repetirlo al cambiar una variable: sólo al
#    rotar un secreto.
./deploy/publicar-entorno.sh staging

# 4. Certificado.
scp deploy/droplet/certificado.sh ops@<servidor>:/tmp/ && ssh ops@<servidor> 'sudo bash /tmp/certificado.sh'

# 5. Sembrar. Lo hace el despliegue solo desde que se añadió el paso, pero en un
#    entorno que ya existía hay que hacerlo una vez a mano (ver más abajo).
```

## El entorno: qué viaja solo y qué hay que instalar a mano

Desde el ADR-0019 el entorno se parte en tres, según quién deba poder cambiarlo:

| Pieza | Dónde vive | Cómo llega |
|---|---|---|
| **Secretos** | `/etc/sede/<entorno>.secretos.env` (root, 600) | `./deploy/publicar-entorno.sh <entorno>`, una vez y al rotar un secreto |
| **Plantilla y valores** | `deploy/plantilla.env`, `deploy/valores-<entorno>.env` | **Solos**, dentro de la imagen de cada versión |
| **El guion que los junta** | `/usr/local/bin/desplegar.sh` | A mano, sólo al cambiar `CONTRATO_ENTORNO` |

Lo que eso significa en el día a día:

- **Cambiar una variable del entorno ya no exige tocar el servidor.** Se edita
  `deploy/plantilla.env` o `deploy/valores-<entorno>.env`, se incorpora y se despliega. El guion del
  servidor renderiza el `.env` de esa versión con los secretos que viven allí.
- **Volver atrás devuelve también la configuración** de la versión anterior.
- **Un secreto nuevo** se añade a `deploy/secretos-requeridos.txt` (sólo el NOMBRE), al guion del
  servidor y a `.accesos.local.env`; después se publica el entorno y se instala el guion. La puerta
  `deploy/pruebas/guarda-entorno.sh` falla si las listas no coinciden.
- **El dominio de producción**, cuando se decida, son dos líneas en
  `deploy/valores-produccion.env`. Mientras no estén, un despliegue a producción se detiene antes de
  tocar un contenedor y dice qué falta.
- **Por qué el guion no se autoactualiza:** si se extrajera de la imagen como el resto, quien pudiera
  empujar al registro ejecutaría código como `root` en el servidor. Es el ancla de confianza y se
  instala deliberadamente:

  ```bash
  scp deploy/droplet/despliegue-remoto.sh ops@<servidor>:/tmp/
  ssh ops@<servidor> 'sudo install -m 755 -o root -g root /tmp/despliegue-remoto.sh /usr/local/bin/desplegar.sh'
  ```

- **Cuidado al cambiar de rama antes de publicar.** `publicar-entorno.sh` renderiza TU copia local.
  Y si `git checkout` va a una rama que todavía versiona un archivo que en la rama actual está
  ignorado, git lo sobrescribe sin avisar.

## La trampa: el guion vive en dos sitios

`despliegue-remoto.sh` está en el repositorio **y** instalado como
`/usr/local/bin/desplegar.sh` en el servidor. La canalización ejecuta el del
servidor.

> **Cambiar el del repositorio no cambia el del servidor.** Si modificas el
> guion y no lo reinstalas, el despliegue sigue corriendo la versión vieja y
> nada lo dice.

Después de tocar `deploy/droplet/despliegue-remoto.sh`:

```bash
scp deploy/droplet/despliegue-remoto.sh ops@<servidor>:/tmp/
ssh ops@<servidor> 'sudo bash -n /tmp/despliegue-remoto.sh &&
                    sudo install -m 755 -o root -g root /tmp/despliegue-remoto.sh /usr/local/bin/desplegar.sh'
```

El `bash -n` valida la sintaxis **antes** de sustituir el guion que despliega: un
guion roto en `/usr/local/bin` deja el servidor sin poder desplegar.

## Qué hace un despliegue

1. Trae las imágenes de la versión.
2. `php artisan migrate --force`.
3. **`php artisan db:seed --class=SedeSeeder --force`**.
4. Levanta los contenedores y espera a que `/ready` responda.
5. Si algo falla, vuelve solo a la versión anterior.

### Por qué siembra, y por qué así

El despliegue migraba y no sembraba. El resultado medido en `staging`: la API
devolvía `total: 0`, la portada decía «el catálogo no está disponible» y **cada
ficha respondía 404**. El código viajaba y los datos no, y el despliegue
terminaba en verde.

Se nombra `SedeSeeder` por su clase, y no se ejecuta `db:seed` a secas, porque
el sembrador por defecto (`DatabaseSeeder`) es el punto de entrada de Laravel y
el que usan las pruebas y `make preparar`: cualquier dato que mañana se añada
ahí para desarrollar viajaría a producción sin que nadie lo note.

Los cuatro sembradores que llama `SedeSeeder` son idempotentes, así que correrlo
en cada despliegue no duplica nada ni pisa lo que haya corregido una persona.
`TramiteSeeder` además respeta los trámites editados a mano.

## Sembrar un entorno que ya existe

Si el entorno se desplegó antes de que existiera el paso de siembra, su base
está vacía. Se siembra una vez a mano:

```bash
ssh ops@<servidor>
sudo docker compose -f /opt/sede/staging/compose.yaml --env-file /opt/sede/staging/.env \
  run --rm app php artisan db:seed --class=SedeSeeder --force
```

Sustituye `staging` por `produccion` donde corresponda. Es idempotente: repetirlo
no duplica nada.

## Cuando la siembra no llega sola

`ingesta: tramites:ingerir` **no** es un sembrador y no corre en el despliegue.
Sale a la red pidiendo la ficha oficial de cada trámite a GOV.CO, así que se
ejecuta a propósito y no en cada publicación:

```bash
php artisan tramites:ingerir --detalle
```

El sembrador, en cambio, lee la copia congelada de SUIT y no toca la red: es lo
que permite que el despliegue sea reproducible.

## La verificación semanal de vínculos y el borde (Cloudflare)

`.github/workflows/vinculos.yml` rastrea la sede desde los ejecutores de GitHub. **Medido el
2026-11-10:** Cloudflare responde `403` con `cf-mitigated=challenge` («Just a moment...») a esos
ejecutores, así que el verificador no pasa de la portada y lo dice («No se pudo iniciar la
verificación»), sin culpar a un vínculo. Rayos de ejemplo: `a58886575b4df0b9-DFW`,
`a58884677f0deae8-ORD` (este último, desde otra red, sí pasó). Hasta que el borde lo permita, la
ejecución semanal fallará con ese mensaje.

Lo que hay que hacer en Cloudflare (decisión del titular; no se hace desde el repositorio):

1. **Saber qué regla desafía.** Seguridad → Eventos, buscando el rayo de arriba. Dice si es una
   regla WAF propia, el nivel de seguridad o el *Bot Fight Mode*.
2. **Si es una regla WAF o el nivel de seguridad:** crear una regla personalizada con la acción
   *Omitir* (reglas personalizadas restantes y desafío administrado) cuando
   `http.request.headers["x-verificador-vinculos"][0] eq "<token>"`, y guardar el mismo `<token>`
   en GitHub → Settings → Secrets and variables → Actions → secreto `VERIFICADOR_VINCULOS_TOKEN`.
   El flujo ya envía la cabecera si el secreto existe, y **solo a la sede**, nunca a sitios externos.
3. **Si es el *Bot Fight Mode* (plan gratuito):** una regla personalizada no lo omite. Hay que
   decidir entre desactivarlo, subir de plan o correr el verificador desde el servidor.

Después de cualquiera de las dos, se vuelve a lanzar el flujo a mano (Actions → Verificación de
vínculos → Run workflow) y debe informar `0 vínculos internos rotos` con más de una página.

## Lo que está pendiente

- **El dominio de producción.** `publicar-entorno.sh produccion` no publica sin
  `SEDE_DOMINIO_PRODUCTION`, y está sin decidir (D-20 · §21.A de `docs/archivo/plan.md`). El
  guion aborta a propósito en vez de inventarse el dominio.
- **La política de ramas del entorno `production`** permite `master`, que no
  existe en este repositorio (la rama por defecto es `main`). Mientras siga así,
  el despliegue a producción sólo puede lanzarse a mano desde `staging`.
- **`SEDE_DOMINIO`** no está definida, así que el enlace del entorno en GitHub
  sale como `https://`.
- **La indexación en producción no se puede activar hoy (verificado el 2026-11-10).** Nada en el
  despliegue declara `SEDE_INDEXABLE` (ni `plantilla.env`, ni `valores-*.env`, ni los flujos, ni
  `docker/sitio/Dockerfile`), y por defecto es falsa. La leen dos mecanismos que **no coinciden en
  cuándo**: `robots.txt` la lee **en ejecución** (`NUXT_PUBLIC_INDEXABLE`), pero la cabecera
  `X-Robots-Tag` sale de `routeRules` en `nuxt.config.ts`, que Nitro **fija en la compilación**. La
  imagen se compila sin la variable, así que lleva `X-Robots-Tag: noindex, nofollow` para todos los
  entornos. Comprobado con una compilación por defecto y `NUXT_PUBLIC_INDEXABLE=true` en ejecución:
  `robots.txt` permite el rastreo y declara el mapa, pero la cabecera sigue en `noindex, nofollow`.
  Efecto probable: la sede de producción no se indexaría aunque se activara la variable en el
  servidor. **Antes del primer despliegue a producción:** decidir la cabecera en ejecución (por
  ejemplo, un manejador de Nitro que lea `runtimeConfig.public.indexable`) o compilar una imagen
  distinta, y declarar la variable en `valores-produccion.env`. La prueba que lo detecta ya existe:
  `BASE_URL=https://<dominio> INDEXABLE=1 npm run test:robots` falla si la cabecera sigue en `noindex`.
  (`SEDE_DOMINIO`, en cambio, sí está protegido: `plantilla.env` lo toma de `DOMINIO` y un
  despliegue a producción se detiene sin él.)
