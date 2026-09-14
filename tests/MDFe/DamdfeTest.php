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
        $this->assertTrue(Utils::pdfContemTexto($pdf, '53250509231544000139550010000568821095071500'));
    }

    /**
     * O responsável pelo seguro é obrigatório no modal rodoviário desde a lei 11.442/07
     * e deve ser impresso nas duas formas previstas pelo schema
     */
    public function test_seguro_responsavel(): void
    {
        $damdfe = new Damdfe(file_get_contents(TEST_FIXTURES . 'xml/mdfe_seguro.xml'));
        $pdf = $damdfe->render();
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, 'Responsável pelo Seguro: Emitente do MDF-e'),
            'O responsável pelo seguro não foi impresso para o emitente'
        );
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, 'Responsável pelo Seguro: Contratante'),
            'O responsável pelo seguro não foi impresso para o contratante'
        );
    }

    /**
     * O grupo infSeg e o número da apólice são opcionais no schema, e a ausência deles
     * não deve interromper a geração do documento
     */
    public function test_seguro_sem_dados_opcionais(): void
    {
        $damdfe = new Damdfe(file_get_contents(TEST_FIXTURES . 'xml/mdfe_seguro.xml'));
        $pdf = $damdfe->render();
        /* o fixture traz um terceiro seguro apenas com o responsável, e os dois primeiros
           continuam impressos por completo */
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, 'SEGURADORA ALFA'),
            'O nome da seguradora não foi impresso'
        );
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, '2355523235325002'),
            'O número da apólice não foi impresso'
        );
    }

    /**
     * Os seguros são agrupados pelo responsável, que aparece uma única vez como título
     * do bloco, e a lista é distribuída em dois pares de colunas
     */
    public function test_seguro_agrupado_por_responsavel(): void
    {
        $damdfe = new Damdfe(file_get_contents(TEST_FIXTURES . 'xml/mdfe_seguro.xml'));
        $pdf = $damdfe->render();
        /* o fixture traz dois seguros do emitente e um do contratante: o título do
           emitente sai uma vez só, e não uma vez por seguro */
        $this->assertSame(
            1,
            substr_count(Utils::textoPdf($pdf), 'Responsável pelo Seguro: Emitente do MDF-e'),
            'O responsável do grupo foi repetido a cada seguro'
        );
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, 'Seguradora'),
            'O cabeçalho da coluna da seguradora não foi impresso'
        );
        $this->assertTrue(
            Utils::pdfContemTexto($pdf, 'Número da Apólice'),
            'O cabeçalho da coluna da apólice não foi impresso'
        );
    }

    /**
     * O quadro de Observação é impresso em posição fixa na folha, então os seguros que não
     * cabem acima dele seguem em folha de continuação, em vez de se sobreporem
     */
    public function test_seguro_continuacao(): void
    {
        $damdfe = new Damdfe(file_get_contents(TEST_FIXTURES . 'xml/mdfe_seguro_continuacao.xml'));
        $pdf = $damdfe->render();
        $texto = Utils::textoPdf($pdf);
        $this->assertStringContainsString(
            'SEGURO DA CARGA - CONTINUACÃO',
            $texto,
            'A folha de continuação do seguro não foi gerada'
        );
        /* o fixture traz sessenta seguros: nenhum pode se perder na divisão entre as folhas */
        for ($i = 1; $i <= 60; $i++) {
            $this->assertStringContainsString(
                "SEGURADORA NUMERO {$i}",
                $texto,
                "O seguro {$i} não foi impresso em nenhuma folha"
            );
        }
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
