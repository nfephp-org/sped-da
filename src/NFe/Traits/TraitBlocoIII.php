<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco itens da NFe
 */
trait TraitBlocoIII
{
    protected function blocoIII($y)
    {
        if ($this->flagResume) {
            return $y;
        }
        $matrix = [0.13, $this->descPercent, 0.10, 0.07, 0.12, 0.12, 0.12];
        $fsize = 7;
        if ($this->paperwidth < 70) {
            $fsize = 5;
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => $fsize, 'style' => ''];
        $bFont = ['font' => $this->fontePadrao, 'size' => $fsize, 'style' => 'B'];

        $texto = "Cód";
        $x = $this->margem;
        $this->pdf->textBox($x, $y, ($this->wPrint * $matrix[0]), 3, $texto, $bFont, 'T', 'L', false, '', true);

        $texto = "Descrição";
        $x1 = $x + ($this->wPrint * $matrix[0]);
        $this->pdf->textBox($x1, $y, ($this->wPrint * $matrix[1]), 3, $texto, $bFont, 'T', 'L', false, '', true);

        $texto = "Qtde";
        $x2 = $x1 + ($this->wPrint * $matrix[1]);
        $this->pdf->textBox($x2, $y, ($this->wPrint * $matrix[2]), 3, $texto, $bFont, 'T', 'C', false, '', true);

        $texto = "UN";
        $x3 = $x2 + ($this->wPrint * $matrix[2]);
        $this->pdf->textBox($x3, $y, ($this->wPrint * $matrix[3]), 3, $texto, $bFont, 'T', 'C', false, '', true);

        $texto = "Vl Unit";
        $x4 = $x3 + ($this->wPrint * $matrix[3]);
        $this->pdf->textBox($x4, $y, ($this->wPrint * $matrix[4]), 3, $texto, $bFont, 'T', 'C', false, '', true);

        $texto = "Desc";
        $x5 = $x4 + ($this->wPrint * $matrix[4]);
        $this->pdf->textBox($x5, $y, ($this->wPrint * $matrix[5]), 3, $texto, $bFont, 'T', 'R', false, '', true);

        $texto = "Total";
        $x6 = $x5 + ($this->wPrint * $matrix[5]);
        $y1 = $this->pdf->textBox($x6, $y, ($this->wPrint * $matrix[6]), 3, $texto, $bFont, 'T', 'R', false, '', true);

        $y2 = $y + $y1 + 0.5;
        if ($this->det->length == 0) {
        } else {
            foreach ($this->itens as $item) {
                $it = (object) $item;
                $this->pdf->textBox(
                    $x,
                    $y2,
                    ($this->wPrint * $matrix[0]),
                    $it->height,
                    $it->codigo,
                    $aFont,
                    'T',
                    'L',
                    false,
                    '',
                    true
                );
                $this->pdf->textBox(
                    $x1,
                    $y2,
                    ($this->wPrint * $matrix[1]),
                    $it->height,
                    $it->desc,
                    $aFont,
                    'T',
                    'L',
                    false,
                    '',
                    false
                );
                $this->pdf->textBox(
                    $x2,
                    $y2,
                    ($this->wPrint * $matrix[2]),
                    $it->height,
                    $it->qtd,
                    $aFont,
                    'T',
                    'R',
                    false,
                    '',
                    true
                );
                $this->pdf->textBox(
                    $x3,
                    $y2,
                    ($this->wPrint * $matrix[3]),
                    $it->height,
                    $it->un,
                    $aFont,
                    'T',
                    'C',
                    false,
                    '',
                    true
                );
                $this->pdf->textBox(
                    $x4,
                    $y2,
                    ($this->wPrint * $matrix[4]),
                    $it->height,
                    $it->vunit,
                    $aFont,
                    'T',
                    'R',
                    false,
                    '',
                    true
                );
                $this->pdf->textBox(
                    $x5,
                    $y2,
                    ($this->wPrint * $matrix[5]),
                    $it->height,
                    $it->vdesc,
                    $aFont,
                    'T',
                    'C',
                    false,
                    '',
                    true
                );
                $this->pdf->textBox(
                    $x6,
                    $y2,
                    ($this->wPrint * $matrix[6]),
                    $it->height,
                    $it->valor,
                    $aFont,
                    'T',
                    'R',
                    false,
                    '',
                    true
                );
                $y2 += $it->height;
            }
        }
        $this->pdf->dashedHLine($this->margem, $this->bloco3H + $y, $this->wPrint, 0.1, 30);
        return $this->bloco3H + $y;
    }

    protected function calculateHeightItens($descriptionWidth)
    {
        if ($this->flagResume) {
            return 0;
        }
        $fsize = 7;
        if ($this->paperwidth < 70) {
            $fsize = 5;
        }
        $hfont = (imagefontheight($fsize) / 72) * 15;

        $htot = 0;
        if ($this->det->length == 0) {
        } else {
            foreach ($this->det as $item) {
                $prod = $item->getElementsByTagName("prod")->item(0);
                $cProd = str_pad($this->getTagValue($prod, "cProd"), 5, '0', STR_PAD_LEFT);
                $xProd = substr($this->getTagValue($prod, "xProd"), 0, 45);
                $qCom = $this->formatValueWithDecimalPlaces((float) $this->getTagValue($prod, "qCom"), $this->getQuantityDecimalPlaces());
                $uCom = $this->getTagValue($prod, "uCom");
                $vUnCom = $this->formatValueWithDecimalPlaces((float) $this->getTagValue($prod, "vUnCom"), $this->getPriceDecimalPlaces());
                $vDesc = $this->formatValueWithDecimalPlaces((float) $this->getTagValue($prod, "vDesc"), $this->getPriceDecimalPlaces());
                $vProd = $this->formatValueWithDecimalPlaces((float) $this->getTagValue($prod, "vProd"), $this->getPriceDecimalPlaces());

                $tempPDF = new \NFePHP\DA\Legacy\Pdf(); // cria uma instancia temporaria da class pdf
                $tempPDF->setFont($this->fontePadrao, '', $fsize); // seta a font do PDF

                $n = $tempPDF->wordWrap($xProd, $descriptionWidth);
                $limit = 45;
                while ($n > 2) {
                    $limit -= 1;
                    $xProd = substr($this->getTagValue($prod, "xProd"), 0, $limit);
                    $p = $xProd;
                    $n = $tempPDF->wordWrap($p, $descriptionWidth);
                }
                $marginReduction = $this->paperwidth === 58 ? 2.4 : 0.4;
                $h = ($hfont * $n) - $marginReduction;
                $this->itens[] = [
                    "codigo" => $cProd,
                    "desc" => $xProd,
                    "qtd" => $qCom,
                    "un" => $uCom,
                    "vunit" => $vUnCom,
                    "vdesc" => $vDesc,
                    "valor" => $vProd,
                    "height" => $h
                ];
                $htot += $h;
            }
        }
        return $htot + 3;
    }
}