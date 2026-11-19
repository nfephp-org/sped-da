<?php

namespace NFePHP\DA\Tests;

use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\NFe\DanfeRtc;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

class DanfeRtcTest extends TestCase
{
    public function testRenderDanfeRtc()
    {
        $xml = file_get_contents($this->fixture());
        $pdf = (new DanfeRtc($xml))->render();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testDanfeAtualContinuaRenderizando()
    {
        $xml = file_get_contents($this->fixture());
        $pdf = (new Danfe($xml))->render();

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    /**
     * Simples Nacional: sem ICMS/IPI/IS no XML, o item mostra só IBS/CBS, com rótulo.
     */
    public function testItemSimplesMostraSoOQueExisteNoXml()
    {
        $danfe = $this->danfe('nfe_rtc_itens.xml');

        $this->assertSame([
            'bases' => ['IBS/CBS 10,00'],
            'aliquotas' => ['CBS 0,90% / IBS UF 0,10%', 'IBS MUN 0,00%'],
            'valores' => ['CBS 0,09 / IBS UF 0,01', 'IBS MUN 0,00'],
        ], $danfe->tributos(0));
    }

    /**
     * Com gRed a alíquota impressa é a efetiva (NT 2026.010, item 4.3).
     */
    public function testItemComReducaoMostraAliquotaEfetiva()
    {
        $danfe = $this->danfe('nfe_rtc_itens.xml');

        $this->assertSame(
            ['CBS 0,36% / IBS UF 0,04%', 'IBS MUN 0,00%'],
            $danfe->tributos(4)['aliquotas']
        );
    }

    /**
     * O par só divide a linha se couber na coluna em 6 pt: "IBS UF 0,04% / IBS MUN 0,00%" encolheria a fonte.
     */
    public function testParQueNaoCabeNaColunaVaiUmPorLinha()
    {
        $danfe = $this->danfe('nfe_rtc_completa.xml');

        $this->assertSame(
            ['ICMS 12,00% / CBS 0,36%', 'IBS UF 0,04%', 'IBS MUN 0,00%'],
            $danfe->tributos(1)['aliquotas']
        );
    }

    /**
     * O modelo da NT não tem a coluna do código, que o MOC não deixa suprimir: vai no início da descrição.
     */
    public function testCodigoDoProdutoNaDescricao()
    {
        $pdf = (new DanfeRtc(file_get_contents($this->fixture('nfe_rtc_itens.xml'))))->render();

        $this->assertStringContainsString('1564 - RTC TESTE CST000', (new Parser())->parseContent($pdf)->getText());
    }

    /**
     * Imune (CST 410) não tem gIBSCBS: nada impresso e nenhuma linha reservada.
     */
    public function testItemImuneNaoOcupaLinhasDeTributo()
    {
        $danfe = $this->danfe('nfe_rtc_itens.xml');

        $this->assertSame(['bases' => [], 'aliquotas' => [], 'valores' => []], $danfe->tributos(2));
    }

    /**
     * Seis itens cabem numa folha: a faixa só cresce com o que o item tem.
     */
    public function testNotaDeSeisItensCabeEmUmaFolha()
    {
        $pdf = (new DanfeRtc(file_get_contents($this->fixture('nfe_rtc_itens.xml'))))->render();

        $this->assertSame(1, preg_match_all('#/Type\s*/Page[^s]#', $pdf));
    }

    /**
     * Regime normal: ICMS e IPI entram nos pares do modelo (ICMS/CBS, IBS UF/IPI, IBS MUN/IS).
     */
    public function testItemRegimeNormalSegueOsParesDoModelo()
    {
        $danfe = $this->danfe('nfe_rtc_regime_normal.xml');

        $this->assertSame([
            'bases' => ['ICMS 10,00', 'IBS/CBS 10,00', 'IPI 10,00'],
            'aliquotas' => ['ICMS 20,00% / CBS 0,90%', 'IBS UF 0,10% / IPI 10,00%', 'IBS MUN 0,00%'],
            'valores' => ['ICMS 2,00 / CBS 0,09', 'IBS UF 0,01 / IPI 1,00', 'IBS MUN 0,00'],
        ], $danfe->tributos(0));
    }

    /**
     * Monofasia ad valorem (CST 620): base, alíquotas e valores vêm de gIBSCBSMono,
     * e os totais vão para o bloco IBS/CBS/IS (NT 2026.010, item 4.1).
     */
    public function testMonofasiaNoItemENoBlocoIbsCbs()
    {
        $danfe = $this->danfe('nfe_rtc_mono.xml');

        $this->assertSame([
            'bases' => ['MONO 620,00'],
            'aliquotas' => ['CBS 0,90% / IBS UF 0,10%', 'IBS MUN 0,00%'],
            'valores' => ['CBS 5,58 / IBS UF 0,62', 'IBS MUN 0,00'],
        ], $danfe->tributos(0));
        $this->assertSame([
            ['VALOR DO IBS MONOFÁSICO', '0,62'],
            ['VALOR DA CBS MONOFÁSICA', '5,58'],
        ], $danfe->mono());
        $this->assertSame([], $danfe->icms());
    }

    /**
     * Retido anteriormente: o XML só traz o valor retido, sem base nem alíquota.
     * gMono zerado nos totais não abre a linha da monofasia.
     */
    public function testMonofasiaRetidaAnteriormente()
    {
        $danfe = $this->danfe('nfe_rtc_mono_ret.xml');

        $this->assertSame([
            'bases' => [],
            'aliquotas' => [],
            'valores' => ['CBS RET ANT 2,15', 'IBS RET ANT 0,24'],
        ], $danfe->tributos(0));
        $this->assertSame([], $danfe->mono());
    }

    /**
     * Monofasia ad rem (combustíveis): base em quantidade e alíquota em R$ por unidade.
     * O par que não cabe na coluna vai um por linha, sem encolher a fonte.
     */
    public function testMonofasiaAdRem()
    {
        $danfe = $this->danfe('nfe_rtc_mono_adrem.xml');

        $this->assertSame([
            'bases' => ['MONO 1.000 L', 'RETEN 500 L'],
            'aliquotas' => ['CBS R$ 1,0530/L', 'IBS R$ 0,1170/L', 'CBS RETEN R$ 1,0530/L', 'IBS RETEN R$ 0,1170/L'],
            'valores' => [
                'CBS 1.053,00 / IBS 117,00',
                'CBS RETEN 526,50',
                'IBS RETEN 58,50',
                'CBS RET ANT 90,00',
                'IBS RET ANT 10,00',
            ],
        ], $danfe->tributos(0));
        $this->assertSame([
            ['VALOR DO IBS MONOFÁSICO', '117,00'],
            ['VALOR DA CBS MONOFÁSICA', '1.053,00'],
            ['VALOR DO IBS MONOFÁSICO POR RETENÇÃO', '58,50'],
            ['VALOR DA CBS MONOFÁSICA POR RETENÇÃO', '526,50'],
        ], $danfe->mono());
    }

    /**
     * Nota com todos os quadros: ICMS ST, FCP, DIFAL, IPI, IS, ISSQN, monofasia de ICMS e
     * de IBS/CBS com retenção, fatura, transporte e volumes, numa folha.
     */
    public function testNotaCompletaPreencheOsCamposCondicionados()
    {
        $danfe = $this->danfe('nfe_rtc_completa.xml');

        $this->assertSame([
            ['VALOR DO FCP', '2,40'],
            ['VALOR DO FCP RETIDO POR ST', '3,36'],
            ['VALOR DO DIFAL NA UF DE DESTINO', '42,00'],
            ['VALOR DO FCP NA UF DE DESTINO', '10,50'],
            ['BC DO ICMS MONOFÁSICO', '1.000'],
            ['VALOR DO ICMS MONOFÁSICO', '1.120,00'],
            ['BC DO ICMS MONOFÁSICO POR RETENÇÃO', '500'],
            ['VALOR DO ICMS MONOFÁSICO POR RETENÇÃO', '560,00'],
        ], $danfe->icms());
        $this->assertSame([
            ['VALOR DO IBS MONOFÁSICO', '6,00'],
            ['VALOR DA CBS MONOFÁSICA', '54,00'],
            ['VALOR DO IBS MONOFÁSICO POR RETENÇÃO', '3,00'],
            ['VALOR DA CBS MONOFÁSICA POR RETENÇÃO', '27,00'],
        ], $danfe->mono());
        $this->assertSame([
            'bases' => ['ICMS 525,00', 'IBS/CBS 500,00', 'IS 500,00', 'IPI 500,00'],
            'aliquotas' => ['ICMS 12,00% / CBS 0,90%', 'IBS UF 0,10% / IPI 10,00%', 'IBS MUN 0,00% / IS 5,00%'],
            'valores' => ['ICMS 63,00 / CBS 4,50', 'IBS UF 0,50 / IPI 50,00', 'IBS MUN 0,00 / IS 25,00'],
        ], $danfe->tributos(0));
        $this->assertSame([
            'bases' => ['MONO 6.000,00', 'RETEN 3.000,00'],
            'aliquotas' => ['CBS 0,90% / IBS UF 0,10%', 'IBS MUN 0,00%', 'CBS RETEN 0,90%', 'IBS RETEN 0,10%'],
            'valores' => ['CBS 54,00 / IBS UF 6,00', 'IBS MUN 0,00', 'CBS RETEN 27,00', 'IBS RETEN 3,00'],
        ], $danfe->tributos(2));

        $pdf = (new DanfeRtc(file_get_contents($this->fixture('nfe_rtc_completa.xml'))))->render();
        $this->assertSame(1, preg_match_all('#/Type\s*/Page[^s]#', $pdf));
    }

    /**
     * Paisagem (tpImp=2, leiaute alternativo da NT): os quadros não cabem com os itens na folha 1;
     * a grade vai inteira para a folha 2, sem nada desenhado por cima, e o ISSQN vem antes do transportador.
     */
    public function testPaisagemComTodosOsQuadrosLevaOsItensParaAFolhaSeguinte()
    {
        $pdf = (new DanfeRtc($this->paisagem('nfe_rtc_completa.xml')))->render();
        $folhas = (new Parser())->parseContent($pdf)->getPages();

        $this->assertCount(2, $folhas);
        $folha1 = $folhas[0]->getText();
        $this->assertStringNotContainsString('DADOS DOS PRODUTOS', $folha1);
        $this->assertLessThan(strpos($folha1, 'TRANSPORTADOR'), strpos($folha1, 'CÁLCULO DO ISSQN'));
        $this->assertStringContainsString('SERVICO DE INSTALACAO', $folhas[1]->getText());
    }

    /**
     * Paisagem: a grade da folha 1 desce até os dados adicionais (cinco dos seis itens) e não
     * reserva espaço para as formas de pagamento, que só aparecem com exibirTextoFatura.
     */
    public function testPaisagemAproveitaAFolhaUm()
    {
        $pdf = (new DanfeRtc($this->paisagem('nfe_rtc_itens.xml')))->render();
        $folhas = (new Parser())->parseContent($pdf)->getPages();

        $this->assertCount(2, $folhas);
        $this->assertStringContainsString('RTC TESTE CST515', $folhas[0]->getText());
        $this->assertStringContainsString('RTC TESTE CST830', $folhas[1]->getText());
    }

    /**
     * Mais de 7 duplicatas viram uma linha de aviso, mas a Danfe reserva 12 mm por grupo de 7:
     * a folha 1 usa a altura que sobra de fato, sem mandar o item sozinho para a folha 2.
     */
    public function testPaisagemComDuplicatasUsaAAlturaQueSobra()
    {
        $dups = '';
        for ($i = 1; $i <= 8; $i++) {
            $dups .= "<dup><nDup>00$i</nDup><dVenc>2026-12-0$i</dVenc><vDup>1.00</vDup></dup>";
        }
        $xml = str_replace(
            '<pag>',
            '<cobr><fat><nFat>1</nFat><vOrig>8.00</vOrig><vDesc>0.00</vDesc><vLiq>8.00</vLiq></fat>'
                . $dups . '</cobr><pag>',
            $this->paisagem('nfe_rtc_mono.xml')
        );
        $folhas = (new Parser())->parseContent((new DanfeRtc($xml))->render())->getPages();

        $this->assertCount(1, $folhas);
        $this->assertStringContainsString('RTC TESTE MONO01', $folhas[0]->getText());
    }

    /**
     * infAdProd longo (lista de IMEIs) quebra em linhas mantendo os 5 pt, em vez de encolher
     * a fonte até caber numa linha só.
     */
    public function testInfAdProdLongoQuebraEmLinhas()
    {
        $imeis = [];
        for ($i = 10; $i <= 23; $i++) {
            $imeis[] = '3512345000000' . $i;
        }
        $xml = preg_replace(
            '#<vItem>#',
            '<infAdProd>' . implode('; ', $imeis) . '</infAdProd><vItem>',
            file_get_contents($this->fixture('nfe_rtc_itens.xml')),
            1
        );
        $texto = (new Parser())->parseContent((new DanfeRtc($xml))->render())->getText();

        $linhas = array_values(array_filter(explode("\n", $texto), function ($linha) {
            return strpos($linha, '35123450000') !== false;
        }));
        $this->assertGreaterThan(1, count($linhas));
        $this->assertStringContainsString('351234500000010', $linhas[0]);
        $this->assertStringNotContainsString('351234500000023', $linhas[0]);
    }

    private function paisagem($nome)
    {
        return preg_replace('#<tpImp>\d</tpImp>#', '<tpImp>2</tpImp>', file_get_contents($this->fixture($nome)));
    }

    private function fixture($nome = 'nfe_rtc_minimo.xml')
    {
        return __DIR__ . '/fixtures/xml/' . $nome;
    }

    private function danfe($nome)
    {
        return new class (file_get_contents($this->fixture($nome))) extends DanfeRtc {
            public function tributos($i)
            {
                $this->render(); // os pares de tributo são medidos na fonte do PDF
                return $this->tributosItem($this->det->item($i));
            }

            public function mono()
            {
                return $this->camposMonoOpcionais();
            }

            public function icms()
            {
                return $this->camposIcmsOpcionais();
            }
        };
    }
}
