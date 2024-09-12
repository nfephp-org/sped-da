<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
trait Bloco2
{
    protected function bloco2($y)
    {
        $this->pdf->setFillColor(0, 0, 0);
        $chave_acesso = str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        $borderWidth = $this->wPrint - ($this->margem * 2) - 9;
        $borderHeight = 12;
        $x = $this->margem;
        $this->fillBarCode($x, $borderWidth, $chave_acesso, $borderHeight, $y);
        $this->fillAccessKey($x, $y, $chave_acesso, $borderHeight);

        $y += $borderHeight + 6;

        $this->validateBloco2();
        $this->fillProtocolAndDate($x, $y);

        $this->pdf->line($this->margem, $y + 12, $this->wPrint + $this->margem, $y + 12);
        return $y + 13;
    }

    private function fillBarCode($x, $borderWidth, $chave_acesso, $borderHeight, $y)
    {
        $this->pdf->code128($x + (($this->wPrint - $borderWidth) / 2), $y + 2, $chave_acesso, $borderWidth, $borderHeight);
    }

    private function fillAccessKey($x, $y, $chave_acesso, $borderHeight)
    {
        $texto = $this->formatField($chave_acesso, $this->formatoChave);
        $aFont = ['font' => $this->fontePadrao, 'size' => 11, 'style' => 'B'];
        $this->pdf->textBox($x, $y + $borderHeight +  2, $this->wPrint - 2, 7, 'Chave de acesso', $aFont, 'T', 'C', false, '');
        $this->pdf->textBox($x, $y + $borderHeight +  5, $this->wPrint - 2, 7, $texto, $aFont, 'T', 'C', false, '');
    }

    private function fillProtocolAndDate($x, $y)
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 11, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $this->wPrint, 7, 'Protocolo de Autorização', $aFont, 'B', 'C', 0, '');

        [$protocolo, $dtHora] = $this->getProtocolAndDate();
        $texto = "{$protocolo} - " . $dtHora->format('d/m/Y H:i:s');

        $aFont = ['font' => $this->fontePadrao, 'size' => 9, 'style' => ''];
        $this->pdf->textBox($x, $y + 3, $this->wPrint, 7, $texto, $aFont, 'B', 'C', 0, '');
    }

    private function getProtocolAndDate()
    {
        $protocolo  = !empty($this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue)
            ? $this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue
            : '';
        $dtHora = $this->toDateTime($this->nfeProc->getElementsByTagName("dhRecbto")->item(0)->nodeValue);

        return [$protocolo, $dtHora];
    }

    private function validateBloco2()
    {
        if (empty($this->infProt)) {
            throw new \Exception('Apenas NFe autorizadas podem ser impressas em formato de etiqueta');
        }
        if ($this->canceled) {
            throw new \Exception('Esta NFe está cancelada, e apenas NFe autorizadas podem ser '
                . 'impressas em formato de etiqueta');
        }
    }
}
