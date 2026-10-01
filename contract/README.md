# Contrato de la API

`openapi.yaml` es la **única fuente de verdad** del intercambio HTTP de la sede. El backend
lo emite y los dos clientes —el sitio público y el panel— lo consumen. Ninguno de los tres
se acopla con los otros dos: los tres se acoplan al contrato.

## La forma de la respuesta

Todas las respuestas usan un **sobre plano** con media type `application/json`.

```json
{ "success": true, "message": null, "data": { }, "errors": null }
```

| Situación | Forma |
|---|---|
| Recurso individual | `data` con el objeto |
| Colección | `data` con el arreglo, más `meta` y `links` |
| Error | `success: false`, `message` legible, `errors` en `null` |
| Error de validación | `errors` con una entrada por campo |

El media type `application/vnd.api+json` **no se usa**. Si aparece, la puerta de contrato
falla.

## Cinco esquemas compartidos, tres por recurso

Los esquemas compartidos son siempre los mismos y no se duplican:

`ApiEnvelope` · `PaginatedEnvelope` · `PageMeta` · `CollectionLinks` · `ApiError`

Por cada recurso se declaran **exactamente tres** esquemas:

| Esquema | Para qué |
|---|---|
| `<Recurso>Item` | El objeto individual que viaja en `data` |
| `<Recurso>Collection` | La colección paginada |
| `<Recurso>Input` | El cuerpo de las peticiones de escritura |

Un recurso declara además los esquemas que su `<Recurso>Item` **compone** —en Trámites,
`TramiteCategoria`, `TramiteRequisito`, `TramiteDocumento`, `TramitePaso` y
`TramiteProcedencia`—. No son esquemas del recurso: son partes de él, y por eso no cuentan
como uno de los tres. Los tres sólo existen cuando el recurso tiene colección y escritura:
`Entidad` declara `EntidadItem` y nada más porque no se lista ni se escribe.

## Reglas de autoría

| Regla | Convención |
|---|---|
| Media type | `application/json` en toda respuesta y toda petición |
| `id` | Entero, nunca cadena |
| `type` | Plural en kebab-case, declarado con `const` |
| `readOnly` | Campos autogenerados, excluidos del cuerpo de la petición |
| Actualización | Verbo `PATCH`, nunca `PUT` |
| Paginación | `page` y `per_page`; `meta` y `links` en el nivel superior |
| `per_page` máximo | 100; por encima la respuesta es `422` |
| Errores | `errors` con clave por campo: `{"campo": ["mensaje"]}` |

## Extensiones propias

Cada operación lleva dos:

- **`x-status`**: `pending` mientras no está implementada, `implemented` cuando lo está.
  Permite apuntar el frontend al simulador sin cambiar nada.
- **`x-criterios`**: los códigos del expediente que la operación acredita —`FUN-021`,
  `SEG-006`—. De aquí se genera la matriz de trazabilidad, de modo que deja de escribirse a
  mano.

## Ciclo de trabajo

```
1. Diseñar la operación aquí
2. Validar       openapi-spec-validator  +  redocly lint
3. Simular       docker run --rm -p 4010:4010 \
                   -v $PWD/contract/openapi.yaml:/tmp/openapi.yaml \
                   stoplight/prism:4 mock -h 0.0.0.0 /tmp/openapi.yaml
4. Tipar         npx openapi-typescript contract/openapi.yaml \
                   -o src/types/api.d.ts --read-write-markers
5. Implementar   backend y frontend en paralelo, contra el mismo contrato
6. Verificar     la deriva entre el contrato y las rutas registradas
```

**No se avanza al paso 5 hasta que el simulador responde.**

## Estado

El contrato está en construcción. Las operaciones marcadas `pending` todavía no existen en
el backend: para esas, el frontend trabaja contra el simulador.
