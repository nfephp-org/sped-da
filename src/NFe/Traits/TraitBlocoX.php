<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco Informações sobre impostos aproximados
 */
trait TraitBlocoX
{
    protected function blocoX($y)
    {
        //$this->bloco9H = 3;
        
        /*
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco9H, '', $aFont, 'T', 'C', true, '', false);
        */
        
        $aFont = ['font'=> $this->fontePadrao, 'size' => 6, 'style' => 'I'];
        if ($this->paperwidth < 70) {
            $aFont = ['font'=> $this->fontePadrao, 'size' => 4, 'style' => 'I'];
        }
        if (!empty($this->creditos)) {
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
        return $this->bloco9H + $y;
    }
}
