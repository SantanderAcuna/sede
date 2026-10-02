# Migraciones Laravel 13 y Seeders

> **Convención:** las migraciones son **idempotentes** (RT-06): `down()` implementado, seeders re-ejecutables con `updateOrCreate`.
> **Numeración:** continúa desde las migraciones existentes en `backend/database/migrations/`.
> **Patrón:** primero schema, luego claves foráneas, luego índices.

---

## 1. Plan de migraciones (orden cronológico)

| # | Archivo | Tabla(s) | Descripción |
|---|---|---|---|
| M-01 | `2026_10_01_000001_create_extensiones_postgres.php` | — | Habilita `pg_trgm`, `unaccent`, `uuid-ossp`, `pgcrypto` |
| M-02 | `2026_10_01_000002_create_rol_permiso_tables.php` | `rol`, `permiso`, `rol_usuario` | RBAC base |
| M-03 | `2026_10_01_000003_create_usuario_table.php` | `usuario` | Cuentas del panel |
| M-04 | `2026_10_01_000004_create_dependencia_table.php` | `dependencia` | Organigrama |
| M-05 | `2026_10_01_000005_create_escala_salarial_table.php` | `escala_salarial` | Escala salarial |
| M-06 | `2026_10_01_000006_create_servidor_publico_table.php` | `servidor_publico` | Directorio |
| M-07 | `2026_10_01_000007_create_menu_item_table.php` | `menu_item` | Menú |
| M-08 | `2026_10_01_000008_create_rol_menu_table.php` | `rol_menu` | Visibilidad menú |
| M-09 | `2026_10_01_000009_create_top_bar_tables.php` | `top_bar`, `top_bar_item` | Top bar GOV.CO |
| M-10 | `2026_10_01_000010_create_footer_tables.php` | `footer`, `footer_item` | Footer |
| M-11 | `2026_10_01_000011_create_noticia_tables.php` | `noticia`, `noticia_imagen`, `categoria_noticia`, `noticia_categoria` | Noticias |
| M-12 | `2026_10_01_000012_create_subseccion_transparencia_table.php` | `subseccion_transparencia` | 10 subsecciones |
| M-13 | `2026_10_01_000013_create_categoria_documento_table.php` | `categoria_documento` | — |
| M-14 | `2026_10_01_000014_create_tipo_documento_table.php` | `tipo_documento` | — |
| M-15 | `2026_10_01_000015_create_archivo_storage_table.php` | `archivo_storage` | — |
| M-16 | `2026_10_01_000016_create_documento_tables.php` | `documento`, `documento_version`, `metadato_documento` | Core transparencia |
| M-17 | `2026_10_01_000017_create_documento_dependencia_table.php` | `documento_dependencia` | N:M |
| M-18 | `2026_10_01_000018_create_busqueda_log_table.php` | `busqueda_log` | — |
| M-19 | `2026_10_01_000019_create_instrumento_gestion_tables.php` | `instrumento_gestion`, `activo_informacion`, `informacion_clasificada` | Ley 1712 |
| M-20 | `2026_10_01_000020_create_politica_table.php` | `politica` | — |
| M-21 | `2026_10_01_000021_create_ita_tables.php` | `ita_item`, `ita_evaluacion` | Tablero ITA |
| M-22 | `2026_10_01_000022_create_alerta_publicacion_table.php` | `alerta_publicacion` | Alertas |
| M-23 | `2026_10_01_000023_create_notificacion_table.php` | `notificacion` | — |
| M-24 | `2026_10_01_000024_create_log_auditoria_table.php` | `log_auditoria` (PARTICIONADA) | — |
| M-25 | `2026_10_01_000025_create_materialized_views.php` | `mv_documentos_subseccion`, `mv_servidor_publico` | Performance |
| M-26 | `2026_10_01_000026_create_rls_policies.php` | — | Row Level Security |

---

## 2. Migraciones de muestra (formato Laravel 13)

### M-01 — Extensiones PostgreSQL

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pg_trgm"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "unaccent"');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS "unaccent"');
        DB::statement('DROP EXTENSION IF EXISTS "pg_trgm"');
        DB::statement('DROP EXTENSION IF EXISTS "pgcrypto"');
        DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp"');
    }
};
```

### M-04 — `dependencia` (organigrama)

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dependencia', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 20);
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('dependencia_padre_id')->nullable();
            $table->unsignedBigInteger('responsable_id')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 200)->nullable();
            $table->string('nivel', 20);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique('codigo');
            $table->index('dependencia_padre_id');
            $table->index(['activo']);
        });

        // Auto-referencia (FK se añade en M-06 cuando exista servidor_publico o al final)
        Schema::table('dependencia', function (Blueprint $table) {
            $table->foreign('dependencia_padre_id')
                ->references('id')->on('dependencia')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependencia');
    }
};
```

