# Tipografía autoalojada

Estas fuentes se sirven desde el propio origen. Cargarlas desde Google Fonts
entregaría la IP de cada funcionario a un servicio de terceros, que en una
entidad pública es una cesión de datos que no corresponde hacer sin base legal.

## Archivos

| Archivo | Familia | Ejes | Subset | sha256 |
|---|---|---|---|---|
| `inter-normal.woff2` | Inter | `wght 100–900` | `latin` | `3100e775e8616cd2611beecfa23a4263d7037586789b43f035236a2e6fbd4c62` |
| `montserrat-normal.woff2` | Montserrat | `wght 100–900` | `latin` | `06b16db7a969135d48d38c49183be7fb88d4452e2a3011957c7851941f4e4879` |
| `jetbrains-mono-normal.woff2` | JetBrains Mono | `wght 100–800` | `latin` | `18be452724bfdc236c074ca94a249a7f41a86752c7d04ab258ce9ed5651f6a7e` |

Son **fuentes variables**: un solo archivo cubre todos los pesos, así que
`font-medium` y `font-bold` no piden descargas distintas.

El subset `latin` cubre lo que usa el español —`á é í ó ú ñ ü ¿ ¡`— y mantiene
los tres archivos en 124 KB. Si algún día hace falta `latin-ext` (rumano,
turco, polaco), se añade la regla correspondiente sin tocar nada más.

## Licencia

Las tres familias se publican bajo **SIL Open Font License 1.1**, que permite
usarlas, modificarlas y redistribuirlas, incluso comercialmente, siempre que no
se vendan por sí solas ni se use el nombre reservado. Conservar este archivo
junto a los binarios es lo que deja la procedencia trazable.

## Cómo se regeneran

Se obtuvieron de la API CSS de Google Fonts, pidiendo el eje completo y con un
agente de navegador para recibir `woff2` en lugar de `ttf`:

```
https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Montserrat:wght@100..900&family=JetBrains+Mono:wght@100..800&display=swap
```

De la respuesta se conservan **sólo los bloques `/* latin */`** y se reescribe
cada `url(...)` remoto por la ruta local. `assets/styles/fonts.css` es el
resultado de esa transformación.

**Fecha de obtención:** 2026-09-30. Las huellas de arriba permiten comprobar que
los binarios no han cambiado desde entonces.
