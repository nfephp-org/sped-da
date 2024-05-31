<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitHeaderNfe
{
    protected function header(float $y, $pagina = 1, $totalPaginas = 1): float
    {
        $head = "header{$this->orientacao}";
        return $this->$head($y, $pagina, $totalPaginas);
    }

    protected function headerP(float $y, $pagina = 1, $totalPaginas = 1): float
    {
        $x = $this->margesq;
        $w = round($this->wPrint * 0.40, 0);
        $w1  = round($this->wPrint * 0.17, 0); //35;
        $w2 = $this->wPrint - ($w + $w1);
        $w3 = round($this->wPrint * 0.250, 0); //ultima linha 4 divisorias
        $h    = 32;
        $h1   = 7;
        //desenha caixa principal
        $this->pdf->textBox($this->margesq, $y, $this->wPrint, $h+2*$h1);
        //linha divisória vertical
        $this->pdf->line($this->margesq+$w, $y, $this->margesq+$w, $y+$h);
        //linha divisória vertical
        $this->pdf->line($this->margesq+$w+$w1, $y, $this->margesq+$w+$w1, $y+$h+$h1);
        //linha divisoria horizontal
        $this->pdf->line($this->margesq, $y+$h, $this->wPrint+$this->margesq, $y+$h);
        //linha divisória vertical
        $this->pdf->line($this->margesq+$w, $y+$h, $this->margesq+$w, $y+$h);
        //####################################################################################
        //coluna esquerda identificação do emitente
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'I'];
        $texto = 'IDENTIFICAÇÃO DO EMITENTE';
        $this->pdf->textBox($x, $y, $w, 5, $texto, $aFont, 'T', 'C', 0, '', true);
        //estabelecer o alinhamento
        //pode ser left L, center C, right R, full logo L
        //se for left separar 1/3 da largura para o tamanho da imagem
        //os outros 2/3 serão usados para os dados do emitente
        //se for center separar 1/2 da altura para o logo e 1/2 para os dados
        //se for right separa 2/3 para os dados e o terço seguinte para o logo
        //se não houver logo centraliza dos dados do emitente
        $force = true;
        // coloca o logo
        if (!empty($this->logomarca)) {
            $logoInfo = getimagesize($this->logomarca);
            //largura da imagem em mm
            $logoWmm = ($logoInfo[0] / 72) * 25.4;
            //altura da imagem em mm
            $logoHmm = ($logoInfo[1] / 72) * 25.4;
            if ($this->logoAlign === 'L') {
                $nImgW = round($w / 3, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg  = $x;
                $yImg  = round(($h - $nImgH) / 2, 0) + $y;
                //estabelecer posições do texto
                $x1 = round($xImg + $nImgW, 0);
                $y1 = round($h / 8 + $y, 0);
                $y2    = $y1;
                $tw = $w - $nImgW;
                $force = false;
            } elseif ($this->logoAlign === 'C') {
                $nImgH = round($h / 3, 0);
                $nImgW = round($logoWmm * ($nImgH / $logoHmm), 0);
                $xImg  = round(($w - $nImgW) / 2 + $x, 0);
                $yImg  = $y + 3;
                $x1    = $x+1;
                $y1    = round($yImg + $nImgH+1, 0);
                $y2    = $y1;
                $tw    = $w;
            } elseif ($this->logoAlign === 'R') {
                $nImgW = round($w / 3, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg  = round($x + ($w - (1 + $nImgW)), 0);
                $yImg  = round(($h - $nImgH) / 2, 0) + $y;
                $x1    = $x;
                $y1    = round($h / 8 + $y, 0);
                $y2    = $y1;
                $tw    = $w - $nImgW;
                $force = false;
            } elseif ($this->logoAlign === 'F') {
                $nImgH = round($h - 5, 0);
                $nImgW = round($logoWmm * ($nImgH / $logoHmm), 0);
                $xImg  = round(($w - $nImgW) / 2 + $x, 0);
                $yImg  = $y + 3;
                $x1    = $x;
                $y1    = round($yImg + $nImgH + 1, 0);
                $tw    = $w;
            }
            $type = (substr($this->logomarca, 0, 7) === 'data://') ? 'jpg' : null;
            $this->pdf->Image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $x1 = $x;
            $y1 = round($h / 3 + $y, 0);
            $tw = $w;
        }
        // monta as informações apenas se diferente de full logo
        if ($this->logoAlign !== 'F') {
            //Nome emitente
            $aFont = ['font' => $this->fontePadrao, 'size' => 12, 'style' => 'B'];
            $texto = $this->std->emit->xNome;
            $this->pdf->textBox($x1 - 1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '', $force);
            if (!$force) {
                $lines = $this->pdf->getNumLines($texto, $tw, $aFont);
                $y2 = $y2 + $lines * 6;
            } else {
                $y2 += 5;
            }
            //endereço
            $aFont  = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
            $fone   = $this->std->emit->enderEmit->fone ?? '';
            $lgr    = $this->std->emit->enderEmit->xLgr;
            $nro    = $this->std->emit->enderEmit->nro;
            $cpl    = $this->std->emit->enderEmit->xCpl ?? '';
            $bairro = $this->std->emit->enderEmit->xBairro;
            $CEP    = $this->std->emit->enderEmit->CEP;
            $CEP    = $this->formatField($CEP, "#####-###");
            $mun    = $this->std->emit->enderEmit->xMun;
            $UF     = $this->std->emit->enderEmit->UF;
            $texto  = $lgr . ", " . $nro . $cpl . "\n" . $bairro . " - "
                . $CEP . "\n" . $mun . " - " . $UF . " "
                . "Fone/Fax: " . $fone;
            $this->pdf->textBox($x1-1, $y2, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        }
        //####################################################################################
        //coluna central Danfe
        $x = $this->margesq + $w;
        $texto = "DANFE";
        $aFont = ['font' => $this->fontePadrao, 'size' => 14, 'style' => 'B'];
        $this->pdf->textBox($x, $y + 1, $w1, $h1, $texto, $aFont, 'T', 'C', 0, '', true);
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $texto = 'Documento Auxiliar da Nota Fiscal Eletrônica';
        $this->pdf->textBox($x, $y + 6, $w1, $h1, $texto, $aFont, 'T', 'C', 0, '', false);
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $texto = '0 - ENTRADA';
        $y1    = $y + 14;
        $this->pdf->textBox($x + 2, $y1, $w1/2, $h1, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = '1 - SAÍDA';
        $y1    = $y + 17;
        $this->pdf->textBox($x + 2, $y1, $w1/2, $h1, $texto, $aFont, 'T', 'L', 0, '', true);
        //tipo de nF
        $aFont = ['font' => $this->fontePadrao, 'size' => 12, 'style' => 'B'];
        $y1    = $y + 13;
        $texto = $this->std->ide->tpNF;
        $this->pdf->textBox($x+$w1/2+$w2/10, $y1, 5, $h1, $texto, $aFont, 'C', 'C', 1, '');
        //numero da NF
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $y1    = $y + 22;
        $texto = "Nº. " . $this->numero;
        $this->pdf->textBox($x, $y1, $w1, 7, $texto, $aFont, 'T', 'C', 0, '', true);
        //Série
        $y1    = $y + 25;
        $texto = "Série " . $this->serie;
        $this->pdf->textBox($x, $y1, $w1, 7, $texto, $aFont, 'T', 'C', 0, '', true);
        //numero paginas
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => 'I'];
        $y1    = $y + 28;
        $texto = "Folha " . $pagina . "/" . $totalPaginas;
        $this->pdf->textBox($x, $y1, $w1, 7, $texto, $aFont, 'T', 'C', 0, '', true);
        //####################################################################################
        //coluna codigo de barras
        $this->pdf->setFillColor(0, 0, 0);
        $chave_acesso = str_replace('NFe', '', $this->std->attributes->Id);
        $bW           = $w2-4;
        $bH           = 12;
        //codigo de barras
        $this->pdf->code128($this->margesq + $w + $w1 + 2, $y + 2, $chave_acesso, $bW, $bH);
        //linhas divisorias horizontais
        $x = $this->margesq + $w + $w1;
        $this->pdf->line($x, $y + 4 + $bH, $this->wPrint+$this->margesq, $y + 4 + $bH);
        $this->pdf->line($x, $y + 12 + $bH, $this->wPrint+$this->margesq, $y + 12 + $bH);
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $y1    = $y + 4 + $bH;
        $texto = 'CHAVE DE ACESSO';
        $this->pdf->textBox($x, $y1, $w, $h1, $texto, $aFont, 'T', 'L', 0, '', true);
        $aFont = ['font' => $this->fontePadrao, 'size' => 9, 'style' => 'B'];
        $y1    = $y + 8 + $bH;
        $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";
        $texto = $this->formatField($chave_acesso, $formatoChave);
        $this->pdf->textBox($x + 2, $y1, $w2 - 2, $h1, $texto, $aFont, 'T', 'C', 0, '', true);
        $y1                = $y + 12 + $bH;
        $aFont             = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $chaveContingencia = "";
        if (!empty($this->epec) && $this->std->ide->tpEmis == '4') {
            $cabecalhoProtoAutorizacao = 'NÚMERO DE REGISTRO EPEC';
        } else {
            $cabecalhoProtoAutorizacao = 'PROTOCOLO DE AUTORIZAÇÃO DE USO';
        }
        //tpEmiss = 2 FS-IA não é mais autorizado somente está aqui para efeitos retroativos
        //esse tipo de formulario não é mais fabricado
        if (($this->std->ide->tpEmis == 2 || $this->std->ide->tpEmis == 5)) {
            $cabecalhoProtoAutorizacao = "DADOS DA NF-E";
            $chaveContingencia         = $this->geraChaveAdicionalDeContingencia();
            $this->pdf->setFillColor(0, 0, 0);
            //codigo de barras
            $this->pdf->code128($x + 11, $y1 + 1, $chaveContingencia, $bW * .9, $bH / 2);
        } else {
            $texto = 'Consulta de autenticidade no portal nacional da NF-e';
            $this->pdf->textBox($x + 2, $y1, $w - 2, $h1, $texto, $aFont, 'T', 'C', 0, '');
            $y1    = $y + 16 + $bH;
            $texto = 'www.nfe.fazenda.gov.br/portal ou no site da Sefaz Autorizadora';
            $this->pdf->textBox(
                $x + 2,
                $y1,
                $w - 2,
                $h1,
                $texto,
                $aFont,
                'T',
                'C',
                0,
                'http://www.nfe.fazenda.gov.br/portal ou no site da Sefaz Autorizadora'
            );
        }
        //####################################################################################
        //Dados da NF do cabeçalho
        $x = $this->margesq;
        $y1 = $y+$h;
        $texto = 'NATUREZA DA OPERAÇÃO';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y1, $w+$w1, $h1, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->ide->natOp;
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y1+1.8, $w+$w1, $h1, $texto, $aFont, 'T', 'C', 0, '', true);

        //linhas divisorias horizontais
        $this->pdf->line($x, $y1+$h1, $this->wPrint+$this->margesq, $y1+$h1);

        //PROTOCOLO DE AUTORIZAÇÃO DE USO ou DADOS da NF-E
        $x += $w+$w1;
        $prot = '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y1, $w2, $h1, $cabecalhoProtoAutorizacao, $aFont, 'T', 'L', 0, '', true);
        // algumas NFe podem estar sem o protocolo de uso portanto sua existencia deve ser
        // testada antes de tentar obter a informação.
        // NOTA : DANFE sem protocolo deve existir somente no caso de contingência !!!
        // Além disso, existem várias NFes em contingência que eu recebo com protocolo de autorização.
        // Na minha opinião, deveríamos mostra-lo, mas o  manual  da NFe v4.01 diz outra coisa...
        if (($this->std->ide->tpEmis == 2 || $this->std->ide->tpEmis == 5) && empty($this->epec)) {
            $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'];
            $texto = $this->formatField(
                $chaveContingencia,
                "#### #### #### #### #### #### #### #### ####"
            );
        } else {
            $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            if (!empty($this->epec)) {
                $prot = $this->epec['protocolo'] . ' - ' . $this->epec['data'];
            } else {
                if (!empty($this->protNFe)) {
                    $nProt  = $this->protNFe->infProt->nProt;
                    $dtHora = \DateTime::createFromFormat('Y-m-d\TH:i?sP', $this->protNFe->infProt->dhRecbto)
                        ->format('d/m/Y H:i:s');
                    $prot = $nProt . ' - ' . $dtHora;
                }
            }
        }
        $this->pdf->textBox($x, $y1+2, $w2, $h1, $prot, $aFont, 'T', 'C', 0, '', true);
        //####################################################################################
        //linhas divisorias verticais
        $x = $this->margesq;
        $y1 += $h1;
        $this->pdf->line($x+$w3, $y1, $x+$w3, $y1+$h1);
        $this->pdf->line($x+2*$w3, $y1, $x+2*$w3, $y1+$h1);
        $this->pdf->line($x+3*$w3, $y1, $x+3*$w3, $y1+$h1);
        //INSCRIÇÃO ESTADUAL
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y1, $w3, $h1, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->emit->IE;
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y1+2, $w3, $h1, $texto, $aFont, 'T', 'C', 0, '', true);
        //INSCRIÇÃO MUNICIPAL
        $x += $w3;
        $texto = 'INSCRIÇÃO MUNICIPAL';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y1, $w3, $h1, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->emit->IM ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y1+2, $w3, $h1, $texto, $aFont, 'T', 'C', 0, '', true);
        //INSCRIÇÃO ESTADUAL DO SUBST. TRIBUT.
        $x += $w3;
        $texto = 'INSCRIÇÃO ESTADUAL DO SUBST. TRIBUT.';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y1, $w3, $h1, $texto, $aFont, 'T', 'L', 0, '', true);
        $texto = $this->std->emit->IEST ?? '';
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y1+2, $w3, $h1, $texto, $aFont, 'T', 'C', 0, '', true);
        //CNPJ
        $x += $w3;
        $texto = 'CNPJ / CPF';
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y1, $w3-$this->margesq, $h1, $texto, $aFont, 'T', 'L', 0, '', true);
        //Pegando valor do CPF/CNPJ
        $texto = '';
        if (!empty($this->std->emit->CNPJ)) {
            $texto = $this->formatField(
                $this->std->emit->CNPJ,
                "###.###.###/####-##"
            );
        } else {
            if (!empty($this->std->emit->CPF)) {
                $texto = $this->formatField($this->std->emit->CPF,"###.###.###-##");
            }
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x, $y1+2, $w3-$this->margesq, $h1, $texto, $aFont, 'T', 'C', 0, '', true);
        return 46;
    }

    protected function headerL(float $y, $pagina = 1, $totalPaginas = 1): float
    {
        return 46;
    }
}
