<?php
namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco VII informações do consumidor e dados da NFCe
 */
trait TraitBlocoVII
{
    protected function blocoVII($y)
    {
        //$this->bloco7H = 20;
        
        /*
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco7H, '', $aFont, 'T', 'C', false, '', false);
        */
        
        $nome = $this->getTagValue($this->dest, "xNome");
        $cnpj = $this->getTagValue($this->dest, "CNPJ");
        $cpf = $this->getTagValue($this->dest, "CPF");
        $rua = $this->getTagValue($this->enderDest, "xLgr");
        $numero = $this->getTagValue($this->enderDest, "nro");
        $complemento = $this->getTagValue($this->enderDest, "xCpl");
        $bairro = $this->getTagValue($this->enderDest, "xBairro");
        $mun = $this->getTagValue($this->enderDest, "xMun");
        $uf = $this->getTagValue($this->enderDest, "UF");
        $texto = '';
        $yPlus = 0;
        if (!empty($cnpj)) {
            $texto = "CONSUMIDOR - CNPJ "
                . $this->formatField($cnpj, "##.###.###/####-##") . " - " . $nome;
            $texto .= "\n {$rua}, {$numero} {$complemento} {$bairro} {$mun}-{$uf}";
        } elseif (!empty($cpf)) {
            $texto = "CONSUMIDOR - CPF "
                . $this->formatField($cpf, "###.###.###-##") . " = " . $nome;
            $texto .= "\n {$rua}, {$numero} {$complemento} {$bairro} {$mun}-{$uf}";
        } else {
            $texto = 'CONSUMIDOR NÃO IDENTIFICADO';
            $yPlus = 1;
        }
        if ($this->getTagValue($this->nfeProc, "xMsg")) {
            $texto .= "\n {$this->getTagValue($this->nfeProc, "xMsg")}";
            $this->bloco7H += 4;
        }
        $subSize = 0;
        if ($this->paperwidth < 70) {
            $subSize = 1.5;
        }
        if ($this->tpEmis == 9) {
            $aFont = ['font'=> $this->fontePadrao, 'size' => (7-$subSize), 'style' => ''];
            $y += 2*$yPlus;
            $y1 = $this->pdf->textBox(
                $this->margem,
                $y,
                $this->wPrint,
                $this->bloco7H,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                false
            );
        
            $y1 += 2*$yPlus;
            $num = str_pad($this->getTagValue($this->ide, "nNF"), 9, '0', STR_PAD_LEFT);
            $serie = str_pad($this->getTagValue($this->ide, "serie"), 3, '0', STR_PAD_LEFT);
            $data = (new \DateTime($this->getTagValue($this->ide, "dhEmi")))->format('d/m/Y H:i:s');
            $texto = "NFCe n. {$num} Série {$serie} {$data}";
            $aFont = ['font'=> $this->fontePadrao, 'size' => 8, 'style' => 'B'];
            $y2 = $this->pdf->textBox(
                $this->margem,
                $y+$y1,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );

            $texto = $this->via;
            $y3 = $this->pdf->textBox(
                $this->margem,
                $y+$y1+$y2,
                $this->wPrint,
                4,
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
            $y4 = $this->pdf->textBox(
                $this->margem,
                $y+$y1+$y2+$y3,
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
            $y5 = $this->pdf->textBox(
                $this->margem,
                $y+$y1+$y2+$y3+$y4,
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
            $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
            $y1 = $this->pdf->textBox(
                $this->margem,
                $y+1,
                $this->wPrint,
                $this->bloco7H,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                false
            );
        
            $num = str_pad($this->getTagValue($this->ide, "nNF"), 9, '0', STR_PAD_LEFT);
            $serie = str_pad($this->getTagValue($this->ide, "serie"), 3, '0', STR_PAD_LEFT);
            $data = (new \DateTime($this->getTagValue($this->ide, "dhEmi")))->format('d/m/Y H:i:s');
            $texto = "NFCe n. {$num} Série {$serie} {$data}";
            $aFont = ['font'=> $this->fontePadrao, 'size' => (8-$subSize), 'style' => 'B'];
            $y2 = $this->pdf->textBox(
                $this->margem,
                $y+1+$y1,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );

            $protocolo = '';
            $data = '';
            if (!empty($this->nfeProc)) {
                $protocolo = $this->formatField($this->getTagValue($this->nfeProc, 'nProt'), '### ########## ##');
                $data = (new \DateTime($this->getTagValue($this->nfeProc, "dhRecbto")))->format('d/m/Y H:i:s');
            }
            
            $texto = "Protocolo de Autorização:  {$protocolo}";
            $aFont = ['font'=> $this->fontePadrao, 'size' => (8-$subSize), 'style' => ''];
            $y3 = $this->pdf->textBox(
                $this->margem,
                $y+1+$y1+$y2,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );
            
            $texto = "Data de Autorização:  {$data}";
            $aFont = ['font'=> $this->fontePadrao, 'size' => (8-$subSize), 'style' => ''];
            $y4 = $this->pdf->textBox(
                $this->margem,
                $y+1+$y1+$y2+$y3,
                $this->wPrint,
                4,
                $texto,
                $aFont,
                'T',
                'C',
                false,
                '',
                true
            );
        }
        $this->pdf->dashedHLine($this->margem, $this->bloco7H+$y, $this->wPrint, 0.1, 30);
        return $this->bloco7H + $y;
    }
}
