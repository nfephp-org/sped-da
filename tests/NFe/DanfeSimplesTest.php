<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\DanfeSimples;
use PHPUnit\Framework\TestCase;

class DanfeSimplesTest extends TestCase
{
    public function testImprimeCepDoDestinatarioSemLocalDeEntrega(): void
    {
        $obj = new DanfeSimples(file_get_contents(TEST_FIXTURES . 'xml/nfe.xml'));
        $pdf = $obj->render();
        $this->assertStringContainsString('CEP 96880000', $this->textoDoPdf($pdf));
    }

    /**
     * O conteudo das paginas sai comprimido: descomprime cada stream para ler o texto.
     */
    private function textoDoPdf(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
        $texto = '';
        foreach ($streams[1] as $stream) {
            $conteudo = @gzuncompress($stream);
            $texto .= $conteudo === false ? $stream : $conteudo;
        }
        return $texto;
    }
}
