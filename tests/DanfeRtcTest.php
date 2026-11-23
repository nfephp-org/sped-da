<?php

namespace NFePHP\DA\Tests;

use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\NFe\DanfeRtc;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

class DanfeRtcTest extends TestCase
{
    public function testRenderDanfeRtc()
    {
        $xml = file_get_contents($this->fixture());
        $pdf = (new DanfeRtc($xml))->render();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testDanfeAtualContinuaRenderizando()
    {
        $xml = file_get_contents($this->fixture());
        $pdf = (new Danfe($xml))->render();

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function testIdentificaBasesAliquotasEValoresNoPdf()
    {
        $texto = $this->textoPdf(file_get_contents($this->fixture()));

        foreach (['ICMS 20,00', 'IBS/CBS 20,00', 'IS 20,00', 'IPI 20,00',
            'ICMS 18,00% / CBS 0,90%', 'IBS UF 0,10% / IPI 5,00%',
            'IBS MUN 0,00% / IS 1,00%', 'ICMS 3,60 / CBS 0,18',
            'IBS UF 0,02 / IPI 1,00', 'IBS MUN 0,00 / IS 0,20'] as $campo) {
            $this->assertStringContainsString($campo, $texto);
        }
    }

    public function testIdentificaAliquotasEfetivasSemImprimirAliquotasCheias()
    {
        $xml = file_get_contents($this->fixture());
        $xml = str_replace(
            '<pIBSUF>0.10</pIBSUF>',
            '<pIBSUF>0.10</pIBSUF><gRed><pRedAliq>60.00</pRedAliq>'
                . '<pAliqEfet>0.04</pAliqEfet></gRed>',
            $xml
        );
        $xml = str_replace(
            '<pCBS>0.90</pCBS>',
            '<pCBS>0.90</pCBS><gRed><pRedAliq>60.00</pRedAliq>'
                . '<pAliqEfet>0.36</pAliqEfet></gRed>',
            $xml
        );
        $texto = $this->textoPdf($xml);

        $this->assertStringContainsString('ICMS 18,00% / CBS 0,36%', $texto);
        $this->assertStringContainsString('IBS UF 0,04% / IPI 5,00%', $texto);
        $this->assertStringNotContainsString('CBS 0,90%', $texto);
        $this->assertStringNotContainsString('IBS UF 0,10%', $texto);
    }

    public function testTributosAusentesNaoCriamValoresNemSeparadores()
    {
        $dom = new \DOMDocument();
        $dom->loadXML(file_get_contents($this->fixture()));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('n', 'http://www.portalfiscal.inf.br/nfe');
        foreach ($xpath->query('//n:det/n:imposto/n:ICMS | //n:det/n:imposto/n:IPI'
            . ' | //n:det/n:imposto/n:IS | //n:det//n:gIBSMun') as $node) {
            $node->parentNode->removeChild($node);
        }
        $texto = $this->textoPdf($dom->saveXML());

        $this->assertStringContainsString('IBS/CBS 20,00', $texto);
        $this->assertStringContainsString('CBS 0,90%', $texto);
        $this->assertStringContainsString('IBS UF 0,10%', $texto);
        foreach (['ICMS 0,00', 'IPI 0,00', 'IS 0,00', 'IBS MUN 0,00',
            '/ CBS 0,90%', 'IBS UF 0,10% /'] as $campo) {
            $this->assertStringNotContainsString($campo, $texto);
        }
    }

    public function testMantemIdentificacaoDosTributosEmTodasAsPaginas()
    {
        $dom = new \DOMDocument();
        $dom->loadXML(file_get_contents($this->fixture()));
        $det = $dom->getElementsByTagName('det')->item(0);
        for ($i = 2; $i <= 30; $i++) {
            $copia = $det->cloneNode(true);
            $copia->setAttribute('nItem', (string) $i);
            $copia->getElementsByTagName('xProd')->item(0)->nodeValue = 'PRODUTO RTC ' . $i;
            $det->parentNode->insertBefore($copia, $dom->getElementsByTagName('total')->item(0));
        }
        $pdf = (new DanfeRtc($dom->saveXML()))->render();
        $documento = (new Parser())->parseContent($pdf);
        $this->assertGreaterThan(1, count($documento->getPages()));
        $texto = preg_replace('/\s+/', ' ', $documento->getText());
        $this->assertSame(30, substr_count($texto, 'ICMS 18,00% / CBS 0,90%'));
        $this->assertSame(30, substr_count($texto, 'IBS UF 0,02 / IPI 1,00'));
        $this->assertStringContainsString('PRODUTO RTC 30', $texto);
    }

    private function textoPdf($xml)
    {
        $pdf = (new DanfeRtc($xml))->render();

        return preg_replace('/\s+/', ' ', (new Parser())->parseContent($pdf)->getText());
    }

    private function fixture()
    {
        return __DIR__ . '/fixtures/xml/nfe_rtc_minimo.xml';
    }
}
