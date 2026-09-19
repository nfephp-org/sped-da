<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;

class DanfeTest extends TestCase
{
    public function test_gerar_nfe_linha_continua(): void
    {
        $obj = new Danfe(file_get_contents(TEST_FIXTURES . 'xml/nfe.xml'));
        $obj->setUsarLinhaTracejadaSeparacaoItens(false);
        $pdf = $obj->render();
        file_put_contents(TEST_FIXTURES . 'pdf/nfe_linhas.pdf', $pdf);
        $this->assertIsString($pdf);
    }

    public function test_gerar_nfe_com_totais_ibscbs(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe.xml');
        $xml = str_replace(
            "                </ICMSTot>\n            </total>",
            "                </ICMSTot>\n"
            . "                <IBSCBSTot>\n"
            . "                    <vBCIBSCBS>247.14</vBCIBSCBS>\n"
            . "                    <gIBS>\n"
            . "                        <vIBS>0.17</vIBS>\n"
            . "                    </gIBS>\n"
            . "                    <gCBS>\n"
            . "                        <vCBS>1.38</vCBS>\n"
            . "                    </gCBS>\n"
            . "                </IBSCBSTot>\n"
            . "            </total>",
            $xml
        );

        $pdf = (new Danfe($xml))->render();

        $this->assertTrue(Utils::pdfContemTexto($pdf, 'VALOR TOTAL IBS'));
        $this->assertTrue(Utils::pdfContemTexto($pdf, 'VALOR TOTAL CBS'));
        $this->assertTrue(Utils::pdfContemTexto($pdf, '0,17'));
        $this->assertTrue(Utils::pdfContemTexto($pdf, '1,38'));
    }
}
