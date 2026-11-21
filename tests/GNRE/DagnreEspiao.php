<?php

namespace NFePHP\DA\Tests\GNRE;

use NFePHP\DA\GNRE\Dagnre;

/**
 * Registra os códigos de barras e QR Codes desenhados
 */
class DagnreEspiao extends Dagnre
{
    /**
     * @var array<int, array{tipo: string, codigo: string}>
     */
    public $codigos = [];
    /**
     * @var array<int, string>
     */
    public $qrcodes = [];

    protected function codigoBarras(string $codigo, float $x, float $y, float $w, float $h): void
    {
        $this->codigos[] = ['tipo' => self::TIPO_CODIGO_BARRAS, 'codigo' => $codigo];
        parent::codigoBarras($codigo, $x, $y, $w, $h);
    }

    protected function qrCode(string $payload, float $x, float $y, float $tamanho): void
    {
        $this->qrcodes[] = $payload;
        parent::qrCode($payload, $x, $y, $tamanho);
    }
}
