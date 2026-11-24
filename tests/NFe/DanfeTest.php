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
}
