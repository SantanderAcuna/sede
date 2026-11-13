<?php

declare(strict_types=1);

namespace App\Support\Noticias;

use App\Enums\EstadoNoticia;

/**
 * Lo que el panel puede pedirle al listado de noticias: buscar por título, filtrar por
 * estado y ordenar. Valores ya validados por `ListarNoticiasPanelRequest`; este objeto
 * existe para que ninguna capa de abajo reciba un arreglo de cadenas sueltas.
 */
final readonly class FiltrosNoticiasPanel
{
    public const ORDEN_FECHA = 'fecha_publicacion';

    public const ORDEN_TITULO = 'titulo';

    /** Columnas por las que se puede ordenar: una lista cerrada, nunca texto del cliente. */
    public const ORDENES = [self::ORDEN_FECHA, self::ORDEN_TITULO];

    public function __construct(
        public ?string $titulo = null,
        public ?EstadoNoticia $estado = null,
        public string $ordenarPor = self::ORDEN_FECHA,
        public string $direccion = 'desc',
    ) {}
}
