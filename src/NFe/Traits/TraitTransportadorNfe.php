<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitTransportadorNfe
{
    public function transportador(float $y): float
    {
        $trans = "transportador{$this->orientacao}";
        return $this->$trans($y);
    }

    public function transportadorP(float $y): float
    {
        $tipoFrete = $this->std->transp->modFrete ?? 0;
        if ($tipoFrete == 9) {
            return 0;
        }
        $x = $this->margesq;
        $h = 7;
        $w1 = round($this->wPrint * 0.29, 0);
        $w6 = round($this->wPrint/6, 0);
        $wuf = 8;
        $w2 = ($this->wPrint - $w1 -$w6 - $wuf) / 3;
        $w3 = $w1 + $w2;
        $w4 = 2 * $w2;

        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        //borda do bloco
        $this->pdf->textBox($x, $y+3, $this->wPrint, 21, '', $aFont, 'T', 'L', 1, '');
        //linhas horizontais
        $this->pdf->line($x, $y+10, $this->wPrint+$this->margesq, $y+10);
        $this->pdf->line($x, $y+17, $this->wPrint+$this->margesq, $y+17);
        //linhas verticais
        $this->pdf->line($x+$w1, $y+3, $x+$w1, $y+10);
        $this->pdf->line($x+$w1+$w2, $y+3, $x+$w1+$w2, $y+24);
        //$this->pdf->line($x+$w1+2*$w2, $y+3, $x+$w1+2*$w2, $y+10);
        $this->pdf->line($x+$w1+3*$w2, $y+10, $x+$w1+3*$w2, $y+17);
        $this->pdf->line($x+$w1+3*$w2+$wuf, $y+3, $x+$w1+3*$w2+$wuf, $y+24);
        $this->pdf->line($x+$w6, $y+17, $x+$w6, $y+24);
        $this->pdf->line($x+2*$w6, $y+17, $x+2*$w6, $y+24);
        $this->pdf->line($x+4*$w6, $y+17, $x+4*$w6, $y+24);

        //#####################################################################
        //TRANSPORTADOR / VOLUMES TRANSPORTADOS
        $texto = "TRANSPORTADOR / VOLUMES TRANSPORTADOS";
        $this->pdf->textBox($x, $y, $this->wPrint, 3, $texto, $aFont, 'T', 'L', 0, '');
        //NOME / RAZÃO SOCIAL
        $y += 3;
        $texto = 'NOME / RAZÃO SOCIAL';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'T', 'L', 0, '',true);
        $texto = $this->std->transp->transporta->xNome ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w1, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //FRETE POR CONTA
        $x += $w1;
        $texto = 'FRETE';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $tipoFrete = $this->std->transp->modFrete ?? 0;
        switch ($tipoFrete) {
            case 0:
                $texto = "0- Por conta do Emitente";
                break;
            case 1:
                $texto = "1-Por conta do Destinatário";
                break;
            case 2:
                $texto = "2-Por conta de Terceiro";
                break;
            case 3:
                $texto = "3-Próprio por conta Remetente";
                break;
            case 4:
                $texto = "4-Próprio por conta Destinatário";
                break;
            case 9:
                $texto = "9-Sem Transporte";
                break;
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w2, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        if (!empty($this->std->transp->vagao) || !empty($this->std->transp->balsa)) {
            //VAGÃO ou BALSA
            $texto = 'BALSA';
            if (!empty($this->std->transp->vagao)) {
                $texto = 'VAGÂO';
            }
            $x += $w2;
            $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
            $this->pdf->textBox($x, $y, 2*$w2+$wuf, $h, $texto, $aFont, 'T', 'L', 0, '', true);
            $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $texto = $this->std->transp->vagao ?? $this->std->transp->balsa;
            $this->pdf->textBox($x, $y+2, 2*$w2+$wuf, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        } else {
            $this->pdf->line($this->margesq+$w1+2*$w2, $y, $this->margesq+$w1+2*$w2, $y+7);
            $this->pdf->line($this->margesq+$w1+3*$w2, $y, $this->margesq+$w1+3*$w2, $y+7);
            $this->pdf->line($this->margesq+$w1+3*$w2+$wuf, $y, $this->margesq+$w1+3*$w2+$wuf, $y+7);
            //VEICULO ou REBOQUE
            //CÓDIGO ANTT
            $x += $w2;
            $texto = 'CÓDIGO ANTT (RNTC)';
            $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
            $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
            $vrntc = $this->std->transp->veicTransp->RNTC ?? '';
            $rrntc = $this->std->transp->reboque->RNTC ?? '';
            $texto = $vrntc . $rrntc; //RNTC do veiculo ou do reboque
            $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'B', 'C', 0, '', true);
            //PLACA DO VEÍC / REBOQUE
            $texto = 'PLACA DO VEÍCULO';
            if (!empty($this->std->transp->reboque)) {
                $texto = 'PLACA DO REBOQUE';
            }
            $x += $w2;
            $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
            $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
            $veic = $this->std->transp->veicTransp->placa ?? '';
            $reb = $this->std->transp->reboque->placa ?? '';
            $texto = $veic . $reb; //placa do veiculo ou do reboque
            $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'B', 'C', 0, '', true);
            //UF
            $x += $w2;
            $texto = 'UF';
            $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
            $this->pdf->textBox($x, $y, $wuf, $h, $texto, $aFont, 'T', 'L', 0, '', true);
            $vuf = $this->std->transp->veicTransp->UF ?? '';
            $ruf = $this->std->transp->reboque->UF ?? '';
            $texto = $vuf . $ruf; //uf do veiculo ou do reboque
            $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $this->pdf->textBox($x, $y, $wuf, $h, $texto, $aFont, 'B', 'C', 0, '', true);
        }
        //CNPJ / CPF
        $x = $this->wPrint-$w6+$this->margesq;
        $texto = 'CNPJ / CPF';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w6, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $jdoc = $this->std->transp->transporta->CNPJ ?? '';
        $fdoc = $this->std->transp->transporta->CPF ?? '';
        $texto = '';
        if (!empty($jdoc)) {
            $texto = $this->formatField($jdoc, "##.###.###/####-##");
        } elseif (!empty($fdoc)) {
            $texto = $this->formatField($fdoc,"###.###.###-##");
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w6, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //#####################################################################
        //ENDEREÇO
        $y += $h;
        $x = $this->margesq;
        $texto = 'ENDEREÇO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w1+$w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->transp->transporta->xEnder ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w1+$w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //MUNICÍPIO
        $x += $w1+$w2;
        $texto = 'MUNICÍPIO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, 2*$w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto =  $this->std->transp->transporta->xMun ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, 2*$w2, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //UF
        $x += 2*$w2;
        $texto = 'UF';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $wuf, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto =  $this->std->transp->transporta->UF ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $wuf, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //INSCRIÇÃO ESTADUAL
        $x += $wuf;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w6, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->std->transp->transporta->IE ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w6, $h, $texto, $aFont, 'T', 'C', 0, '');
        //Tratar Multiplos volumes
        $vol = $this->std->transp->vol;
        if (is_array($vol)) {
            $volumes = $vol;
        } else {
            $volumes[] = $vol;
        }
        $quantidade  = 0;
        $especie     = '';
        $marca       = '';
        $numero      = '';
        $pesoBruto   = 0;
        $pesoLiquido = 0;
        $esp = [];
        $marcas = [];
        $nvols = [];
        foreach ($volumes as $volume) {
            $quantidade  += $volume->qVol ?? 0;
            $pesoBruto   += $volume->pesoB ?? 0;
            $pesoLiquido += $volume->pesoL ?? 0;
            if (!empty($volume->nVol)) {
                $nvols[] = $volume->nVol;
            }
            if (!empty($volume->esp)) {
                $esp[] = strtoupper(trim($volume->esp));
            }
            if (!empty($volume->marca)) {
                $marcas[] = strtoupper(trim($volume->marca));
            }
        }
        $esp = array_unique($esp);
        $marcas = array_unique($marcas);
        $nvols = array_unique($nvols);
        if (count($esp) > 1) {
            $especie = 'VÁRIAS';
        } else {
            $especie = $esp[0] ?? '';
        }
        if (count($marcas) > 1) {
            $marca = 'VÁRIAS';
        } else {
            $marca = $marcas[0] ?? '';
        }
        if (count($nvols) > 1) {
            $numero = 'VÁRIOS';
        } else {
            $numero = $nvols[0] ?? '';
        }
        //#####################################################################
        //QUANTIDADE
        $y += $h;
        $x = $this->margesq;
        $texto = 'QUANTIDADE';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w6, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = !empty($quantidade) ? $quantidade : '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w6, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //ESPÉCIE
        $x += $w6;
        $texto = 'ESPÉCIE';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w6, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $especie;
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w6, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //MARCA
        $x += $w6;
        $texto = 'MARCA';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w6, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $marca;
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w6-7, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        //NUMERAÇÃO
        $x += $w6-7;
        $texto = 'NUMERAÇÃO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w6+7, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $numero;
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w6+7, $h, $texto, $aFont, 'T', 'C', 0, '', true);
        //PESO BRUTO
        $x = $this->wPrint-2*$w6+$this->margesq+2;
        $texto = 'PESO BRUTO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w6, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = !empty($pesoBruto) ? number_format($pesoBruto, 3, ",", ".") : '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w6-2, $h, $texto, $aFont, 'T', 'R', 0, '', true);
        //PESO LÍQUIDO
        $x = $this->wPrint-$w6+$this->margesq;
        $texto = 'PESO LÍQUIDO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w6, $h, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = !empty($pesoLiquido) ? number_format($pesoLiquido, 3, ",", ".") : '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y+2, $w6, $h, $texto, $aFont, 'T', 'R', 0, '', true);
        return 3*$h+3;
    }

    public function transportadorL(float $y): float
    {
        return 0;
    }

}
