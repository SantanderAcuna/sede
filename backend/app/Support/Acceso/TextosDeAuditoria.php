<?php

declare(strict_types=1);

namespace App\Support\Acceso;

/**
 * Las frases con que la auditoría se lee en el panel. Una sola fuente: el servidor las entrega con cada
 * registro (`descripcion`, `recurso_nombre`) y el panel solo las muestra.
 *
 * Toda acción que el código registra debe estar aquí; una prueba rastrea el código y falla si falta alguna.
 * Una acción que no esté (por ejemplo, de una versión anterior) se muestra tal cual, marcada como sin
 * descripción, en vez de ocultarla o inventarle un significado.
 */
final class TextosDeAuditoria
{
    /** @var array<string, string> */
    public const ACCIONES = [
        // Sesión y permisos
        'login' => 'Inició sesión',
        'login_failed' => 'Intento fallido de inicio de sesión',
        'logout' => 'Cerró sesión',
        'role_attached' => 'Asignó un rol a una cuenta',
        'role_detached' => 'Quitó un rol a una cuenta',
        'permission_attached' => 'Asignó permisos',
        'permission_detached' => 'Quitó permisos',
        // Usuarios y roles
        'usuario_creado' => 'Creó una cuenta de usuario',
        'usuario_actualizado' => 'Cambió los datos de una cuenta de usuario',
        'usuario_estado_cambiado' => 'Activó o desactivó una cuenta de usuario',
        'rol_creado' => 'Creó un rol',
        'rol_actualizado' => 'Cambió un rol y sus permisos',
        'rol_eliminado' => 'Eliminó un rol',
        // Carrusel
        'banner_creado' => 'Creó una diapositiva del carrusel',
        'banner_actualizado' => 'Cambió una diapositiva del carrusel',
        'banner_imagen_cambiada' => 'Cambió la imagen de una diapositiva',
        'banner_eliminado' => 'Eliminó una diapositiva del carrusel',
        'banners_reordenados' => 'Cambió el orden del carrusel',
        // Noticias
        'noticia_creada' => 'Creó una noticia',
        'noticia_actualizada' => 'Cambió una noticia',
        'noticia_imagen_cambiada' => 'Cambió la imagen de una noticia',
        'noticia_eliminada' => 'Eliminó una noticia',
        // Políticas
        'politica_creada' => 'Creó una política',
        'politica_actualizada' => 'Cambió una política',
        'politica_eliminada' => 'Eliminó una política',
        'politica_documento_adjuntado' => 'Adjuntó un documento a una política',
        'politica_documento_quitado' => 'Quitó el documento de una política (el archivo se conserva)',
        // Trámites y entidad
        'tramite_creado' => 'Creó un trámite',
        'tramite_actualizado' => 'Cambió un trámite',
        'tramite_eliminado' => 'Eliminó un trámite',
        'entidad_actualizada' => 'Cambió los datos de la entidad',
        // Intentos rechazados
        'acceso_denegado' => 'Intento sin permiso suficiente',
        'datos_rechazados' => 'Cambio rechazado por datos inválidos o una regla del sistema',
        'conflicto_rechazado' => 'Cambio rechazado: el contenido había cambiado',
    ];

    /** @var array<string, string> */
    public const RECURSOS = [
        'banners' => 'Carrusel de la portada',
        'noticias' => 'Noticias',
        'politicas' => 'Políticas',
        'tramites' => 'Trámites y servicios',
        'entidad' => 'Entidad',
        'usuarios' => 'Usuarios',
        'roles' => 'Roles',
        'archivos' => 'Archivos',
        'contenido' => 'Imágenes del contenido',
        'session' => 'Sesión',
    ];

    public static function descripcion(string $accion): string
    {
        return self::ACCIONES[$accion] ?? "Acción sin descripción: {$accion}";
    }

    /** El nombre del módulo; lo que no sea un módulo conocido (nombres de rol o de permiso) pasa igual. */
    public static function recurso(string $recurso): string
    {
        return self::RECURSOS[$recurso] ?? $recurso;
    }
}
