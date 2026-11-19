<?php

namespace NFePHP\DA\Tests;

use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\NFe\DanfeRtc;
use NFePHP\DA\NFe\DanfeRtcPdf;
use PHPUnit\Framework\TestCase;

class DanfeRtcPdfTest extends TestCase
{
    /**
     * O texto entra 0,5 mm e sobe 0,6 mm em relação ao Pdf; a borda fica no lugar.
     */
    public function testAfastaOTextoDaBorda()
    {
        $fonte = ['font' => 'Arial', 'size' => 10, 'style' => 'B'];
        $original = $this->posicaoDoTexto(new Pdf(), $fonte);
        $rtc = $this->posicaoDoTexto(new DanfeRtcPdf(), $fonte);
        $k = 72 / 25.4;

        $this->assertEqualsWithDelta($original[0] - 0.5 * $k, $rtc[0], 0.02);
        $this->assertEqualsWithDelta($original[1] + 0.6 * $k, $rtc[1], 0.02);
    }

    public function testSoADanfeRtcUsaODanfeRtcPdf()
    {
        $xml = file_get_contents(__DIR__ . '/fixtures/xml/nfe_rtc_minimo.xml');
        $rtc = new class ($xml) extends DanfeRtc {
            public function pdf()
            {
                return $this->pdf;
            }
        };
        $danfe = new class ($xml) extends Danfe {
            public function pdf()
            {
                return $this->pdf;
            }
        };
        $rtc->render();
        $danfe->render();

        $this->assertInstanceOf(DanfeRtcPdf::class, $rtc->pdf());
        $this->assertSame(Pdf::class, get_class($danfe->pdf()));
    }

    /**
     * @param Pdf $pdf
     * @param array $fonte
     * @return float[] x e y do texto, em pontos
     */
    private function posicaoDoTexto(Pdf $pdf, array $fonte)
    {
        $pdf->addPage();
        $pdf->textBox(10, 10, 40, 7, '6.917,56', $fonte, 'B', 'R', 1, '');
        preg_match('/BT ([\d.]+) ([\d.]+) Td/', $pdf->pages[1], $m);

        return [(float) $m[1], (float) $m[2]];
    }
}
