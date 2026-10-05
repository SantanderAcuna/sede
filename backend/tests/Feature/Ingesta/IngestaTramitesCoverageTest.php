<?php

declare(strict_types=1);

namespace Tests\Feature\Ingesta;

use App\Contracts\Integraciones\FuenteFichaGovCoInterface;
use App\Contracts\Repositories\IngestaTramiteRepositoryInterface;
use App\Enums\CanalInicioTramite;
use App\Enums\CostoTramite;
use App\Enums\ModalidadTramite;
use App\Exceptions\FuenteNoDisponible;
use App\Models\Tramite;
use App\Services\GovCo\ClienteFichaGovCo;
use App\Services\IngestaTramites;
use App\Support\Ingesta\FichaFuente;
use App\Support\Ingesta\MapeoFicha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de coverage para IngestaTramites.
 *
 * @covers \App\Services\IngestaTramites
 */
final class IngestaTramitesCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_ejecutar_llama_al_callback_de_aviso(): void
    {
        $avisoLlamado = false;
        $avisoMensaje = '';

        $fuente = $this->createMock(FuenteFichaGovCoInterface::class);
        $avance = $this->createMock(IngestaTramiteRepositoryInterface::class);

        $avance->method('completos')->willReturn([]);
        $avance->expects($this->once())->method('cerrar');

        $ficha = $this->crearFichaMinima('T2621', 'Trámite de Prueba');
        $fuente->method('ficha')->willReturn($ficha);

        $service = new IngestaTramites($fuente, $avance, new MapeoFicha, new ClienteFichaGovCo);

        $resultado = $service->ejecutar(['T2621'], function (string $mensaje) use (&$avisoLlamado, &$avisoMensaje) {
            $avisoLlamado = true;
            $avisoMensaje = $mensaje;
        });

        $this->assertTrue($avisoLlamado);
        $this->assertStringContainsString('T2621', $avisoMensaje);
        $this->assertSame(1, $resultado['traidos']);
    }

    public function test_ejecutar_invoca_aviso_cuando_fuente_lanza_fuente_no_disponible(): void
    {
        $avisoLlamado = false;
        $avisoMensaje = '';

        $fuente = $this->createMock(FuenteFichaGovCoInterface::class);
        $avance = $this->createMock(IngestaTramiteRepositoryInterface::class);

        $avance->method('completos')->willReturn([]);

        $fuente->method('ficha')
            ->willThrowException(FuenteNoDisponible::porRespuesta('T9999', '/ficha/T9999', 429, 3));

        $service = new IngestaTramites($fuente, $avance, new MapeoFicha, new ClienteFichaGovCo);

        $resultado = $service->ejecutar(['T9999'], function (string $mensaje) use (&$avisoLlamado, &$avisoMensaje) {
            $avisoLlamado = true;
            $avisoMensaje = $mensaje;
        });

        $this->assertTrue($avisoLlamado);
        $this->assertStringContainsString('T9999', $avisoMensaje);
        $this->assertStringContainsString('FALLÓ', $avisoMensaje);
        $this->assertCount(1, $resultado['fallidos']);
    }

    public function test_ejecutar_marca_como_fallido_cuando_fuente_lanza_excepcion_general(): void
    {
        $fuente = $this->createMock(FuenteFichaGovCoInterface::class);
        $avance = $this->createMock(IngestaTramiteRepositoryInterface::class);

        $avance->method('completos')->willReturn([]);
        $avance->expects($this->once())->method('fallar')
            ->with('T9999', $this->stringContains('Error de red'));

        // Que lance una excepción genérica, no FuenteNoDisponible
        $fuente->method('ficha')
            ->willThrowException(new \RuntimeException('Error de red'));

        $service = new IngestaTramites($fuente, $avance, new MapeoFicha, new ClienteFichaGovCo);

        $resultado = $service->ejecutar(['T9999']);

        $this->assertSame(0, $resultado['traidos']);
        $this->assertCount(1, $resultado['fallidos']);
        $this->assertSame('T9999', $resultado['fallidos'][0]['codigo']);
    }

    public function test_ejecutar_informa_cuando_procede_de_persona(): void
    {
        $fuente = $this->createMock(FuenteFichaGovCoInterface::class);
        $avance = $this->createMock(IngestaTramiteRepositoryInterface::class);

        $avance->method('completos')->willReturn([]);
        $avance->expects($this->once())->method('cerrar');

        $ficha = $this->crearFichaMinima('T2621', 'Trámite Protegido');

        // Simulamos que el trámite existe y fue corregido por una persona
        Tramite::factory()->create([
            'codigo' => 'T2621',
            'nombre' => 'Antiguo',
            'procedencia_fuente' => 'Manual', // No es FuenteSuit::NOMBRE
            'publicado_en' => now(),
            'url_ficha_gov_co' => 'https://www.gov.co/ficha-tramites-y-servicios/T2621',
            'modalidad' => ModalidadTramite::EN_LINEA,
            'tiene_costo' => CostoTramite::GRATUITO,
            'tiempo_solucion_dias' => 5,
            'canal_inicio' => CanalInicioTramite::PROPIO,
            'consulta_estado' => '/seguimiento',
            'requisitos' => json_encode([]),
        ]);

        $fuente->method('ficha')->willReturn($ficha);

        $avisoMensaje = '';
        $service = new IngestaTramites($fuente, $avance, new MapeoFicha, new ClienteFichaGovCo);

        $resultado = $service->ejecutar(['T2621'], function (string $mensaje) use (&$avisoMensaje) {
            $avisoMensaje = $mensaje;
        });

        $this->assertSame(1, $resultado['protegidos']);
        $this->assertStringContainsString('no se toca', $avisoMensaje);
    }

    private function crearFichaMinima(string $codigo, string $nombre): FichaFuente
    {
        return new FichaFuente(
            codigo: $codigo,
            nombre: $nombre,
            proposito: 'Propósito del trámite',
            tipoTramite: 'TRAMITE',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'GRATUITO',
            tiempoObtencion: '10 DIA(S)',
            resultadoObtiene: 'Resultado del trámite',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );
    }
}
