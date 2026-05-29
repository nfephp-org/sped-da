<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco Informações sobre impostos aproximados
 */
trait TraitBlocoX
{
    protected function blocoX($y)
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'I'];
        if ($this->paperwidth < 70) {
            $aFont = ['font' => $this->fontePadrao, 'size' => 4, 'style' => 'I'];
        }
        $y = $this->hPrint + (2 * $this->margem) - 4;
        if (!empty($this->creditos)) {
            $this->pdf->textBox(
                $this->margem,
                $y,
                $this->wPrint,
                $this->bloco10H,
                $this->creditos,
                $aFont,
                'T',
                'R',
                false,
                '',
                true
            );
        }
        return $this->bloco10H + $y;
    }
}
