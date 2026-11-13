<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Acceso;

use App\Support\Acceso\CatalogoPermisos;
use PHPUnit\Framework\TestCase;

/**
 * El catálogo es módulo × acción: cada módulo tiene sus acciones por separado (ver, crear, editar,
 * eliminar, publicar…) y toda acción distinta de «ver» exige «ver» del mismo módulo.
 */
final class CatalogoPermisosTest extends TestCase
{
    public function test_todo_nombre_es_modulo_punto_accion_salvo_el_acceso_al_panel(): void
    {
        foreach (CatalogoPermisos::nombres() as $nombre) {
            if ($nombre === 'panel-administrative') {
                continue;
            }
            $this->assertMatchesRegularExpression('/^[a-z]+\.(ver|crear|editar|eliminar|publicar)$/', $nombre, $nombre);
        }
    }

    public function test_no_hay_nombres_repetidos_ni_el_permiso_gestionar_que_lo_daba_todo_junto(): void
    {
        $nombres = CatalogoPermisos::nombres();
        $this->assertSame($nombres, array_values(array_unique($nombres)));
        foreach ($nombres as $nombre) {
            $this->assertStringEndsNotWith('.gestionar', $nombre);
        }
    }

    public function test_todo_modulo_con_acciones_de_escritura_tiene_ver(): void
    {
        foreach (CatalogoPermisos::modulos() as $modulo => $datos) {
            $acciones = array_keys($datos['acciones']);
            if ($acciones === ['entrar']) {
                continue;
            }
            $this->assertContains('ver', $acciones, "El módulo «{$modulo}» no tiene «ver».");
        }
    }

    public function test_las_acciones_de_escritura_exigen_ver_del_mismo_modulo(): void
    {
        $this->assertSame(['noticias.ver'], CatalogoPermisos::requeridos('noticias.editar'));
        $this->assertSame(['tramites.ver'], CatalogoPermisos::requeridos('tramites.publicar'));
        $this->assertSame([], CatalogoPermisos::requeridos('noticias.ver'));
        $this->assertSame([], CatalogoPermisos::requeridos('panel-administrative'));
    }

    public function test_faltantes_dice_que_ver_hay_que_añadir(): void
    {
        $this->assertSame([], CatalogoPermisos::faltantes(['noticias.ver', 'noticias.editar']));
        $this->assertSame(['noticias.ver'], CatalogoPermisos::faltantes(['noticias.editar', 'noticias.eliminar']));
        $this->assertSame(['banners.ver', 'noticias.ver'], CatalogoPermisos::faltantes(['noticias.crear', 'banners.editar']));
    }

    public function test_cada_permiso_tiene_etiqueta_y_descripcion_propias(): void
    {
        $etiquetas = [];
        foreach (CatalogoPermisos::agrupar(CatalogoPermisos::nombres()) as $grupo) {
            foreach ($grupo['permisos'] as $p) {
                $this->assertNotSame('', trim($p['etiqueta']));
                $this->assertNotSame('', trim($p['descripcion']));
                $this->assertArrayHasKey('modulo', $p);
                $this->assertArrayHasKey('accion', $p);
                $this->assertArrayHasKey('requiere', $p);
                $etiquetas[] = $p['nombre'];
            }
        }
        $this->assertEqualsCanonicalizing(CatalogoPermisos::nombres(), $etiquetas);
    }

    public function test_un_permiso_que_no_esta_en_el_catalogo_va_a_otros(): void
    {
        $grupos = CatalogoPermisos::agrupar(['noticias.ver', 'raro.cosa']);
        $ultimo = end($grupos);
        $this->assertSame('Otros', $ultimo['grupo']);
        $this->assertSame('raro.cosa', $ultimo['permisos'][0]['nombre']);
    }

    public function test_el_mapa_legado_cubre_los_gestionar_y_no_inventa_nombres(): void
    {
        $mapa = CatalogoPermisos::legado();
        foreach (['noticias', 'tramites', 'banners', 'politicas', 'entidad', 'usuarios', 'cms', 'autenticacion', 'documental', 'integraciones', 'asignacion', 'configuracion'] as $m) {
            $this->assertArrayHasKey("{$m}.gestionar", $mapa);
        }
        foreach ($mapa as $nuevos) {
            foreach ($nuevos as $n) {
                $this->assertContains($n, CatalogoPermisos::nombres(), $n);
            }
        }
        // Lo que el permiso viejo «usuarios.gestionar» daba (usuarios y roles) no se pierde.
        $this->assertEqualsCanonicalizing(
            ['usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar'],
            $mapa['usuarios.gestionar'],
        );
        $this->assertSame(['solicitudes.ver'], $mapa['tramites.ver']);
        $this->assertEqualsCanonicalizing(['portal.ver', 'portal.publicar'], $mapa['portal.publicar']);
    }
}