### M-06 — `servidor_publico`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('servidor_publico', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dependencia_id');
            $table->string('codigo_sigep', 50)->nullable();
            $table->string('numero_identificacion', 20);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('cargo', 200);
            $table->string('correo_institucional', 200)->nullable();
            $table->string('extension', 20)->nullable();
            $table->date('fecha_ingreso');
            $table->date('fecha_salida')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('codigo_sigep');
            $table->unique('numero_identificacion');
            $table->foreign('dependencia_id')
                ->references('id')->on('dependencia')
                ->onDelete('restrict');
        });

        // ENUM
        DB::statement("ALTER TABLE servidor_publico ADD CONSTRAINT servidor_publico_estado_check
          CHECK (estado IN ('activo', 'inactivo', 'comision', 'desvinculado'))");

        // Trigger: BR-SP-01 (validación de campos públicos)
        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE FUNCTION validar_servidor_publico() RETURNS trigger AS $$
        BEGIN
          IF NEW.numero_identificacion IS NULL OR length(NEW.numero_identificacion) < 5 THEN
            RAISE EXCEPTION 'Número de identificación inválido';
          END IF;
          IF NEW.fecha_salida IS NOT NULL AND NEW.fecha_salida < NEW.fecha_ingreso THEN
            RAISE EXCEPTION 'Fecha de salida anterior a fecha de ingreso';
          END IF;
          RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;

        CREATE TRIGGER trg_validar_servidor_publico
          BEFORE INSERT OR UPDATE ON servidor_publico
          FOR EACH ROW EXECUTE FUNCTION validar_servidor_publico();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_validar_servidor_publico ON servidor_publico');
        DB::statement('DROP FUNCTION IF EXISTS validar_servidor_publico()');
        Schema::dropIfExists('servidor_publico');
    }
};
```

### M-12 — `subseccion_transparencia` (las 10 subsecciones)

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subseccion_transparencia', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 50);
            $table->string('nombre', 200);
            $table->text('descripcion');
            $table->integer('orden');
            $table->integer('numero_ley');
            $table->timestamps();

            $table->unique('codigo');
            $table->index('orden');
        });

        DB::statement('ALTER TABLE subseccion_transparencia
          ADD CONSTRAINT subseccion_orden_check CHECK (orden BETWEEN 1 AND 10)');

        DB::statement('ALTER TABLE subseccion_transparencia
          ADD CONSTRAINT subseccion_numero_ley_check CHECK (numero_ley BETWEEN 1 AND 10)');
    }

    public function down(): void
    {
        Schema::dropIfExists('subseccion_transparencia');
    }
};
```

