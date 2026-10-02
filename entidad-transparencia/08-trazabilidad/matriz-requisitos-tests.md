# Matriz RF/RNF → Tests (Pest, Playwright, axe-core)

> **Marco:** pirámide de tests — 70% unitarios, 20% integración, 10% E2E.
> **Cobertura objetivo:** ≥80% backend, ≥70% frontend.
> **Trazabilidad:** cada test referencia el requisito que valida.

---

## 1. Resumen de tests por tipo

| Tipo | Cantidad | Cobertura objetivo | Tiempo ejecución |
|---|---|---|---|
| Unit (Pest) | ≥60 | ≥80% backend | < 30 s |
| Feature/Integration (Pest) | ≥30 | ≥70% | < 2 min |
| Unit/Component (Vitest) | ≥40 | ≥70% frontend | < 30 s |
| E2E (Playwright) | ≥15 | 100% flujos críticos | < 5 min |
| Accesibilidad (axe-core) | ≥10 | 100% rutas principales | < 1 min |
| Performance (k6) | ≥3 | 100% endpoints críticos | bajo demanda |
| **Total** | **≥158** | | |

---

## 2. Matriz RF módulo 01 → Tests

| RF | Test unitario (Pest/Vitest) | Test integración (Pest/Playwright) | Test E2E (Playwright) | Test a11y (axe-core) |
|---|---|---|---|---|
| RF-01-001 | `TopBarResourceTest`, `TopBarComponentTest` | `TopBarApiTest` | `topbar-visible-en-todas.spec.ts` | `topbar-a11y.spec.ts` |
| RF-01-002 | `FooterResourceTest`, `FooterComponentTest` | `FooterApiTest` | `footer-visible.spec.ts` | `footer-a11y.spec.ts` |
| RF-01-003 | `PrefijoTelefonicoRuleTest` | `TelefonosApiTest` | — | — |
| RF-01-004 | — | — | `logo-click-vuelve-inicio.spec.ts` | — |
| RF-01-005 | — | — | `tokens-css-aplicados.spec.ts` | — |
| RF-01-006 | `VolverArribaComponentTest` | — | `volver-arriba.spec.ts` | — |
| RF-01-007 | `MenuControllerTest`, `MenuItemResourceTest` | `MenuApiTest` | `menu-principal.spec.ts` | `menu-a11y.spec.ts` |
| RF-01-008 | `MenuPrincipalComponentTest` (viewport prop) | — | `menu-responsive.spec.ts` | `menu-mobile-a11y.spec.ts` |
| RF-01-009 | `BuscadorServiceTest`, `useBusquedaTest` | `BuscadorApiTest` | `buscador-autocompletar.spec.ts` | `buscador-a11y.spec.ts` |
| RF-01-010 | — | — | `sitemap-xml.spec.ts` | — |
| RF-01-011 | `MigasPanComponentTest` | — | `migas-pan.spec.ts` | `migas-pan-a11y.spec.ts` |
| RF-01-012 | `VinculoRotoCommandTest` | — | `v0-vinculos-rotos.spec.ts` | — |
| RF-01-013 | `NoticiaResourceTest`, `TarjetaNoticiaComponentTest` | `NoticiaApiTest` | `noticias-home.spec.ts` | `noticias-a11y.spec.ts` |
| RF-01-014 | `CarruselNoticiasComponentTest` | — | `carrusel-pausa.spec.ts` | `carrusel-a11y.spec.ts` |
| RF-01-015 | — | — | `404-personalizada.spec.ts` | `404-a11y.spec.ts` |
| RF-01-016 | `AvisoSalidaExternaComponentTest` | — | `aviso-externo.spec.ts` | — |
| RF-01-017 | `useCookiesConsentTest` | — | `banner-cookies.spec.ts` | `banner-cookies-a11y.spec.ts` |
| RF-01-018..020 | `PoliticaControllerTest` | `PoliticaApiTest` | `politicas.spec.ts` | `politicas-a11y.spec.ts` |
| RF-01-021 | `BtnComponentTest`, `ModalComponentTest`, etc. | — | `kit-ui-componentes.spec.ts` | `kit-ui-a11y.spec.ts` |
| RF-01-022 | `PlanIntegracionControllerTest` | `PlanIntegracionApiTest` | — | — |
| RF-01-D01 | (diferido) | — | — | — |

