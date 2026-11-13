<?php

declare(strict_types=1);

namespace App\Support\Acceso;

/**
 * Los permisos del panel: módulo × acción, dichos en lenguaje llano.
 *
 * El nombre técnico (`modulo.accion`) es lo que el servidor comprueba; la etiqueta es lo que lee
 * quien administra. Cada módulo tiene sus acciones **por separado** —ver, crear, editar, eliminar y,
 * donde el módulo publica, publicar— para poder dar, por ejemplo, solo «ver».
 *
 * Reglas:
 *  - Toda acción distinta de «ver» exige «ver» del mismo módulo: sin poder abrir el registro no
 *    hay dónde actuar. El panel marca «ver» solo; el servidor rechaza un rol que no lo cumpla.
 *  - Un permiso que existe en la base pero no aquí se muestra en «Otros» con su nombre técnico, para
 *    que nunca quede invisible.
 *  - `legado()` dice qué reciben los roles que tenían los permisos de antes (`X.gestionar`), de modo
 *    que nadie gane ni pierda acceso al migrar.
 */
final class CatalogoPermisos
{
    public const ACCESO_PANEL = 'panel-administrative';

    private const VER = ['Ver', 'Consultar y abrir los registros.'];

    private const CREAR = ['Crear', 'Dar de alta registros nuevos.'];

    private const EDITAR = ['Editar', 'Modificar los registros existentes.'];

    private const ELIMINAR = ['Eliminar', 'Borrar registros.'];

    private const PUBLICAR = ['Publicar', 'Pasar el contenido a la versión pública o retirarlo de ella.'];

    /**
     * grupo → módulo → [etiqueta del módulo, lista de acciones]. Cada acción usa el texto común de
     * arriba salvo que `DESCRIPCION_ESPECIAL` la matice.
     *
     * @var array<string, array<string, array{0: string, 1: list<string>}>>
     */
    private const GRUPOS = [
        'Contenidos del sitio' => [
            'noticias' => ['Noticias', ['ver', 'crear', 'editar', 'eliminar', 'publicar']],
            'tramites' => ['Trámites y servicios', ['ver', 'crear', 'editar', 'eliminar', 'publicar']],
            'banners' => ['Carrusel de la portada', ['ver', 'crear', 'editar', 'eliminar', 'publicar']],
            'politicas' => ['Políticas del sitio', ['ver', 'editar']],
            'entidad' => ['Datos de la entidad', ['ver', 'editar']],
            'menus' => ['Menús', ['ver', 'crear', 'editar', 'eliminar']],
            'temas' => ['Temas de trámites', ['ver', 'crear', 'editar', 'eliminar']],
            'dependencias' => ['Dependencias', ['ver', 'crear', 'editar', 'eliminar']],
            'cms' => ['Contenidos generales', ['ver', 'crear', 'editar', 'eliminar']],
            'portal' => ['Portal', ['ver', 'publicar']],
            'sede' => ['Sede electrónica', ['ver', 'publicar']],
            'transparencia' => ['Transparencia', ['ver']],
        ],
        'Atención al ciudadano' => [
            'pqrsd' => ['PQRSD', ['ver']],
            'solicitudes' => ['Solicitudes de trámites', ['ver']],
            'citas' => ['Citas', ['ver']],
            'notificaciones' => ['Notificaciones', ['ver']],
            'carpeta' => ['Carpeta ciudadana', ['ver']],
            'autenticacion' => ['Autenticación ciudadana', ['ver', 'crear', 'editar', 'eliminar']],
            'valoraciones' => ['Valoraciones de la ciudadanía', ['ver', 'eliminar']],
            'consentimientos' => ['Consentimientos registrados', ['ver']],
        ],
        'Archivos' => [
            'archivos' => ['Archivos e imágenes', ['ver', 'crear', 'eliminar']],
            'documental' => ['Gestión documental', ['ver', 'crear', 'editar', 'eliminar']],
        ],
        'Administración del panel' => [
            'usuarios' => ['Usuarios', ['ver', 'crear', 'editar']],
            'roles' => ['Roles y permisos', ['ver', 'crear', 'editar', 'eliminar']],
            'auditoria' => ['Auditoría', ['ver']],
            'reportes' => ['Reportes', ['ver']],
            'asignacion' => ['Asignaciones', ['ver', 'crear', 'editar', 'eliminar']],
            'configuracion' => ['Configuración general', ['ver', 'crear', 'editar', 'eliminar']],
            'integraciones' => ['Integraciones', ['ver', 'crear', 'editar', 'eliminar']],
            'sigmi' => ['SIGMI', ['ver']],
        ],
    ];

