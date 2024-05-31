<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitDadosAdicionaisNfe
{
    public function dadosAdicionais(string $infCpl = '', string $infAdFisco = '', float $heigth = null): object
    {
        $dados = "dadosAdicionais{$this->orientacao}";
        return $this->$dados($infCpl, $infAdFisco, $heigth);
    }

    public function dadosAdicionaisP(string $infCpl = '', string $infAdFisco = '', float $heigth = null): object
    {
        if (empty($heigth)) {
            $heigth = 24;
        }
        $h = $heigth;
        $y = $this->maxH - (7 + $h);
        $x = $this->margesq;
        $wAdic = round($this->wPrint * 0.66, 0);
        $wFisco = round($this->wPrint - $wAdic, 0);
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x, $y+3, $this->wPrint, $heigth, '', $aFont, 'T', 'L', 1, '');
        //linha vertical
        $this->pdf->line($x+$wAdic, $y+3, $x+$wAdic, $y+$heigth+3);

        //##################################################################################
        //DADOS ADICIONAIS
        $texto = "DADOS ADICIONAIS";
        $w = $this->wPrint;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $w, 8, $texto, $aFont, 'T', 'L', 0, '');

        //INFORMAÇÕES COMPLEMENTARES
        $texto = "INFORMAÇÕES COMPLEMENTARES";
        $y     += 3;
        $w     = $wAdic;
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //o texto com os dados adicionais foi obtido na função montaDANFE
        //e carregado em uma propriedade privada da classe
        $y     += 1;
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $info = $infCpl;
        $info = preg_replace('/(?:\s\s+)/', ' ', $info);
        $info = str_replace(';', "\n", $info);
        $resp = $this->limitLines(9, $info, $w, $aFont);
        $complemento = "";
        $infCpl = '';
        if ($resp->pos < $resp->len) {
            $infCpl = substr($info, $resp->pos);
            $complemento = "   CONTINUA ...";
        }
        $texto = trim($resp->string) . $complemento;
        $this->pdf->textBox($x, $y + 2, $w - 2, $h, $texto, $aFont, 'T', 'L', 0, '', false);
        //RESERVADO AO FISCO
        $texto = "RESERVADO AO FISCO";
        $x += $w;
        $y -= 1;
        $w = $this->wPrint - $w;
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $resp = $this->limitLines(9, $infAdFisco, $w, $aFont);
        $complemento = "";
        $infAdFisco = '';
        if ($resp->pos < $resp->len && !empty($info)) {
            $infAdFisco = substr($info, $resp->pos);
            $complemento = "   CONTINUA ...";
        }
        $texto = $resp->string . $complemento;
        $y     += 2;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];
        $this->pdf->textBox($x, $y, $w - 2, $h, $texto, $aFont, 'T', 'L', 0, '', false);

        return (object) [
            'infCpl' => $infCpl,
            'infAdFisco' => $infAdFisco,
            'heigth' => $h + 8
        ];
    }

    public function dadosAdicionaisL(string $infCpl, string $infAdFisco, float $heigth = null): object
    {

    }

    public function limitLines(int $max, string $texto, float $width, array $font): object
    {
        if (empty($texto)) {
            return (object) ['string' => '', 'pos' => 0, 'len' => 0];
        }
        $string = trim($texto);
        $numLin = $this->pdf->getNumLines($texto, $width, $font);
        $len = strlen($string);
        $pos = $len;
        if ($numLin > $max) {
            while (true) {
                $numLin = $this->pdf->getNumLines($string, $width, $font);
                if ($numLin > $max) {
                    $posspc = strrpos($string, ' ');
                    $poslf = strrpos($string, "\n");
                    $pos = $posspc < $poslf ? $poslf : $posspc;
                    $string = substr($string, 0, $pos);
                } else {
                    $posspc = strrpos($string, ' ');
                    $poslf = strrpos($string, "\n");
                    $pos = $posspc < $poslf ? $poslf : $posspc;
                    $string = substr($string, 0, $pos);
                    break;
                }
            }
        }
        return (object) ['string' => $string, 'pos' => $pos, 'len' => $len];
    }

}
