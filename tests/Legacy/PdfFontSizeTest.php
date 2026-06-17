<?php

namespace NFePHP\DA\Tests\Legacy;

use NFePHP\DA\Legacy\Pdf;
use PHPUnit\Framework\TestCase;

class PdfFontSizeTest extends TestCase
{
    public function testAplicaEscalaAoTamanhoDaFonte(): void
    {
        $pdf = new Pdf();
        $pdf->setFontSizeScale(0.8);

        $pdf->setFont('times', '', 10);

        $this->assertSame(8.0, $pdf->fontSizePt);
    }

    public function testAplicaTamanhoMinimoDeFonte(): void
    {
        $pdf = new Pdf();
        $pdf->setFontSizeScale(0.5, 6);

        $pdf->setFont('times', '', 8);

        $this->assertSame(6.0, $pdf->fontSizePt);
    }

    public function testSubstituiTamanhoDaFontePorMapa(): void
    {
        $pdf = new Pdf();
        $pdf->setFontSizeMap([
            10 => 9,
            '5.7' => 5,
        ]);

        $pdf->setFont('times', '', 10);
        $this->assertSame(9.0, $pdf->fontSizePt);

        $pdf->setFont('times', '', 5.7);
        $this->assertSame(5.0, $pdf->fontSizePt);
    }
}
