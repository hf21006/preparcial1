<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Enums\TipoUsuario;
use App\Exceptions\DatosInvalidosException;
use App\Exceptions\EspacioNoDisponibleException;
use App\Models\Reserva;
use App\Services\ReservaServicio;

// El autoload va antes de session_start() para poder guardar objetos en la sesión.
session_start();
$_SESSION['reservas'] ??= [];

$servicio = new ReservaServicio();
$errores = [];
$comprobante = null;
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    try {
        $comprobante = $servicio->crear($_POST);
        $_SESSION['reservas'][] = $comprobante;
        $old = [];
    } catch (DatosInvalidosException $ex) {
        $errores = $ex->errores();
    } catch (EspacioNoDisponibleException $ex) {
        $errores = [$ex->getMessage()];
    }
}

/** @var Reserva[] $reservas */
$reservas = $_SESSION['reservas'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservas deportivas</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; color: #222; }
        h1, h2 { color: #1f3a5f; }
        form, .caja { background: #f4f6f9; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        label { display: block; margin-top: .8rem; font-weight: bold; }
        input, select { width: 100%; padding: .45rem; margin-top: .25rem; box-sizing: border-box; }
        button { margin-top: 1rem; padding: .6rem 1.4rem; background: #1f3a5f; color: #fff; border: 0; border-radius: 4px; cursor: pointer; }
        .errores { background: #fdecea; border-left: 4px solid #c0392b; padding: .5rem 1rem; margin-bottom: 1rem; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: .4rem .6rem; text-align: left; }
        th { background: #1f3a5f; color: #fff; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Reservas del centro deportivo universitario</h1>

    <?php if ($errores !== []): ?>
        <div class="errores">
            <strong>No se pudo registrar la reserva:</strong>
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($old['nombre'] ?? '') ?>">

        <label for="correo">Correo</label>
        <input type="text" id="correo" name="correo" value="<?= e($old['correo'] ?? '') ?>">

        <label for="tipo">Tipo de usuario</label>
        <select id="tipo" name="tipo">
            <option value="">-- Seleccione --</option>
            <?php foreach (TipoUsuario::cases() as $tipo): ?>
                <option value="<?= e($tipo->value) ?>" <?= ($old['tipo'] ?? '') === $tipo->value ? 'selected' : '' ?>>
                    <?= e($tipo->etiqueta()) ?> (<?= e((string) ($tipo->descuento() * 100)) ?>% de descuento)
                </option>
            <?php endforeach; ?>
        </select>

        <label for="disciplina">Disciplina</label>
        <select id="disciplina" name="disciplina">
            <option value="">-- Seleccione --</option>
            <?php foreach ($servicio->disciplinas() as $disciplina): ?>
                <option value="<?= e($disciplina) ?>" <?= ($old['disciplina'] ?? '') === $disciplina ? 'selected' : '' ?>>
                    <?= e($disciplina) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="espacio">Espacio</label>
        <select id="espacio" name="espacio">
            <option value="">-- Seleccione --</option>
            <?php foreach (ReservaServicio::ESPACIOS as $clave => $espacio): ?>
                <option value="<?= e($clave) ?>" <?= ($old['espacio'] ?? '') === $clave ? 'selected' : '' ?>>
                    <?= e($espacio['nombre']) ?> · <?= e($espacio['disciplina']) ?>
                    · cap. <?= e((string) $espacio['capacidad']) ?>
                    · <?= e(dinero($espacio['costo_hora'])) ?>/h
                    <?= $espacio['disponible'] ? '' : '· NO DISPONIBLE' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="participantes">Participantes</label>
        <input type="text" id="participantes" name="participantes" value="<?= e($old['participantes'] ?? '') ?>">

        <label for="horas">Horas</label>
        <input type="text" id="horas" name="horas" value="<?= e($old['horas'] ?? '') ?>">

        <button type="submit">Reservar</button>
    </form>

    <?php if ($comprobante !== null): ?>
        <div class="caja">
            <h2>Comprobante de reserva</h2>
            <p><strong>Fecha:</strong> <?= e($comprobante->fecha) ?></p>
            <p><strong>Nombre:</strong> <?= e($comprobante->nombre) ?></p>
            <p><strong>Correo:</strong> <?= e($comprobante->correo) ?></p>
            <p><strong>Tipo de usuario:</strong> <?= e($comprobante->tipo->etiqueta()) ?></p>
            <p><strong>Disciplina:</strong> <?= e($comprobante->disciplina) ?></p>
            <p><strong>Espacio:</strong> <?= e($comprobante->espacio) ?></p>
            <p><strong>Participantes:</strong> <?= e((string) $comprobante->participantes) ?></p>
            <p><strong>Horas:</strong> <?= e((string) $comprobante->horas) ?></p>
            <hr>
            <p><strong>Costo por hora:</strong> <?= e(dinero($comprobante->costoHora)) ?></p>
            <p><strong>Subtotal:</strong> <?= e(dinero($comprobante->subtotal)) ?></p>
            <p><strong>Descuento (<?= e((string) ($comprobante->tipo->descuento() * 100)) ?>%):</strong>
                -<?= e(dinero($comprobante->descuento)) ?></p>
            <p><strong>Cargo de mantenimiento:</strong> <?= e(dinero($comprobante->mantenimiento)) ?></p>
            <p class="total">Total a pagar: <?= e(dinero($comprobante->total)) ?></p>
        </div>
    <?php endif; ?>

    <h2>Reservas registradas</h2>
    <?php if ($reservas === []): ?>
        <p>Aún no hay reservas.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Nombre</th><th>Tipo</th><th>Espacio</th>
                    <th>Horas</th><th>Participantes</th><th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $i => $r): ?>
                    <tr>
                        <td><?= e((string) ($i + 1)) ?></td>
                        <td><?= e($r->nombre) ?></td>
                        <td><?= e($r->tipo->etiqueta()) ?></td>
                        <td><?= e($r->espacio) ?></td>
                        <td><?= e((string) $r->horas) ?></td>
                        <td><?= e((string) $r->participantes) ?></td>
                        <td><?= e(dinero($r->total)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
