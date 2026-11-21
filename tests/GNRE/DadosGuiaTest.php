<?php

namespace NFePHP\DA\Tests\GNRE;

use NFePHP\DA\GNRE\ResultadoLote;
use NFePHP\DA\GNRE\DadosGuia;
use PHPUnit\Framework\TestCase;
use stdClass;

class DadosGuiaTest extends TestCase
{
    private const FIXTURES = TEST_FIXTURES . 'xml/gnre/';

    /**
     * @return array<int, stdClass>
     */
    private function guiasV2(): array
    {
        $std = ResultadoLote::paraStd((string) file_get_contents(self::FIXTURES . 'retorno-resultado-v2.xml'));
        return $std->resultado->guia;
    }

    public function testGuia200Simples(): void
    {
        $guia = DadosGuia::deResultado($this->guiasV2()[0]);

        $this->assertSame('2.00', $guia->versao);
        $this->assertSame('0', $guia->situacaoGuia);
        $this->assertSame('RS', $guia->ufFavorecida);
        $this->assertSame('100099', $guia->receita);
        $this->assertSame('35200111111111111111550400000096111125788316', $guia->documentoOrigem);
        $this->assertSame('11.111.111/1111-11', $guia->emitenteDocumento);
        $this->assertSame('TESTES NF-E 4.00', $guia->emitenteRazaoSocial);
        $this->assertSame('22.222.222/2222-22', $guia->destinatarioDocumento);
        $this->assertSame('3333333333', $guia->destinatarioIE);
        $this->assertSame('28/01/2020', $guia->dataVencimento);
        $this->assertSame('28/01/2020', $guia->dataLimitePagamento);
        $this->assertSame('243,00', $guia->valorPrincipal);
        $this->assertSame('', $guia->juros);
        $this->assertSame('243,00', $guia->valorTotal);
        $this->assertSame('0096220013500000', $guia->numeroControle);
        $this->assertSame('858500000029430099992006280100962200000000000000', $guia->linhaDigitavel);
        $this->assertSame('85850000002430099992002801000000000000000000', $guia->codigoBarras);
        $this->assertSame('1', $guia->identificadorGuia);
    }

    public function testGuia200ComFcp(): void
    {
        $guia = DadosGuia::deResultado($this->guiasV2()[1]);

        $this->assertSame('184,07', $guia->valorPrincipal);
        $this->assertSame('8,00', $guia->atualizacaoMonetaria);
        $this->assertSame('5,11', $guia->juros);
        $this->assertSame('5,11', $guia->multa);
        $this->assertSame('182,25', $guia->valorTotal);
    }

    public function testGuia100NoResultado(): void
    {
        $origem = (object) [
            'attributes' => (object) ['versao' => '1.00'],
            'situacaoGuia' => '0',
            'c01_UfFavorecida' => 'SP',
            'c02_receita' => '100099',
            'c27_tipoIdentificacaoEmitente' => '1',
            'c03_idContribuinteEmitente' => (object) ['CNPJ' => '06157250000116'],
            'c04_docOrigem' => '5656',
            'c06_valorPrincipal' => '10.00',
            'c07_atualizacaoMonetaria' => '1.00',
            'c08_juros' => '0.50',
            'c09_multa' => '0.20',
            'c10_valorTotal' => '11.70',
            'c14_dataVencimento' => '2026-12-15',
            'c16_razaoSocialEmitente' => 'X',
            'c29_dataLimitePagamento' => '2026-12-20',
            'c30_nossoNumero' => '123',
            'c05_referencia' => (object) ['mes' => '11', 'ano' => '2026'],
            'linhaDigitavel' => '858500000029430099992006280100962200000000000000',
            'codigoBarras' => '85850000002430099992002801000000000000000000',
        ];

        $guia = DadosGuia::deResultado($origem);

        $this->assertSame('1.00', $guia->versao);
        $this->assertSame('SP', $guia->ufFavorecida);
        $this->assertSame('100099', $guia->receita);
        $this->assertSame('06.157.250/0001-16', $guia->emitenteDocumento);
        $this->assertSame('5656', $guia->documentoOrigem);
        $this->assertSame('10,00', $guia->valorPrincipal);
        $this->assertSame('1,00', $guia->atualizacaoMonetaria);
        $this->assertSame('0,50', $guia->juros);
        $this->assertSame('0,20', $guia->multa);
        $this->assertSame('11,70', $guia->valorTotal);
        $this->assertSame('15/12/2026', $guia->dataVencimento);
        $this->assertSame('20/12/2026', $guia->dataLimitePagamento);
        $this->assertSame('123', $guia->numeroControle);
        $this->assertSame('11/2026', $guia->periodoReferencia);
        $this->assertSame('85850000002430099992002801000000000000000000', $guia->codigoBarras);
    }

    public function testDeTxtV1(): void
    {
        $std = ResultadoLote::paraStd((string) file_get_contents(self::FIXTURES . 'retorno-resultado-v1.xml'));
        $txt = ResultadoLote::lerTxtV1((string) $std->resultado);

        $guia = DadosGuia::deTxtV1($txt->guias[0]);

        $this->assertSame('1.00', $guia->versao);
        $this->assertSame('1', $guia->situacaoGuia);
        $this->assertSame('AC', $guia->ufFavorecida);
        $this->assertSame('123456', $guia->receita);
        $this->assertSame('TESTE RAZAO SOCIAL', $guia->emitenteRazaoSocial);
        $this->assertSame('10/10/2014', $guia->dataVencimento);
        $this->assertSame('11/2014', $guia->periodoReferencia);
        $this->assertSame('005', $guia->parcela);
    }

    public function testFormatarLinhaDigitavel(): void
    {
        $this->assertSame(
            '85850000002-9 43009999200-6 28010096220-0 00000000000-0',
            DadosGuia::formatarLinhaDigitavel('858500000029430099992006280100962200000000000000')
        );
        $this->assertSame('12345', DadosGuia::formatarLinhaDigitavel('12345'));
    }

    public function testFormatarValor(): void
    {
        $this->assertSame('1.234,50', DadosGuia::formatarValor('1234.5'));
        $this->assertSame('', DadosGuia::formatarValor('0'));
        $this->assertSame('', DadosGuia::formatarValor(''));
    }

    public function testFormatarDocumento(): void
    {
        $this->assertSame('06.157.250/0001-16', DadosGuia::formatarDocumento('06157250000116'));
        $this->assertSame('904.839.260-86', DadosGuia::formatarDocumento('90483926086'));
        $this->assertSame('ABC', DadosGuia::formatarDocumento('ABC'));
    }

    public function testInformacoesComplementares(): void
    {
        $guia = $this->guiasV2()[0];
        $guia->informacoesComplementares = (object) ['informacao' => ['A', 'B']];
        $this->assertSame("A\nB", DadosGuia::deResultado($guia)->informacoesComplementares);

        $guia->informacoesComplementares = (object) ['informacao' => 'A'];
        $this->assertSame('A', DadosGuia::deResultado($guia)->informacoesComplementares);
    }
}
