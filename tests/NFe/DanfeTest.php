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
     * Na Nota de Débito (finNFe = 6) o DANFE deve informar o tipo do débito
     * (tpNFDebito) e os documentos referenciados pelos itens em
     * det/DFeReferenciado, agrupados por chave de acesso
     */
    public function test_nota_de_debito_imprime_finalidade_e_dfe_referenciados(): void
    {
        $danfe = new Danfe(file_get_contents(TEST_FIXTURES . 'xml/nfe_debito.xml'));
        $danfe->setGerarInformacoesAutomaticas(true);
        $texto = Utils::textoPdf($danfe->render());

        $this->assertStringContainsString('Nota de Débito - tpNFDebito 04: Multa e juros', $texto);
        $this->assertStringContainsString(
            'DFe Ref.: série:1 número:1234 emit:11.111.111/0001-91 em 05/2026',
            $texto
        );
        $this->assertStringContainsString('itens: 1, 2', $texto);
        $this->assertStringContainsString(
            'DFe Ref.: série:1 número:5678 emit:11.111.111/0001-91 em 06/2026',
            $texto
        );
        $this->assertStringContainsString('itens: 3', $texto);
    }

    /**
     * Sem setGerarInformacoesAutomaticas(true) o DANFE continua como antes
     */
    public function test_nota_de_debito_sem_informacoes_automaticas(): void
    {
        $danfe = new Danfe(file_get_contents(TEST_FIXTURES . 'xml/nfe_debito.xml'));
        $texto = Utils::textoPdf($danfe->render());

        $this->assertStringNotContainsString('Nota de Débito', $texto);
        $this->assertStringNotContainsString('DFe Ref.', $texto);
    }
}
