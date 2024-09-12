<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
trait Bloco1
{
    protected function bloco1($y)
    {
        $h = 12;
        $texto = 'DANFE SIMPLIFICADO - ETIQUETA';
        $aFont = ['font' => $this->fontePadrao, 'size' => 12, 'style' => 'B'];
        $y += $this->pdf->textBox(
            $this->margem,
            $this->margem + 1,
            $this->wPrint,
            $h + $this->margem,
            $texto,
            $aFont,
            'T',
            'C',
            false
        );

        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => ''];
        $y += $this->pdf->textBox($this->margem, $y + 2, $this->wPrint, 1, '', $aFont, 'T', 'C', false, '');

        return $y;
    }
}
