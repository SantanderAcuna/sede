<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Listener que registra en audit_log los eventos de Spatie Permission y Auth.
 *
 * Espía:
 * - Spatie: RoleAttachedEvent, RoleDetachedEvent,
 *           PermissionAttachedEvent, PermissionDetachedEvent
 * - Auth:   Login, Logout, Failed (intento de acceso fallido)
 */
final class PermissionAuditListener
{
    /**
     * La petición en curso, resuelta en el momento de registrar.
     *
     * No se inyecta por constructor: el suscriptor se crea al arrancar la
     * aplicación y conservaría la petición de ese instante, no la que dispara el
     * evento. En FPM coinciden, pero en un worker de larga vida (Octane, colas) o
     * en las pruebas la IP y el agente registrados serían los de otra petición.
     */
    private function peticion(): Request
    {
        return request();
    }

    /**
     * Registrar el listener en el event dispatcher.
     *
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            RoleAttachedEvent::class => 'onRoleAttached',
            RoleDetachedEvent::class => 'onRoleDetached',
            PermissionAttachedEvent::class => 'onPermissionAttached',
            PermissionDetachedEvent::class => 'onPermissionDetached',
            Login::class => 'onLogin',
            Failed::class => 'onLoginFailed',
            Logout::class => 'onLogout',
        ];
    }

    /**
     * Rol赋予了权限.
     *
     * RoleAttachedEvent: se attachó un rol a un usuario.
     * $event->model     → User al que se le attachó el rol
     * $event->rolesOrIds → rol o IDs de roles que fueron attachados
     */
    public function onRoleAttached(RoleAttachedEvent $event): void
    {
        $roles = $this->nombresRoles($event->rolesOrIds);

        $this->registrar([
            'accion' => 'role_attached',
            'recurso' => implode(', ', $roles),
            'cambios' => [
                'roles' => $roles,
            ],
        ]);
    }

    /**
     * Se quitó un rol de un usuario.
     *
     * RoleDetachedEvent: se detachó un rol de un usuario.
     * $event->model     → User al que se le detachó el rol
     * $event->rolesOrIds → rol o IDs de roles que fueron detachados
     */
    public function onRoleDetached(RoleDetachedEvent $event): void
    {
        $roles = $this->nombresRoles($event->rolesOrIds);

        $this->registrar([
            'accion' => 'role_detached',
            'recurso' => implode(', ', $roles),
            'cambios' => [
                'roles' => $roles,
            ],
        ]);
    }

    /**
     * Se agregó un permiso directamente a un usuario.
     *
     * PermissionAttachedEvent: se attachó un permiso a un usuario.
     * $event->model           → User al que se le attachó el permiso
     * $event->permissionsOrIds → permiso o IDs de permisos attachados
     */
    public function onPermissionAttached(PermissionAttachedEvent $event): void
    {
        $permisos = $this->nombresPermisos($event->permissionsOrIds);
        $nombresPermisos = implode(', ', $permisos);

        $roles = [];
        if ($event->model instanceof User) {
            $roles = $event->model->getRoleNames()->toArray();
        }

        $this->registrar([
            'accion' => 'permission_attached',
            'recurso' => $nombresPermisos,
            'cambios' => $roles !== [] ? ['roles' => $roles] : null,
        ]);
    }

    /**
     * Se quitó un permiso directamente de un usuario.
     *
     * PermissionDetachedEvent: se detachó un permiso de un usuario.
     * $event->model           → User al que se le detachó el permiso
     * $event->permissionsOrIds → permiso o IDs de permisos detachados
     */
    public function onPermissionDetached(PermissionDetachedEvent $event): void
    {
        $permisos = $this->nombresPermisos($event->permissionsOrIds);
        $nombresPermisos = implode(', ', $permisos);

        $roles = [];
        if ($event->model instanceof User) {
            $roles = $event->model->getRoleNames()->toArray();
        }

        $this->registrar([
            'accion' => 'permission_detached',
            'recurso' => $nombresPermisos,
            'cambios' => $roles !== [] ? ['roles' => $roles] : null,
        ]);
    }