---

## 3. Matriz RF módulo 02 → Tests

| RF | Test unitario | Test integración | Test E2E | Test a11y |
|---|---|---|---|---|
| RF-02-001 | `SubseccionResourceTest` | `SubseccionApiTest` | `transparencia-10-subsecciones.spec.ts` | `transparencia-a11y.spec.ts` |
| RF-02-002 | `DocumentoRepositoryTest` | `DocumentoListarApiTest` | `listado-cronologico.spec.ts` | — |
| RF-02-003, RF-02-027 | `BuscadorServiceTest` | `BuscadorApiTest` | `buscar-transparencia.spec.ts` | `buscador-a11y.spec.ts` |
| RF-02-004 | — | `DocumentoMostrarApiTest` | `url-canonica.spec.ts` | — |
| RF-02-005 | `DependenciaResourceTest` | `DependenciaApiTest` | `info-entidad.spec.ts` | `info-entidad-a11y.spec.ts` |
| RF-02-006 | `ServidorPublicoResourceTest`, `SigepSyncServiceTest` | `DirectorioApiTest` | `directorio.spec.ts` | `directorio-a11y.spec.ts` |
| RF-02-007 | — | — | `grupos-interes.spec.ts` | — |
| RF-02-008 | `DocumentoRepositoryTest` | `DocumentoPorSubseccionTest` | `normativa.spec.ts` | — |
| RF-02-009, RF-02-010 | `EnlaceExternoValidatorTest` | — | `enlaces-externos.spec.ts` | — |
| RF-02-011 | `PlanAccionVerificarVigenciaJobTest` | — | `plan-accion-31ene.spec.ts` | — |
| RF-02-012..014 | — | — | `informes.spec.ts` | — |
| RF-02-015 | — | — | `tributaria.spec.ts` | — |
| RF-02-016 | `CalendarioTributarioResourceTest` | `CalendarioApiTest` | `calendario-tributario.spec.ts` | `calendario-a11y.spec.ts` |
| RF-02-017 | — | — | `datos-abiertos.spec.ts` | — |
| RF-02-018 | — | — | `tramites-suit.spec.ts` | — |
| RF-02-019, RF-02-020 | — | — | `participa.spec.ts` | — |
| RF-02-021 | `DocumentoServiceTest`, `EditorDocumentoComponentTest` | `DocumentoCrudApiTest` | `crud-documento.spec.ts` | `editor-a11y.spec.ts` |
| RF-02-022 | `AlertaPublicacionServiceTest` | `AlertaApiTest` | `alertas.spec.ts` | — |
| RF-02-023 | `IntegracionCaidaTest` | — | `caida-secop.spec.ts` | — |
| RF-02-024 | `HashSha256ServiceTest` | `DocumentoHashTest` | `hash-visible.spec.ts` | — |
| RF-02-025 | `FormatoAbiertoValidatorTest` | — | `formatos-abiertos.spec.ts` | — |
| RF-02-026 | `FernandezHuertaCalculadorTest` | `LenguajeClaroTest` | — | — |
| RF-02-027 | `FtsQueryTest` | `FtsApiTest` | `fts-busqueda.spec.ts` | — |
| RF-02-028 | `FiltrosDocumentoTest` | `FiltrosApiTest` | `filtros.spec.ts` | — |
| RF-02-029 | `ItaCalculatorServiceTest` | `TableroItaApiTest` | `ita-tablero.spec.ts` | — |
| RF-02-030 | `MetadatoDocumentoTest` | `DocumentoConMetadatosTest` | `metadatos.spec.ts` | — |

---

