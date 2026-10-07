<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para el log de auditoría.
 *
 * @tags Panel — Auditoría
 *
 * @operationId listarAuditoria
 *
 * @response 200 {
 *   "success": true,
 *   "data": AuditLogResource[],
 *   "meta": { ... },
 *   "links": { ... }
 * }
 */
final class AuditoriaController extends Controller
{
    /**
     * Lista el log de auditoría con filtros opcionales.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()
            ->with('causer')
            ->orderByDesc('created_at');

        if ($request->filled('usuario_id')) {
            $query->where('causer_id', (int) $request->input('usuario_id'));
        }

        if ($request->filled('recurso')) {
            $query->where('recurso', $request->input('recurso'));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }

        $pagina = $query->paginate(
            perPage: (int) $request->input('por_pagina', 15),
            page: (int) $request->input('pagina', 1),
        );

        $coleccion = AuditLogResource::collection($query->limit(0)->get());

        return ApiResponse::coleccion($coleccion, $pagina);
    }
}