    /** Lo que dice cada acción cuando un módulo la matiza (el resto usa la descripción común). */
    private const DESCRIPCION_ESPECIAL = [
        'tramites.publicar' => 'Publicar o retirar trámites; solo se publican los que tienen la ficha de GOV.CO completa.',
        'archivos.crear' => 'Subir imágenes y documentos.',
        'archivos.ver' => 'Consultar los archivos y las imágenes subidos.',
        'archivos.eliminar' => 'Borrar archivos subidos.',
        'banners.editar' => 'Modificar los banners, cambiar su imagen y su orden.',
        'usuarios.editar' => 'Modificar usuarios, activarlos o desactivarlos y cambiar sus roles.',
        'roles.editar' => 'Cambiar el nombre de un rol y sus permisos.',
        'auditoria.ver' => 'Consultar quién hizo qué y cuándo.',
    ];

    /** Un permiso que no es de la forma módulo.acción. */
    private const ESPECIALES = [
        self::ACCESO_PANEL => ['Entrar al panel', 'Acceso general al panel de administración.'],
    ];

    /**
     * Los módulos con sus acciones.
     *
     * @return array<string, array{etiqueta: string, grupo: string, acciones: array<string, array{nombre: string, etiqueta: string, descripcion: string}>}>
     */
    public static function modulos(): array
    {
        $salida = [];
        foreach (self::GRUPOS as $grupo => $modulos) {
            foreach ($modulos as $modulo => [$etiquetaModulo, $acciones]) {
                $filas = [];
                foreach ($acciones as $accion) {
                    $nombre = "{$modulo}.{$accion}";
                    [$etiqueta, $descripcion] = self::textoComun($accion);
                    $filas[$accion] = [
                        'nombre' => $nombre,
                        'etiqueta' => $etiqueta,
                        'descripcion' => self::DESCRIPCION_ESPECIAL[$nombre] ?? $descripcion,
                    ];
                }
                $salida[$modulo] = ['etiqueta' => $etiquetaModulo, 'grupo' => $grupo, 'acciones' => $filas];
            }
        }
        foreach (self::ESPECIALES as $nombre => [$etiqueta, $descripcion]) {
            $salida[$nombre] = [
                'etiqueta' => $etiqueta,
                'grupo' => 'Administración del panel',
                'acciones' => ['entrar' => ['nombre' => $nombre, 'etiqueta' => $etiqueta, 'descripcion' => $descripcion]],
            ];
        }

        return $salida;
    }

    /** @return list<string> todos los nombres de permiso del catálogo */
    public static function nombres(): array
    {
        $nombres = [];
        foreach (self::modulos() as $datos) {
            foreach ($datos['acciones'] as $accion) {
                $nombres[] = $accion['nombre'];
            }
        }

        return $nombres;
    }

    /** @return list<string> todos los permisos de los módulos dados */
    public static function de(string ...$modulos): array
    {
        $nombres = [];
        foreach ($modulos as $modulo) {
            foreach (self::modulos()[$modulo]['acciones'] ?? [] as $accion) {
                $nombres[] = $accion['nombre'];
            }
        }

        return $nombres;
    }

    /**
     * Qué permisos exige uno: toda acción distinta de «ver» exige «ver» de su módulo.
     *
     * @return list<string>
     */
    public static function requeridos(string $nombre): array
    {
        if (! str_contains($nombre, '.')) {
            return [];
        }
        [$modulo, $accion] = explode('.', $nombre, 2);
        $ver = "{$modulo}.ver";

        return $accion !== 'ver' && in_array($ver, self::nombres(), true) ? [$ver] : [];
    }

