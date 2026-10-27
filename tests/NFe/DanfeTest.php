<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\Danfe;
use PHPUnit\Framework\TestCase;
use NFePHP\DA\Tests\Utils;

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

    public function testDeveExibirNfeReferenciadaPorRefNFe(): void
    {
        $xml = file_get_contents(
            TEST_FIXTURES . 'xml/nfe-ref-nfe.xml'
        );

        $danfe = new Danfe($xml);
        $danfe->setGerarInformacoesAutomaticas(true);

        $pdf = $danfe->render();

        $this->assertTrue(
            Utils::pdfContemTexto(
                $pdf,
                'NFe Ref.: série:1 número:123456'
            )
        );
    }

    public function testDeveExibirNfeReferenciadaPorDFeReferenciado(): void
    {
        $xml = file_get_contents(
            TEST_FIXTURES . 'xml/nfe-dfe-referenciado.xml'
        );

        $danfe = new Danfe($xml);
        $danfe->setGerarInformacoesAutomaticas(true);

        $pdf = $danfe->render();

        $this->assertTrue(
            Utils::pdfContemTexto(
                $pdf,
                'NFe Ref.: série:1 número:123456'
            )
        );
    }

    public function testNaoDeveDuplicarNfeReferenciadaPorDFeReferenciado(): void
    {
        $xml = file_get_contents(
            TEST_FIXTURES . 'xml/nfe-dfe-referenciado.xml'
        );

        $danfe = new Danfe($xml);
        $danfe->setGerarInformacoesAutomaticas(true);

        $pdf = $danfe->render();

        $texto = Utils::textoPdf($pdf);

        $this->assertSame(
            1,
            substr_count($texto, 'NFe Ref.')
        );
    }
}
