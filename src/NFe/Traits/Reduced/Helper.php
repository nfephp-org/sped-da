<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

trait Helper
{
    protected function calculateHeightItens($descriptionWidth)
    {
        $fsize = $this->getFontSize();
        $hfont = $this->getFontHeight($fsize);
        $htot = 0;

        if ($this->det->length != 0) {
            foreach ($this->det as $item) {
                $htot += $this->processItem($item, $fsize, $descriptionWidth, $hfont);
            }
        }

        return $htot + 10;
    }

    private function getFontSize()
    {
        return $this->paperwidth < 70 ? 5 : 7;
    }

    private function getFontHeight($fsize)
    {
        return (imagefontheight($fsize) / 72) * 15;
    }

    private function processItem($item, $fsize, $descriptionWidth, $hfont)
    {
        $prod = $item->getElementsByTagName("prod")->item(0);
        $cProd = $this->formatProductCode($prod);
        $xProd = $this->getTruncatedDescription($prod, $descriptionWidth);
        $qCom = $this->formatQuantity($prod);
        $uCom = $this->getTagValue($prod, "uCom");
        $vUnCom = $this->formatUnitPrice($prod);
        $vDesc = $this->formatDiscount($prod);
        $vProd = $this->formatProductValue($prod);

        $h = $this->calculateItemHeight($xProd, $descriptionWidth, $fsize, $hfont);

        $this->updateTotals($vProd, $vDesc);
        $this->storeItem($cProd, $xProd, $qCom, $uCom, $vUnCom, $vDesc, $vProd, $h);

        return $h;
    }

    private function formatProductCode($prod)
    {
        return str_pad($this->getTagValue($prod, "cProd"), 5, '0', STR_PAD_LEFT);
    }

    private function getTruncatedDescription($prod, $descriptionWidth)
    {
        $xProd = substr($this->getTagValue($prod, "xProd"), 0, 30);
        return $this->truncateDescriptionToFit($xProd, $descriptionWidth);
    }

    private function truncateDescriptionToFit($xProd, $descriptionWidth)
    {
        $tempPDF = new \NFePHP\DA\Legacy\Pdf();
        $tempPDF->setFont($this->fontePadrao, '', $this->getFontSize());

        $n = $tempPDF->wordWrap($xProd, $descriptionWidth);
        $limit = 20;

        while ($n > 2) {
            $xProd = substr((string) $xProd, 0, $limit);
            $tempPDF->wordWrap($xProd, $descriptionWidth, true);
            $n--;
        }

        return $xProd;
    }

    private function formatQuantity($prod)
    {
        return $this->formatValueWithDecimalPlaces((float)$this->getTagValue($prod, "qCom"), $this->getQuantityDecimalPlaces());
    }

    private function formatUnitPrice($prod)
    {
        return $this->formatValueWithDecimalPlaces((float)$this->getTagValue($prod, "vUnCom"), $this->getPriceDecimalPlaces());
    }

    private function formatDiscount($prod)
    {
        return $this->formatValueWithDecimalPlaces((float)$this->getTagValue($prod, "vDesc"), $this->getPriceDecimalPlaces());
    }

    private function formatProductValue($prod)
    {
        return $this->formatValueWithDecimalPlaces((float)$this->getTagValue($prod, "vProd"), $this->getPriceDecimalPlaces());
    }

    private function calculateItemHeight($xProd, $descriptionWidth, $fsize, $hfont)
    {
        $tempPDF = new \NFePHP\DA\Legacy\Pdf();
        $tempPDF->setFont($this->fontePadrao, '', $fsize);
        $n = $tempPDF->wordWrap($xProd, $descriptionWidth);

        $marginReduction = $this->paperwidth === 58 ? 2.4 : 0.4;
        return ($hfont * $n) - $marginReduction;
    }

    private function updateTotals($vProd, $vDesc)
    {
        $this->totalProducts += $vProd;
        $this->totalDesc += $vDesc;
    }

    private function storeItem($cProd, $xProd, $qCom, $uCom, $vUnCom, $vDesc, $vProd, $h)
    {
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
    }

    private function calculatePaperLength()
    {
        $wprint = $this->paperwidth - (2 * $this->margem);
        $this->bloco6 = $this->calculateHeightItens($wprint * $this->descPercent);

        return array_sum([
            $this->bloco1,
            $this->bloco2,
            $this->bloco3,
            $this->bloco4,
            $this->bloco5,
            $this->bloco6,
            $this->bloco7,
            $this->bloco8
        ]);
    }
}
