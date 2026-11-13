<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Regla de acceso del panel que impide la operación (el mensaje es para quien administra). */
final class AccionNoPermitida extends RuntimeException {}
