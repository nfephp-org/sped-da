<?php

namespace NFePHP\DA\Tests\CTe;

use NFePHP\DA\CTe\Dacte;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;

class DacteTest extends TestCase
{
    /**
     * No CT-e Substituto (tpCTe = 3) o quadro de detalhamento deve se referir ao
     * CT-e substituido, e nao ao CT-e anulado, que e o caso do tpCTe = 2
     */
    public function test_detalhamento_do_cte_substituido(): void
    {
        $dacte = new Dacte(file_get_contents(TEST_FIXTURES . 'xml/cte_substituto.xml'));
        $pdf = $dacte->render();

        $this->assertTrue(
            Utils::pdfContemTexto($pdf, 'DETALHAMENTO DO CT-E SUBSTITUÍDO'),
            'O quadro do CT-e substituido nao foi impresso'
        );
        $this->assertTrue(Utils::pdfContemTexto($pdf, 'CHAVE DO CT-E SUBSTITUÍDO'));
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, '41260111111111000191570010000000001000000019'),
            'A chave do CT-e substituido nao foi impressa'
        );
        $this->assertFalse(
            Utils::pdfContemTexto($pdf, 'ANULADO'),
            'O CT-e Substituto foi impresso como CT-e de Anulacao'
        );
    }
}
