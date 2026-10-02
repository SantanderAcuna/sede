# Instrucciones para agentes

Las reglas de este proyecto viven en [`AGENTS.md`](AGENTS.md), en un solo lugar.

Este archivo se mantiene como enlace porque algunas herramientas leen `CLAUDE.md`
y otras `AGENTS.md`, y dos copias de las mismas reglas terminan divergiendo. La
fuente es `AGENTS.md`.

## Lo mínimo, si vas con prisa

1. **`public/govco/**` no se toca.** Es el Kit y Bootstrap vendorizados byte a byte
   contra su CDN, y el Kit **depende** de Bootstrap sin distribuirlo.
2. **Un color no se escribe en crudo fuera de `tokens.css`.**
3. **Antes de dar algo por hecho**: `typecheck`, `build`, `stylelint` y
   **`scripts/captura-visual.mjs`**. La captura es la única prueba que detecta que
   el diseño cambió — `axe` no lo ve.
4. **Nada de contenido institucional inventado.** Lo aprueba la Entidad.

El resto, en [`AGENTS.md`](AGENTS.md).
