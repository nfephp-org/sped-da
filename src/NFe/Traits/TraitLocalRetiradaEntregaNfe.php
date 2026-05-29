<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitLocalRetiradaEntregaNfe
{

    protected function local(float $y, string $tipo): float
    {
        $local = "local{$this->orientacao}";
        return $this->$local($y, $tipo);
    }

    protected function localP(float $y, string $tipo): float
    {
        $x = $this->margesq;
        $h = 7;
        $htot = 21;
        $w = round($this->wPrint * 0.61, 0);
        $w1 = round($this->wPrint * 0.23, 0); //cnpj
        $w2 = $this->wPrint - $w - $w1; //ie
        $w3 = round($this->wPrint * 0.52, 0); //endereço
        $w4 = $this->wPrint - $w3 - $w2;
        $wuf = 8;
        $w5 = $this->wPrint - $wuf - $w2;

        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];
        //borda do bloco de informações
        $this->pdf->textBox($x, $y+3, $this->wPrint, $htot, '', $aFont, 'T', 'L', 1, '');
        //linhas horizontais
        $this->pdf->line($x, $y+10, $this->wPrint+$this->margesq, $y+10);
        $this->pdf->line($x, $y+17, $this->wPrint+$this->margesq, $y+17);
        //linhas verticais
        $this->pdf->line($x+$w, $y+3, $x+$w, $y+10); // nome | cnpj
        $this->pdf->line($x+$w+$w1, $y+3, $x+$w+$w1, $y+24); // cnpj | ie
        $this->pdf->line($x+$w3, $y+10, $x+$w3, $y+17); // endereço | bairro
        $this->pdf->line($x+$w5, $y+17, $x+$w5, $y+24); // municipio | uf

        //####################################################################################
        //LOCAL DE RETIRADA ou de ENTREGA
        $texto = "INFORMAÇÕES DO LOCAL DE {$tipo}";
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //NOME / RAZÃO SOCIAL
        $y += 3;
        $texto = 'NOME / RAZÃO SOCIAL';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->retirada->xNome ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //CNPJ / CPF
        $x += $w;
        $texto = 'CNPJ / CPF';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //Pegando valor do CPF/CNPJ
        $texto = '';
        $doc = $this->std->retirada->CNPJ ?? '';
        if (!empty($doc)) {
            $texto = $this->formatField($doc,"###.###.###/####-##");
        } else {
            $doc = $this->std->retirada->CPf ?? '';
            $texto = $this->formatField($this->std->retirada->CPF ?? '', "###.###.###-##");
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w1, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //INSCRIÇÃO ESTADUAL
        $x += $w1;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->retirada->IE ?? '123456';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w2, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //ENDEREÇO
        $y += $h;
        $x = $this->margesq;
        $texto = 'ENDEREÇO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->retirada->xLgr ?? '';
        $texto .= ', ' . $this->std->retirada->nro ?? '';
        $texto .= ' '. $this->std->retirada->xCpl ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w3, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //BAIRRO / DISTRITO
        $x += $w3;
        $texto = 'BAIRRO / DISTRITO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w4, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->retirada->xBairro ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w4, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //CEP
        $x += $w4;
        $texto = 'CEP';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->retirada->CEP ?? '12345000';
        $texto = $this->formatField($texto, "#####-###");
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w2, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //MUNICÍPIO
        $y += $h;
        $x = $this->margesq;
        $texto = 'MUNICÍPIO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w5, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->retirada->xMun ?? '';
        if (!empty($this->std->retirada->xPais) && strtoupper(trim($texto)) === "EXTERIOR") {
            $texto .= " - " . $this->std->xPais;
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w5, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //UF
        $x += $w5;
        $texto = 'UF';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $wuf, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->retirada->UF;
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $wuf, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //FONE / FAX
        $x += $wuf;
        $texto = 'FONE / FAX';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->retirada->fone ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w2, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        return 24;
    }

    protected function localL(float $y, string $tipo)
    {
        return 0;
    }

}
