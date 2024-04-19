<?php

namespace NFePHP\DA\NFe;

use Exception;
use InvalidArgumentException;
use NFePHP\DA\Common\DaCommon;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;

class DanfeEtiqueta extends DaCommon
{
    protected $papel;
    protected $paperwidth = 100; //mm
    protected $paperlength = 150; //mm
    protected $descPercent = 0.38;
    protected $email = null;
    protected $xml; // string XML NFe
    protected $dom;
    protected $logomarca=''; // path para logomarca em jpg
    protected $formatoChave="#### #### #### #### #### #### #### #### #### #### ####";
    protected $nfeProc;
    protected $nfe;
    protected $infNFe;
    protected $ide;
    protected $enderDest;
    protected $ICMSTot;
    protected $imposto;
    protected $emit;
    protected $enderEmit;
    protected $compra;
    protected $det;
    protected $infAdic;
    protected $infCpl;
    protected $infAdFisco;
    protected $infProt;
    protected $textoAdic;
    protected $tpEmis;
    protected $tpAmb;
    protected $tpImp;
    protected $pag;
    protected $vTroco;
    protected $itens = [];
    protected $dest;
    protected $urlQR = '';
    protected $pdf;
    protected $margem = 3;
    protected $hMaxLinha = 5;
    protected $hBoxLinha = 6;
    protected $hLinha = 3;
    protected $fontePadrao = 'arial';
    protected $aFont = [];
    protected $canceled = false;
    protected $submessage = null;

    /**
     * Construtor
     *
     * @param string $xml
     *
     * @throws Exception
     */
    public function __construct($xml)
    {
        $this->xml = $xml;
        if (empty($xml)) {
            throw new Exception('Um xml de NFe deve ser passado ao construtor da classe.');
        }
        //carrega dados do xml
        $this->loadXml();

        $this->aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
    }

    /**
     * Seta a largura do papel de impressão em mm
     *
     * @param int $width
     */
    public function setPaperWidth($width = 100)
    {
        if ($width < 58) {
            throw new Exception("Largura insuficiente para a impressão do documento");
        }
        $this->paperwidth = $width;
    }

    /**
     * Seta a largura do papel de impressão em mm
     *
     * @param int $width
     */
    public function setPaperLength($length = 150)
    {
        if ($length < 120) {
            throw new Exception("Comprimento insuficiente para a impressão do documento");
        }
        $this->paperlength = $length;
    }

    /**
     * Seta a fonte a ser usada times ou arial
     *
     * @param string $font
     */
    public function setFont($font = 'arial')
    {
        if (!in_array($font, ['times', 'arial'])) {
            $this->fontePadrao = 'times';
        } else {
            $this->fontePadrao = $font;
        }
        $this->aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
    }

    /**
     * Seta as margens de impressão em mm
     *
     * @param int $width
     */
    public function setMargins($width = 1)
    {
        if ($width > 4 || $width < 0) {
            throw new Exception("As margens devem estar entre 0 e 4 mm.");
        }
        $this->margem = $width;
    }

    public function setEmitEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Renderiza o pdf
     *
     * @param string $logo
     * @return string
     */
    public function render($logo = '')
    {
        $this->monta($logo);
        return $this->pdf->getPdf();
    }

    protected function monta(
        $logo = ''
    ) {
        if (!empty($logo)) {
            $this->logomarca = $this->adjustImage($logo, true);
        }
        $margSup = $this->margem;
        $margEsq = $this->margem;
        $margInf = $this->margem;
        $xInic = $margEsq;
        $yInic = $margSup;
        $maxW = $this->paperwidth;
        $maxH = $this->paperlength;
        //total inicial de paginas
        $totPag = 1;
        //largura imprimivel em mm: largura da folha menos as margens esq/direita
        $this->wPrint = $maxW-($margEsq * 2);
        //comprimento (altura) imprimivel em mm: altura da folha menos as margens
        //superior e inferior
        $this->hPrint = $maxH-$margSup-$margInf;
        $this->orientacao = 'P';
        $this->papel = [$this->paperwidth, $this->paperlength];
        $this->logoAlign = 'L';
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        $this->pdf->aliasNbPages();
        $this->pdf->setMargins($margEsq, $margSup); // fixa as margens
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->open(); // inicia o documento
        $this->pdf->addPage($this->orientacao, $this->papel); // adiciona a primeira página
        $this->pdf->setLineWidth(0.1); // define a largura da linha
        $this->pdf->setTextColor(0, 0, 0);

        $this->pdf->textBox(
            $this->margem,
            $this->margem,
            $this->wPrint,
            $this->hPrint,
            '',
            $this->aFont,
            'T',
            'L',
            true
        );

        $y = $this->bloco1($yInic);
        $y = $this->bloco2($y);
        $y = $this->bloco3($y);
        $y = $this->bloco4($y);
        $y = $this->bloco5($y);
        $y = $this->bloco6($y);
    }

