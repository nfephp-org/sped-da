<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitImpostosNfe
{
    public function impostos(float $y): float
    {
        $imp = "impostos{$this->orientacao}";
        return $this->$imp($y);
    }

    public function impostosP(float $y): float
    {
        $x = $this->margesq;
        $campos_por_linha = 9;
        $h = 7;
        $title_size = 31;
        $w = $this->wPrint / $campos_por_linha;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x, $y+3, $this->wPrint, 14, '', $aFont, 'T', 'L', 1, '');
        $fontTitulo = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $fontValor  = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        //linha horizontal
        $this->pdf->line($x, $y+10, $this->wPrint+$this->margesq, $y+10);
        //linhas verticais
        $this->pdf->line($x+$w, $y+3, $x+$w, $y+17);
        $this->pdf->line($x+2*$w, $y+3, $x+2*$w, $y+17);
        $this->pdf->line($x+3*$w, $y+3, $x+3*$w, $y+17);
        $this->pdf->line($x+4*$w, $y+3, $x+4*$w, $y+17);
        $this->pdf->line($x+5*$w, $y+3, $x+5*$w, $y+17);
        $this->pdf->line($x+6*$w, $y+3, $x+6*$w, $y+17);
        $this->pdf->line($x+7*$w, $y+3, $x+7*$w, $y+17);
        $this->pdf->line($x+8*$w, $y+3, $x+8*$w, $y+17);
        $texto = "CÁLCULO DO IMPOSTO";
        $this->pdf->textBox($x, $y, $title_size, 8, $texto, $aFont, 'T', 'L', 0, '');
        $y += 3;
        //valores
        $val = $this->std->total->ICMSTot;
        //vBC
        $this->pdf->textBox($x, $y, $w, $h, 'BASE DE CÁLC. DO ICMS', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vBC, 2, ',', '.');
        $this->pdf->textBox($x, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //vICMS
        $this->pdf->textBox($x+$w, $y, $w, $h, 'VALOR DO ICMS', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vICMS, 2, ',', '.');
        $this->pdf->textBox($x+$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //vBCST
        $this->pdf->textBox($x+2*$w, $y, $w, $h, 'BASE DE CÁLC. ICMS S.T', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vBCST ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+2*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //VALOR DO ICMS SUBST.
        $this->pdf->textBox($x+3*$w, $y, $w, $h, 'VALOR DO ICMS SUBST.', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vST ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+3*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //V. IMP. IMPORTAÇÃO
        $this->pdf->textBox($x+4*$w, $y, $w, $h, 'V. IMP. IMPORTAÇÃO', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vII ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+4*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //V. ICMS UF REMET.
        $this->pdf->textBox($x+5*$w, $y, $w, $h, 'V. ICMS UF REMET.', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vICMSUFRemet ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+5*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //V. FCP UF DEST.
        $this->pdf->textBox($x+6*$w, $y, $w, $h, 'V. FCP UF DEST.', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vFCPUFDest ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+6*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //VALOR DO PIS
        $this->pdf->textBox($x+7*$w, $y, $w, $h, 'VALOR DO PIS', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vPIS ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+7*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //V. TOTAL PRODUTOS
        $this->pdf->textBox($x+8*$w, $y, $w, $h, 'V. TOTAL PRODUTOS', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vProd ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+8*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        $y += $h;
        //VALOR DO FRETE
        $this->pdf->textBox($x, $y, $w, $h, 'VALOR DO FRETE', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vFrete ?? 0, 2, ',', '.');
        $this->pdf->textBox($x, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //VALOR DO SEGURO
        $this->pdf->textBox($x+$w, $y, $w, $h, 'VALOR DO SEGURO', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vSeg ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //DESCONTO
        $this->pdf->textBox($x+2*$w, $y, $w, $h, 'DESCONTO', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vDesc ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+2*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //OUTRAS DESPESAS
        $this->pdf->textBox($x+3*$w, $y, $w, $h, 'OUTRAS DESPESAS', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vOutro ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+3*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //VALOR TOTAL IPI
        $this->pdf->textBox($x+4*$w, $y, $w, $h, 'VALOR TOTAL IPI', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vIPI ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+4*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //V. ICMS UF DEST.
        $this->pdf->textBox($x+5*$w, $y, $w, $h, 'V. ICMS UF DEST.', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vICMSUFDest ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+5*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //V. TOT. TRIB.
        $this->pdf->textBox($x+6*$w, $y, $w, $h, 'V. TOT. TRIB.', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vTotTrib ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+6*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //VALOR DA COFINS
        $this->pdf->textBox($x+7*$w, $y, $w, $h, 'VALOR DA COFINS', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vCOFINS ?? 0, 2, ',', '.');
        $this->pdf->textBox($x+7*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        //V. TOTAL DA NOTA
        $this->pdf->textBox($x+8*$w, $y, $w, $h, 'V. TOTAL DA NOTA', $fontTitulo, 'T', 'L', 0, '');
        $valor = number_format($val->vNF, 2, ',', '.');
        $this->pdf->textBox($x+8*$w, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
        return 17;
    }

    public function impostosL(float $y): float
    {
        return 0;
    }

}
