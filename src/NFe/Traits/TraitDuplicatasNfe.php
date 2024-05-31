<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitDuplicatasNfe
{
    protected function duplicatas(float $y, array $dups, int $total = 1): float
    {
        $dup = "duplicatas{$this->orientacao}";
        return $this->$dup($y, $dups, $total);
    }

    protected function duplicatasP(float $y, array $dups, int $total = 0): float
    {
        $x = $this->margesq;
        $linha = 1;
        $oldx = $x;
        if (empty($dups) && $this->exibirFatura && !empty($this->std->cobr->fat)) {
            //exibir dados de pagamento no bloco
            $texto = $this->getTextoFatura();
            $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $lines = $this->pdf->getNumLines($texto, $this->wPrint, $aFont);
            $tit = "FATURA / DUPLICATA";
            $w = $this->wPrint;
            $h = $lines * 4;
            $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
            $this->pdf->textBox($x, $y, $w, 5, $tit, $aFont, 'T', 'L', 0, '');
            $this->pdf->textBox($x, $y+3, $w, $h, '', $aFont, 'T', 'L', 1, '');
            $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $this->pdf->textBox($x, $y+3, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
            return $h+3;
        }
        //caso não tenha dados de
        if (empty($dups) && !$this->exibirFatura) {
            return 0;
        }


        //#####################################################################
        $w = $this->wPrint / 10;
        $h = 8;
        //FATURA / DUPLICATA
        $texto = "FATURA / DUPLICATA";
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yx = $y+3;
        //define o numero de blocos para inserir das duplicatas
        $linhas = ceil(count($dups)/10);
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        for($l=1; $l<=$linhas; $l++) {
            //insere a borda do bloco
            $this->pdf->textBox($x, $yx, $this->wPrint, $h, '', $aFont, 'T', 'L', 1, '');
            $xl = $x;
            for($k=1; $k<=9; $k++) {
                $xl += $w;
                $this->pdf->line($xl, $yx, $xl, $yx+$h);
            }
            $yx += $h;
        }
        $y += 3;
        $slice = array_chunk($dups, 10);
        $hfim = 0;
        foreach ($slice as $key => $ds) {
            $hfim += $h;
            foreach ($ds as $d) {
                $nDup = $d->nDup;
                $dDup = \DateTime::createFromFormat('Y-m-d', $d->dVenc)->format('d/m/Y');
                $vDup = 'R$ ' . number_format($d->vDup, 2, ',', '.');
                $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
                $this->pdf->textBox($x, $y, $w, $h, 'Num.', $aFont, 'T', 'L', 0, '');
                $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
                $this->pdf->textBox($x, $y, $w, $h, $nDup, $aFont, 'T', 'R', 0, '');
                $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
                $this->pdf->textBox($x, $y, $w, $h, 'Venc.', $aFont, 'C', 'L', 0, '');
                $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
                $this->pdf->textBox($x, $y, $w, $h, $dDup, $aFont, 'C', 'R', 0, '');
                $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
                $this->pdf->textBox($x, $y, $w, $h, 'Valor', $aFont, 'B', 'L', 0, '');
                $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
                $this->pdf->textBox($x + 5.4, $y, $w - 5.4, $h, $vDup, $aFont, 'B', 'R', 0, '');
                $x += $w;
            }
            if ($key == count($slice) - 1 && $total > count($dups)) {
                //tem mais duplicatas do que o limite de 20
                //incluir uma mensagem
                $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'B'];
                $this->pdf->textBox($x, $y, $w, $h, 'EXISTEM MAIS DUPLICATAS NO XML', $aFont, 'C', 'C', 1, '', false);
            }
            $x = $this->margesq;
            $y += 8;
        }
        return $hfim + 3;
    }

    protected function duplicatasL(float $y, array $dups, int $total = 1): float
    {
        return 0;
    }

    protected function getTextoFatura()
    {
        $formaPag = [
            0 => 'Pagamento à vista',
            1 => 'Pagamento a prazo',
            2 => 'Outros'
        ];
        $tipoPag = [
            '01' => 'Dinheiro',
            '02' => 'Cheque',
            '03' => 'Cartão de Crédito',
            '04' => 'Cartão de Débito',
            '05' => 'Crédito Loja',
            '10' => 'Vale Alimentação',
            '11' => 'Vale Refeição',
            '12' => 'Vale Presente',
            '13' => 'Vale Combustível',
            '15' => 'Boleto Bancário',
            '90' => 'Sem Pagamento',
            '99' => 'Outro',
        ];
        $bandeira = [
            '01' => 'Visa',
            '02' => 'Mastercard',
            '03' => 'American Express',
            '04' => 'Sorocred',
            '05' => 'Diners Club',
            '06' => 'Elo',
            '07' => 'Hipercard',
            '08' => 'Aura',
            '09' => 'Cabal',
            '99' => 'Outros',
        ];
        $fat = $this->std->cobr->fat ?? null;
        $pag = $this->std->pag ?? null;
        $texto = '';
        $textoFat = '';
        $itens = [];
        $troco = null;
        $indPag = $this->std->ide->indPag ?? null;
        if (isset($indPag)) {
            $textoFat = $formaPag[$indPag] . "\n";
        }
        if (isset($fat)) {
            $nFat = "Número Fatura: {$fat->nFat}";
            $vOrig = ", Valor Original: R$ " . number_format($fat->vOrig, 2, ',', '.');
            $vDesc = ", Valor Desconto: R$ " . number_format($fat->vDesc, 2, ',', '.');
            $vLiq = ", Valor Líquido: R$ " . number_format($fat->vLiq, 2, ',', '.');
            $textoFat .= $nFat . ' ' . $vOrig . ' ' . $vDesc . ' ' . $vLiq . "\n";
        }
        if (isset($pag)) {
            $i = 0;
            if (is_array($pag->detPag)) {
                foreach ($pag->detPag as $dp) {
                    if (isset($dp->indPag)) {
                        $itens[$i]['forma'] = $formaPag[$dp->indPag];
                    }
                    $itens[$i]['tipo'] = $tipoPag[$dp->tPag];
                    if ((string)$dp->tPag === '99') {
                        $itens[$i]['tipo'] = $tipoPag[$dp->tPag] . ', ' . $dp->xPag;
                    }
                    $itens[$i]['valor'] = 'R$ ' . number_format($dp->vPag, 2, ',', '.');
                    if (!empty($dp->card)) {
                        if (!empty($dp->card->tBand)) {
                            $itens[$i]['bandeira'] = $bandeira[$dp->card->tBand];
                        }
                        if (!empty($dp->card->cAut)) {
                            $itens[$i]['autenticacao'] = $dp->card->cAut;
                        }
                    }
                    $i++;
                }
            } else {
                $dp = $pag->detPag;
                if (isset($dp->indPag)) {
                    $textoFat = $formaPag[$dp->indPag] . "\n" . $textoFat;
                }
                $itens[$i]['tipo'] = $tipoPag[$dp->tPag];
                if ((string)$dp->tPag === '99') {
                    $itens[0]['tipo'] = $tipoPag[$dp->tPag] . ', ' . $dp->xPag;
                }
                $itens[$i]['valor'] = 'R$ ' . number_format($dp->vPag, 2, ',', '.');
                if (!empty($dp->card)) {
                    if (!empty($dp->card->tBand)) {
                        $itens[0]['bandeira'] = $bandeira[$dp->card->tBand];
                    }
                    if (!empty($dp->card->cAut)) {
                        $itens[0]['autenticacao'] = $dp->card->cAut;
                    }
                }
            }
            $troco = !empty($pag->troco) ? 'R$ ' . number_format($pag->troco, 2, ',', '.') : null;
        }
        if (count($itens) == 1) {
            //tem um unico conjunto de detPag
            $texto = $textoFat;
            $texto .= $itens[0]['tipo'];
            $texto .= !empty($itens[0]['bandeira']) ? $itens[0]['bandeira'] : '';
            $texto .= !empty($itens[0]['autenticacao']) ? $itens[0]['autenticacao'] : '';
        } else {
            foreach($itens as $item) {
                $std = (object) $item;
                $texto = $textoFat;
                $texto .= !empty($std->forma) ? $std->forma . ', ' : '';
                $texto .= $std->tipo . ' - Valor: ' . $std->valor;
                $texto .= !empty($std->bandeira) ? $std->bandeira : '';
                $texto .= !empty($std->autenticacao) ? ' - aut: '. $std->autenticacao : '';
                $texto .= "\n";
            }
        }
        if (!empty($troco)) {
            $texto .= ' Troco: ' . $troco;
        }
        return $texto;
    }
}
