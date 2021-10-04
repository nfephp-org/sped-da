<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco cabecalho com a identificação e logo do emitente
 */
trait TraitBlocoI
{
    protected function blocoI()
    {
        //$this->bloco1H = 18;
        $y = $this->margem;
        //$aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        //$this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco1H, '', $aFont, 'T', 'C', true, '', false);
        $emitRazao = $this->getTagValue($this->emit, "xNome");
        $emitCnpj = $this->getTagValue($this->emit, "CNPJ");
        $emitIE = $this->getTagValue($this->emit, "IE");
        $emitCnpj = $this->formatField($emitCnpj, "###.###.###/####-##");
        $emitLgr = $this->getTagValue($this->enderEmit, "xLgr");
        $emitNro = $this->getTagValue($this->enderEmit, "nro");
        $emitBairro = $this->getTagValue($this->enderEmit, "xBairro");
        $emitMun = $this->getTagValue($this->enderEmit, "xMun");
        $emitUF = $this->getTagValue($this->enderEmit, "UF");
        
        $emitFone = $this->getTagValue($this->enderEmit, "fone");
        if (strlen($emitFone)>0) {
            if (strlen($emitFone) == 11) {
                $emitFone = $this->formatField($emitFone, "(##)#####-####");
            } else {
                $emitFone = $this->formatField($emitFone, "(##) ####-####");
            }
        }
        
        $h = 0;
        $maxHimg = $this->bloco1H - 4;
        if (!empty($this->logomarca)) {
            $xImg = $this->margem;
            $yImg = $this->margem + 1;
            $logoInfo = getimagesize($this->logomarca);
            $logoWmm = ($logoInfo[0]/72)*25.4;
            $logoHmm = ($logoInfo[1]/72)*25.4;
            $nImgW = $this->wPrint/4;
            $nImgH = round($logoHmm * ($nImgW/$logoWmm), 0);
            if ($nImgH > $maxHimg) {
                $nImgH = $maxHimg;
                $nImgW = round($logoWmm * ($nImgH/$logoHmm), 0);
            }
            $xRs = ($nImgW) + $this->margem;
            $wRs = ($this->wPrint - $nImgW);
            $alignH = 'L';
            $this->pdf->image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $xRs = $this->margem;
            $wRs = $this->wPrint;
            $alignH = 'C';
        }
        //COLOCA RAZÃO SOCIAL
        $aFont = ['font'=>$this->fontePadrao, 'size' => 8, 'style' => ''];
        $texto = "{$emitRazao}";
        $y += $this->pdf->textBox(
            $xRs+2,
            $this->margem,
            $wRs-2,
            $this->bloco1H-$this->margem-1,
            $texto,
            $aFont,
            'T',
            $alignH,
            false,
            '',
            true
        );
        if ($this->pdf->fontSizePt < 8) {
            $aFont = ['font'=>$this->fontePadrao, 'size' => $this->pdf->fontSizePt, 'style' => ''];
        }
        $texto = "CNPJ: {$emitCnpj} IE: {$emitIE}";
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $texto = $emitLgr . ", " . $emitNro;
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $texto = $emitBairro;
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $texto = $emitMun . "-" . $emitUF . ($emitFone ? "  Fone: ".$emitFone : "");
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $this->pdf->dashedHLine($this->margem, $this->bloco1H, $this->wPrint, 0.1, 30);
        return $this->bloco1H;
    }
}
