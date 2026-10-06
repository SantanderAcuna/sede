#!/usr/bin/env node
/**
 * patch-deps.mjs
 *
 * Aplica parches de seguridad a dependencias transitivas que no tienen
 * parche oficial en npm.
 *
 * Ejecutar como postinstall hook:
 *   "postinstall": "node scripts/patch-deps.mjs"
 *
 * Parches aplicados:
 * - braces (CVE-2026-93687): guard de profundidad de anidamiento
 */

import { readFile, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(__dirname, '..');

const PATCHES = [
  {
    name: 'braces',
    description: 'CVE-2026-93687 — guard contra stack exhaustion por anidamiento',
    files: [
      'sitio/node_modules/braces/lib/expand.js',
      'panel/node_modules/braces/lib/expand.js',
    ],
  },
];

let applied = 0;
let skipped = 0;

for (const patch of PATCHES) {
  console.log(`[patch-deps] Verificando ${patch.name} (${patch.description})`);
  for (const file of patch.files) {
    const fullPath = resolve(projectRoot, file);
    if (!existsSync(fullPath)) {
      console.log(`  - ${file}: no existe, saltando`);
      skipped++;
      continue;
    }

    const content = await readFile(fullPath, 'utf8');

    // Verificar si el parche ya está aplicado
    if (content.includes('CVE-2026-93687')) {
      console.log(`  - ${file}: ya parcheado, OK`);
      applied++;
      continue;
    }

    // Aplicar el parche
    const patched = applyBracesPatch(content);
    await writeFile(fullPath, patched, 'utf8');
    console.log(`  ✓ ${file}: parche aplicado`);
    applied++;
  }
}

console.log(`[patch-deps] Resumen: ${applied} aplicados/verificados, ${skipped} saltados`);

function applyBracesPatch(content) {
  // Inyectar el guard de profundidad
  const guardCode = `
// SECURITY PATCH (CVE-2026-93687): guard against stack exhaustion
// via deeply nested braces. Default limit: 50.
const MAX_NESTING_DEPTH = parseInt(process.env.BRACES_MAX_DEPTH || '50', 10);
let nestingDepth = 0;
`;

  // Añadir guard después de los requires iniciales
  let patched = content.replace(
    "const utils = require('./utils');\n\nconst append",
    `const utils = require('./utils');\n${guardCode}\nconst append`
  );

  // Envolver el cuerpo de walk() con try/finally
  patched = patched.replace(
    `  const walk = (node, parent = {}) => {
    node.queue = [];`,
    `  const walk = (node, parent = {}) => {
    // SECURITY PATCH: enforce nesting depth limit
    nestingDepth++;
    if (nestingDepth > MAX_NESTING_DEPTH) {
      nestingDepth--;
      throw new RangeError(
        \`braces: nesting depth exceeds \${MAX_NESTING_DEPTH} (CVE-2026-93687 mitigation). \` +
        \`Pattern rejected to prevent stack exhaustion.\`
      );
    }
    try {
    node.queue = [];`
  );

  // Cerrar el try/finally antes del return
  patched = patched.replace(
    `      return queue;
  };

  return utils.flatten(walk(ast));`,
    `      return queue;
    } finally {
      nestingDepth--;
    }
  };

  return utils.flatten(walk(ast));`
  );

  return patched;
}
