<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
trait Bloco5
{
    protected function bloco5($y)
    {
        $y = $this->fillRecipientTitle($y);
        $y = $this->fillRecipientName($y);
        $y = $this->fillRecipientAddress($y);
        $y = $this->fillRecipientDocumentAndIE($y);

        $this->pdf->line($this->margem, $y + 2, $this->wPrint + $this->margem, $y + 2);
        return $y + 4;
    }

    protected function fillRecipientTitle($y)
    {
        $texto = 'DESTINATÁRIO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        return $y + $this->pdf->textBox($this->margem, $y, $this->wPrint, 7, $texto, $aFont, 'T', 'C', 0, '') + 1;
    }

    protected function fillRecipientName($y)
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 9, 'style' => ''];
        $texto = $this->dest->getElementsByTagName("xNome")->item(0)->nodeValue;
        return $y + $this->pdf->textBox($this->margem, $y, $this->wPrint, 7, $texto, $aFont, 'T', 'C', 0, '', false) + 1;
    }

    protected function fillRecipientAddress($y)
    {
        $destLgr = $this->getTagValue($this->enderDest, "xLgr");
        $destNro = $this->getTagValue($this->enderDest, "nro");
        $destBairro = $this->getTagValue($this->enderDest, "xBairro");
        $destMun = $this->getTagValue($this->enderDest, "xMun");
        $destUF = $this->getTagValue($this->enderDest, "UF");

        $texto = $destLgr . ', ' . $destNro . ' - ' . $destBairro . ' - ' . $destMun . ' - ' . $destUF;
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => ''];

        return $y + $this->pdf->textBox($this->margem, $y, $this->wPrint, 3, $texto, $aFont, 'T', 'C', false, '', false) + 1;
    }

    protected function fillRecipientDocumentAndIE($y)
    {
        $doc = $this->getDocument();
        $cnpjOrCpf = preg_replace('/[^0-9]/', '', $doc);
        $textLabel = strlen($cnpjOrCpf) == 14 ? 'CNPJ: ' : 'CPF: ';

        $ie = !empty($this->dest->getElementsByTagName("IE")->item(0))
            ? $this->formatField($this->dest->getElementsByTagName("IE")->item(0)->nodeValue, "###.###.###.###.###")
            : null;

        $texto = $textLabel . $doc . ' - ' . 'IE: ' . $ie;
        $aFont = ['font' => $this->fontePadrao, 'size' => 9, 'style' => ''];

        return $y + $this->pdf->textBox($this->margem, $y, $this->wPrint, 7, $texto, $aFont, 'T', 'C', 0, '') + 1;
    }

    protected function getDocument()
    {
        $cnpj = !empty($this->dest->getElementsByTagName("CNPJ")->item(0))
            ? $this->formatField($this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue, "###.###.###/####-##")
            : null;
        $cpf = !empty($this->dest->getElementsByTagName("CPF")->item(0))
            ? $this->formatField($this->dest->getElementsByTagName("CPF")->item(0)->nodeValue, '###.###.###-##')
            : null;
        return $cnpj ?? $cpf;
    }
}
