<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\DanfeFontSizes;
use PHPUnit\Framework\TestCase;

class DanfeFontSizesTest extends TestCase
{
    public function testRetornaTamanhoCustomizadoPorCaminho(): void
    {
        $fontSizes = DanfeFontSizes::fromArray([
            'itens' => [
                'dados' => 6,
            ],
        ]);

        $this->assertSame(6, $fontSizes->get('itens.dados', 7));
    }

    public function testRetornaTamanhoPadraoQuandoCaminhoNaoFoiCustomizado(): void
    {
        $fontSizes = new DanfeFontSizes();

        $this->assertSame(7, $fontSizes->get('itens.dados', 7));
    }

    public function testIgnoraTamanhosInvalidos(): void
    {
        $fontSizes = DanfeFontSizes::fromArray([
            'cabecalho' => [
                'titulo_danfe' => 0,
                'numero' => 'texto',
            ],
        ]);

        $this->assertSame(14, $fontSizes->get('cabecalho.titulo_danfe', 14));
        $this->assertSame(10, $fontSizes->get('cabecalho.numero', 10));
    }
}
