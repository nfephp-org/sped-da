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
        $codePercent = $this->getCodeColumnPercent();
        $matrix = [$codePercent, $this->descPercent, 0.10, 0.07, 0.13, 0.13, 0.13];
        $fsize = 7;
        if ($this->paperwidth < 70) {
            $fsize = 5;
        }
        $aFont = ['font'=> $this->fontePadrao, 'size' => $fsize, 'style' => ''];
        $descriptionWidth = $this->getDescriptionBlockWidth();

        $texto = "Código";
        $x = $this->margem;
        $this->pdf->textBox($x, $y, ($this->wPrint * $matrix[0]), 3, $texto, $aFont, 'T', 'L', false, '', true);

        $texto = "Descrição";
        $x1 = $x + ($this->wPrint * $matrix[0]);
        $this->pdf->textBox($x1, $y, ($this->wPrint * $matrix[1]), 3, $texto, $aFont, 'T', 'L', false, '', true);

        $texto = "Qtde";
        $x2 = $x1 + ($this->wPrint * $matrix[1]);
        $this->pdf->textBox($x2, $y, ($this->wPrint * $matrix[2]), 3, $texto, $aFont, 'T', 'C', false, '', true);

        $texto = "UN";
        $x3 = $x2 + ($this->wPrint * $matrix[2]);
        $this->pdf->textBox($x3, $y, ($this->wPrint * $matrix[3]), 3, $texto, $aFont, 'T', 'C', false, '', true);

        $texto = "Vl Unit";
        $x4 = $x3 + ($this->wPrint * $matrix[3]);
        $this->pdf->textBox($x4, $y, ($this->wPrint * $matrix[4]), 3, $texto, $aFont, 'T', 'C', false, '', true);

        $texto = "Desc";
        $x5 = $x4 + ($this->wPrint * $matrix[4]);
        $this->pdf->textBox($x5, $y, ($this->wPrint * $matrix[5]), 3, $texto, $aFont, 'T', 'R', false, '', true);

        $texto = "Vl Total";
        $x6 = $x5 + ($this->wPrint * $matrix[5]);
        $y1 = $this->pdf->textBox($x6, $y, ($this->wPrint * $matrix[6]), 3, $texto, $aFont, 'T', 'R', false, '', true);

        $y2 = $y + $y1;
        if ($this->det->length == 0) {
        } else {
            foreach ($this->itens as $item) {
                $it = (object) $item;
                $this->pdf->textBox(
                    $x,
                    $y2,
                    ($this->wPrint * $matrix[0]),
                    $it->descHeight,
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
                    $descriptionWidth,
                    $it->descHeight,
                    $it->desc,
                    $aFont,
                    'T',
                    'L',
                    false,
                    '',
                    false
                );
                $yNum = $y2 + $it->descHeight;
                $this->pdf->textBox(
                    $x2,
                    $yNum,
                    ($this->wPrint * $matrix[2]),
                    $it->lineHeight,
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
                    $yNum,
                    ($this->wPrint * $matrix[3]),
                    $it->lineHeight,
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
                    $yNum,
                    ($this->wPrint * $matrix[4]),
                    $it->lineHeight,
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
                    $yNum,
                    ($this->wPrint * $matrix[5]),
                    $it->lineHeight,
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
                    $yNum,
                    ($this->wPrint * $matrix[6]),
                    $it->lineHeight,
                    $it->valor,
                    $aFont,
                    'T',
                    'R',
                    false,
                    '',
                    true
                );
                $y2 += ($it->descHeight + $it->lineHeight);
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
        $descriptionWidth = $this->normalizeDescriptionWidth($descriptionWidth);
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
                $limit = 120;
                $xProd = substr($this->getTagValue($prod, "xProd"), 0, $limit);
                $qCom = $this->formatValueWithDecimalPlaces((float) $this->getTagValue($prod, "qCom"), $this->getQuantityDecimalPlaces());
                $uCom = $this->getTagValue($prod, "uCom");
                $vUnCom = $this->formatValueWithDecimalPlaces((float) $this->getTagValue($prod, "vUnCom"), $this->getPriceDecimalPlaces());
                $vDesc = $this->formatValueWithDecimalPlaces((float) $this->getTagValue($prod, "vDesc"), 2);
                $vProd = $this->formatValueWithDecimalPlaces((float) $this->getTagValue($prod, "vProd"), 2);

                $tempPDF = new \NFePHP\DA\Legacy\Pdf(); // cria uma instancia temporaria da class pdf
                $tempPDF->setFont($this->fontePadrao, '', $fsize); // seta a font do PDF

                $p = $xProd;
                $n = $tempPDF->wordWrap($p, $descriptionWidth);
                $n = max(1, $n);

                $lineHeight = $tempPDF->fontSize;
                $descHeight = $lineHeight * $n;

                $this->itens[] = [
                    "codigo" => $cProd,
                    "desc" => $xProd,
                    "qtd" => $qCom,
                    "un" => $uCom,
                    "vunit" => $vUnCom,
                    "vdesc" => $vDesc,
                    "valor" => $vProd,
                    "descHeight" => $descHeight,
                    "lineHeight" => $lineHeight,
                    "height" => $descHeight + $lineHeight
                ];
                $htot += ($descHeight + $lineHeight);
            }
        }
        return $htot + 4;
    }

    protected function normalizeDescriptionWidth($descriptionWidth)
    {
        $fullWidth = $this->calculateFullDescriptionWidth();

        if ($descriptionWidth <= 0) {
            return $fullWidth;
        }

        return max($descriptionWidth, $fullWidth);
    }

    protected function getDescriptionBlockWidth()
    {
        return $this->normalizeDescriptionWidth($this->wPrint * $this->descPercent);
    }

    protected function calculateFullDescriptionWidth()
    {
        $usableWidth = ($this->wPrint > 0) ? $this->wPrint : max(1, ($this->paperwidth - (4 * $this->margem)));
        return round($usableWidth * (1 - $this->getCodeColumnPercent()), 2);
    }

    protected function getCodeColumnPercent()
    {
        return 0.12;
    }
}
