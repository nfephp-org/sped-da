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

    /**
     * Mais de 7 duplicatas imprimem uma linha de aviso, mas a estimativa reservava 12 mm por
     * grupo de 7: em paisagem o 15º item ia sozinho para a folha 2. A folha 1 usa a altura real.
     */
    public function test_paisagem_com_duplicatas_usa_a_altura_real_da_folha_1(): void
    {
        $xml = preg_replace(
            '#<tpImp>\d</tpImp>#',
            '<tpImp>2</tpImp>',
            file_get_contents(TEST_FIXTURES . 'xml/nfe_rtc_itens.xml')
        );
        preg_match('#<det nItem="1">.*?</det>#s', $xml, $item);
        $extras = '';
        for ($i = 7; $i <= 15; $i++) {
            $extras .= str_replace('nItem="1"', 'nItem="' . $i . '"', $item[0]);
        }
        $dups = '';
        for ($i = 1; $i <= 8; $i++) {
            $dups .= "<dup><nDup>00$i</nDup><dVenc>2026-12-0$i</dVenc><vDup>1.00</vDup></dup>";
        }
        $xml = preg_replace('#(<det nItem="6">.*?</det>)#s', '$1' . $extras, $xml);
        $xml = str_replace(
            '<pag>',
            '<cobr><fat><nFat>1</nFat><vOrig>8.00</vOrig><vDesc>0.00</vDesc><vLiq>8.00</vLiq></fat>'
                . $dups . '</cobr><pag>',
            $xml
        );

        $folhas = (new Parser())->parseContent((new Danfe($xml))->render())->getPages();

        $this->assertCount(1, $folhas);
        $this->assertSame(15, preg_match_all('/RTC TESTE CST/', $folhas[0]->getText()));
    }

    public function orientacoes(): array
    {
        return ['retrato' => ['1'], 'paisagem' => ['2']];
    }

    /**
     * Na Danfe o quadro do ISSQN fica entre a grade de itens e os dados adicionais, que ficam
     * presos ao pé da folha. A medição da folha 1 precisa reservar esse quadro: sem a reserva,
     * a grade descia até os dados adicionais e o ISSQN era desenhado por cima deles.
     *
     * @dataProvider orientacoes
     */
    public function test_issqn_fica_acima_dos_dados_adicionais(string $tpImp): void
    {
        // nfe_rtc_completa tem ISSQNtot com vServ; a Danfe ignora os grupos da reforma.
        $xml = preg_replace(
            '#<tpImp>\d</tpImp>#',
            "<tpImp>$tpImp</tpImp>",
            file_get_contents(TEST_FIXTURES . 'xml/nfe_rtc_completa.xml')
        );
        $danfe = new class ($xml) extends Danfe {
            /** @var float|null fim do quadro do ISSQN na folha 1, em mm a partir do topo */
            public $fimIssqn;
            /** @var float|null início dos dados adicionais na folha 1, em mm a partir do topo */
            public $inicioDadosAdicionais;

            protected function issqn($x, $y)
            {
                return $this->fimIssqn = parent::issqn($x, $y);
            }

            protected function dadosAdicionais($x, $y, $h)
            {
                // Mesma posição que dadosAdicionais() usa: presa ao pé da folha.
                $this->inicioDadosAdicionais = $this->maxH - (7 + $h);
                return parent::dadosAdicionais($x, $y, $h);
            }
        };

        // A nota tem uma folha; os valores gravados são os da última montagem (monta() pode montar duas vezes).
        $danfe->render();

        $this->assertNotNull($danfe->fimIssqn);
        $this->assertLessThanOrEqual($danfe->inicioDadosAdicionais, $danfe->fimIssqn);
    }
}