    protected function bloco1($y)
    {
        $h = 12;
        $texto = 'DANFE SIMPLIFICADO - ETIQUETA';
        $aFont = ['font' => $this->fontePadrao, 'size' => 12, 'style' => 'B'];
        $y += $this->pdf->textBox(
            $this->margem,
            $this->margem + 1,
            $this->wPrint,
            $h + $this->margem,
            $texto,
            $aFont,
            'T',
            'C',
            false
        );
        $numNF = str_pad(
            $this->ide->getElementsByTagName('nNF')->item(0)->nodeValue,
            9,
            "0",
            STR_PAD_LEFT
        );
        $serie = str_pad(
            $this->ide->getElementsByTagName('serie')->item(0)->nodeValue,
            3,
            "0",
            STR_PAD_LEFT
        );
        $tpNF = $this->ide->getElementsByTagName('tpNF')->item(0)->nodeValue;
        $tipo = 'Tipo NFe: 1 - Saída';
        if ($tpNF == '0') {
            $tipo = 'Tipo NFe: 0 - Entrada';
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => ''];
        $texto = "NFe n. " . $numNF . '   Série: ' . $serie . '  ' . $tipo;
        $y += $this->pdf->textBox($this->margem, $y+2, $this->wPrint, 7, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->line($this->margem, $y+4, $this->wPrint+$this->margem, $y+4);
        return $y+4;
    }

    protected function bloco2($y)
    {
        $emitRazao = $this->getTagValue($this->emit, "xNome");
        $emitIE = $this->getTagValue($this->emit, "IE");
        $emitCnpj = $this->formatField(
            $this->getTagValue($this->emit, "CNPJ"),
            "###.###.###/####-##"
        );
        $emitLgr = $this->getTagValue($this->enderEmit, "xLgr");
        $emitNro = $this->getTagValue($this->enderEmit, "nro");
        $emitBairro = $this->getTagValue($this->enderEmit, "xBairro");
        $emitMun = $this->getTagValue($this->enderEmit, "xMun");
        $emitUF = $this->getTagValue($this->enderEmit, "UF");
        $emitFone = $this->getTagValue($this->enderEmit, "fone");
        if (strlen($emitFone) > 0) {
            if (strlen($emitFone) == 11) {
                $emitFone = $this->formatField($emitFone, "(##) #####-####");
            } else {
                $emitFone = $this->formatField($emitFone, "(##) ####-####");
            }
        }
        $h = 20;
        $maxHimg = $h-2;
        if (!empty($this->logomarca)) {
            $xImg = $this->margem + 2;
            $logoInfo = getimagesize($this->logomarca);
            $logoWmm = ($logoInfo[0]/72)*25.4;
            $logoHmm = ($logoInfo[1]/72)*25.4;
            $nImgW = $this->wPrint/4;
            $nImgH = round($logoHmm * ($nImgW/$logoWmm), 0);
            if ($nImgH > $maxHimg) {
                $nImgH = $maxHimg;
                $nImgW = round($logoWmm * ($nImgH/$logoHmm), 0);
            }
            $xRs = ($nImgW) + $this->margem;
            $wRs = ($this->wPrint - $nImgW);
            $alignH = 'L';
            $yImg = ($h - $nImgH)/2 + $y;
            $this->pdf->image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $xRs = $this->margem;
            $wRs = $this->wPrint;
            $alignH = 'C';
        }
        //COLOCA RAZÃO SOCIAL
        $aFont = ['font'=>$this->fontePadrao, 'size' => 9, 'style' => 'B'];
        $texto = "{$emitRazao}";
        $y += $this->pdf->textBox(
            $xRs+2,
            $y,
            $wRs-2,
            $this->margem-1,
            $texto,
            $aFont,
            'T',
            $alignH,
            false,
            '',
            true
        );
        $aFont = ['font'=>$this->fontePadrao, 'size' => 8, 'style' => ''];
        $texto = "CNPJ: {$emitCnpj} IE: {$emitIE}";
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $texto = $emitLgr . ", " . $emitNro;
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $texto = $emitBairro;
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $texto = $emitMun . "-" . $emitUF . ($emitFone ? "  Fone: ".$emitFone : "");
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $texto = "E-mail: {$this->email}";
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $this->pdf->line($this->margem, $y+2, $this->wPrint+$this->margem, $y+2);
        return $y+2;
    }

    protected function bloco3($y)
    {
        $this->pdf->setFillColor(0, 0, 0);
        $chave_acesso = str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        $bW = $this->wPrint - ($this->margem * 2) - 9;
        $bH = 12;
        $x = $this->margem;
        //codigo de barras
        $this->pdf->code128($x + (($this->wPrint - $bW) / 2), $y + 2, $chave_acesso, $bW, $bH);
        $texto = $this->formatField($chave_acesso, $this->formatoChave);
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $this->pdf->textBox($x + 5, $y+ $bH +  2, $this->wPrint - 2, 7, $texto, $aFont, 'T', 'L', 0, '');
        $y += $bH + 3;
        if (empty($this->infProt)) {
            throw new \Exception('Apenas NFe autorizadas podem ser impressas em formato de etiqueta');
        }
        if ($this->canceled) {
            throw new \Exception('Esta NFe está cancelada, e apenas NFe autorizadas podem ser '
                .'impressas em formato de etiqueta');
        }
        $protocolo  = !empty($this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue)
            ? $this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue
            : '';
        $dtHora = $this->toDateTime($this->nfeProc->getElementsByTagName("dhRecbto")->item(0)->nodeValue);
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $texto = "PROTOCOLO: {$protocolo} - ";
        $texto .= $dtHora->format('d/m/Y H:i:s');
        $this->pdf->textBox($x, $y, $this->wPrint, 7, $texto, $aFont, 'B', 'C', 0, '');

        $this->pdf->line($this->margem, $y+8, $this->wPrint+$this->margem, $y+8);
        return $y+8;
    }

    protected function bloco4($y)
    {
        $texto = 'Destinatário:';
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => 'I'];
        $this->pdf->textBox($this->margem, $y, $this->wPrint, 7, $texto, $aFont, 'T', 'L', 0, '');

        $aFont = ['font' => $this->fontePadrao, 'size' => 9, 'style' => 'B'];
        $texto = $this->dest->getElementsByTagName("xNome")->item(0)->nodeValue;
        $this->pdf->textBox($this->margem + 5, $y+5, $this->wPrint, 7, $texto, $aFont, 'T', 'L', 0, '');
        $cnpj = !empty($this->dest->getElementsByTagName("CNPJ")->item(0))
            ? $this->formatField($this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue, "###.###.###/####-##")
            : null;
        $cpf = !empty($this->dest->getElementsByTagName("CPF")->item(0))
            ? $this->formatField($this->dest->getElementsByTagName("CPF")->item(0)->nodeValue, '###.###.###-##')
            : null;
        $doc = $cnpj ?? $cpf;
        $texto = "CNPJ/CPF: {$doc}";
        $this->pdf->textBox($this->margem + 5, $y+9, $this->wPrint, 7, $texto, $aFont, 'T', 'L', 0, '');
        $ie = !empty($this->dest->getElementsByTagName("IE")->item(0))
            ? $this->formatField($this->dest->getElementsByTagName("IE")->item(0)->nodeValue, "###.###.###.###.###")
            : null;
        $texto = "IE: {$ie}";
        $y += 13;
        $y += $this->pdf->textBox($this->margem + 5, $y, $this->wPrint, 7, $texto, $aFont, 'T', 'L', 0, '');

        $destLgr = $this->getTagValue($this->enderDest, "xLgr");
        $destNro = $this->getTagValue($this->enderDest, "nro");
        $destBairro = $this->getTagValue($this->enderDest, "xBairro");
        $destMun = $this->getTagValue($this->enderDest, "xMun");
        $destUF = $this->getTagValue($this->enderDest, "UF");
        $destFone = $this->getTagValue($this->enderDest, "fone");
        if (strlen($destFone) > 0) {
            if (strlen($destFone) == 11) {
                $emitFone = $this->formatField($destFone, "(##) #####-####");
            } else {
                $emitFone = $this->formatField($destFone, "(##) ####-####");
            }
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => ''];
        $texto = $destLgr . ", " . $destNro;
        $y += $this->pdf->textBox($this->margem + 5, $y, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', true);
        $texto = $destBairro;
        $y += $this->pdf->textBox($this->margem + 5, $y, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', true);
        $texto = $destMun . "-" . $destUF . ($destFone ? "  Fone: ".$destFone : "");
        $y += $this->pdf->textBox($this->margem + 5, $y, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', true);
        $this->pdf->line($this->margem, $y+2, $this->wPrint+$this->margem, $y+2);
        return $y+2;
    }

    protected function bloco5($y)
    {
        $total = number_format($this->getTagValue($this->ICMSTot, 'vNF'), 2, ',', '.');
        $texto = "Valor TOTAL da NFe: R$ $total";
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $y += $this->pdf->textBox($this->margem, $y, $this->wPrint, 6, $texto, $aFont, 'C', 'C', false, '', true);
        $this->pdf->line($this->margem, $y+3, $this->wPrint+$this->margem, $y+3);
        return $y+2;
    }

    protected function bloco6($y)
    {
        if (!empty($this->compra)) {
            $pedido = $this->getTagValue($this->compra, 'xPed');
            $texto = "PEDIDO: $pedido";
            $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $y += $this->pdf->textBox(
                $this->margem+1,
                $y+2,
                $this->wPrint,
                6,
                $texto,
                $aFont,
                'T',
                'L',
                false,
                '',
                false
            );
        }
        $texto = "Informações Complementares:";
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => 'I'];
        $y += $this->pdf->textBox($this->margem+1, $y+4, $this->wPrint, 6, $texto, $aFont, 'T', 'L', false, '', false);
        $texto = $this->infCpl . "\n" . $this->infAdFisco;
        $aFont = ['font' => $this->fontePadrao, 'size' => 9, 'style' => ''];
        $y += $this->pdf->textBox(
            $this->margem+1,
            $y+5,
            $this->wPrint-2,
            6,
            $texto,
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );
    }

    /**
     * Carrega os dados do xml na classe
     * @param string $xml
     *
     * @throws InvalidArgumentException
     */
    private function loadXml()
    {
        $this->dom = new Dom();
        $this->dom->loadXML($this->xml);
        $this->ide = $this->dom->getElementsByTagName("ide")->item(0);
        $mod = $this->getTagValue($this->ide, "mod");
        if ($this->getTagValue($this->ide, "mod") != '55') {
            throw new \Exception("O xml do DANFE deve ser uma NF-e modelo 55");
        }
        $this->tpAmb = $this->getTagValue($this->ide, 'tpAmb');
        $this->nfeProc = $this->dom->getElementsByTagName("nfeProc")->item(0) ?? null;
        $this->infProt = $this->dom->getElementsByTagName("infProt")->item(0) ?? null;
        $this->nfe = $this->dom->getElementsByTagName("NFe")->item(0);
        $this->infNFe = $this->dom->getElementsByTagName("infNFe")->item(0);
        $this->emit = $this->dom->getElementsByTagName("emit")->item(0);
        $this->enderEmit = $this->dom->getElementsByTagName("enderEmit")->item(0);
        $this->dest = $this->dom->getElementsByTagName("dest")->item(0);
        $this->enderDest = $this->dom->getElementsByTagName("enderDest")->item(0);
        $this->det = $this->dom->getElementsByTagName("det");
        $this->imposto = $this->dom->getElementsByTagName("imposto")->item(0);
        $this->ICMSTot = $this->dom->getElementsByTagName("ICMSTot")->item(0);
        $this->tpImp = $this->ide->getElementsByTagName("tpImp")->item(0)->nodeValue;
        $this->infAdic = $this->dom->getElementsByTagName("infAdic")->item(0);
        $this->tpEmis = $this->dom->getValue($this->ide, "tpEmis");
        $this->compra = $this->infNFe->getElementsByTagName("compra")->item(0);
        $this->infCpl = '';
        if (!empty($this->infAdic)) {
            if (!empty($this->infAdic->getElementsByTagName("infCpl")->item(0))) {
                $this->infCpl = $this->infAdic->getElementsByTagName("infCpl")->item(0)->nodeValue;
            }
            if (!empty($this->infAdic->getElementsByTagName("infAdFisco")->item(0))) {
                $this->infAdFisco = $this->infAdic->getElementsByTagName("infAdFisco")->item(0)->nodeValue;
            }
        }
        //se for o layout 4.0 busca pelas tags de detalhe do pagamento
        //senão, busca pelas tags de pagamento principal
        if ($this->infNFe->getAttribute("versao") == "4.00") {
            $this->pag = $this->dom->getElementsByTagName("detPag");
            $tagPag = $this->dom->getElementsByTagName("pag")->item(0);
            $this->vTroco = $this->getTagValue($tagPag, "vTroco");
        } else {
            $this->pag = $this->dom->getElementsByTagName("pag");
        }
        if (!empty($this->infProt)) {
            $cStat = $this->getTagValue($this->infProt, 'cStat');
            if (!in_array($cStat, [100,150])) {
                $this->canceled = true;
            } elseif (!empty($retEvento = $this->nfeProc->getElementsByTagName('retEvento')->item(0))) {
                $infEvento = $retEvento->getElementsByTagName('infEvento')->item(0);
                $cStat = $this->getTagValue($infEvento, "cStat");
                $tpEvento= $this->getTagValue($infEvento, "tpEvento");
                $dhEvento = date(
                    "d/m/Y H:i:s",
                    $this->toTimestamp(
                        $this->getTagValue($infEvento, "dhRegEvento")
                    )
                );
                $nProt = $this->getTagValue($infEvento, "nProt");
                if (($tpEvento == '110111' || $tpEvento == '110112')
                    && (
                        $cStat == '101'
                        || $cStat == '151'
                        || $cStat == '135'
                        || $cStat == '155')
                ) {
                    $this->canceled = true;
                    $this->submessage = "Data: {$dhEvento}\nProtocolo: {$nProt}";
                }
            }
        }
    }
}
