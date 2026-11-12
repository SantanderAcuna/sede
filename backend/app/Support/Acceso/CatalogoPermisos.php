<?php

declare(strict_types=1);

namespace App\Support\Acceso;

/**
 * Los permisos del panel dichos en lenguaje llano.
 *
 * El nombre técnico (`modulo.accion`) es lo que el servidor comprueba; la etiqueta es lo que
 * lee quien administra. Un permiso que existe en la base pero no aquí se muestra en «Otros»
 * con su nombre técnico, para que nunca quede invisible.
 */
final class CatalogoPermisos
{
    /** @var array<string, array<string, array{0: string, 1: string}>> */
    private const GRUPOS = [
        'Contenidos del sitio' => [
            'noticias.gestionar' => ['Gestionar noticias', 'Crear, editar, publicar y eliminar noticias.'],
            'tramites.gestionar' => ['Gestionar trámites y servicios', 'Crear y editar trámites; publicar los que tienen la ficha de GOV.CO completa.'],
            'politicas.gestionar' => ['Gestionar políticas del sitio', 'Editar las políticas y los requisitos legales publicados.'],
            'entidad.gestionar' => ['Gestionar datos de la entidad', 'Editar la información institucional que muestra el sitio.'],
            'cms.gestionar' => ['Gestionar contenidos generales', 'Administrar páginas y bloques de contenido.'],
            'portal.publicar' => ['Publicar en el portal', 'Pasar contenidos a la versión pública.'],
            'sede.publicar' => ['Publicar en la sede electrónica', 'Publicar servicios de la sede.'],
            'transparencia.ver' => ['Ver transparencia', 'Consultar la sección de transparencia.'],
        ],
        'Atención al ciudadano' => [
            'pqrsd.ver' => ['Ver PQRSD', 'Consultar peticiones, quejas, reclamos, sugerencias y denuncias.'],
            'tramites.ver' => ['Ver solicitudes de trámites', 'Consultar las solicitudes que presenta la ciudadanía.'],
            'citas.ver' => ['Ver citas', 'Consultar las citas agendadas.'],
            'notificaciones.ver' => ['Ver notificaciones', 'Consultar las notificaciones enviadas.'],
            'carpeta.ver' => ['Ver carpeta ciudadana', 'Consultar la carpeta ciudadana.'],
            'autenticacion.gestionar' => ['Gestionar la autenticación ciudadana', 'Administrar el acceso de la ciudadanía.'],
        ],
        'Archivos' => [
            'archivos.ver' => ['Ver archivos', 'Consultar los archivos y las imágenes subidos.'],
            'archivos.crear' => ['Subir archivos', 'Subir imágenes y documentos.'],
            'archivos.eliminar' => ['Eliminar archivos', 'Borrar archivos subidos.'],
            'documental.gestionar' => ['Gestionar gestión documental', 'Administrar el archivo documental.'],
        ],
        'Administración del panel' => [
            'usuarios.gestionar' => ['Gestionar usuarios y roles', 'Crear usuarios, asignar roles y definir qué puede hacer cada rol.'],
            'auditoria.ver' => ['Ver la auditoría', 'Consultar quién hizo qué y cuándo.'],
            'reportes.ver' => ['Ver reportes', 'Consultar los reportes del sistema.'],
            'asignacion.gestionar' => ['Gestionar asignaciones', 'Asignar solicitudes al personal.'],
            'configuracion.gestionar' => ['Gestionar la configuración', 'Cambiar la configuración general.'],
            'integraciones.gestionar' => ['Gestionar integraciones', 'Administrar las conexiones con otros sistemas.'],
            'sigmi.ver' => ['Ver SIGMI', 'Consultar el sistema SIGMI.'],
            'panel-administrative' => ['Entrar al panel', 'Acceso general al panel de administración.'],
        ],
    ];

    /**
     * @param  list<string>  $existentes  nombres de permiso que hay en la base
     * @return list<array{grupo: string, permisos: list<array{nombre: string, etiqueta: string, descripcion: string}>}>
     */
    public static function agrupar(array $existentes): array
    {
        $resultado = [];
        $usados = [];

        foreach (self::GRUPOS as $grupo => $permisos) {
            $filas = [];
            foreach ($permisos as $nombre => [$etiqueta, $descripcion]) {
                if (in_array($nombre, $existentes, true)) {
                    $filas[] = ['nombre' => $nombre, 'etiqueta' => $etiqueta, 'descripcion' => $descripcion];
                    $usados[] = $nombre;
                }
            }
            if ($filas !== []) {
                $resultado[] = ['grupo' => $grupo, 'permisos' => $filas];
            }
        }

        $otros = [];
        foreach ($existentes as $nombre) {
            if (! in_array($nombre, $usados, true)) {
                $otros[] = ['nombre' => $nombre, 'etiqueta' => 'Permiso «'.str_replace('.', ' ', $nombre).'»', 'descripcion' => 'Permiso sin descripción registrada.'];
            }
        }
        if ($otros !== []) {
            $resultado[] = ['grupo' => 'Otros', 'permisos' => $otros];
        }

        return $resultado;
    }
}
