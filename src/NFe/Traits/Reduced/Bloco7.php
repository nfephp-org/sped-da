<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
trait Bloco7
{
    protected function bloco7($y)
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 9, 'style' => ''];

        $y = $this->fillTotalItems($y, $aFont);
        $y = $this->fillTotalValueProducts($y, $aFont);
        $y = $this->fillTotalDiscounts($y, $aFont);
        $y = $this->fillTotalInvoiceValue($y, $aFont);

        $this->pdf->line($this->margem, $y + 2, $this->wPrint + $this->margem, $y + 2);
        return $y + 4;
    }

    protected function fillTotalItems($y, $aFont)
    {
        $texto = 'Qtd. Total de Itens';
        $this->pdf->textBox($this->margem, $y, $this->wPrint, 1, $texto, $aFont, 'T', 'L', false, '');

        $texto = count($this->itens);
        $y += $this->pdf->textBox($this->margem, $y, $this->wPrint, 1, $texto, $aFont, 'T', 'R', false, '') + 1;

        return $y;
    }

    protected function fillTotalValueProducts($y, $aFont)
    {
        $texto = 'Valor Total dos Produtos R$';
        $this->pdf->textBox($this->margem, $y, $this->wPrint, 1, $texto, $aFont, 'T', 'L', false, '');

        $vProd = $this->formatValue($this->ICMSTot->getElementsByTagName("vProd")->item(0)->nodeValue);
        $y += $this->pdf->textBox($this->margem, $y, $this->wPrint, 1, $vProd, $aFont, 'T', 'R', false, '') + 1;

        return $y;
    }

    protected function fillTotalDiscounts($y, $aFont)
    {
        $texto = 'Valor Descontos R$';
        $this->pdf->textBox($this->margem, $y, $this->wPrint, 1, $texto, $aFont, 'T', 'L', false, '');

        $vDesc = $this->formatValue($this->ICMSTot->getElementsByTagName("vDesc")->item(0)->nodeValue);
        $y += $this->pdf->textBox($this->margem, $y, $this->wPrint, 1, $vDesc, $aFont, 'T', 'R', false, '') + 1;

        return $y;
    }

    protected function fillTotalInvoiceValue($y, $aFont)
    {
        $texto = 'Valor Total R$';
        $this->pdf->textBox($this->margem, $y, $this->wPrint, 1, $texto, $aFont, 'T', 'L', false, '');

        $vNf = $this->formatValue($this->ICMSTot->getElementsByTagName("vNF")->item(0)->nodeValue);
        $y += $this->pdf->textBox($this->margem, $y, $this->wPrint, 1, $vNf, $aFont, 'T', 'R', false, '') + 1;

        return $y;
    }

    protected function formatValue($value)
    {
        return number_format($value, 2, ",", ".") . " ";
    }
}
