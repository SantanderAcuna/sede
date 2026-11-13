<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\UsuarioPanelServiceInterface;
use App\Exceptions\AccionNoPermitida;
use App\Exceptions\UltimoSuperAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Http\Requests\ListarUsuariosPanelRequest;
use App\Http\Resources\UsuarioPanelResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Usuarios del panel. Cada acción exige su permiso (ver `CatalogoPermisos`); las reglas contra la escalada de
 * privilegios las aplica el servicio, no la interfaz.
 *
 * @tags Panel — Usuarios
 */
final class PanelUsuarioController extends Controller
{
    public function __construct(private readonly UsuarioPanelServiceInterface $usuarios) {}

    /**
     * Usuarios con sus roles. Se busca por nombre o correo (`q`), se filtra por `estado` y `rol`.
     *
     * @operationId listarUsuariosPanel
     */
    public function index(ListarUsuariosPanelRequest $peticion): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $pagina = $this->usuarios->listar(
            (int) $peticion->validated('per_page', 20),
            $peticion->validated('q'),
            $peticion->validated('estado'),
            $peticion->validated('rol'),
        );

        return ApiResponse::coleccion(UsuarioPanelResource::collection($pagina), $pagina);
    }

    /**
     * Un usuario.
     *
     * @operationId obtenerUsuarioPanel
     */
    public function show(string $uuid): JsonResponse
    {
        $usuario = $this->usuarios->porUuid($uuid);
        if ($usuario === null) {
            return ApiResponse::notFound('El usuario no existe.');
        }
        Gate::authorize('view', $usuario);

        return ApiResponse::ok((new UsuarioPanelResource($usuario))->resolve());
    }

    /**
     * Crea un usuario con una contraseña temporal y, opcionalmente, sus roles.
     *
     * @operationId crearUsuarioPanel
     */
    public function store(GuardarUsuarioRequest $peticion): JsonResponse
    {
        Gate::authorize('create', User::class);

        try {
            /** @var array{name: string, email: string, password: string, estado?: string, roles?: list<string>} $datos */
            $datos = $peticion->datos();
            $usuario = $this->usuarios->crear($this->actor(), $datos);
        } catch (AccionNoPermitida $e) {
            return ApiResponse::forbidden($e->getMessage());
        }

        return ApiResponse::created((new UsuarioPanelResource($usuario))->resolve(), 'Usuario creado correctamente.');
    }

    /**
     * Cambia nombre, correo, clave, estado o roles. Solo se envía lo que cambia.
     *
     * @operationId actualizarUsuarioPanel
     */
    public function update(GuardarUsuarioRequest $peticion, string $uuid): JsonResponse
    {
        $objetivo = $this->usuarios->porUuid($uuid);
        if ($objetivo === null) {
            return ApiResponse::notFound('El usuario no existe.');
        }
        Gate::authorize('update', $objetivo);

        try {
            $usuario = $this->usuarios->actualizar($this->actor(), $objetivo, $peticion->datos());
        } catch (AccionNoPermitida $e) {
            return ApiResponse::forbidden($e->getMessage());
        } catch (UltimoSuperAdmin $e) {
            return ApiResponse::validationError(['usuario' => [$e->getMessage()]]);
        }

        return ApiResponse::ok((new UsuarioPanelResource($usuario))->resolve(), 'Usuario actualizado correctamente.');
    }

    private function actor(): User
    {
        /** @var User $actor */
        $actor = auth()->user();

        return $actor;
    }
}
