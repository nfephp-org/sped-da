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

    private function fixture()
    {
        return __DIR__ . '/fixtures/xml/nfe_rtc_minimo.xml';
    }
}
