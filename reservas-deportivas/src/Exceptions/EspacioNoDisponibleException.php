<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class EspacioNoDisponibleException extends RuntimeException
{
    public static function porEspacio(string $nombre): self
    {
        return new self(sprintf('El espacio "%s" no está disponible por el momento.', $nombre));
    }
}
