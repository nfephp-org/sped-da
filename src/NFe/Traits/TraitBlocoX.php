<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco Informações sobre impostos aproximados
 */
trait TraitBlocoX
{
    protected function blocoX($y)
    {
        $aFont = ['font'=> $this->fontePadrao, 'size' => 6, 'style' => 'I'];
        if ($this->paperwidth < 70) {
            $aFont = ['font'=> $this->fontePadrao, 'size' => 4, 'style' => 'I'];
        }
        if (!empty($this->creditos)) {
            $y += 4;

            $this->pdf->textBox(
                $this->margem,
                $y,
                $this->wPrint,
                $this->bloco9H,
                $this->creditos,
                $aFont,
                'T',
                'R',
                false,
                '',
                true
            );
        }

        if (!empty($this->textoExtra)) {
            $y += 4;

            $this->pdf->textBox(
                $this->margem,
                $y,
                $this->wPrint,
                $this->bloco9H,
                $this->textoExtra,
                $aFont,
                'T',
                'L',
                false,
                '',
                true
            );
        }
        return $this->bloco9H + $y;
    }
}
