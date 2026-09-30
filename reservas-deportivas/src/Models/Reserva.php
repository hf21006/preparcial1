<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoUsuario;

readonly class Reserva
{
    public function __construct(
        public string $nombre,
        public string $correo,
        public TipoUsuario $tipo,
        public string $disciplina,
        public string $espacio,
        public int $participantes,
        public int $horas,
        public float $costoHora,
        public float $subtotal,
        public float $descuento,
        public float $mantenimiento,
        public float $total,
        public string $fecha
    ) {
    }
}
