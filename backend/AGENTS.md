# Guía para agentes — `backend/`

Este archivo describe las reglas de **este** proyecto. No es el marcador genérico que
genera el instalador de Laravel: se sustituyó a propósito, porque el proyecto tiene sus
propias convenciones y su propio plan, y un agente que trabaje aquí debe conocerlos antes
de escribir la primera línea.

## Antes de tocar nada

1. Leer `../plan.md`. Es el plan de construcción y la fuente de las decisiones.
2. Leer `../contract/openapi.yaml`. Es la fuente de verdad del intercambio HTTP.
3. Leer `../GUIA-MAESTRA-COMPLETA.md` si la tarea toca convenciones de código.

## Reglas que no se negocian

| Regla | Por qué |
|---|---|
| **Contrato primero.** Ninguna ruta existe sin su operación declarada en el contrato | La prueba de deriva comprueba que no sobre ni falte ninguna |
| **`declare(strict_types=1)` en todo archivo PHP** | Lo verifica Pint y falla la puerta |
| **Las capas se respetan**: Controller → FormRequest → Service → Repository → Model | Un controlador con lógica de negocio rompe la arquitectura y el análisis |
| **El sobre es plano** `{success, message, data, errors}` con `application/json` | `application/vnd.api+json` está descartado |
| **Actualizaciones con `PATCH`**, nunca `PUT` | Convención del contrato |
| **Los locales de PHP están en `$fillable`**; jamás `$guarded = []` | Asignación masiva |
| **Autorización por Policy**, con `$user->can(...)` | `hasRole()` dentro de una Policy no se usa |
| **Dinero en `decimal`**, nunca en `float` | Un céntimo perdido en una tasa es un defecto legal |
| **Nada de secretos en el repositorio** | Es público |

## Comandos

```bash
php artisan test              # pruebas
./vendor/bin/pint             # formato (con --test para verificar)
./vendor/bin/phpstan analyse --memory-limit=1G   # análisis en nivel 8
php artisan route:list --except-vendor           # rutas registradas
```

Las tres primeras son puertas: ninguna se salta.

## Estructura

```
app/
├── Console/Commands/      Comandos programados
├── Contracts/             Interfaces: Repositories, Services, Integraciones
├── Enums/                 Estados, tipos, modalidades
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Middleware/        Cabeceras, métodos permitidos, correlación
│   ├── Requests/          Un FormRequest por operación de escritura
│   └── Resources/         Un Resource por recurso del contrato
├── Models/
├── Policies/
├── Repositories/Eloquent/
├── Services/
└── Support/               Utilidades transversales
```

Las carpetas vacías llevan `.gitkeep`: existen porque la arquitectura las define, no porque
haya que llenarlas.

## Las sondas de salud

`/health` y `/ready` viven en `routes/salud.php`, **fuera de la API versionada** y sin el
grupo de middleware de la API. La diferencia entre ambas importa y está explicada en el
controlador: confundirlas produce reinicios en bucle cuando lo que falla es una dependencia
y no el proceso.

## Lo que este proyecto **no** usa

- **Inertia y Blade** están prohibidos: el backend es una API y los clientes son
  independientes. Hay dos, y ninguno renderiza en el servidor de Laravel.
- **`PUT`**: las actualizaciones son `PATCH`.
- **`application/vnd.api+json`**: el sobre es plano.

## Sobre Laravel Boost

El instalador de Laravel deja un marcador que pide instalar `laravel/boost` y ejecutar
`boost:install`. **No se ha instalado**: es una dependencia de desarrollo que no está en el
plan aprobado, y el titular decide si entra. Si se incorpora, este archivo se regenera con
las guías que Boost produce para la aplicación.
