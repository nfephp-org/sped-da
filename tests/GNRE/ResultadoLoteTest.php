<?php

namespace NFePHP\DA\Tests\GNRE;

use DOMDocument;
use DOMElement;
use NFePHP\DA\GNRE\ResultadoLote;
use PHPUnit\Framework\TestCase;
use stdClass;

class ResultadoLoteTest extends TestCase
{
    private const FIXTURES = TEST_FIXTURES . 'xml/gnre/';

    private function fixture(string $arquivo): string
    {
        return (string) file_get_contents(self::FIXTURES . $arquivo);
    }

    public function testParaStdComPrefixoEEnvelope(): void
    {
        $std = ResultadoLote::paraStd($this->fixture('retorno-resultado-v2.xml'));

        $this->assertSame('402', $std->situacaoProcess->codigo);
        $this->assertIsArray($std->resultado->guia);
        $this->assertCount(2, $std->resultado->guia);
        $this->assertSame('2.00', $std->resultado->guia[0]->attributes->versao);
    }

    public function testAtributoComTexto(): void
    {
        $item = ResultadoLote::paraStd($this->fixture('retorno-resultado-v2.xml'))->resultado->guia[1]->itensGNRE->item;

        $this->assertCount(7, $item->valor);
        $this->assertSame('12', $item->valor[1]->attributes->tipo);
        $this->assertSame('1.82', $item->valor[1]->value);
        $this->assertSame('22', $item->documentoOrigem->attributes->tipo);
    }

    public function testUmaGuiaViraObjeto(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML($this->fixture('retorno-resultado-v2.xml'));
        $segunda = $dom->getElementsByTagNameNS('*', 'guia')->item(1);
        $this->assertInstanceOf(DOMElement::class, $segunda);
        $this->assertNotNull($segunda->parentNode);
        $segunda->parentNode->removeChild($segunda);

        $std = ResultadoLote::paraStd((string) $dom->saveXML());

        $this->assertInstanceOf(stdClass::class, $std->resultado->guia);
    }

    public function testLerNormalizaAsGuias(): void
    {
        $resultado = ResultadoLote::ler($this->fixture('retorno-resultado-v2.xml'));

        $this->assertSame('402', $resultado->situacaoProcess->codigo);
        $this->assertCount(2, $resultado->guias);
        $this->assertSame('RS', $resultado->guias[0]->ufFavorecida);
        $this->assertSame('184,07', $resultado->guias[1]->valorPrincipal);
    }

    public function testLerResultadoTxtV1(): void
    {
        $resultado = ResultadoLote::ler($this->fixture('retorno-resultado-v1.xml'));

        $this->assertSame('403', $resultado->situacaoProcess->codigo);
        $this->assertCount(1, $resultado->guias);
        $this->assertSame('AC', $resultado->guias[0]->ufFavorecida);
        $this->assertSame('1', $resultado->guias[0]->situacaoGuia);
        $this->assertSame('205', $resultado->guias[0]->motivosRejeicao[0]->codigo);
    }

    public function testLerTxtV1(): void
    {
        $txt = (string) ResultadoLote::paraStd($this->fixture('retorno-resultado-v1.xml'))->resultado;

        $retorno = ResultadoLote::lerTxtV1($txt);

        $this->assertSame('0000070008', $retorno->cabecalho->numeroProtocoloLote);
        $this->assertSame('0001', $retorno->guias[0]->sequencialGuia);
        $this->assertSame('c02_receita', $retorno->rejeicoes[0]->nomeCampo);
        $this->assertSame('0.00', $retorno->guias[0]->valorPrincipal);
    }

    public function testNaoEResultado(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ResultadoLote::paraStd($this->fixture('retorno-recibo.xml'));
    }

    public function testNaoEXml(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ResultadoLote::paraStd('abc');
    }

    /**
     * Linha TXT 1.00 sintética: campos posicionados por CARACTERE (base 1)
     *
     * @param array<int, string> $campos início => conteúdo
     */
    private function linhaTxt(array $campos, int $tamanho = 1098): string
    {
        $chars = array_fill(0, $tamanho, ' ');
        foreach ($campos as $inicio => $conteudo) {
            foreach (preg_split('//u', $conteudo, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $i => $char) {
                $chars[$inicio - 1 + $i] = $char;
            }
        }
        return implode('', $chars);
    }

    public function testTxtComAcentoNaoDeslocaCampos(): void
    {
        $linha = $this->linhaTxt([
            1 => '1',
            2 => '0001',
            6 => '0',
            32 => 'JOSÉ DA CONCEIÇÃO',
            92 => 'AVENIDA SÃO JOÃO',
            919 => '000000000012345',
            979 => '858500000029430099992006280100962200000000000000',
            1027 => '85850000002430099992002801000000000000000000',
        ]);

        $guia = ResultadoLote::lerTxtV1($linha)->guias[0];

        $this->assertSame('JOSÉ DA CONCEIÇÃO', $guia->razaoSocialEmitente);
        $this->assertSame('AVENIDA SÃO JOÃO', $guia->enderecoEmitente);
        $this->assertSame('123.45', $guia->valorPrincipal);
        $this->assertSame('858500000029430099992006280100962200000000000000', $guia->representacaoNumerica);
        $this->assertSame('85850000002430099992002801000000000000000000', $guia->codigoBarras);
    }
}
