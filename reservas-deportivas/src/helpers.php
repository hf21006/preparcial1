<?php

declare(strict_types=1);

/**
 * Escapa texto para imprimirlo de forma segura en HTML.
 */
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Da formato de dinero a un monto (parámetros con valor por defecto).
 */
function dinero(float $monto, string $simbolo = '$', int $decimales = 2): string
{
    return $simbolo . number_format($monto, $decimales);
}
