<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TipoUsuario;
use App\Exceptions\DatosInvalidosException;
use App\Exceptions\EspacioNoDisponibleException;
use App\Models\Reserva;

class ReservaServicio
{
    public const CARGO_MANTENIMIENTO = 2.50;

    public const ESPACIOS = [
        'cancha-futbol' => [
            'nombre' => 'Cancha de fútbol',
            'disciplina' => 'Fútbol',
            'capacidad' => 22,
            'costo_hora' => 25.00,
            'disponible' => true,
        ],
        'cancha-basquet' => [
            'nombre' => 'Cancha de baloncesto',
            'disciplina' => 'Baloncesto',
            'capacidad' => 10,
            'costo_hora' => 15.00,
            'disponible' => true,
        ],
        'piscina' => [
            'nombre' => 'Piscina semiolímpica',
            'disciplina' => 'Natación',
            'capacidad' => 20,
            'costo_hora' => 18.00,
            'disponible' => true,
        ],
        'sala-tenis-mesa' => [
            'nombre' => 'Sala de tenis de mesa',
            'disciplina' => 'Tenis de mesa',
            'capacidad' => 4,
            'costo_hora' => 8.00,
            'disponible' => true,
        ],
        'gimnasio' => [
            'nombre' => 'Gimnasio de pesas',
            'disciplina' => 'Pesas',
            'capacidad' => 15,
            'costo_hora' => 12.00,
            'disponible' => false,
        ],
    ];

    private const CAMPOS = ['nombre', 'correo', 'tipo', 'disciplina', 'espacio', 'participantes', 'horas'];

    /** @return string[] */
    public function disciplinas(): array
    {
        $lista = [];

        foreach (self::ESPACIOS as $espacio) {
            if (!in_array($espacio['disciplina'], $lista, true)) {
                $lista[] = $espacio['disciplina'];
            }
        }

        return $lista;
    }

    /**
     * Limpia los datos con funciones de cadenas.
     *
     * @param array<string, mixed> $entrada
     * @return array<string, string>
     */
    public function limpiar(array $entrada): array
    {
        $datos = [];

        foreach (self::CAMPOS as $campo) {
            $datos[$campo] = trim(strip_tags((string) ($entrada[$campo] ?? '')));
        }

        $nombre = preg_replace('/\s+/', ' ', $datos['nombre']) ?? $datos['nombre'];
        $datos['nombre'] = mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8');
        $datos['correo'] = mb_strtolower(str_replace(' ', '', $datos['correo']));

        return $datos;
    }

    /**
     * @param array<string, string> $d datos ya limpios
     * @return string[] lista de errores (vacía si todo está bien)
     */
    public function validar(array $d): array
    {
        $errores = [];

        $obligatorios = [
            'nombre' => 'El nombre es obligatorio.',
            'correo' => 'El correo es obligatorio.',
            'tipo' => 'El tipo de usuario es obligatorio.',
            'disciplina' => 'La disciplina es obligatoria.',
            'espacio' => 'El espacio es obligatorio.',
            'participantes' => 'Los participantes son obligatorios.',
            'horas' => 'Las horas son obligatorias.',
        ];

        foreach ($obligatorios as $campo => $mensaje) {
            if ($d[$campo] === '') {
                $errores[] = $mensaje;
            }
        }

        if ($d['correo'] !== '' && filter_var($d['correo'], FILTER_VALIDATE_EMAIL) === false) {
            $errores[] = 'El correo no tiene un formato válido.';
        }

        if ($d['tipo'] !== '' && TipoUsuario::tryFrom($d['tipo']) === null) {
            $errores[] = 'El tipo de usuario no es válido.';
        }

        $opciones = ['options' => ['min_range' => 1]];
        $participantes = filter_var($d['participantes'], FILTER_VALIDATE_INT, $opciones);
        $horas = filter_var($d['horas'], FILTER_VALIDATE_INT, $opciones);

        if ($d['participantes'] !== '' && $participantes === false) {
            $errores[] = 'Los participantes deben ser un número entero mayor que 0.';
        }

        if ($d['horas'] !== '' && $horas === false) {
            $errores[] = 'Las horas deben ser un número entero mayor que 0.';
        }

        $espacio = self::ESPACIOS[$d['espacio']] ?? null;

        if ($d['espacio'] !== '' && $espacio === null) {
            $errores[] = 'El espacio seleccionado no existe.';
        }

        if ($d['disciplina'] !== '' && !in_array($d['disciplina'], $this->disciplinas(), true)) {
            $errores[] = 'La disciplina seleccionada no está permitida.';
        } elseif ($espacio !== null && $d['disciplina'] !== '' && $espacio['disciplina'] !== $d['disciplina']) {
            $errores[] = sprintf('El espacio "%s" no es para %s.', $espacio['nombre'], $d['disciplina']);
        }

        if ($espacio !== null && $participantes !== false && $participantes > $espacio['capacidad']) {
            $errores[] = sprintf(
                'Los participantes (%d) superan la capacidad del espacio (%d).',
                $participantes,
                $espacio['capacidad']
            );
        }

        return $errores;
    }

    /**
     * @return array{subtotal: float, descuento: float, mantenimiento: float, total: float}
     */
    public function calcularCostos(float $costoHora, int $horas, TipoUsuario $tipo): array
    {
        $subtotal = $costoHora * $horas;
        $descuento = $subtotal * $tipo->descuento();
        $mantenimiento = ($horas >= 1 && $subtotal > 0) ? self::CARGO_MANTENIMIENTO : 0.0;
        $total = $subtotal - $descuento + $mantenimiento;

        return [
            'subtotal' => round($subtotal, 2),
            'descuento' => round($descuento, 2),
            'mantenimiento' => $mantenimiento,
            'total' => round($total, 2),
        ];
    }

    /**
     * @param array<string, mixed> $entrada normalmente $_POST
     * @throws DatosInvalidosException
     * @throws EspacioNoDisponibleException
     */
    public function crear(array $entrada): Reserva
    {
        $datos = $this->limpiar($entrada);
        $errores = $this->validar($datos);

        if ($errores !== []) {
            throw new DatosInvalidosException($errores);
        }

        $espacio = self::ESPACIOS[$datos['espacio']];

        if (!$espacio['disponible']) {
            throw EspacioNoDisponibleException::porEspacio($espacio['nombre']);
        }

        $tipo = TipoUsuario::from($datos['tipo']);
        $horas = (int) $datos['horas'];
        $costos = $this->calcularCostos($espacio['costo_hora'], $horas, $tipo);

        return new Reserva(
            nombre: $datos['nombre'],
            correo: $datos['correo'],
            tipo: $tipo,
            disciplina: $datos['disciplina'],
            espacio: $espacio['nombre'],
            participantes: (int) $datos['participantes'],
            horas: $horas,
            costoHora: $espacio['costo_hora'],
            subtotal: $costos['subtotal'],
            descuento: $costos['descuento'],
            mantenimiento: $costos['mantenimiento'],
            total: $costos['total'],
            fecha: date('d/m/Y H:i')
        );
    }
}
