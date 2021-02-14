<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco sub cabecalho com a identificação e logo do emitente
 */
trait TraitBlocoII
{
    protected function blocoII($y)
    {
        //$this->bloco2H = 12;
        //$aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        //$this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco2H, '', $aFont, 'T', 'C', true, '', false);
        if ($this->tpEmis == 9) {
            $texto = "Documento Auxiliar da Nota Fiscal de Consumidor Eletronica";
            $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
            $y1 = $this->pdf->textBox(
                $this->margem,
                $y,
                $this->wPrint,
                $this->bloco2H,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );
            $texto = "Não permite aproveitamento de crédito de ICMS";
            $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
            $y1 += $this->pdf->textBox(
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
                true
            );
            //contingencia offline
            $texto = "EMITIDA EM CONTINGÊNCIA";
            $aFont = ['font'=> $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $y2 = $this->pdf->textBox(
                $this->margem,
                $y+$y1,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'B',
                'C',
                false,
                '',
                true
            );
            
            $texto = "Pendente de autorização";
            $aFont = ['font'=> $this->fontePadrao, 'size' => 8, 'style' => 'I'];
            $this->pdf->textBox(
                $this->margem,
                $y+$y1+$y2,
                $this->wPrint,
                3,
                $texto,
                $aFont,
                'B',
                'C',
                false,
                '',
                true
            );
        } else {
            $texto = "Documento Auxiliar da Nota Fiscal de Consumidor Eletronica\n"
                . "Não permite aproveitamento de crédito de ICMS";
            $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
            $y1 = $this->pdf->textBox(
                $this->margem,
                $this->bloco1H-2,
                $this->wPrint,
                $this->bloco2H,
                $texto,
                $aFont,
                'C',
                'C',
                false,
                '',
                true
            );
        }
        $this->pdf->dashedHLine($this->margem, $this->bloco2H+$y, $this->wPrint, 0.1, 30);
        return $this->bloco2H + $y;
    }
}
