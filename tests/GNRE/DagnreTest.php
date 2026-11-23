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

    /**
     * @return array<string, array{0: string}>
     */
    public function logoProvider(): array
    {
        $png = __DIR__ . '/../../examples/images/tulipas.png';
        return [
            'arquivo png' => [$png],
            'data uri' => ['data://text/plain;base64,' . base64_encode((string) file_get_contents($png))],
        ];
    }

    /**
     * @dataProvider logoProvider
     */
    public function testLogo(string $logo): void
    {
        $semLogo = (new Dagnre($this->fixture('retorno-resultado-v2.xml')))->render();
        $comLogo = (new Dagnre($this->fixture('retorno-resultado-v2.xml')))->render($logo);

        $this->assertStringStartsWith('%PDF', $comLogo);
        $this->assertGreaterThan(
            substr_count($semLogo, '/Subtype /Image'),
            substr_count($comLogo, '/Subtype /Image')
        );
    }

    public function testCreditos(): void
    {
        $dagnre = new Dagnre($this->fixture('retorno-resultado-v2.xml'));
        $dagnre->creditsIntegratorFooter('MINHA EMPRESA');

        $texto = $this->texto($dagnre->render());

        $this->assertStringContainsString('MINHA EMPRESA', $texto);
        $this->assertStringContainsString('Powered by NFePHP', $texto);
    }

    public function testI25VetorialTresParaUm(): void
    {
        $barras = Dagnre::barrasI25('85850000002430099992002801000000000000000000');

        $this->assertCount(4 + 22 * 10 + 3, $barras);
        $this->assertSame([[true, 1], [false, 1], [true, 1], [false, 1]], array_slice($barras, 0, 4));
        $this->assertSame([[true, 3], [false, 1], [true, 1]], array_slice($barras, -3));
        $larguras = array_unique(array_column($barras, 1));
        sort($larguras);
        $this->assertSame([1, 3], $larguras);
        $this->assertSame(4 + 22 * 18 + 5, array_sum(array_column($barras, 1)));
    }

    public function testI25RejeitaTamanhoImpar(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Dagnre::barrasI25('123');
    }

    public function testCodigoBarrasCom13mm(): void
    {
        $dagnre = new DagnreEspiao($this->fixture('retorno-resultado-v2.xml'));
        $dagnre->render();

        $this->assertSame(13.0, $dagnre->geometrias[0]['h']);
        $this->assertGreaterThanOrEqual(100.0, $dagnre->geometrias[0]['w']);
    }

    public function testChaveNfeCabeNaCelula(): void
    {
        $dagnre = new DagnreEspiao($this->fixture('retorno-resultado-v2.xml'));
        $dagnre->render();
        $chave = '35200111111111111111550400000096131957613855';
        $largura = 40.0;

        $fonte = $dagnre->espiarFonteQueCabe($chave, $largura, 8);
        $dagnre->pdf()->setFont('times', '', $fonte);

        $this->assertLessThan(8, $fonte);
        $this->assertLessThanOrEqual($largura, $dagnre->pdf()->getStringWidth($chave));
    }

    public function testTextosLongosNaoSobrepoem(): void
    {
        $dagnre = new DagnreEspiao($this->fixture('retorno-resultado-v2.xml'));
        $dagnre->render();
        $linhas = [
            'Convênio/Protocolo: ' . str_repeat('C', 30) . '   Produto: ' . str_repeat('PRODUTO LONGO ', 18),
            'Informações Complementares: ' . str_repeat('INFORMACAO COMPLEMENTAR ', 12),
            'Documento válido para pagamento até 28/01/2020',
        ];

        $distribuicao = $dagnre->espiarDistribuirLinhas($linhas, 120.0, 22.0);

        $this->assertCount(3, $distribuicao);
        $this->assertLessThanOrEqual(22.0 + 0.001, array_sum(array_column($distribuicao, 'altura')));
        foreach ($distribuicao as $item) {
            $dagnre->pdf()->setFont('times', '', $item['fonte']);
            $necessaria = $dagnre->pdf()->getNumLines(
                $item['texto'],
                120.0,
                ['font' => 'times', 'size' => $item['fonte'], 'style' => '']
            ) * $item['fonte'] * 0.3528 * 1.1;
            $this->assertLessThanOrEqual($item['altura'] + 0.001, $necessaria, $item['texto']);
        }
        $this->assertSame('Documento válido para pagamento até 28/01/2020', $distribuicao[2]['texto']);
    }

    public function testMensagemComSituacaoDoLote(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML($this->fixture('retorno-resultado-v2.xml'));
        $resultado = $dom->getElementsByTagNameNS('*', 'resultado')->item(0);
        $this->assertInstanceOf(DOMElement::class, $resultado);
        $this->assertNotNull($resultado->parentNode);
        $resultado->parentNode->removeChild($resultado);
        $codigo = $dom->getElementsByTagNameNS('*', 'codigo')->item(0);
        $this->assertInstanceOf(DOMElement::class, $codigo);
        $codigo->nodeValue = '401';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/401/');
        new Dagnre((string) $dom->saveXML());
    }

    public function testLogoLargaTemLarguraLimitada(): void
    {
        $imagem = imagecreatetruecolor(800, 100);
        $this->assertNotFalse($imagem);
        ob_start();
        imagepng($imagem);
        $png = (string) ob_get_clean();
        $dagnre = new DagnreEspiao($this->fixture('retorno-resultado-v2.xml'));

        $pdf = $dagnre->render('data://text/plain;base64,' . base64_encode($png));

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertLessThanOrEqual(30.0, $dagnre->espiarLarguraLogo(7.0));
        $this->assertGreaterThan(0.0, $dagnre->espiarLarguraLogo(7.0));
    }
}
