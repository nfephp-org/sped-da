<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco forma de pagamento
 */
trait TraitBlocoV
{
    protected function blocoV($y)
    {
        $this->bloco5H = $this->calculateHeightPag();

        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        //$this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco5H, '', $aFont, 'T', 'C', true, '', false);
        $arpgto = [];
        if ($this->pag->length > 0) {
            foreach ($this->pag as $pgto) {
                $tipo = $this->pagType((int) $this->getTagValue($pgto, 'tPag'));
                $valor = number_format((float) $this->getTagValue($pgto, 'vPag'), 2, ',', '.');
                $arpgto[] = [
                    'tipo' => $tipo,
                    'valor' => $valor
                ];
            }
        } else {
            $tipo = $this->pagType((int) $this->getTagValue($this->pag, 'tPag'));
            $valor = number_format((float) $this->getTagValue($pgto, 'vPag'), 2, ',', '.');
            $arpgto[] = [
                'tipo' => $tipo,
                'valor' => $valor
            ];
        }
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $texto = "FORMA PAGAMENTO";
        $this->pdf->textBox($this->margem, $y, $this->wPrint, 4, $texto, $aFont, 'T', 'L', false, '', false);
        $texto = "VALOR PAGO R$";
        $y1 = $this->pdf->textBox($this->margem, $y, $this->wPrint, 4, $texto, $aFont, 'T', 'R', false, '', false);

        $z = $y + $y1;
        foreach ($arpgto as $p) {
            $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $p['tipo'], $aFont, 'T', 'L', false, '', false);
            $y2 = $this->pdf->textBox(
                $this->margem,
                $z,
                $this->wPrint,
                3,
                $p['valor'],
                $aFont,
                'T',
                'R',
                false,
                '',
                false
            );
            $z += $y2;
        }

        $texto = "Troco R$";
        $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', false);
        $texto =  !empty($this->vTroco) ? number_format((float) $this->vTroco, 2, ',', '.') : '0,00';
        $y1 = $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'R', false, '', false);


        $this->pdf->dashedHLine($this->margem, $this->bloco5H+$y, $this->wPrint, 0.1, 30);
        return $this->bloco5H + $y;
    }

    protected function pagType($type)
    {
        $lista = [
            1 => 'Dinheiro',
            2 => 'Cheque',
            3 => 'Cartão de Crédito',
            4 => 'Cartão de Débito',
            5 => 'Crédito Loja',
            10 => 'Vale Alimentação',
            11 => 'Vale Refeição',
            12 => 'Vale Presente',
            13 => 'Vale Combustível',
            15 => 'Boleto Bancário',
            16 => 'Depósito Bancário',
            17 => 'Pagamento Instantâneo (PIX)',
            18 => 'Transferência bancária, Carteira Digital',
            19 => 'Programa de fidelidade, Cashback, Crédito Virtual',
            90 => 'Sem pagamento',
            99 => 'Outros',
        ];
        return $lista[$type];
    }

    protected function calculateHeightPag()
    {
        $n = $this->pag->length > 0 ? $this->pag->length : 1;
        $height = 4 + (2.4 * $n) + 3;
        return $height;
    }
}