    /**
     * Los «ver» que faltan para que el conjunto sea coherente.
     *
     * @param  list<string>  $permisos
     * @return list<string>
     */
    public static function faltantes(array $permisos): array
    {
        $faltan = [];
        foreach ($permisos as $permiso) {
            foreach (self::requeridos($permiso) as $requerido) {
                if (! in_array($requerido, $permisos, true)) {
                    $faltan[$requerido] = $requerido;
                }
            }
        }
        $faltan = array_values($faltan);
        sort($faltan);

        return $faltan;
    }

    /**
     * Qué reciben quienes tenían un permiso de antes. Nadie gana ni pierde acceso al migrar.
     *
     * @return array<string, list<string>>
     */
    public static function legado(): array
    {
        $mapa = [];
        $gestionar = [
            'noticias' => ['noticias'], 'tramites' => ['tramites'], 'banners' => ['banners'],
            'politicas' => ['politicas'], 'entidad' => ['entidad'],
            // «usuarios.gestionar» abría usuarios y también roles y permisos.
            'usuarios' => ['usuarios', 'roles'],
            'cms' => ['cms'], 'autenticacion' => ['autenticacion'], 'documental' => ['documental'],
            'integraciones' => ['integraciones'], 'asignacion' => ['asignacion'], 'configuracion' => ['configuracion'],
        ];
        foreach ($gestionar as $viejo => $modulos) {
            $mapa["{$viejo}.gestionar"] = self::de(...$modulos);
        }
        // `portal.publicar` y `sede.publicar` ya existían; ahora exigen «ver».
        $mapa['portal.publicar'] = ['portal.ver', 'portal.publicar'];
        $mapa['sede.publicar'] = ['sede.ver', 'sede.publicar'];
        // `tramites.ver` era «ver solicitudes de la ciudadanía»; `tramites.ver` pasa a ser el catálogo.
        $mapa['tramites.ver'] = ['solicitudes.ver'];

        return $mapa;
    }

    /**
     * @param  list<string>  $existentes  nombres de permiso que hay en la base
     * @return list<array{grupo: string, permisos: list<array{nombre: string, etiqueta: string, descripcion: string, modulo: string, modulo_etiqueta: string, accion: string, requiere: list<string>}>}>
     */
    public static function agrupar(array $existentes): array
    {
        $resultado = [];
        $usados = [];
        $porGrupo = [];

        foreach (self::modulos() as $modulo => $datos) {
            foreach ($datos['acciones'] as $accion => $fila) {
                if (! in_array($fila['nombre'], $existentes, true)) {
                    continue;
                }
                $porGrupo[$datos['grupo']][] = $fila + [
                    'modulo' => $modulo,
                    'modulo_etiqueta' => $datos['etiqueta'],
                    'accion' => $accion,
                    'requiere' => self::requeridos($fila['nombre']),
                ];
                $usados[] = $fila['nombre'];
            }
        }
        foreach (array_keys(self::GRUPOS) as $grupo) {
            if (isset($porGrupo[$grupo])) {
                $resultado[] = ['grupo' => $grupo, 'permisos' => $porGrupo[$grupo]];
            }
        }

        $otros = [];
        foreach ($existentes as $nombre) {
            if (! in_array($nombre, $usados, true)) {
                $otros[] = [
                    'nombre' => $nombre,
                    'etiqueta' => 'Permiso «'.str_replace('.', ' ', $nombre).'»',
                    'descripcion' => 'Permiso sin descripción registrada.',
                    'modulo' => 'otros',
                    'modulo_etiqueta' => 'Otros',
                    'accion' => $nombre,
                    'requiere' => [],
                ];
            }
        }
        if ($otros !== []) {
            $resultado[] = ['grupo' => 'Otros', 'permisos' => $otros];
        }

        return $resultado;
    }

    /** @return array{0: string, 1: string} */
    private static function textoComun(string $accion): array
    {
        return match ($accion) {
            'ver' => self::VER,
            'crear' => self::CREAR,
            'editar' => self::EDITAR,
            'eliminar' => self::ELIMINAR,
            'publicar' => self::PUBLICAR,
            default => [ucfirst($accion), 'Acción del módulo.'],
        };
    }
}
