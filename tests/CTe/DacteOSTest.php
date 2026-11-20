<?php

namespace NFePHP\DA\Tests\CTe;

use NFePHP\DA\CTe\DacteOS;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;

class DacteOSTest extends TestCase
{
    /**
     * O grupo infTribFed e opcional no CT-e OS, e sem ele o DACTE OS deve ser gerado
     * com os tributos federais zerados
     */
    public function test_renderiza_sem_o_grupo_inftribfed(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/cteos_sem_inftribfed.xml');
        $this->assertStringNotContainsString('infTribFed', $xml);

        $pdf = (new DacteOS($xml))->render();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertTrue(Utils::pdfContemTexto($pdf, '0,00'));
    }

    /**
     * Cada tributo federal dentro de infTribFed tambem e opcional: o que falta sai zerado
     * e o que existe e impresso
     */
    public function test_renderiza_com_inftribfed_parcial(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/cteos_sem_inftribfed.xml');
        $xml = str_replace(
            '</imp>',
            '<infTribFed><vIR>52.50</vIR></infTribFed></imp>',
            $xml
        );

        $pdf = (new DacteOS($xml))->render();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertTrue(Utils::pdfContemTexto($pdf, '52,50'));
    }
}
