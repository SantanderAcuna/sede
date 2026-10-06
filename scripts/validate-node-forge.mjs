#!/usr/bin/env node
/**
 * validate-node-forge.mjs
 *
 * Verifica que node-forge NO se incluya en el build de producción.
 *
 * node-forge (CVE-2026-85393) solo se usa en dev vía:
 *   nuxt -> @nuxt/cli -> listhen -> node-forge
 *
 * En producción, Nuxt usa el servidor Node.js nativo, NO listhen.
 * Por lo tanto, node-forge no debe aparecer en .output/server/.
 *
 * Uso: node scripts/validate-node-forge.mjs
 *      (ejecutar después de npm run build)
 */

import { existsSync, readdirSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(__dirname, '..');
const sitioDir = resolve(projectRoot, 'sitio');
const outputDir = resolve(sitioDir, '.output');

console.log('=== Validación: node-forge NO debe estar en producción ===\n');

if (!existsSync(outputDir)) {
  console.log(`❌ No existe ${outputDir}`);
  console.log('   Ejecuta primero: cd sitio && npm run build');
  process.exit(1);
}

// Buscar node-forge en .output/
const found = [];
function searchDir(dir) {
  for (const entry of readdirSync(dir)) {
    const full = join(dir, entry);
    const st = statSync(full);
    if (st.isDirectory()) {
      if (entry === 'node_modules') {
        // Verificar si hay node-forge dentro
        checkNodeForge(full);
      } else {
        searchDir(full);
      }
    }
  }
}

function checkNodeForge(nmDir) {
  for (const pkg of readdirSync(nmDir)) {
    if (pkg === 'node-forge' || pkg.startsWith('node-forge-')) {
      found.push(join(nmDir, pkg));
    }
  }
}

searchDir(outputDir);

if (found.length === 0) {
  console.log('✅ node-forge NO está en el build de producción');
  console.log('   El workaround de aislamiento es efectivo');
  console.log('   Vulnerabilidad CVE-2026-85393 no afecta producción');
  process.exit(0);
} else {
  console.log(`❌ node-forge ENCONTRADO en ${found.length} ubicaciones:`);
  for (const f of found) {
    console.log(`   - ${relative(projectRoot, f)}`);
  }
  console.log('\nAcción requerida:');
  console.log('1. Verificar que nuxt NO bundlee devDependencies');
  console.log('2. Configurar externals en nuxt.config.ts');
  console.log('3. Considerar eliminar @nuxt/cli de devDependencies si es posible');
  process.exit(1);
}