### M-16 — `documento`, `documento_version`, `metadato_documento`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('archivo_storage', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('path', 500);
            $table->string('nombre_original', 300);
            $table->string('mime_type', 100);
            $table->bigInteger('tamano_bytes');
            $table->char('hash_sha256', 64);
            $table->timestamp('uploaded_at')->useCurrent();
            $table->unsignedBigInteger('uploaded_by');
            $table->foreign('uploaded_by')->references('id')->on('usuario');
            $table->unique('path');
            $table->unique('hash_sha256');
        });

        Schema::create('documento', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug', 250);
            $table->string('titulo', 300);
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('subseccion_id');
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->unsignedBigInteger('tipo_documento_id');
            $table->unsignedBigInteger('archivo_id');
            $table->unsignedBigInteger('version_actual_id')->nullable();
            $table->unsignedBigInteger('autor_id');
            $table->date('fecha_publicacion')->nullable();
            $table->date('fecha_documento');
            $table->string('periodicidad', 20)->default('eventual');
            $table->string('estado', 20)->default('borrador');
            $table->boolean('destacado')->default(false);
            $table->decimal('indice_lecturabilidad', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('slug');
            $table->foreign('subseccion_id')->references('id')->on('subseccion_transparencia');
            $table->foreign('categoria_id')->references('id')->on('categoria_documento');
            $table->foreign('tipo_documento_id')->references('id')->on('tipo_documento');
            $table->foreign('archivo_id')->references('id')->on('archivo_storage');
            $table->foreign('autor_id')->references('id')->on('usuario');
        });

        // CHECKs y ENUMs
        DB::statement("ALTER TABLE documento ADD CONSTRAINT documento_estado_check
          CHECK (estado IN ('borrador','revision','publicado','despublicado','archivado'))");
        DB::statement("ALTER TABLE documento ADD CONSTRAINT documento_periodicidad_check
          CHECK (periodicidad IN ('anual','semestral','trimestral','mensual','eventual'))");
        DB::statement("ALTER TABLE documento ADD CONSTRAINT documento_lecturabilidad_check
          CHECK (indice_lecturabilidad IS NULL OR (indice_lecturabilidad BETWEEN 0 AND 100))");

        Schema::create('documento_version', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('documento_id');
            $table->integer('numero_version');
            $table->unsignedBigInteger('archivo_id');
            $table->text('motivo_cambio')->nullable();
            $table->unsignedBigInteger('autor_id');
            $table->timestamp('fecha_version')->useCurrent();
            $table->boolean('version_publicada')->default(true);

            $table->unique(['documento_id', 'numero_version']);
            $table->foreign('documento_id')->references('id')->on('documento')->onDelete('cascade');
            $table->foreign('archivo_id')->references('id')->on('archivo_storage');
            $table->foreign('autor_id')->references('id')->on('usuario');
        });

        Schema::create('metadato_documento', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('documento_id');
            $table->string('clave', 100);
            $table->text('valor');
            $table->unique(['documento_id', 'clave']);
            $table->foreign('documento_id')->references('id')->on('documento')->onDelete('cascade');
        });

        // FK circular: documento.version_actual_id → documento_version.id
        Schema::table('documento', function (Blueprint $table) {
            $table->foreign('version_actual_id')->references('id')->on('documento_version');
        });

        // Columna FTS generada
        DB::statement("ALTER TABLE documento ADD COLUMN fts tsvector
          GENERATED ALWAYS AS (
            to_tsvector('spanish',
              unaccent(coalesce(titulo, '') || ' ' || coalesce(descripcion, '')))
          ) STORED");
        DB::statement("CREATE INDEX idx_documento_fts ON documento USING GIN (fts)");
        DB::statement("CREATE INDEX idx_documento_titulo_trgm ON documento USING GIN (titulo gin_trgm_ops)");
    }

    public function down(): void
    {
        Schema::table('documento', function (Blueprint $table) {
            $table->dropForeign(['version_actual_id']);
        });
        Schema::dropIfExists('metadato_documento');
        Schema::dropIfExists('documento_version');
        Schema::dropIfExists('documento');
        Schema::dropIfExists('archivo_storage');
    }
};
```

### M-24 — `log_auditoria` (PARTICIONADA)

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
        CREATE TABLE log_auditoria (
          id BIGINT NOT NULL,
          usuario_id BIGINT,
          accion VARCHAR(50) NOT NULL,
          recurso VARCHAR(100),
          recurso_id BIGINT,
          cambios JSONB,
          ip_origen INET,
          user_agent TEXT,
          created_at TIMESTAMP NOT NULL,
          PRIMARY KEY (id, created_at)
        ) PARTITION BY RANGE (created_at);

        CREATE INDEX idx_log_auditoria_usuario_fecha
          ON log_auditoria (usuario_id, created_at DESC);
        CREATE INDEX idx_log_auditoria_recurso
          ON log_auditoria (recurso, recurso_id);
        CREATE INDEX idx_log_auditoria_fecha
          ON log_auditoria (created_at);
        SQL);

        // Partición inicial
        DB::statement(<<<'SQL'
        CREATE TABLE log_auditoria_2026_10 PARTITION OF log_auditoria
          FOR VALUES FROM ('2026-10-01') TO ('2026-11-01');
        CREATE TABLE log_auditoria_2026_11 PARTITION OF log_auditoria
          FOR VALUES FROM ('2026-11-01') TO ('2026-12-01');
        CREATE TABLE log_auditoria_2026_12 PARTITION OF log_auditoria
          FOR VALUES FROM ('2026-12-01') TO ('2027-01-01');
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS log_auditoria CASCADE');
    }
};
```

---

## 3. Seeders (orden de ejecución)

| # | Seeder | Propósito |
|---|---|---|
| S-01 | `RolesPermisosSeeder` | Crea roles base: `administrador`, `editor`, `aprobador`, `consultor`, `seguridad` |
| S-02 | `PermisosSeeder` | Crea permisos granulares: `documento.crear`, `documento.editar`, etc. |
| S-03 | `DependenciasSeeder` | Carga organigrama inicial desde `database/datos/dependencias.json` |
| S-04 | `TiposDocumentoSeeder` | Carga tipos: PDF, PDF-A, XLSX, CSV, JSON, RDF, ODF, HTML |
| S-05 | `SubseccionesTransparenciaSeeder` | Carga las 10 subsecciones de Ley 1712 |
| S-06 | `MenuPrincipalSeeder` | Carga menú con los 4 ítems obligatorios (Inicio, Transparencia, Atención, Participa) |
| S-07 | `TopBarSeeder` | Carga configuración inicial del top bar GOV.CO |
| S-08 | `FooterSeeder` | Carga datos institucionales del footer |
| S-09 | `PoliticasSeeder` | Carga 5 políticas: términos, privacidad, cookies, derechos autor, accesibilidad |
| S-10 | `ItaItemsSeeder` | Carga items del tablero ITA desde la Res. 1519/2020 Anexo 2 |
| S-11 | `AlertasPublicacionSeeder` | Carga alertas: plan-accion, informe-gestion, pqrsd, control-interno, calendario-tributario |
| S-12 | `UsuarioAdminSeeder` | Crea usuario admin inicial (password generado, mostrar una vez) |
| S-13 | `DatosInicialesSeeder` | Datos demo: 5 noticias, 10 documentos de transparencia, 20 servidores |

