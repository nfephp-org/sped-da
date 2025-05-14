<?php

namespace NFePHP\DA\Tests\MDFe;

use NFePHP\DA\MDFe\Damdfe;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

class DamdfeTest extends TestCase
{
    public function test_imprimir_damdfe(): void
    {
        $pathXml = TEST_FIXTURES . 'xml' . DIRECTORY_SEPARATOR . 'mdfe_modelo_nao_valido.xml';
        $pathPdf = TEST_FIXTURES . 'pdf' . DIRECTORY_SEPARATOR;
        $pdfName = 'mdfe_modelo_nao_valido.pdf';

        if (file_exists($pathPdf . $pdfName)) {
            unlink($pathPdf . $pdfName);
        }

        $xmlFile = file_get_contents($pathXml);

        $damdfe = new Damdfe($xmlFile, 'P', 'A4', '', 'F', $pathPdf);
        $pdfContent = $damdfe->render();

        if ($pdfContent) {
            file_put_contents($pathPdf . $pdfName, $pdfContent);
        }

        $this->assertFileExists($pathPdf . $pdfName);
    }

    /**
     * Os documentos da MDF-e (NF-e, CT-e e outras MDF-e) não devem aparecer quando a MDF-e foi autorizada
     */
    public function test_nao_incluir_documentos(): void
    {
        $damdfe = new Damdfe(file_get_contents(TEST_FIXTURES . 'xml/mdfe.xml'));
        $pdf = $damdfe->render();
        $this->assertFalse($this->pdfContemTexto($pdf, '53250509231544000139550010000568821095071500'));
    }

    /**
     * Os documentos da MDF-e (NF-e, CT-e e outras MDF-e) devem aparecer quando a MDF-e foi emitida em contingência
     */
    public function test_incluir_documentos_emitida_em_contingencia(): void
    {
        $damdfe = new Damdfe(file_get_contents(TEST_FIXTURES . 'xml/mdfe_contingencia.xml'));
        $pdf = $damdfe->render();
        $this->assertTrue($this->pdfContemTexto($pdf, '53250509231544000139550010000568821095071500'));
    }

    private function pdfContemTexto(string $conteudoPdf, string $textoProcurado): bool
    {
        $parser = new Parser();
        $pdf = $parser->parseContent($conteudoPdf);

        $texto = $pdf->getText();

        return strpos($texto, $textoProcurado) !== false;
    }
}
