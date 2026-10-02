# Despliegue

Cómo se publica la Sede en el servidor y qué hay que hacer a mano.

## Las piezas

| Archivo | Dónde termina | Para qué |
|---|---|---|
| `droplet/preparar.sh` | Se ejecuta **en** el servidor, una vez | Prepara la máquina: docker, cortafuegos, fail2ban, la cuenta de despliegue y su llave restringida |
| `droplet/certificado.sh` | En el servidor | Emite y renueva el certificado del origen |
| `droplet/despliegue-remoto.sh` | **`/usr/local/bin/desplegar.sh`** | Despliega una versión y vuelve atrás si algo falla |
| `publicar-entorno.sh` | Se ejecuta desde tu máquina | Escribe `/opt/sede/<entorno>/` con `.env`, `compose.yaml` y `certs/` |
| `plantilla.env` | — | La plantilla con la que se renderiza cada `.env` |

La canalización (`.github/workflows/despliegue.yml`) sólo puede ejecutar **un**
comando en el servidor: `desplegar.sh <entorno> <versión>`. La llave de
despliegue está restringida a eso, así que no puede copiar archivos ni ejecutar
nada más.

## El orden, la primera vez

```bash
# 1. Preparar el servidor (una vez).
scp deploy/droplet/preparar.sh ops@<servidor>:/tmp/ && ssh ops@<servidor> 'sudo bash /tmp/preparar.sh'

# 2. Instalar el guion de despliegue (ver la trampa, más abajo).
scp deploy/droplet/despliegue-remoto.sh ops@<servidor>:/tmp/
ssh ops@<servidor> 'sudo install -m 755 -o root -g root /tmp/despliegue-remoto.sh /usr/local/bin/desplegar.sh'

# 3. Publicar el entorno. Exige los valores en `.accesos.local.env`, que no se versiona.
./deploy/publicar-entorno.sh staging

# 4. Certificado.
scp deploy/droplet/certificado.sh ops@<servidor>:/tmp/ && ssh ops@<servidor> 'sudo bash /tmp/certificado.sh'

# 5. Sembrar. Lo hace el despliegue solo desde que se añadió el paso, pero en un
#    entorno que ya existía hay que hacerlo una vez a mano (ver más abajo).
```

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

## Lo que está pendiente

- **El dominio de producción.** `publicar-entorno.sh produccion` no publica sin
  `SEDE_DOMINIO_PRODUCTION`, y está sin decidir (D-20 · §21.A de `plan.md`). El
  guion aborta a propósito en vez de inventarse el dominio.
- **La política de ramas del entorno `production`** permite `master`, que no
  existe en este repositorio (la rama por defecto es `main`). Mientras siga así,
  el despliegue a producción sólo puede lanzarse a mano desde `staging`.
- **`SEDE_DOMINIO`** no está definida, así que el enlace del entorno en GitHub
  sale como `https://`.
