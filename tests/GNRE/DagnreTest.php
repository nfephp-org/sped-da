<?php

namespace NFePHP\DA\Tests\GNRE;

use DOMDocument;
use DOMElement;
use NFePHP\DA\GNRE\Dagnre;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

class DagnreTest extends TestCase
{
    private const FIXTURES = TEST_FIXTURES . 'xml/gnre/';

    private function fixture(string $arquivo): string
    {
        return (string) file_get_contents(self::FIXTURES . $arquivo);
    }

    /**
     * Resultado v2 com alterações por guia (índice => [tag => valor])
     *
     * @param array<int, array<string, string>> $alteracoes
     */
    private function resultado(array $alteracoes = []): string
    {
        $dom = new DOMDocument();
        $dom->loadXML($this->fixture('retorno-resultado-v2.xml'));
        $guias = $dom->getElementsByTagNameNS('*', 'guia');
        foreach ($alteracoes as $indice => $tags) {
            $guia = $guias->item($indice);
            $this->assertInstanceOf(DOMElement::class, $guia);
            foreach ($tags as $tag => $valor) {
                $no = $guia->getElementsByTagNameNS('*', $tag)->item(0);
                if (!$no instanceof DOMElement) {
                    $no = $dom->createElementNS('http://www.gnre.pe.gov.br', 'ns1:' . $tag);
                    $guia->appendChild($no);
                }
                $no->nodeValue = $valor;
            }
        }
        return (string) $dom->saveXML();
    }

    private function texto(string $pdf): string
    {
        return (new Parser())->parseContent($pdf)->getText();
    }

    private function paginas(string $pdf): int
    {
        return count((new Parser())->parseContent($pdf)->getPages());
    }

    public function testPdfDoResultadoV2(): void
    {
        $pdf = (new Dagnre($this->fixture('retorno-resultado-v2.xml')))->render();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(2, $this->paginas($pdf));
        $texto = $this->texto($pdf);
        $this->assertStringContainsString('GNRE', $texto);
        $this->assertStringContainsString('via - Banco', $texto);
        $this->assertStringContainsString('via - Contribuinte/Fisco', $texto);
        $this->assertStringContainsString('TESTES NF-E 4.00', $texto);
        $this->assertStringContainsString('85850000002-9 43009999200-6 28010096220-0 00000000000-0', $texto);
        $this->assertStringContainsString('243,00', $texto);
    }

    public function testCodigoBarrasEhI25(): void
    {
        $dagnre = new DagnreEspiao($this->fixture('retorno-resultado-v2.xml'));
        $dagnre->render();

        $this->assertCount(6, $dagnre->codigos);
        $this->assertSame('I25', $dagnre->codigos[0]['tipo']);
        $this->assertSame('85850000002430099992002801000000000000000000', $dagnre->codigos[0]['codigo']);
    }

    public function testQrCodePix(): void
    {
        $xml = $this->resultado([0 => ['qrcodePayload' => '00020126580014br.gov.bcb.pix0136teste']]);
        $dagnre = new DagnreEspiao($xml);

        $texto = $this->texto($dagnre->render());

        $this->assertStringContainsString('Pagamento via PIX', $texto);
        $this->assertCount(3, $dagnre->qrcodes);
    }

    public function testSomenteRejeitadas(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/nenhuma guia/');
        new Dagnre($this->resultado([0 => ['situacaoGuia' => '1'], 1 => ['situacaoGuia' => '1']]));
    }

    public function testGuiasNaoImpressas(): void
    {
        $dagnre = new Dagnre($this->resultado([1 => ['situacaoGuia' => '2']]));

        $pdf = $dagnre->render();

        $this->assertSame(1, $this->paginas($pdf));
        $this->assertCount(1, $dagnre->getGuias());
        $this->assertCount(1, $dagnre->getGuiasNaoImpressas());
        $this->assertSame('2', $dagnre->getGuiasNaoImpressas()[0]->situacaoGuia);
    }

    public function testCodigoBarrasInvalido(): void
    {
        $dagnre = new DagnreEspiao($this->resultado([0 => ['codigoBarras' => '1234567890']]));

        $texto = $this->texto($dagnre->render());

        $this->assertStringContainsString('digo de barras indispon', $texto);
        $this->assertCount(3, $dagnre->codigos);
    }

    public function testResultadoV1TxtSemGuiaValida(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Dagnre($this->fixture('retorno-resultado-v1.xml'));
    }

    public function testResultadoV1Txt(): void
    {
        $xml = (string) preg_replace('/100011AC/', '100010AC', $this->fixture('retorno-resultado-v1.xml'), 1);

        $pdf = (new Dagnre($xml))->render();

        $this->assertSame(1, $this->paginas($pdf));
        $this->assertStringContainsString('TESTE RAZAO SOCIAL', $this->texto($pdf));
    }

    public function testNaoEResultado(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Dagnre($this->fixture('retorno-recibo.xml'));
    }

    public function testCreditos(): void
    {
        $dagnre = new Dagnre($this->fixture('retorno-resultado-v2.xml'));
        $dagnre->creditsIntegratorFooter('MINHA EMPRESA');

        $texto = $this->texto($dagnre->render());

        $this->assertStringContainsString('MINHA EMPRESA', $texto);
        $this->assertStringContainsString('Powered by NFePHP', $texto);
    }
}
