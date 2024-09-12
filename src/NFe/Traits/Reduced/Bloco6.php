<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
trait Bloco6
{
    protected function bloco6($y)
    {
        $headerPercentage = [0.12, 0.32, 0.10, 0.07, 0.13, 0.13, 0.13];
        $fsize = $this->determineFontSize();
        $aFont = ['font' => $this->fontePadrao, 'size' => $fsize, 'style' => ''];
        $bFont = ['font' => $this->fontePadrao, 'size' => $fsize, 'style' => 'B'];

        $y = $this->fillHeader($y, $headerPercentage, $bFont);
        $y = $this->fillItems($y, $headerPercentage, $aFont);

        $this->pdf->line($this->margem, $y + 3, $this->wPrint + $this->margem, $y + 3);
        return $y + 4;
    }

    protected function determineFontSize()
    {
        return ($this->paperwidth < 70) ? 5 : 7;
    }

    protected function fillHeader($y, $headerPercentage, $bFont)
    {
        $x = $this->margem;
        $x1 = $x + ($this->wPrint * $headerPercentage[0]);
        $x2 = $x1 + ($this->wPrint * $headerPercentage[1]);
        $x3 = $x2 + ($this->wPrint * $headerPercentage[2]);
        $x4 = $x3 + ($this->wPrint * $headerPercentage[3]);
        $x5 = $x4 + ($this->wPrint * $headerPercentage[4]);
        $x6 = $x5 + ($this->wPrint * $headerPercentage[5]);

        $this->pdf->textBox($x, $y, ($this->wPrint * $headerPercentage[0]), 3, "Cód", $bFont, 'T', 'L', false, '', true);
        $this->pdf->textBox($x1, $y, ($this->wPrint * $headerPercentage[1]), 3, "Descrição", $bFont, 'T', 'L', false, '', true);
        $this->pdf->textBox($x2, $y, ($this->wPrint * $headerPercentage[2]), 3, "Qtde", $bFont, 'T', 'C', false, '', true);
        $this->pdf->textBox($x3, $y, ($this->wPrint * $headerPercentage[3]), 3, "UN", $bFont, 'T', 'C', false, '', true);
        $this->pdf->textBox($x4, $y, ($this->wPrint * $headerPercentage[4]), 3, "Vl Unit", $bFont, 'T', 'C', false, '', true);
        $this->pdf->textBox($x5, $y, ($this->wPrint * $headerPercentage[5]), 3, "Desc", $bFont, 'T', 'R', false, '', true);
        $y1 = $this->pdf->textBox($x6, $y, ($this->wPrint * $headerPercentage[6]), 3, "Total", $bFont, 'T', 'R', false, '', true);

        return $y + $y1 + 0.5;
    }

    protected function fillItems($y2, $headerPercentage, $aFont)
    {
        if ($this->det->length == 0) {
            return $y2;
        }

        foreach ($this->itens as $item) {
            $it = (object) $item;
            $x = $this->margem;
            $x1 = $x + ($this->wPrint * $headerPercentage[0]);
            $x2 = $x1 + ($this->wPrint * $headerPercentage[1]);
            $x3 = $x2 + ($this->wPrint * $headerPercentage[2]);
            $x4 = $x3 + ($this->wPrint * $headerPercentage[3]);
            $x5 = $x4 + ($this->wPrint * $headerPercentage[4]);
            $x6 = $x5 + ($this->wPrint * $headerPercentage[5]);

            $this->pdf->textBox($x, $y2, ($this->wPrint * $headerPercentage[0]), $it->height, $it->codigo, $aFont, 'T', 'L', false, '', true);
            $this->pdf->textBox($x1, $y2, ($this->wPrint * $headerPercentage[1]), $it->height, $it->desc, $aFont, 'T', 'L', false, '', false);
            $this->pdf->textBox($x2, $y2, ($this->wPrint * $headerPercentage[2]), $it->height, $it->qtd, $aFont, 'T', 'R', false, '', true);
            $this->pdf->textBox($x3, $y2, ($this->wPrint * $headerPercentage[3]), $it->height, $it->un, $aFont, 'T', 'C', false, '', true);
            $this->pdf->textBox($x4, $y2, ($this->wPrint * $headerPercentage[4]), $it->height, $it->vunit, $aFont, 'T', 'R', false, '', true);
            $this->pdf->textBox($x5, $y2, ($this->wPrint * $headerPercentage[5]), $it->height, $it->vdesc, $aFont, 'T', 'C', false, '', true);
            $this->pdf->textBox($x6, $y2, ($this->wPrint * $headerPercentage[6]), $it->height, $it->valor, $aFont, 'T', 'R', false, '', true);

            $y2 += $it->height;
        }

        return $y2;
    }
}
