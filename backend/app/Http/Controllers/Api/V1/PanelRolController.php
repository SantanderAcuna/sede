<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\RolPanelServiceInterface;
use App\Exceptions\AccionNoPermitida;
use App\Exceptions\RolEnUso;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarRolRequest;
use App\Http\Resources\RolPanelResource;
use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Roles y el catálogo de permisos. Cada acción exige su permiso (ver `CatalogoPermisos`).
 *
 * @tags Panel — Roles
 */
final class PanelRolController extends Controller
{
    public function __construct(private readonly RolPanelServiceInterface $roles) {}

    /**
     * Roles con sus permisos y cuántos usuarios los tienen.
     *
     * @operationId listarRolesPanel
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);

        return ApiResponse::ok(RolPanelResource::collection($this->roles->listar())->resolve());
    }

    /**
     * Catálogo de permisos agrupado y descrito en lenguaje llano.
     *
     * @operationId listarPermisosPanel
     */
    public function permisos(): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);

        /** @var list<string> $nombres */
        $nombres = Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all();

        return ApiResponse::ok(CatalogoPermisos::agrupar($nombres));
    }

    /**
     * Un rol.
     *
     * @operationId obtenerRolPanel
     */
    public function show(int $id): JsonResponse
    {
        $rol = $this->roles->porId($id);
        if ($rol === null) {
            return ApiResponse::notFound('El rol no existe.');
        }
        Gate::authorize('view', $rol);

        return ApiResponse::ok((new RolPanelResource($rol))->resolve());
    }

    /**
     * Crea un rol con los permisos elegidos. Solo puede otorgar permisos que usted tiene.
     *
     * @operationId crearRolPanel
     */
    public function store(GuardarRolRequest $peticion): JsonResponse
    {
        Gate::authorize('create', Role::class);

        try {
            /** @var list<string> $permisos */
            $permisos = $peticion->validated('permisos', []);
            $rol = $this->roles->crear($this->actor(), (string) $peticion->validated('name'), $permisos);
        } catch (AccionNoPermitida $e) {
            return ApiResponse::forbidden($e->getMessage());
        }

        return ApiResponse::created((new RolPanelResource($rol))->resolve(), 'Rol creado correctamente.');
    }

    /**
     * Cambia el nombre o los permisos de un rol. El rol de súper administrador es intocable.
     *
     * @operationId actualizarRolPanel
     */
    public function update(GuardarRolRequest $peticion, int $id): JsonResponse
    {
        $rol = $this->roles->porId($id);
        if ($rol === null) {
            return ApiResponse::notFound('El rol no existe.');
        }
        Gate::authorize('update', $rol);

        try {
            /** @var list<string>|null $permisos */
            $permisos = $peticion->has('permisos') ? $peticion->validated('permisos', []) : null;
            $actualizado = $this->roles->actualizar($this->actor(), $rol, $peticion->validated('name'), $permisos);
        } catch (AccionNoPermitida $e) {
            return ApiResponse::forbidden($e->getMessage());
        }

        return ApiResponse::ok((new RolPanelResource($actualizado))->resolve(), 'Rol actualizado correctamente.');
    }

    /**
     * Elimina un rol sin usuarios.
     *
     * @operationId eliminarRolPanel
     */
    public function destroy(int $id): JsonResponse
    {
        $rol = $this->roles->porId($id);
        if ($rol === null) {
            return ApiResponse::notFound('El rol no existe.');
        }
        Gate::authorize('delete', $rol);

        try {
            $this->roles->eliminar($this->actor(), $rol);
        } catch (AccionNoPermitida $e) {
            return ApiResponse::forbidden($e->getMessage());
        } catch (RolEnUso $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }

        return ApiResponse::ok(null, 'Rol eliminado correctamente.');
    }

    private function actor(): User
    {
        /** @var User $actor */
        $actor = auth()->user();

        return $actor;
    }
}