    /**
     * Inicio de sesión.
     */
    public function onLogin(Login $event): void
    {
        $this->registrar([
            'accion' => 'login',
            'recurso' => 'session',
            'causer_id' => $event->user?->getAuthIdentifier(),
        ]);
    }

    /**
     * Intento de acceso fallido (H-14, AC-13.1).
     *
     * Registra la IP, el agente y el correo que se intentó, y el motivo en una
     * clave de máquina. NUNCA se guarda la contraseña: `Failed` trae las
     * credenciales completas y aquí se descartan todas salvo el correo y el motivo.
     */
    public function onLoginFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;
        $motivo = $event->credentials['motivo'] ?? null;

        // Un fallo al escribir la auditoría no debe cambiar la respuesta del login:
        // el intento ya fue rechazado, y convertir ese 401 en un 500 solo le diría a
        // quien ataca que la base de datos tiene un problema. Se reporta al log.
        try {
            $this->registrar([
                'accion' => 'login_failed',
                'recurso' => 'session',
                'cambios' => [
                    'email' => is_string($email) ? mb_substr($email, 0, 254) : null,
                    'motivo' => is_string($motivo) ? $motivo : null,
                ],
                'causer_id' => $event->user?->getAuthIdentifier(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Cierre de sesión.
     */
    public function onLogout(Logout $event): void
    {
        $this->registrar([
            'accion' => 'logout',
            'recurso' => 'session',
            'causer_id' => $event->user?->getAuthIdentifier(),
        ]);
    }

    /**
     * Persiste el registro en la tabla audit_log.
     *
     * @param  array<string, mixed>  $datos
     */
    private function registrar(array $datos): void
    {
        AuditLog::create([
            'accion' => $datos['accion'],
            'recurso' => $datos['recurso'],
            'recurso_id' => null,
            'cambios' => $datos['cambios'] ?? null,
            'ip_origen' => $this->peticion()->ip(),
            'user_agent' => $this->peticion()->userAgent(),
            'causer_id' => $datos['causer_id'] ?? $this->peticion()->user()?->getAuthIdentifier(),
            'causer_type' => null,
        ]);
    }

    /**
     * Convierte el argumento rolesOrIds a un array de nombres de rol.
     *
     * @return string[]
     */
    private function nombresRoles(mixed $rolesOrIds): array
    {
        if ($rolesOrIds instanceof Role) {
            return [$rolesOrIds->name];
        }
        if ($rolesOrIds instanceof Collection) {
            $rolesOrIds = $rolesOrIds->all();
        }
        if (! is_array($rolesOrIds)) {
            return [];
        }

        /** @var int[] $ids */
        $ids = [];
        /** @var string[] $nombres */
        $nombres = [];

        foreach ($rolesOrIds as $item) {
            if (is_int($item)) {
                $ids[] = $item;
            } elseif (is_string($item)) {
                $nombres[] = $item;
            } elseif ($item instanceof Role) {
                $nombres[] = $item->name;
            }
        }

        $resultados = [];
        if ($ids !== []) {
            $resultados = Role::whereIn('id', $ids)->pluck('name')->toArray();
        }

        return array_merge($resultados, $nombres);
    }

    /**
     * Convierte el argumento permissionsOrIds a un array de nombres de permiso.
     *
     * @return string[]
     */
    private function nombresPermisos(mixed $permissionsOrIds): array
    {
        if ($permissionsOrIds instanceof Permission) {
            return [$permissionsOrIds->name];
        }
        if ($permissionsOrIds instanceof Collection) {
            $permissionsOrIds = $permissionsOrIds->all();
        }
        if (! is_array($permissionsOrIds)) {
            return [];
        }

        /** @var int[] $ids */
        $ids = [];
        /** @var string[] $nombres */
        $nombres = [];

        foreach ($permissionsOrIds as $item) {
            if (is_int($item)) {
                $ids[] = $item;
            } elseif (is_string($item)) {
                $nombres[] = $item;
            } elseif ($item instanceof Permission) {
                $nombres[] = $item->name;
            }
        }

        $resultados = [];
        if ($ids !== []) {
            $resultados = Permission::whereIn('id', $ids)->pluck('name')->toArray();
        }

        return array_merge($resultados, $nombres);
    }
}
