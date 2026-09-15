<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\NFe\DanfeFontSizes;
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

    public function test_gerar_nfe_com_tamanhos_de_fonte_customizados(): void
    {
        $obj = new Danfe(file_get_contents(TEST_FIXTURES . 'xml/nfe.xml'));
        $obj->setFontSizes(DanfeFontSizes::fromArray([
            'cabecalho' => [
                'titulo_danfe' => 12,
                'valor' => 9,
            ],
            'destinatario' => [
                'rotulo' => 5,
                'valor' => 8,
            ],
            'itens' => [
                'cabecalho' => 5,
                'dados' => 6,
            ],
            'dados_adicionais' => [
                'texto' => 5,
            ],
        ]));

        $pdf = $obj->render();

        $this->assertIsString($pdf);
    }
}