### Seeder de muestra — `SubseccionesTransparenciaSeeder`

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Transparencia\SubseccionTransparencia;
use Illuminate\Database\Seeder;

class SubseccionesTransparenciaSeeder extends Seeder
{
    public function run(): void
    {
        $subsecciones = [
            ['codigo' => 'informacion-entidad', 'nombre' => 'Información de la entidad',
             'descripcion' => 'Misión, visión, funciones, organigrama, directorio.', 'orden' => 1, 'numero_ley' => 1],
            ['codigo' => 'normativa', 'nombre' => 'Normativa',
             'descripcion' => 'Normas expedidas por la entidad.', 'orden' => 2, 'numero_ley' => 2],
            ['codigo' => 'contratacion', 'nombre' => 'Contratación',
             'descripcion' => 'Plan anual de adquisiciones, contratos, ejecución.', 'orden' => 3, 'numero_ley' => 3],
            ['codigo' => 'planeacion', 'nombre' => 'Planeación, presupuesto e informes',
             'descripcion' => 'Plan de Acción, presupuesto, informes de gestión, estados financieros.', 'orden' => 4, 'numero_ley' => 4],
            ['codigo' => 'tramites', 'nombre' => 'Trámites',
             'descripcion' => 'Catálogo de trámites SUIT.', 'orden' => 5, 'numero_ley' => 5],
            ['codigo' => 'participa', 'nombre' => 'Participa',
             'descripcion' => 'Mecanismos de participación ciudadana.', 'orden' => 6, 'numero_ley' => 6],
            ['codigo' => 'datos-abiertos', 'nombre' => 'Datos abiertos',
             'descripcion' => 'Datasets publicados en datos.gov.co.', 'orden' => 7, 'numero_ley' => 7],
            ['codigo' => 'grupos-interes', 'nombre' => 'Grupos de interés',
             'descripcion' => 'Información para grupos de interés.', 'orden' => 8, 'numero_ley' => 8],
            ['codigo' => 'reporte-especifico', 'nombre' => 'Obligación de reporte específico',
             'descripcion' => 'Reportes a superintendencias, ministerios, etc.', 'orden' => 9, 'numero_ley' => 9],
            ['codigo' => 'tributaria', 'nombre' => 'Información tributaria territorial',
             'descripcion' => 'Predial, ICA, otros impuestos distritales.', 'orden' => 10, 'numero_ley' => 10],
        ];

        foreach ($subsecciones as $s) {
            SubseccionTransparencia::updateOrCreate(
                ['codigo' => $s['codigo']],
                $s
            );
        }
    }
}
```

---

## 4. Comandos artisan de mantenimiento

| Comando | Descripción |
|---|---|
| `php artisan db:verificar` | Verifica estructura vs spec; reporta drift |
| `php artisan particion:crear --mes=YYYY-MM` | Crea partición mensual de `log_auditoria` |
| `php artisan particion:archivar --mes=YYYY-MM` | Mueve partición vieja a tabla de archivo |
| `php artisan mv:refrescar --vista=mv_documentos_subseccion` | Refresca vista materializada |
| `php artisan sigep:sincronizar` | Job de sincronización con SIGEP |
| `php artisan hash:recalcular --documento=X` | Recalcula SHA-256 si es necesario |
| `php artisan contrato:verificar-drift` | Compara rutas Laravel vs OpenAPI |
| `php artisan openapi:generar-ts` | Genera tipos TS para clientes |
| `php artisan ita:calcular` | Recalcula tablero ITA |

---

## 5. Convenciones de migraciones del proyecto

| Aspecto | Convención |
|---|---|
| Nombres de tabla | `snake_case` en plural: `documento` (singular, como convención del proyecto existente `Tramite`) |
| PK | `id BIGINT UNSIGNED AUTO_INCREMENT` |
| Timestamps | `created_at`, `updated_at` siempre |
| Soft-delete | `deleted_at TIMESTAMP NULL` cuando aplica |
| ENUM | Definidos con CHECK CONSTRAINT (no tipos ENUM de PostgreSQL para permitir agregar valores sin ALTER TYPE) |
| FK | Siempre declaradas en migración de la tabla hija, NO en la tabla padre |
| Índices | Siempre nombrados con prefijo `idx_` o `uk_` |
| Triggers | Nombrados `trg_<tabla>_<accion>` |
| Funciones PL/pgSQL | Nombradas `<verbo>_<tabla>()` |
