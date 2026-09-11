<?php

namespace NFePHP\DA\Tests\MDFe;

use NFePHP\DA\MDFe\Damdfe;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;

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
        $damdfe->setExibirDocumentosVinculados(false);
        $pdf = $damdfe->render();
        $this->assertFalse(Utils::pdfContemTexto($pdf, '53250509231544000139550010000568821095071500'));
    }

    /**
     * Os documentos da MDF-e (NF-e, CT-e e outras MDF-e) devem aparecer quando a MDF-e foi emitida em contingência
     */
    public function test_incluir_documentos_emitida_em_contingencia(): void
    {
        $damdfe = new Damdfe(file_get_contents(TEST_FIXTURES . 'xml/mdfe_contingencia.xml'));
        $pdf = $damdfe->render();
        $this->assertTrue(Utils::pdfContemTexto($pdf, '53250509231544000139550010000568821095071500'));
    }

    /**
     * O responsável pelo pagamento do Vale-Pedágio é informado por CNPJ ou por CPF,
     * conforme o xs:choice do schema, e ambos devem ser impressos
     */
    public function test_vale_pedagio_responsavel_pelo_pagamento(): void
    {
        $damdfe = new Damdfe(file_get_contents(TEST_FIXTURES . 'xml/mdfe_vale_pedagio.xml'));
        $pdf = $damdfe->render();
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, '05405941000129'),
            'O CNPJ do responsável pelo pagamento não foi impresso'
        );
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, '11122233396'),
            'O CPF do responsável pelo pagamento não foi impresso'
        );
    }

    /**
     * O valor do Vale-Pedágio é obrigatório no schema e deve ser impresso formatado.
     * O segundo disp do fixture não traz o nCompra, que é opcional, para garantir que
     * a ausência do campo não interrompe a impressão
     */
    public function test_vale_pedagio_valor(): void
    {
        $damdfe = new Damdfe(file_get_contents(TEST_FIXTURES . 'xml/mdfe_vale_pedagio_valor.xml'));
        $pdf = $damdfe->render();
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, 'Valor R$'),
            'A coluna do valor do Vale-Pedágio não foi impressa'
        );
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, '150,75'),
            'O valor do Vale-Pedágio não foi impresso'
        );
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, '1.234,50'),
            'O valor do Vale-Pedágio não foi impresso com o separador de milhar'
        );
    }
}
