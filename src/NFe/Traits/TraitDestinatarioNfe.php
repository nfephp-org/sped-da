<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitDestinatarioNfe
{
    protected function destinatario(float $y): float
    {
        $dest = "destinatario{$this->orientacao}";
        return $this->$dest($y);
    }

    protected function destinatarioP(float $y): float
    {
        $x = $this->margesq;
        $oldX = $x;
        //####################################################################################
        //DESTINATÁRIO / REMETENTE
        $w = round($this->wPrint * 0.61, 0); //nome
        $w1 = round($this->wPrint * 0.23, 0); //cnpj
        $w2 = $this->wPrint - $w - $w1; //datas horas
        $w3 = round($this->wPrint * 0.47, 0); //endereço
        $w4 = round($this->wPrint * 0.21, 0); //bairro
        $w5 = $this->wPrint - $w3 - $w4 - $w2; //CEP
        $wuf = 8;
        $w6 = $this->wPrint - $w3 - $wuf - $w2 - $w5;
        $w7 = $this->wPrint - $w3 - $wuf - $w2 - $w6;
        $h     = 7;
        $htot  = 21;
        $texto = 'DESTINATÁRIO / REMETENTE';
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $this->wPrint, $h, $texto, $aFont, 'T', 'L', 0, '');
        //borda do campo destinatário/remetente
        $this->pdf->textBox($x, $y+3, $this->wPrint, $htot, '', $aFont, 'T', 'L', 1, '');
        //linhas horizontais
        $this->pdf->line($x, $y+10, $this->wPrint+$this->margesq, $y+10);
        $this->pdf->line($x, $y+17, $this->wPrint+$this->margesq, $y+17);
        //linhas verticais
        $this->pdf->line($x+$w, $y+3, $x+$w, $y+10); //nome | cnpj
        $this->pdf->line($x+$w+$w1, $y+3, $x+$w+$w1, $y+$htot+3); //nome | cnpj
        $this->pdf->line($x+$w3, $y+10, $x+$w3, $y+24); //endereco | bairro
        $this->pdf->line($x+$w3+$w4, $y+10, $x+$w3+$w4, $y+24); //bairro | cep
        $this->pdf->line($x+$w3+8, $y+17, $x+$w3+8, $y+24); //municipio | uf

        //NOME / RAZÃO SOCIAL w = 61 %
        $y += 3;
        $texto = 'NOME / RAZÃO SOCIAL';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->dest->xNome;
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //CNPJ / CPF
        $x     += $w;
        $texto = 'CNPJ / CPF';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //Pegando valor do CPF/CNPJ
        if (!empty($this->std->dest->CNPJ ?? null)) {
            $texto = $this->formatField(
                $this->std->dest->CNPJ,
                "###.###.###/####-##"
            );
        } else {
            $texto = !empty($this->std->dest->CPF ?? null)
                ? $this->formatField(
                    $this->std->dest->CPF,
                    "###.###.###-##"
                )
                : '';
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w1, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //DATA DA EMISSÃO
        $x += $w1;
        $texto = 'DATA DA EMISSÃO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->data->format('d/m/Y');
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w2, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //ENDEREÇO
        $y += $h;
        $x = $this->margesq;
        $texto = 'ENDEREÇO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->dest->enderDest->xLgr;
        $texto .= ', ' . $this->std->dest->enderDest->nro;
        $texto .= ' ' . ($this->std->dest->enderDest->xCpl ?? '');
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w3, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //BAIRRO / DISTRITO
        $x += $w3;
        $texto = 'BAIRRO / DISTRITO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w4, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->dest->enderDest->xBairro ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w4, $h, $texto, $aFont, 'T', 'C', 0, '',true);
        //CEP
        $x += $w4;
        $texto = 'CEP';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w5, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->dest->enderDest->CEP ?? '';
        $texto = $this->formatField($texto, "#####-###");
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w5, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //DATA DA SAÍDA
        $x += $w5;
        $texto = 'DATA DA SAÍDA/ENTRADA';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto =  !empty($this->datasaida) ? $this->datasaida->format('d/m/Y') : '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w2, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //MUNICÍPIO
        $y += $h;
        $x = $this->margesq;
        $texto = 'MUNICÍPIO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->dest->enderDest->xMun;
        if (strtoupper(trim($texto)) === "EXTERIOR" && !empty($this->std->dest->enderDest->xPais)) {
            $texto .= " - " . $this->std->dest->enderDest->xPais;
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w3, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //UF
        $x += $w3;
        $texto = 'UF';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $wuf, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->dest->enderDest->UF;
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $wuf, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //FONE / FAX
        $x += $wuf;
        $texto = 'FONE / FAX';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w6, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->dest->enderDest->fone ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w6, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //INSCRIÇÃO ESTADUAL
        $x += $w6;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w7, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->dest->enderDest->IE ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w7, $h, $texto, $aFont, 'T', 'R', 0, '', true);
        //HORA DA SAÍDA
        $x += $w7;
        $texto = 'HORA DA SAÍDA/ENTRADA';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = !empty($this->datasaida) ? $this->datasaida->format('H:i:s') : '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w2, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        return 24;
    }

    protected function destinatarioL(float $y): float
    {
        return 24;
    }
}
