<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitCanhotoNfe
{

    /**
     * Insere o canhoto
     * @return void
     */
    protected function canhoto(): float
    {
        $cabecfunc = "canhoto{$this->orientacao}";
        $emitente = $this->std->emit->xNome;
        $data = $this->data->format('d/m/Y H:i:s');
        $valor = number_format(
            (float)$this->std->total->ICMSTot->vNF,
            2,
            ',',
            '.'
        );
        $destinatario = $this->std->dest->xNome
            . ' ' . $this->std->dest->enderDest->xLgr
            . ', ' . $this->std->dest->enderDest->nro
            . ' ' . $this->std->dest->enderDest->xBairro
            . ' ' . $this->std->dest->enderDest->xMun
            . '-' . $this->std->dest->enderDest->UF;
        return $this->$cabecfunc($emitente, $this->numero, $this->serie, $data, $valor, $destinatario);
    }

    /**
     * Insere canhoto
     * @param string $emitente
     * @return int
     */
    protected function canhotoP($emitente, $numero, $serie, $data, $valor, $destinatario): float
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];
        $aFontSmall = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $w = round($this->wPrint * 0.86, 0); //texto princippal
        $w1 = $this->wPrint - $w; //dados da nfe
        $w2 = round($this->wPrint * 0.17, 0); //data recebimento
        $w3 = $this->wPrint - $w2 - $w1; //assinatura
        $x = $this->margesq;
        $y = $this->margsup;
        $texto = "RECEBEMOS DE ";
        $texto .= $emitente;
        $texto .= " OS PRODUTOS E/OU SERVIÇOS CONSTANTES DA NOTA FISCAL ELETRÔNICA INDICADA ABAIXO. EMISSÃO: ";
        $texto .= $data;
        $texto .= " NO VALOR TOTAL: R$ ";
        $texto .= $valor . " ";
        $texto .= "DESTINATÁRIO: ";
        $texto .= $destinatario . ' bla sjksj skjs js js kjsl sj slksjlskjsslksjlsk slkjsls sjs lkjs kjslskjslkjslksjsk kjskj lskj lskj skjslkjs slkjslk slksj skjs lksjs lksj sklsj';
        $h = ceil($this->calculeHeight($texto, $w, $aFont)+1);
        $h1 = 8; //altura da linha para escrever
        $htot = $h + $h1 + 1;
        //################## BORDAS E DIVISORIAS
        //desenha caixa externa
        $this->pdf->textBox($x, $y, $this->wPrint, $htot, '', $aFont, 'C', 'L', 1, '', false);
        //linha separadora horizontal
        $this->pdf->line($this->margesq, $y+$h, $this->margesq+$w, $y+$h+1);
        //linha separadora vertical
        $this->pdf->line($this->margesq+$w, $this->margsup, $this->margesq+$w, $htot+$this->margsup);
        //linha separadora vertical inferior
        $this->pdf->line($this->margesq+$w2, $this->margsup+$h, $this->margesq+$w2, $htot+$this->margsup);
        //########### BLOCO TEXTO
        //insere texto
        $this->pdf->textBox($x, $y, $w-3, $h, $texto, $aFont, 'C', 'L', 0, '', false);
        $x1 = $x + $w;
        $texto = "NF-e";
        $aFont = ['font' => $this->fontePadrao, 'size' => 14, 'style' => 'B'];
        $this->pdf->textBox($x1, $y, $w1, $htot, $texto, $aFont, 'T', 'C', 0, '');
        $texto = "Nº. " . $numero . " \n";
        $texto .= "Série $serie";
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x1, $y, $w1, $htot, $texto, $aFont, 'C', 'C', 0, '');

        //################ BLOCO DATA / ASSINATURA
        //DATA DE RECEBIMENTO
        $texto = "DATA DE RECEBIMENTO";
        $y += $h;
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w2, $h1,$texto, $aFont, 'T', 'L', 0, '');
        //IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR
        $x += $w2;
        $w3 = $w - $w2;
        $texto = "IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR";
        $this->pdf->textBox($x, $y, $w3, $h1, $texto, $aFont, 'T', 'L', 0, '');
        $y = $htot+$this->margsup+1;
        $this->pdf->dashedHLine($this->margesq, $y, $this->wPrint, 0.1, 80);
        return $htot+2;
    }

    protected function canhotoL($emitente, $numero, $serie, $data, $valor, $destinatario): float
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];
        $aFontSmall = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $h = round($this->hPrint * 0.86, 0); //texto princippal
        $h1 = $this->hPrint - $h - $this->margsup; //dados da nfe
        $h2 = round($this->hPrint * 0.17, 0); //data recebimento
        $h3 = $this->wPrint - $h2 - $h1; //assinatura
        $x = $this->margesq;
        $y = $this->margsup;
        $texto = "RECEBEMOS DE ";
        $texto .= $emitente;
        $texto .= " OS PRODUTOS E/OU SERVIÇOS CONSTANTES DA NOTA FISCAL ELETRÔNICA INDICADA ABAIXO. EMISSÃO: ";
        $texto .= $data;
        $texto .= " NO VALOR TOTAL: R$ ";
        $texto .= $valor . " ";
        $texto .= "DESTINATÁRIO: ";
        $texto .= $destinatario;
        $w = ceil($this->calculeHeight($texto, $h, $aFont)+1);
        $w1 = 8; //altura da linha para escrever
        //$wtot = $w + $w1 + 1;
        //################## BORDAS E DIVISORIAS
        //como está rotacionado as coordenadas y iniciam de baixo para cima
        $this->pdf->textBox90($this->margesq, $this->hPrint, $this->hPrint-$this->margsup, $w+$w1, '', $aFont, 'C', 'L', 1, '', false);
        //linha separadora vertical
        $this->pdf->line($this->margesq+$w, $this->hPrint, $this->margesq+$w, $this->hPrint-$h);
        //linha separadora horizontal
        $this->pdf->line($this->margesq, $this->hPrint-$h, $this->margesq+$w+$w1, $this->hPrint-$h);
        //linha separadora horizontal inferior
        //$this->margesq+$w, $this->hPrint-$h2-$this->margsup, $h-$this->margsup, $w
        $this->pdf->line($this->margesq+$w, $this->hPrint-$h2-$this->margsup, $this->margesq+$w+$w1, $this->hPrint-$h2-$this->margsup);
        //########### BLOCO TEXTO
        //insere texto
        $this->pdf->textBox90($this->margesq, $this->hPrint, $h-$this->margsup, $w, $texto, $aFont, 'C', 'L', 0, '', false);
        $texto = "NF-e";
        $aFont = ['font' => $this->fontePadrao, 'size' => 14, 'style' => 'B'];
        $this->pdf->textBox90($this->margesq, $this->hPrint-$h, $h1, 7, $texto, $aFont, 'T', 'C', 0, '');
        $texto = "Nº. " . $numero . " \n";
        $texto .= "Série $serie";
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox90($this->margesq+4.5, $this->hPrint-$h, $h1, $w1, $texto, $aFont, 'C', 'C', 0, '');
        //################ BLOCO DATA / ASSINATURA
        //DATA DE RECEBIMENTO
        $texto = "DATA DE RECEBIMENTO";
        $y += $h;
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox90($this->margesq+$w, $this->hPrint, $h-$this->margsup, $w,$texto, $aFont, 'T', 'L', 0, '');

        //IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR
        $texto = "IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR";
        $this->pdf->textBox90($this->margesq+$w, $this->hPrint-$h2-$this->margsup, $h-$this->margsup, $w, $texto, $aFont, 'T', 'L', 0, '');
        /*
        $y = $htot+$this->margsup+1;
        $this->pdf->dashedHLine($this->margesq, $y, $this->wPrint, 0.1, 80);
        return $htot+2;
        */
        //$this->pdf->line($this->margesq+$w, $this->hPrint, $this->margesq+$w, $this->hPrint-$h);
        //$this->pdf->dashedHLine($this->margesq+$w, $y, $this->wPrint, 0.1, 80);
        $this->pdf->dashedVLine($this->margesq+$w+$w1+1, $this->hPrint, 0.1, $this->margsup, 80);
        return 2;

    }
}
