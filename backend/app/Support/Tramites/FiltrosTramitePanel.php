<?php

declare(strict_types=1);

namespace App\Support\Tramites;

/** Lo que el panel pide al listar trámites: texto, estado y orden (lista cerrada). */
final readonly class FiltrosTramitePanel
{
    public const ORDEN_NOMBRE = 'nombre';

    public const ORDEN_ACTUALIZADO = 'actualizado_en';

    public const ORDENES = [self::ORDEN_NOMBRE, self::ORDEN_ACTUALIZADO];

    public const ESTADOS = ['publicado', 'borrador'];

    public function __construct(
        public ?string $texto,
        public ?string $estado,
        public string $ordenarPor,
        public string $direccion,
    ) {}
}
