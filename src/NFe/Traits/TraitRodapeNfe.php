<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitRodapeNfe
{
    public function rodape()
    {
        $x = $this->margesq;
        $y = $this->maxH - 4;
        if ($this->orientacao == 'P') {
            $w = $this->wPrint;
        } else {
            $w = $this->wPrint - $this->wCanhoto;
            $x = $this->wCanhoto;
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'I'];
        $texto = "Impresso em " . date('d/m/Y') . " as " . date('H:i:s')
            . '  ' . $this->creditos;
        $this->pdf->textBox($x, $y, $w, 0, $texto, $aFont, 'T', 'L', false);
        $texto = $this->powered ? "Powered by NFePHP®" : '';
        $this->pdf->textBox($x, $y, $w, 0, $texto, $aFont, 'T', 'R', false, '');
    }
}
