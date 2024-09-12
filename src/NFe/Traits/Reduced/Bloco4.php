<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
trait Bloco4
{
    protected function bloco4($y)
    {
        $y = $this->fillEmission($y);
        $y = $this->fillNFeType($y);
        $this->fillCurrentNFeType($y);
        $y = $this->fillNFeNumberSerieAndEmission($y + 3);
        $this->pdf->line($this->margem, $y + 3, $this->wPrint + $this->margem, $y + 3);

        return $y + 4;
    }

    protected function fillEmission($y)
    {
        $texto = $this->getTagValue($this->ide, "tpAmb") == '2' ? 'SEM VALOR FISCAL' : 'EMISSÃO NORMAL';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $y +=  $this->pdf->textBox($this->margem, $y, $this->wPrint, 7, $texto, $aFont, 'T', 'C', false, '');
        return $y;
    }

    protected function fillNFeType($y)
    {
        $texto = '0 - ENTRADA / 1 - SAÍDA';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => ''];
        $y += $this->pdf->textBox($this->margem, $y + 1, $this->wPrint, 7, $texto, $aFont, 'T', 'C', false, '');
        return $y;
    }

    protected function fillCurrentNFeType($y)
    {
        $tpNF = $this->ide->getElementsByTagName('tpNF')->item(0)->nodeValue;
        $aFont = ['font' => $this->fontePadrao, 'size' => 12, 'style' => 'B'];
        $this->pdf->textBox($this->margem + 7, $y - 2, 6, 5, $tpNF, $aFont, 'T', 'C', true, '');
    }

    protected function fillNFeNumberSerieAndEmission($y)
    {
        $numNF = $this->formatNFeNumber();
        $serie = $this->formatNFeSerie();
        $dhEmi = $this->formatEmissionDate();
        $texto = 'Número: ' . $numNF .  ' - Série: ' . $serie . ' Emissão: ' . $dhEmi;

        $aFont = ['font' => $this->fontePadrao, 'size' => 11, 'style' => ''];
        return $y + $this->pdf->textBox($this->margem, $y, $this->wPrint, 7, $texto, $aFont, 'T', 'C', false, '');
    }

    protected function formatNFeNumber()
    {
        $numNF = str_pad(
            $this->ide->getElementsByTagName('nNF')->item(0)->nodeValue,
            9,
            "0",
            STR_PAD_LEFT
        );
        return $this->formatField($numNF, "###.###.###");
    }

    protected function formatNFeSerie()
    {
        return str_pad(
            $this->ide->getElementsByTagName('serie')->item(0)->nodeValue,
            3,
            "0",
            STR_PAD_LEFT
        );
    }

    protected function formatEmissionDate()
    {
        $dhEmi = $this->toDateTime($this->nfeProc->getElementsByTagName("dhEmi")->item(0)->nodeValue);
        return $dhEmi->format('d/m/Y');
    }
}