## 4. Matriz RNF → Tests

| RNF | Test | Umbral | Medición |
|---|---|---|---|
| RNF-REND-01 | `k6/ttfb.js` | p95 ≤ 200 ms | k6 + Grafana |
| RNF-REND-02 | `k6/lcp.js` | p75 ≤ 2.5 s | Lighthouse + web-vitals |
| RNF-REND-03 | `k6/inp.js` | p75 ≤ 200 ms | web-vitals |
| RNF-CAP-01 | `k6/capacidad.js` | 1000 VU sin errores | k6 |
| RNF-CAP-02 | `k6/storage.js` | búsqueda p95 ≤ 1 s | k6 |
| RNF-DISP-01 | `k6/uptime.js` (24 h) | ≥ 99.5% | UptimeRobot + Grafana |
| RNF-DISP-02 | DRP drills | RTO 4 h, RPO 1 h | Simulacro semestral |
| RNF-SEG-01 | `tests/seguridad/headers.spec.ts` | Observatory A+ | Playwright + Observatory |
| RNF-SEG-02 | `tests/seguridad/auth.spec.ts` | MFA obligatorio | Playwright |
| RNF-SEG-03 | `tests/seguridad/ratelimit.spec.ts` | 429 tras 60/min | k6 |
| RNF-USAB-01 | Estudio SUS trimestral | ≥ 80 | Encuesta a 30 usuarios |
| RNF-USAB-02 | `tests/usabilidad/ancho-linea.spec.ts` | 60-80 char | Playwright |
| RNF-ACES-01 | `tests/a11y/all-routes.spec.ts` | 0 violaciones serias | axe-core |
| RNF-ACES-02 | `tests/a11y/barra-accesibilidad.spec.ts` | 4 modos contraste | Playwright |
| RNF-MANT-01 | Codecov en CI | ≥ 80% / ≥ 70% | Codecov |
| RNF-PORT-01 | `tests/browserstack/cross-browser.spec.ts` | 0 fallas | BrowserStack |
| RNF-PORT-02 | `tests/responsive/viewports.spec.ts` | 6 breakpoints | Playwright |
| RNF-PD-01 | `tests/arco/solicitud-arco.spec.ts` | ≤ 15 días hábiles | Pest |

---

## 5. Tests de smoke por sprint

| Sprint | Smoke tests | A11y tests |
|---|---|---|
| S-01 | Health check, login básico | — |
| S-02 | Top bar, footer, menú | axe-core en top bar/footer/menú |
| S-03 | Listado documentos | axe-core en /transparencia |
| S-04 | Búsqueda, directorio | axe-core en /directorio |
| S-05 | Subir/editar/publicar documento | axe-core en /panel/documentos |
| S-06 | Home, noticias, cookies | axe-core en home + 10 rutas |
| S-07 | ITA, alertas, auditoría | axe-core en /panel |
| S-08 | E2E completo, k6, Lighthouse | Auditoría WCAG final |

---

## 6. Ejemplos de tests concretos

### Test Pest (backend) — `DocumentoListarApiTest`

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Transparencia;

use App\Models\Transparencia\Documento;
use App\Models\Transparencia\SubseccionTransparencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentoListarApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_documentos_publicados_en_orden_cronologico(): void
    {
        // Arrange
        $sub = SubseccionTransparencia::factory()->create(['codigo' => 'normativa']);
        Documento::factory()->create([
            'subseccion_id' => $sub->id,
            'estado' => 'publicado',
            'fecha_publicacion' => '2025-01-15',
            'titulo' => 'Documento antiguo',
        ]);
        Documento::factory()->create([
            'subseccion_id' => $sub->id,
            'estado' => 'publicado',
            'fecha_publicacion' => '2026-01-15',
            'titulo' => 'Documento reciente',
        ]);
        Documento::factory()->create([
            'subseccion_id' => $sub->id,
            'estado' => 'borrador', // NO debe aparecer
            'fecha_publicacion' => '2026-06-01',
            'titulo' => 'Documento borrador',
        ]);

        // Act
        $response = $this->getJson('/api/v1/transparencia/documentos?filter[subseccion]=normativa');

        // Assert
        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.attributes.titulo', 'Documento reciente')
            ->assertJsonPath('data.1.attributes.titulo', 'Documento antiguo')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_hash_sha256_esta_presente_en_la_respuesta(): void
    {
        // ...
    }

    public function test_filtro_por_vigencia_funciona(): void
    {
        // ...
    }
}
```

### Test Vitest (frontend) — `TarjetaDocumentoComponentTest`

```typescript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import TarjetaDocumento from '~/components/transparencia/TarjetaDocumento.vue';

