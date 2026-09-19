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

    public function test_gerar_nfe_sem_dhsaient(): void
    {
        $obj = new Danfe(file_get_contents(TEST_FIXTURES . 'xml/nfe.xml'));
        $pdf = $obj->render();
        file_put_contents(TEST_FIXTURES . 'pdf/nfe_sem_dhsaient.pdf', $pdf);
        $this->assertIsString($pdf);

        // Validar que a hora não aparece no PDF quando dhSaiEnt não existe
        $parser = new Parser();
        $parsedPdf = $parser->parseContent($pdf);
        $text = $parsedPdf->getText();

        // A hora deve estar em branco (não deve conter padrão de hora HH:MM:SS após "HORA DA SAÍDA")
        $this->assertDoesNotMatchRegularExpression('/HORA DA SAÍDA\/ENTRADA\s+\d{2}:\d{2}:\d{2}/', $text);
    }

    public function test_gerar_nfe_com_dhsaient(): void
    {
        $obj = new Danfe(file_get_contents(TEST_FIXTURES . 'xml/nfe_com_dhsaient.xml'));
        $pdf = $obj->render();
        file_put_contents(TEST_FIXTURES . 'pdf/nfe_com_dhsaient.pdf', $pdf);
        $this->assertIsString($pdf);

        // Validar que a hora aparece no PDF quando dhSaiEnt existe
        $parser = new Parser();
        $parsedPdf = $parser->parseContent($pdf);
        $text = $parsedPdf->getText();

        // A hora deve conter a hora 15:30:00 extraída de dhSaiEnt
        $this->assertStringContainsString('15:30:00', $text);
    }
}
