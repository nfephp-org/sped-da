<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\Danfe;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

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

    public function test_setar_limite_numero_duplicatas_com6(): void
    {
        $obj = new Danfe(file_get_contents(TEST_FIXTURES . 'xml/nfe.xml'));
        $obj->setLimiteQtdDuplicatasExibir(6);
        $pdf = $obj->render();
        file_put_contents(TEST_FIXTURES . 'pdf/nfe_duplicatas_limite_2.pdf', $pdf);
        $this->assertIsString($pdf);

        $parser = new Parser();
        $pdfDocument = $parser->parseContent($pdf);
        $text = $pdfDocument->getText();

        $this->assertStringContainsString(
            'Existem mais de 6 duplicatas registradas, portanto não serão exibidas, confira diretamente pelo XML',
            $text
        );
    }

    public function test_setar_limite_numero_duplicatas_com12(): void
    {
        $obj = new Danfe(file_get_contents(TEST_FIXTURES . 'xml/nfe_com_12_duplicatas.xml'));
        $obj->setLimiteQtdDuplicatasExibir(14);
        $pdf = $obj->render();
        file_put_contents(TEST_FIXTURES . 'pdf/nfe_duplicatas_limite_3.pdf', $pdf);
        $this->assertIsString($pdf);

        $parser = new Parser();
        $pdfDocument = $parser->parseContent($pdf);
        $text = $pdfDocument->getText();
        $normalizedText = preg_replace('/\s+/', ' ', $text); // converte tabs e múltiplos espaços em espaço único

        $this->assertStringContainsString('FATURA / DUPLICATA', $normalizedText);
        for ($i = 1; $i <= 12; $i++) {
            $expected = sprintf('Num. %03d', $i);
            $this->assertStringContainsString($expected, $normalizedText);
        }
    }
}
