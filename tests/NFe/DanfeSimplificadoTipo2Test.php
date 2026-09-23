<?php

namespace NFePHP\DA\Tests\NFe;

use InvalidArgumentException;
use NFePHP\DA\NFe\DanfeSimplificadoTipo2;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

/**
 * Testes do DANFE Simplificado Tipo 2 (NF-e modelo 55, tpImp 6, leiaute da NT 2026.003).
 *
 * As fixtures em tests/fixtures/xml/nfe_danfe_simplificado_tipo2_*.xml seguem o leiaute de uma NF-e
 * autorizada em ambiente de homologação (MS), com dados de identificação fictícios e assinatura
 * substituída por marcadores.
 */
class DanfeSimplificadoTipo2Test extends TestCase
{
    public function test_imprimir_autorizada_em_homologacao(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe_danfe_simplificado_tipo2_autorizada.xml');
        $danfe = new DanfeSimplificadoTipo2($xml);

        $pdf = $danfe->render();
        file_put_contents(TEST_FIXTURES . 'pdf/nfe_danfe_simplificado_tipo2.pdf', $pdf);

        $this->assertIsString($pdf);

        $parser = new Parser();
        $document = $parser->parseContent($pdf);
        $this->assertCount(1, $document->getPages());

        $texto = $this->normaliza(Utils::textoPdf($pdf));
        foreach ([
            'DANFE Simplificado Tipo 2',
            'Qtde. total de itens',
            'Valor total R$',
            '55,96',
            'DINHEIRO',
            'Troco R$',
            '44,04',
            '(+) CBS R$',
            '0,50',
            '(+) IBS R$',
            '0,06',
            'CONSUMIDOR CNPJ: 98.765.432/0001-98',
            'Protocolo de autorização: 150260000000001',
            'EMITIDA EM AMBIENTE DE HOMOLOGAÇÃO - SEM VALOR FISCAL',
        ] as $esperado) {
            $this->assertStringContainsString($esperado, $texto, "esperava \"{$esperado}\" no texto do PDF");
        }

        $this->assertStringNotContainsString('(+) IS R$', $texto);
    }

    public function test_contingencia_sem_protocolo_imprime_duas_vias(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe_danfe_simplificado_tipo2_contingencia.xml');
        $danfe = new DanfeSimplificadoTipo2($xml);

        $pdf = $danfe->render();

        $parser = new Parser();
        $document = $parser->parseContent($pdf);
        $this->assertCount(2, $document->getPages());

        $texto = $this->normaliza(Utils::textoPdf($pdf));
        $this->assertStringContainsString('EMITIDA EM CONTINGÊNCIA', $texto);
        $this->assertStringContainsString('Pendente de autorização', $texto);
        $this->assertStringNotContainsString('Protocolo de autorização', $texto);
    }

    public function test_xml_sem_qrcode_imprime_sem_qrcode(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe_danfe_simplificado_tipo2_sem_qrcode.xml');
        $danfe = new DanfeSimplificadoTipo2($xml);

        $pdf = $danfe->render();

        $this->assertIsString($pdf);
        $this->assertStringContainsString('DANFE Simplificado Tipo 2', Utils::textoPdf($pdf));
    }

    public function test_trinta_itens_em_uma_pagina(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe_danfe_simplificado_tipo2_30_itens.xml');
        $danfe = new DanfeSimplificadoTipo2($xml);

        $pdf = $danfe->render();

        $parser = new Parser();
        $document = $parser->parseContent($pdf);
        $this->assertCount(1, $document->getPages());

        $texto = Utils::textoPdf($pdf);
        $this->assertStringContainsString('Qtde. total de itens', $texto);
        $this->assertStringContainsString('30', $texto);
        $this->assertStringContainsString('1.678,80', $texto);
    }

    public function test_desconto_e_acrescimo_imprimem_valor_a_pagar(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe_danfe_simplificado_tipo2_sem_ibscbs_com_desconto.xml');
        $danfe = new DanfeSimplificadoTipo2($xml);

        $texto = $this->normaliza(Utils::textoPdf($danfe->render()));

        foreach (['Acréscimos R$', '2,00', 'Desconto R$', '-5,96', 'Valor a Pagar R$', '52,00', '8,00'] as $esperado) {
            $this->assertStringContainsString($esperado, $texto, "esperava \"{$esperado}\" no texto do PDF");
        }
        $this->assertStringNotContainsString('(+) CBS R$', $texto);
    }

    public function test_venda_nao_presencial_imprime_endereco_de_entrega(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe_danfe_simplificado_tipo2_nao_presencial_entrega.xml');
        $danfe = new DanfeSimplificadoTipo2($xml);

        $texto = $this->normaliza(Utils::textoPdf($danfe->render()));

        $this->assertStringContainsString('AVENIDA DAS NACOES, 1500', $texto);
    }

    public function test_recusa_nfce_modelo_65(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfce_mod65.xml');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('modelo 55, tpImp 6');

        new DanfeSimplificadoTipo2($xml);
    }

    public function test_recusa_nfe_com_tpimp_diferente_de_6(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe_danfe_simplificado_tipo2_tpimp1.xml');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('modelo 55, tpImp 6');

        new DanfeSimplificadoTipo2($xml);
    }

    public function test_recusa_xml_vazio(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DanfeSimplificadoTipo2('');
    }

    public function test_papel_abaixo_de_56mm_e_recusado(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe_danfe_simplificado_tipo2_autorizada.xml');
        $danfe = new DanfeSimplificadoTipo2($xml);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('56 mm');

        $danfe->setPaperWidth(54);
    }

    public function test_papel_de_56mm_e_aceito(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe_danfe_simplificado_tipo2_autorizada.xml');
        $danfe = new DanfeSimplificadoTipo2($xml);

        $danfe->setPaperWidth(56);
        $pdf = $danfe->render();

        $this->assertIsString($pdf);
    }

    private function normaliza(string $texto): string
    {
        return preg_replace('/\s+/u', ' ', $texto);
    }
}
