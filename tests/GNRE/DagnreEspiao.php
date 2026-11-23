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
        $this->geometrias[] = ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h];
        parent::codigoBarras($codigo, $x, $y, $w, $h);
    }

    protected function qrCode(string $payload, float $x, float $y, float $tamanho): void
    {
        $this->qrcodes[] = $payload;
        parent::qrCode($payload, $x, $y, $tamanho);
    }

    /**
     * @var array<int, array{x: float, y: float, w: float, h: float}>
     */
    public $geometrias = [];

    /**
     * @return int tamanho de fonte que faz o texto caber na largura
     */
    public function espiarFonteQueCabe(string $texto, float $largura, int $maximo): int
    {
        return $this->fonteQueCabe($texto, $largura, $maximo);
    }

    /**
     * @param array<int, string> $linhas
     * @return array<int, array{texto: string, fonte: int, altura: float}>
     */
    public function espiarDistribuirLinhas(array $linhas, float $largura, float $altura): array
    {
        return $this->distribuirLinhas($linhas, $largura, $altura);
    }

    public function espiarLarguraLogo(float $altura): float
    {
        return $this->larguraLogo($altura);
    }

    public function pdf(): \NFePHP\DA\Legacy\Pdf
    {
        return $this->pdf;
    }
}
