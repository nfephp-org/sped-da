<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
trait Bloco8
{
    protected function bloco8($y)
    {
        $y = $this->fillTributosInfo($y);
        $y = $this->fillComplementaryInfo($y);

        return $y + 4;
    }

    protected function fillTributosInfo($y)
    {
        $valor = $this->getTagValue($this->ICMSTot, 'vTotTrib');
        $trib = !empty($valor) ? number_format((float) $valor, 2, ',', '.') : '-----';
        $texto = "Informação dos Tributos Totais Incidentes (Lei Federal 12.742/2012): R$ {$trib}";

        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];

        $y += $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint,
            8,
            $texto,
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );

        return $y;
    }

    protected function fillComplementaryInfo($y)
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        if ($this->paperwidth < 70) {
            $aFont['size'] = 5;
        }

        $y += $this->pdf->textBox(
            $this->margem,
            $y + 4,
            $this->wPrint,
            8,
            str_replace(";", "\n", $this->infCpl),
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );

        return $y;
    }
}