describe('TarjetaDocumento', () => {
  it('renderiza el título y enlace al documento', () => {
    const documento = {
      id: '1',
      slug: 'plan-accion-2026',
      titulo: 'Plan de Acción 2026',
      descripcion: 'Plan anual',
      fecha_publicacion: '2026-01-15',
      periodicidad: 'anual',
      hash_sha256: 'a'.repeat(64),
      tamano_bytes: 1024,
      formato_abierto: true,
    };

    const wrapper = mount(TarjetaDocumento, { props: { documento } });

    expect(wrapper.text()).toContain('Plan de Acción 2026');
    expect(wrapper.find('a').attributes('href')).toBe('/transparencia/plan-accion-2026');
  });

  it('muestra advertencia de accesibilidad con aria-labelledby', () => {
    // ...
  });

  it('formatea tamaño legible en KB/MB', () => {
    // ...
  });
});
```

### Test Playwright (E2E) — `topbar-visible-en-todas.spec.ts`

```typescript
import { test, expect } from '@playwright/test';

const rutas = [
  '/',
  '/transparencia',
  '/transparencia/normativa',
  '/directorio',
  '/noticias',
  '/buscar?q=test',
  '/politicas/privacidad',
];

for (const ruta of rutas) {
  test(`Top bar GOV.CO visible en ${ruta}`, async ({ page }) => {
    await page.goto(ruta);
    const topBar = page.getByRole('banner', { name: /barra superior del estado/i });
    await expect(topBar).toBeVisible();

    const logo = topBar.locator('a').first();
    await expect(logo).toHaveAttribute('href', 'https://www.gov.co/home/');

    // Verificar altura 56 px
    const altura = await topBar.evaluate(el => el.getBoundingClientRect().height);
    expect(altura).toBe(56);
  });
}
```

### Test axe-core (a11y) — `topbar-a11y.spec.ts`

```typescript
import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test('Top bar cumple WCAG 2.1 AA', async ({ page }) => {
  await page.goto('/');

  const { violations } = await new AxeBuilder({ page })
    .include('[data-testid="top-bar-govco"]')
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    .analyze();

  const serias = violations.filter(
    v => v.impact === 'serious' || v.impact === 'critical'
  );
  expect(serias).toEqual([]);
});
```

### Test k6 (performance) — `capacidad.js`

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '1m', target: 100 },
    { duration: '5m', target: 1000 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<500'],
  },
};

export default function () {
  const res = http.get('https://staging.santamarta.gov.co/api/v1/transparencia/documentos');
  check(res, {
    'status es 200': (r) => r.status === 200,
    'latencia < 500 ms': (r) => r.timings.duration < 500,
  });
  sleep(1);
}
```

---

## 7. Resumen de cobertura por test

| Categoría | Tests | Cobertura % requisitos |
|---|---|---|
| Unit backend (Pest) | ≥60 | 90% RF backend |
| Integration backend (Pest) | ≥30 | 100% endpoints API |
| Unit frontend (Vitest) | ≥40 | 60% componentes Vue |
| E2E (Playwright) | ≥15 | 100% flujos críticos |
| Accesibilidad (axe-core) | ≥10 | 100% rutas principales |
| Performance (k6) | ≥3 | 100% endpoints críticos |
