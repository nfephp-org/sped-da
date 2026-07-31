<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\Danfe;
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

    public function test_incluir_dados_ibscbstot_no_pdf(): void
    {
        $xmlPath = TEST_FIXTURES . 'xml/nfe-ibscbstot.xml';
        $xmlContent = file_get_contents($xmlPath);

        $danfe = new Danfe($xmlContent);
        $danfe->exibirPIS = true;
        $danfe->exibirIcmsInterestadual = true;
        $danfe->exibirValorTributos = true;
        $danfe->exibirTextoFatura = false;
        $danfe->exibirNumeroItemPedido = false;
        $danfe->descProdInfoComplemento = false;
        $danfe->exibirEmailDestinatario = true;

        $pdfContent = $danfe->render();

        $tempPdfPath = sys_get_temp_dir() . '/test_ibscbstot.pdf';
        file_put_contents($tempPdfPath, $pdfContent);

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($tempPdfPath);
        $text = $pdf->getText();

        $this->assertStringContainsString('BASE DE CÁLC. IBS', $text);
        $this->assertStringContainsString('VALOR TOTAL IBS', $text);
        $this->assertStringContainsString('BASE DE CÁLC. CBS', $text);
        $this->assertStringContainsString('VALOR TOTAL CBS', $text);
        $this->assertStringContainsString('V.T. IMPOSTO SELETIVO', $text);

        $this->assertStringContainsString('800,00', $text);
        $this->assertStringContainsString('4,00', $text);
        $this->assertStringContainsString('801,00', $text);
        $this->assertStringContainsString('7,20', $text);
        $this->assertStringContainsString('5,20', $text);

        unlink($tempPdfPath);
    }    
}
