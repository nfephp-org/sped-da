<?php

namespace NFePHP\DA\NFe\Traits;

use NFePHP\DA\Legacy\Pdf;

/**
 * Bloco Informações sobre impostos aproximados
 */
trait TraitBlocoIX
{
    protected function blocoIX($y)
    {
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $valor = $this->getTagValue($this->ICMSTot, 'vTotTrib');
        $trib = !empty($valor) ? number_format((float) $valor, 2, ',', '.') : '-----';
        $texto = "Tributos totais Incidentes (Lei Federal 12.741/2012): R$ {$trib}";
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint,
            $this->bloco9H,
            $texto,
            $aFont,
            'T',
            'C',
            false,
            '',
            true
        );
        if ($this->paperwidth < 70) {
            $fsize = 5;
            $aFont = ['font'=> $this->fontePadrao, 'size' => 5, 'style' => ''];
        }
        $this->pdf->textBox(
            $this->margem,
            $y+3,
            $this->wPrint,
            $this->bloco9H-4,
            str_replace(";", "\n", $this->infCpl),
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );
        return $y + 3;
    }

    /**
     * Calcula a altura do bloco IX
     * Depende do conteudo de infCpl
     *
     * @return int
     */
    protected function calculateHeighBlokIX()
    {
        $papel = [$this->paperwidth, 100];
        $wprint = $this->paperwidth - (2 * $this->margem);
        $logoAlign = 'L';
        $orientacao = 'P';
        $pdf = new Pdf($orientacao, 'mm', $papel);
        $fsize = 7;
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        if ($this->paperwidth < 70) {
            $fsize = 5;
            $aFont = ['font'=> $this->fontePadrao, 'size' => 5, 'style' => ''];
        }
        $linhas = str_replace(';', "\n", $this->infCpl);
        $hfont = (imagefontheight($fsize)/72)*13;
        $numlinhas = $pdf->getNumLines($linhas, $wprint, $aFont);
        if (!empty($this->textoExtra)) {
            $linhas = str_replace(';', "\n", $this->textoExtra);
            $hfont = (imagefontheight($fsize)/72)*13;
            $numlinhas += $pdf->getNumLines($linhas, $wprint, $aFont);
        }
        return (int) ($numlinhas * $hfont) + 2;
    }
}
