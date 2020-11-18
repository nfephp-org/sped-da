<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco VI informações de chave de acesso
 */
trait TraitBlocoVI
{
    protected function blocoVI($y)
    {
        //$this->bloco6H = 10;
        
        //$aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        //$this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco6H, '', $aFont, 'T', 'C', false, '', false);
        
        $texto = "Consulte pela Chave de Acesso em:";
        $aFont = ['font'=> $this->fontePadrao, 'size' => 8, 'style' => 'B'];
        $y1 = $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint,
            $this->bloco6H,
            $texto,
            $aFont,
            'T',
            'C',
            false,
            '',
            false
        );
        
        $texto =  $this->urlChave;
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $y2 = $this->pdf->textBox(
            $this->margem,
            $y+$y1,
            $this->wPrint,
            2,
            $texto,
            $aFont,
            'T',
            'C',
            false,
            '',
            false
        );
        
        $chave =  str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        $texto = $this->formatField($chave, $this->formatoChave);
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $y3 = $this->pdf->textBox(
            $this->margem,
            $y+$y1+$y2+1,
            $this->wPrint,
            2,
            $texto,
            $aFont,
            'T',
            'C',
            false,
            '',
            true
        );
        $this->pdf->dashedHLine($this->margem, $this->bloco6H+$y, $this->wPrint, 0.1, 30);
        return $this->bloco6H + $y;
    }
}
