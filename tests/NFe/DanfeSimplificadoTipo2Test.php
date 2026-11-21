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
        // chave com tpEmis 9 (contingência offline) na 35ª posição
        $this->assertStringContainsString('5026 0912 3456 7800 0195 5500 6000 9999 1497 1082 3233', $texto);
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

    /**
     * "CARTÃO DA LOJA/OUTROS CREDIÁRIOS" (tPag 05) quebra em duas linhas na meia largura do rótulo: a
     * linha seguinte (Troco) e o resto do bloco têm de descer junto, sem desenhar por cima.
     */
    public function test_forma_de_pagamento_longa_nao_sobrepoe_o_troco(): void
    {
        foreach ([80, 58] as $largura) {
            $xml = str_replace(
                '<pag><detPag><tPag>01</tPag><vPag>100.00</vPag></detPag><vTroco>44.04</vTroco></pag>',
                '<pag><detPag><tPag>05</tPag><vPag>30.00</vPag></detPag>'
                    . '<detPag><tPag>05</tPag><vPag>25.96</vPag></detPag></pag>',
                file_get_contents(TEST_FIXTURES . 'xml/nfe_danfe_simplificado_tipo2_autorizada.xml')
            );
            $danfe = new DanfeSimplificadoTipo2($xml);
            $danfe->setPaperWidth($largura);
            $pdf = $danfe->render();

            // Linha de base (y do PDF, de baixo para cima) de cada `BT x y Td (texto) Tj` do stream.
            $trechos = $this->trechosDeTexto($pdf);
            $baseY = function (string $padrao) use ($trechos, $largura): array {
                $ys = [];
                foreach ($trechos as [$y, $texto]) {
                    if (preg_match($padrao, $texto)) {
                        $ys[] = $y;
                    }
                }
                $this->assertNotEmpty($ys, "não achei {$padrao} no PDF de {$largura} mm");

                return $ys;
            };

            // 5 pt ≈ 1,8 mm: menos que isso entre duas linhas de base de fonte 7 é sobreposição.
            $this->assertGreaterThanOrEqual(5, min($baseY('/CREDI/')) - max($baseY('/^Troco/')), "{$largura} mm: Troco sobre a forma de pagamento");
            $this->assertGreaterThanOrEqual(5, min($baseY('/^Troco/')) - max($baseY('/CBS R/')), "{$largura} mm: CBS sobre o Troco");
            $this->assertGreaterThanOrEqual(5, min($baseY('/IBS R/')) - max($baseY('/^Consulte/')), "{$largura} mm: bloco estourou sobre a chave");
        }
    }

    /** [y, texto] de cada trecho escrito no PDF, lidos dos streams (comprimidos ou não). */
    private function trechosDeTexto(string $pdf): array
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
        $trechos = [];
        foreach ($streams[1] as $stream) {
            $conteudo = @gzuncompress($stream);
            if ($conteudo === false) {
                $conteudo = $stream;
            }
            preg_match_all('/BT [\d.]+ ([\d.]+) Td \((.*?)\) Tj ET/s', $conteudo, $m, PREG_SET_ORDER);
            foreach ($m as $t) {
                $trechos[] = [(float) $t[1], $t[2]];
            }
        }

        return $trechos;
    }

    private function normaliza(string $texto): string
    {
        return preg_replace('/\s+/u', ' ', $texto);
    }
}
