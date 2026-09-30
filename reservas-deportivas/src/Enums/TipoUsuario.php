<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoUsuario: string
{
    case Estudiante = 'estudiante';
    case Docente = 'docente';
    case Visitante = 'visitante';

    public function descuento(): float
    {
        return match ($this) {
            self::Estudiante => 0.20,
            self::Docente => 0.10,
            self::Visitante => 0.0,
        };
    }

    public function etiqueta(): string
    {
        return ucfirst($this->value);
    }
}
