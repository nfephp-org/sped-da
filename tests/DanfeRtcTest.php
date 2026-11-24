<?php

namespace NFePHP\DA\Tests;

use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\NFe\DanfeRtc;
use PHPUnit\Framework\TestCase;

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

    /**
     * pAliqEfet é 3v2-4: até 4 casas, mínimo 2 (0,1000 → 0,10).
     */
    public function testAliquotaEfetivaComQuatroCasas()
    {
        $xml = file_get_contents(__DIR__ . '/fixtures/xml/nfe_rtc_aliquota_quatro_casas.xml');
        $danfe = new DanfeRtc($xml);
        $ref = new \ReflectionObject($danfe);
        $textoAliquota = $ref->getMethod('textoAliquota');
        $textoAliquota->setAccessible(true);

        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $det = $doc->getElementsByTagName('det')->item(4);
        $gCbs = $det->getElementsByTagName('gCBS')->item(0);
        $gIbsUf = $det->getElementsByTagName('gIBSUF')->item(0);
        $gIbsUfCheia = $doc->getElementsByTagName('det')->item(0)
            ->getElementsByTagName('gIBSUF')->item(0);

        $this->assertSame('0,3575', $textoAliquota->invoke($danfe, $gCbs, 'pCBS'));
        $this->assertSame('0,0423', $textoAliquota->invoke($danfe, $gIbsUf, 'pIBSUF'));
        $this->assertSame('0,10', $textoAliquota->invoke($danfe, $gIbsUfCheia, 'pIBSUF'));
    }

    private function fixture()
    {
        return __DIR__ . '/fixtures/xml/nfe_rtc_minimo.xml';
    }
}
