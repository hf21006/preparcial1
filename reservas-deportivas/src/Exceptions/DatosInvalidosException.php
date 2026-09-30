<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class DatosInvalidosException extends RuntimeException
{
    /** @param string[] $errores */
    public function __construct(private array $errores)
    {
        parent::__construct('Los datos enviados no son válidos.');
    }

    /** @return string[] */
    public function errores(): array
    {
        return $this->errores;
    }
}
