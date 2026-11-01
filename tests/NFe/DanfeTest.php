<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;

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
     * O qVol informado como zero (comum em operacoes de importacao) deve
     * ser impresso no campo QUANTIDADE da secao de transportes
     */
    public function test_quantidade_zero_do_transporte_eh_impressa(): void
    {
        $xml = str_replace(
            '<qVol>1015</qVol>',
            '<qVol>0</qVol>',
            file_get_contents(TEST_FIXTURES . 'xml/nfe.xml')
        );
        $obj = new Danfe($xml);
        $pdf = $obj->render();
        $texto = Utils::textoPdf($pdf);
        $inicio = strpos($texto, 'QUANTIDADE') + strlen('QUANTIDADE');
        $fim = strpos($texto, 'ESPECIE', $inicio);
        if ($fim === false) {
            $fim = strpos($texto, 'ESP\xC3\x89CIE', $inicio);
        }
        $trecho = trim(substr($texto, $inicio, $fim - $inicio));
        $this->assertSame(
            '0',
            $trecho,
            'O valor zero do campo QUANTIDADE deve ser impresso, nao omitido'
        );
    }
}
