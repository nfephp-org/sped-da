<?php

namespace NFePHP\DA\MDFe;

/**
 * Esta classe gera do PDF do MDFDe, conforme regras e estruturas
 * estabelecidas pela SEFAZ.
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @name      Damdfe.php
 * @copyright 2009-2016 NFePHP
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Leandro C. Lopez <leandro dot castoldi at gmail dot com>
 */

use Com\Tecnick\Barcode\Barcode;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Common;
use NFePHP\DA\Legacy\Pdf;

class Damdfe extends Common
{
    protected $logoAlign = 'L'; //alinhamento do logo
    protected $yDados = 0;
    protected $debugmode = false; //ativa ou desativa o modo de debug
    protected $pdf; // objeto fpdf()
    protected $xml; // string XML NFe
    protected $logomarca = ''; // path para logomarca em jpg
    protected $errMsg = ''; // mesagens de erro
    protected $errStatus = false;// status de erro TRUE um erro ocorreu false sem erros
    protected $orientacao = 'P'; //orientação da DANFE P-Retrato ou L-Paisagem
    protected $papel = 'A4'; //formato do papel
    protected $fontePadrao = 'Times'; //Nome da Fonte para gerar o DANFE
    protected $wPrint; //largura imprimivel
    protected $hPrint; //comprimento imprimivel
    protected $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";
    protected $margemInterna = 2;
    protected $id;
    protected $chMDFe;
    protected $tpAmb;
    protected $cOrgao;
    protected $xCondUso;
    protected $dhEvento;
    protected $cStat;
    protected $xMotivo;
    protected $CNPJDest = '';
    protected $dhRegEvento;
    protected $nProt;
    protected $tpEmis;
    protected $qrCodMDFe;
    private $dom;
    private $creditos;

    /**
     * __construct
     *
     * @param string $xml Arquivo XML da MDFe
     */
    public function __construct(
        $xml = ''
    ) {
        $this->debugMode();
        $this->loadDoc($xml);
    }

    /**
     * Ativa ou desativa o modo debug
     *
     * @param  bool $activate
     * @return bool
     */
    public function debugMode($activate = null)
    {
        if (isset($activate) && is_bool($activate)) {
            $this->debugmode = $activate;
        }
        if ($this->debugmode) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
        return $this->debugmode;
    }

    private function loadDoc($xml)
    {
        $this->xml = $xml;
        if (!empty($xml)) {
            $this->dom = new Dom();
            $this->dom->loadXML($this->xml);
            $this->mdfeProc = $this->dom->getElementsByTagName("mdfeProc")->item(0);
            if (empty($this->dom->getElementsByTagName("infMDFe")->item(0))) {
                throw new \Exception('Isso não é um MDF-e.');
            }
            $this->infMDFe = $this->dom->getElementsByTagName("infMDFe")->item(0);
            $this->ide = $this->dom->getElementsByTagName("ide")->item(0);
            if ($this->getTagValue($this->ide, "mod") != '58') {
                throw new \Exception("O xml deve ser MDF-e modelo 58.");
            }
            $this->emit = $this->dom->getElementsByTagName("emit")->item(0);
            if ($this->emit->getElementsByTagName("CPF")->item(0)) {
                $this->CPF = $this->emit->getElementsByTagName("CPF")->item(0)->nodeValue;
            } else {
                $this->CNPJ = $this->emit->getElementsByTagName("CNPJ")->item(0)->nodeValue;
            }
            $this->IE = $this->dom->getElementsByTagName("IE")->item(0)->nodeValue;
            $this->xNome = $this->dom->getElementsByTagName("xNome")->item(0)->nodeValue;
            $this->enderEmit = $this->dom->getElementsByTagName("enderEmit")->item(0);
            $this->xLgr = $this->dom->getElementsByTagName("xLgr")->item(0)->nodeValue;
            $this->nro = $this->dom->getElementsByTagName("nro")->item(0)->nodeValue;
            $this->xBairro = $this->dom->getElementsByTagName("xBairro")->item(0)->nodeValue;
            $this->UF = $this->dom->getElementsByTagName("UF")->item(0)->nodeValue;
            $this->xMun = $this->dom->getElementsByTagName("xMun")->item(0)->nodeValue;
            $this->CEP = $this->dom->getElementsByTagName("CEP")->item(0)->nodeValue;
            $this->tpAmb = $this->dom->getElementsByTagName("tpAmb")->item(0)->nodeValue;
            $this->mod = $this->dom->getElementsByTagName("mod")->item(0)->nodeValue;
            $this->serie = $this->dom->getElementsByTagName("serie")->item(0)->nodeValue;
            $this->dhEmi = $this->dom->getElementsByTagName("dhEmi")->item(0)->nodeValue;
            $this->UFIni = $this->dom->getElementsByTagName("UFIni")->item(0)->nodeValue;
            $this->UFFim = $this->dom->getElementsByTagName("UFFim")->item(0)->nodeValue;
            $this->nMDF = $this->dom->getElementsByTagName("nMDF")->item(0)->nodeValue;
            $this->tpEmis = $this->dom->getElementsByTagName("tpEmis")->item(0)->nodeValue;
            $this->tot = $this->dom->getElementsByTagName("tot")->item(0);
            $this->qMDFe = "";
            if ($this->dom->getElementsByTagName("qMDFe")->item(0) != "") {
                $this->qMDFe = $this->dom->getElementsByTagName("qMDFe")->item(0)->nodeValue;
            }
            $this->qNFe = "";
            if ($this->dom->getElementsByTagName("qNFe")->item(0) != "") {
                $this->qNFe = $this->dom->getElementsByTagName("qNFe")->item(0)->nodeValue;
            }
            $this->qNF = "";
            if ($this->dom->getElementsByTagName("qNF")->item(0) != "") {
                $this->qNF = $this->dom->getElementsByTagName("qNF")->item(0)->nodeValue;
            }
            $this->qCTe = "";
            if ($this->dom->getElementsByTagName("qCTe")->item(0) != "") {
                $this->qCTe = $this->dom->getElementsByTagName("qCTe")->item(0)->nodeValue;
            }
            $this->qCT = "";
            if ($this->dom->getElementsByTagName("qCT")->item(0) != "") {
                $this->qCT = $this->dom->getElementsByTagName("qCT")->item(0)->nodeValue;
            }
            $this->qCarga = $this->dom->getElementsByTagName("qCarga")->item(0)->nodeValue;
            $this->cUnid = $this->dom->getElementsByTagName("cUnid")->item(0)->nodeValue;
            $this->infModal = $this->dom->getElementsByTagName("infModal")->item(0);
            $this->rodo = $this->dom->getElementsByTagName("rodo")->item(0);
            $this->aereo = $this->dom->getElementsByTagName("aereo")->item(0);
            $this->aquav = $this->dom->getElementsByTagName("aquav")->item(0);
            $this->ferrov = $this->dom->getElementsByTagName("ferrov")->item(0);
            $this->ciot = "";
            if ($this->dom->getElementsByTagName('CIOT')->item(0) != "") {
                $this->ciot = $this->dom->getElementsByTagName('CIOT')->item(0)->nodeValue;
            }
            $this->veicTracao = $this->dom->getElementsByTagName("veicTracao")->item(0);
            $this->veicReboque = $this->dom->getElementsByTagName("veicReboque");
            $this->valePed = "";
            if ($this->dom->getElementsByTagName("valePed")->item(0) != "") {
                $this->valePed = $this->dom->getElementsByTagName("valePed")->item(0)->getElementsByTagName("disp");
            }
            $this->infCpl = ($infCpl = $this->dom->getElementsByTagName('infCpl')->item(0)) ? $infCpl->nodeValue : "";
            $this->chMDFe = str_replace(
                'MDFe',
                '',
                $this->infMDFe->getAttribute("Id")
            );
            $this->qrCodMDFe = $this->dom->getElementsByTagName('qrCodMDFe')->item(0) ?
                $this->dom->getElementsByTagName('qrCodMDFe')->item(0)->nodeValue : 'SEM INFORMAÇÃO DE QRCODE';
            if (is_object($this->mdfeProc)) {
                $this->nProt = !empty($this->mdfeProc->getElementsByTagName("nProt")->item(0)->nodeValue) ?
                    $this->mdfeProc->getElementsByTagName("nProt")->item(0)->nodeValue : '';
                $this->dhRecbto = $this->mdfeProc->getElementsByTagName("dhRecbto")->item(0)->nodeValue;
            }
        }
    }

    /**
     * buildMDFe
     */
    public function buildMDFe()
    {
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        if ($this->orientacao == 'P') {
            // margens do PDF
            $margSup = 7;
            $margEsq = 7;
            $margDir = 7;
            // posição inicial do relatorio
            $xInic = 7;
            $yInic = 7;
            if ($this->papel == 'A4') { //A4 210x297mm
                $maxW = 210;
                $maxH = 297;
            }
        } else {
            // margens do PDF
            $margSup = 7;
            $margEsq = 7;
            $margDir = 7;
            // posição inicial do relatorio
            $xInic = 7;
            $yInic = 7;
            if ($this->papel == 'A4') { //A4 210x297mm
                $maxH = 210;
                $maxW = 297;
            }
        }//orientação
        //largura imprimivel em mm
        $this->wPrint = $maxW - ($margEsq + $xInic);
        //comprimento imprimivel em mm
        $this->hPrint = $maxH - ($margSup + $yInic);
        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        // fixa as margens
        $this->pdf->setMargins($margEsq, $margSup, $margDir);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        // inicia o documento
        $this->pdf->open();
        // adiciona a primeira página
        $this->pdf->addPage($this->orientacao, $this->papel);
        $this->pdf->setLineWidth(0.1);
        $this->pdf->setTextColor(0, 0, 0);
        //montagem da página
        $pag = 1;
        $x = $xInic;
        $y = $yInic;
        //coloca o cabeçalho Paisagem
        if ($this->orientacao == 'P') {
            $y = $this->headerMDFeRetrato($x, $y, $pag);
        } else {
            $y = $this->headerMDFePaisagem($x, $y, $pag);
        }
        //coloca os dados da MDFe
        $y = $this->bodyMDFe($x, $y);
        //coloca os dados da MDFe
        $y = $this->footerMDFe($x, $y);
    }

    /**
     * headerMDFePaisagem
     *
     * @param  float $x
     * @param  float $y
     * @param  integer $pag
     * @return string
     */
    private function headerMDFePaisagem($x, $y, $pag)
    {
        $oldX = $x;
        $oldY = $y;
        $maxW = $this->wPrint;
        //####################################################################################
        //coluna esquerda identificação do emitente
        //$w = $maxW; //round($maxW*0.41, 0);// 80;
        $w = round($maxW * 0.70, 0);
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $w1 = $w;
        $h = 30;
        $oldY += $h;
        $this->pdf->textBox($x, $y, $w, $h);
        if (is_file($this->logomarca)) {
            $logoInfo = getimagesize($this->logomarca);
            //largura da imagem em mm
            $logoWmm = ($logoInfo[0] / 72) * 25.4;
            //altura da imagem em mm
            $logoHmm = ($logoInfo[1] / 72) * 25.4;
            if ($this->logoAlign == 'L') {
                $nImgW = round($w / 3, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg = $x + 1;
                $yImg = round(($h - $nImgH) / 2, 0) + $y;
                //estabelecer posições do texto
                $x1 = round($xImg + $nImgW + 1, 0);
                $y1 = round($y + 2, 0);
                $tw = round(2 * $w / 3, 0);
            }
            if ($this->logoAlign == 'C') {
                $nImgH = round($h / 3, 0);
                $nImgW = round($logoWmm * ($nImgH / $logoHmm), 0);
                $xImg = round(($w - $nImgW) / 2 + $x, 0);
                $yImg = $y + 3;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            }
            if ($this->logoAlign == 'R') {
                $nImgW = round($w / 3, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg = round($x + ($w - (1 + $nImgW)), 0);
                $yImg = round(($h - $nImgH) / 2, 0) + $y;
                $x1 = $x;
                $y1 = round($h / 3 + $y, 0);
                $tw = round(2 * $w / 3, 0);
            }
            $this->pdf->image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $x1 = $x;
            $y1 = round($h / 3 + $y, 0);
            $tw = $w;
        }
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $razao = $this->xNome;
        if (isset($this->CPF)) {
            $cpfcnpj = 'CPF: ' . $this->formatField($this->CPF, "###.###.###-##");
        } else {
            $cpfcnpj = 'CNPJ: ' . $this->formatField($this->CNPJ, "###.###.###/####-##");
        }
        $ie = 'IE: ' . $this->formatField($this->IE, '##/########');
        $lgr = 'Logradouro: ' . $this->xLgr;
        $nro = 'Nº: ' . $this->nro;
        $bairro = 'Bairro: ' . $this->xBairro;
        $CEP = $this->CEP;
        $CEP = 'CEP: ' . $this->formatField($CEP, "##.###-###");
        $UF = 'UF: ' . $this->UF;
        $mun = 'Municipio: ' . $this->xMun;

        $texto = $cpfcnpj . ' - ' . $ie . "\n";
        $texto .= $lgr . ' - ' . $nro . "\n";
        $texto .= $bairro . "\n";
        $texto .= $UF . ' - ' . $mun . ' - ' . $CEP;
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $this->pdf->textBox($x1, $y1 + 6, $tw, 8, $texto, $aFont, 'T', 'L', 0, '');
        //##################################################
        $w = round($maxW * 0.70, 0);
        $y = $h + 9;
        $this->pdf->textBox($x, $y, $w, 6);
        $aFont = ['font' => $this->fontePadrao, 'size' => 12, 'style' => 'I'];
        $this->pdf->textBox(
            $x,
            $y,
            $w,
            8,
            'DAMDFE - Documento Auxiliar de Manifesto Eletronico de Documentos Fiscais',
            $aFont,
            'T',
            'C',
            0,
            ''
        );

        if ($this->tpAmb != 1) {
            $x = 10;
            if ($this->orientacao == 'P') {
                $yy = round($this->hPrint * 2 / 3, 0);
            } else {
                $yy = round($this->hPrint / 2, 0);
            }
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = ['font' => $this->fontePadrao, 'size' => 48, 'style' => 'B'];
            $this->pdf->textBox($x, $yy, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $aFont = ['font' => $this->fontePadrao, 'size' => 30, 'style' => 'B'];
            $texto = "AMBIENTE DE HOMOLOGAÇÃO";
            $this->pdf->textBox($x, $yy + 14, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        } else {
            $x = 10;
            if ($this->orientacao == 'P') {
                $yy = round($this->hPrint * 2 / 3, 0);
            } else {
                $yy = round($this->hPrint / 2, 0);
            }
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            //indicar FALTA DO PROTOCOLO se MDFe não for em contingência
            if (($this->tpEmis == 2 || $this->tpEmis == 5)) {
                //Contingência
                $texto = "DAMDFE Emitido em Contingência";
                $aFont = ['font' => $this->fontePadrao, 'size' => 48, 'style' => 'B'];
                $this->pdf->textBox($x, $yy, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                $aFont = ['font' => $this->fontePadrao, 'size' => 30, 'style' => 'B'];
                $texto = "devido à problemas técnicos";
                $this->pdf->textBox($x, $yy + 12, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            }
            $this->pdf->setTextColor(0, 0, 0);
        }
        return $y + 8;
    }

    /**
     * headerMDFeRetrato
     *
     * @param  float $x
     * @param  float $y
     * @param  integer $pag
     * @return string
     */
    private function headerMDFeRetrato($x, $y, $pag)
    {
        $oldX = $x;
        $oldY = $y;
        $maxW = $this->wPrint;
        //####################################################################################
        //coluna esquerda identificação do emitente
        //$w = $maxW; //round($maxW*0.41, 0);// 80;
        $w = round($maxW * 0.70, 0);
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'I');
        $w1 = $w;
        $h = 20;
        $oldY += $h;
        $this->pdf->textBox($x, $y, $w, $h);
        if (is_file($this->logomarca)) {
            $logoInfo = getimagesize($this->logomarca);
            //largura da imagem em mm
            $logoWmm = ($logoInfo[0] / 72) * 25.4;
            //altura da imagem em mm
            $logoHmm = ($logoInfo[1] / 72) * 25.4;
            if ($this->logoAlign == 'L') {
                // ajusta a dimensão do logo
                $nImgW = round((round($maxW * 0.50, 0)) / 3, 0);
                $nImgH = round(($h - $y) - 2, 0) + $y;
                $xImg = $x + 1;
                $yImg = round(($h - $nImgH) / 2, 0) + $y;
                //estabelecer posições do texto
                $x1 = round($xImg + $nImgW + 4, 0);
                $y1 = round($y + 2, 0);
                $tw = round(2 * $w / 3, 0);
            }
            if ($this->logoAlign == 'C') {
                $nImgH = round($h / 3, 0);
                $nImgW = round($logoWmm * ($nImgH / $logoHmm), 0);
                $xImg = round(($w - $nImgW) / 2 + $x, 0);
                $yImg = $y + 3;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            }
            if ($this->logoAlign == 'R') {
                $nImgW = round($w / 3, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg = round($x + ($w - (1 + $nImgW)), 0);
                $yImg = round(($h - $nImgH) / 2, 0) + $y;
                $x1 = $x;
                $y1 = round($h / 3 + $y, 0);
                $tw = round(2 * $w / 3, 0);
            }
            $this->pdf->image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $x1 = $x;
            $y1 = $y;
            $tw = $w;
        }

        if ($this->qrCodMDFe !== null) {
            $this->qrCodeDamdfe($y - 3);
        }

        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
        $texto = $this->xNome;
        $this->pdf->textBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'L', 0, '');
        if (isset($this->CPF)) {
            $cpfcnpj = 'CPF: ' . $this->formatField($this->CPF, "###.###.###-##");
        } else {
            $cpfcnpj = 'CNPJ: ' . $this->formatField($this->CNPJ, "###.###.###/####-##");
        }
        $ie = 'IE: ' . $this->formatField($this->IE, '###/#######');
        $lgr = 'Logradouro: ' . $this->xLgr;
        $nro = 'Nº: ' . $this->nro;
        $bairro = 'Bairro: ' . $this->xBairro;
        $CEP = $this->CEP;
        $CEP = 'CEP: ' . $this->formatField($CEP, "##.###-###");
        $mun = 'Municipio: ' . $this->xMun;
        $UF = 'UF: ' . $this->UF;
        $texto = $cpfcnpj . ' - ' . $ie . "\n";
        $texto .= $lgr . ' - ' . $nro . "\n";
        $texto .= $bairro . "\n";
        $texto .= $UF . ' - ' . $mun . ' - ' . $CEP;
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $this->pdf->textBox($x1, $y1 + 4, $tw, 8, $texto, $aFont, 'T', 'L', 0, '');
        //##################################################
        $w = round($maxW * 0.70, 0);
        $y = $h + 9;
        $this->pdf->textBox($x, $y, $w, 6);
        $aFont = array('font' => $this->fontePadrao, 'size' => 12, 'style' => 'I');
        $this->pdf->textBox(
            $x,
            $y,
            $w,
            8,
            'DAMDFE - Documento Auxiliar de Manifesto Eletronico de Documentos Fiscais',
            $aFont,
            'T',
            'C',
            0,
            ''
        );
        $cStat = $this->dom->getElementsByTagName("cStat");
        if ($this->tpAmb != 1) {
            $x = 10;
            if ($this->orientacao == 'P') {
                $yy = round($this->hPrint * 2 / 3, 0);
            } else {
                $yy = round($this->hPrint / 2, 0);
            }
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = array('font' => $this->fontePadrao, 'size' => 48, 'style' => 'B');
            $this->pdf->textBox($x, $yy, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $aFont = array('font' => $this->fontePadrao, 'size' => 30, 'style' => 'B');
            $texto = "AMBIENTE DE HOMOLOGAÇÃO";
            $this->pdf->textBox($x, $yy + 14, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        } elseif ($cStat->item(0)->nodeValue == '101') {
            $x = 10;
            if ($this->orientacao == 'P') {
                $yy = round($this->hPrint * 2 / 3, 0);
            } else {
                $yy = round($this->hPrint / 2, 0);
            }
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "MDFe CANCELADO";
            $aFont = array('font' => $this->fontePadrao, 'size' => 48, 'style' => 'B');
            $this->pdf->textBox($x, $yy, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        } else {
            $x = 10;
            if ($this->orientacao == 'P') {
                $yy = round($this->hPrint * 2 / 3, 0);
            } else {
                $yy = round($this->hPrint / 2, 0);
            }
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            //indicar FALTA DO PROTOCOLO se MDFe não for em contingência
            if (($this->tpEmis == 2 || $this->tpEmis == 5) and empty($this->nProt)) {
                //Contingência
                $texto = "DAMDFE Emitido em Contingência";
                $aFont = array('font' => $this->fontePadrao, 'size' => 48, 'style' => 'B');
                $this->pdf->textBox($x, $yy, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                $aFont = array('font' => $this->fontePadrao, 'size' => 30, 'style' => 'B');
                $texto = "devido à problemas técnicos";
                $this->pdf->textBox($x, $yy + 12, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            } elseif (empty($this->nProt)) {
                $texto = "FALTA PROTOCOLO DE APROVAÇÃO DA SEFAZ";
                $aFont = array('font' => $this->fontePadrao, 'size' => 48, 'style' => 'B');
                $this->pdf->textBox($x, $yy, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            }
            $this->pdf->setTextColor(0, 0, 0);
        }
        return $y + 8;
    }

    /**
     * bodyMDFe
     *
     * @param  float $x
     * @param  float $y
     * @return void
     */
    private function bodyMDFe($x, $y)
    {
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            //$maxW = $this->wPrint / 2;
            $maxW = $this->wPrint * 0.9;
        }
        $x2 = ($maxW / 6);
        $x1 = $x2;
        $this->pdf->textBox($x, $y, $x2 - 22, 12);
        $texto = 'Modelo';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x, $y, $x2 - 22, 8, $texto, $aFont, 'T', 'C', 0, '', false);
        $texto = $this->mod;
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
        $this->pdf->textBox($x, $y + 4, $x2 - 22, 8, $texto, $aFont, 'T', 'C', 0, '', false);

        if ($this->orientacao == 'P') {
            $x1 += $x2 - 47.5;
        } else {
            $x1 += $x2 - 57.5;
        }
        $this->pdf->textBox($x1, $y, $x2 - 22, 12);
        $texto = 'Série';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 22, 8, $texto, $aFont, 'T', 'C', 0, '', false);
        $texto = $this->serie;
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
        $this->pdf->textBox($x1, $y + 4, $x2 - 22, 10, $texto, $aFont, 'T', 'C', 0, '', false);

        $x1 += $x2 - 22;
        $this->pdf->textBox($x1, $y, $x2 - 5, 12);
        $texto = 'Número';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 5, 8, $texto, $aFont, 'T', 'C', 0, '', false);
        $texto = $this->formatField(str_pad($this->nMDF, 9, '0', STR_PAD_LEFT), '###.###.###');
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
        $this->pdf->textBox($x1, $y + 4, $x2 - 5, 10, $texto, $aFont, 'T', 'C', 0, '', false);
        $x1 += $x2 - 5;
        $this->pdf->textBox($x1, $y, $x2 - 22, 12);
        $texto = 'FL';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 22, 8, $texto, $aFont, 'T', 'C', 0, '', false);
        $texto = '1';
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
        $this->pdf->textBox($x1, $y + 4, $x2 - 22, 10, $texto, $aFont, 'T', 'C', 0, '', false);
        $x1 += $x2 - 22;
        if ($this->orientacao == 'P') {
            $x3 = $x2 + 10.5;
        } else {
            $x3 = $x2 + 3;
        }
        $this->pdf->textBox($x1, $y, $x3, 12);
        $texto = 'Data e Hora de Emissão';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x3, 8, $texto, $aFont, 'T', 'C', 0, '', false);
        $data = explode('T', $this->dhEmi);
        $texto = $this->ymdTodmy($data[0]) . ' - ' . $data[1];
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x3, 10, $texto, $aFont, 'T', 'C', 0, '', false);
        $x1 += $x3;

        $this->pdf->textBox($x1, $y, $x2 - 15, 12);
        $texto = 'UF Carreg.';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 15, 8, $texto, $aFont, 'T', 'C', 0, '', false);
        $texto = $this->UFIni;
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x2 - 15, 10, $texto, $aFont, 'T', 'C', 0, '', false);
        $maxW = $this->wPrint;

        $x1 += $x2 - 15;
        $this->pdf->textBox($x1, $y, $x2 - 16, 12);
        $texto = 'UF Descar.';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 16, 8, $texto, $aFont, 'T', 'C', 0, '', false);
        $texto = $this->UFFim;
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x2 - 16, 10, $texto, $aFont, 'T', 'C', 0, '', false);
        $maxW = $this->wPrint;

        if ($this->aquav) {
            $x1 = $x;
            $x2 = $maxW;
            $y += 14;
            $this->pdf->textBox($x1, $y, $x2, 10);
            $texto = 'Embarcação';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $texto = $this->aquav->getElementsByTagName('cEmbar')->item(0)->nodeValue;
            $texto .= ' - ';
            $texto .= $this->aquav->getElementsByTagName('xEmbar')->item(0)->nodeValue;
            $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
            $this->pdf->textBox($x1, $y + 4, $x2, 10, $texto, $aFont, 'T', 'L', 0, '', false);
        }

        $x1 = $x;
        $x2 = $maxW;
        $y += 13;
        $this->pdf->textBox($x1, $y, $x2, 43);
        if ($this->rodo) {
            $texto = 'Modal Rodoviário de Carga';
        }
        if ($this->aereo) {
            $texto = 'Modal Aéreo de Carga';
        }
        if ($this->aquav) {
            $texto = 'Modal Aquaviário de Carga';
        }
        if ($this->ferrov) {
            $texto = 'Modal Ferroviário de Carga';
        }
        $aFont = array('font' => $this->fontePadrao, 'size' => 12, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 1, $x2 / 2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = 'CONTROLE DO FISCO';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1 + ($x2 / 2), $y + 1, $x2 / 2, 8, $texto, $aFont, 'T', 'L', 0, '', false);

        $x1 = $x;
        $x2 = ($maxW / 6);
        $y += 6;
        $this->pdf->textBox($x1, $y, $x2, 12);
        $texto = 'Qtd. CT-e';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = str_pad($this->qCTe, 3, '0', STR_PAD_LEFT);
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x2, 10, $texto, $aFont, 'T', 'C', 0, '', false);
        $x1 += $x2;
        $this->pdf->textBox($x1, $y, $x2, 12);
        $texto = 'Qtd. NF-e';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = str_pad($this->qNFe, 3, '0', STR_PAD_LEFT);
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x2, 10, $texto, $aFont, 'T', 'C', 0, '', false);
        $x1 += $x2;
        $this->pdf->textBox($x1, $y, $x2, 12);

        if ($this->rodo
            || $this->aereo
            || $this->ferrov
        ) {
            if ($this->cUnid == 01) {
                $texto = 'Peso Total (Kg)';
            } else {
                $texto = 'Peso Total (Ton)';
            }
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $texto = number_format($this->qCarga, 4, ',', '.');
            $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
            $this->pdf->textBox($x1, $y + 4, $x2, 10, $texto, $aFont, 'T', 'C', 0, '', false);
        }

        if ($this->aquav) {
            $texto = 'Qtd. MDF-e Ref.';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $texto = str_pad($this->qMDFe, 3, '0', STR_PAD_LEFT);
            $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
            $this->pdf->textBox($x1, $y + 4, $x2, 10, $texto, $aFont, 'T', 'C', 0, '', false);

            $ya = $y + 12;
            $this->pdf->textBox($x, $ya, $maxW / 2, 12);
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            if ($this->cUnid == 01) {
                $texto = 'Peso Total (Kg)';
            } else {
                $texto = 'Peso Total (Ton)';
            }
            $this->pdf->textBox($x, $ya, $maxW / 2, 8, $texto, $aFont, 'T', 'L', 0, '');
            $texto = number_format($this->qCarga, 4, ',', '.');
            $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
            $this->pdf->textBox($x, $ya + 4, $x2, 10, $texto, $aFont, 'T', 'L', 0, '', false);
        }

        // codigo de barras da chave
        $x1 += $x2;
        //$y = $y + 8;
        $this->pdf->textBox($x1, $y, $maxW / 2, 20);
        $bH = 16;
        $w = $maxW;
        $this->pdf->setFillColor(0, 0, 0);
        $this->pdf->code128($x1 + 5, $y + 2, $this->chMDFe, ($maxW / 2) - 10, $bH);
        $this->pdf->setFillColor(255, 255, 255);

        // protocolo de autorização
        $y = $y + 24;
        $this->pdf->textBox($x, $y, $maxW / 2, 13);
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $texto = 'Protocolo de Autorização';
        $this->pdf->textBox($x, $y, $maxW / 2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
        if (is_object($this->mdfeProc)) {
            $tsHora = $this->toTimestamp($this->dhRecbto);
            $texto = $this->nProt . ' - ' . date('d/m/Y H:i:s', $tsHora);
        } else {
            $texto = 'DAMDFE impresso em contingência - ' . date('d/m/Y   H:i:s');
        }
        $this->pdf->textBox($x, $y + 4, $maxW / 2, 8, $texto, $aFont, 'T', 'C', 0, '');

        $y -= 4;

        // chave de acesso
        $this->pdf->textBox($x + $maxW / 2, $y, $maxW / 2, 17);
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'I');
        $tsHora = $this->toTimestamp($this->dhEvento);
        $texto = 'Chave de Acesso';
        $this->pdf->textBox($x + $maxW / 2, $y, $maxW / 2, 6, $texto, $aFont, 'T', 'L', 0, '');
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
        $texto = $this->formatField($this->chMDFe, $this->formatoChave);
        $this->pdf->textBox($x + $maxW / 2, $y + 4, $maxW / 2, 6, $texto, $aFont, 'T', 'C', 0, '');
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $texto = 'Consulte em https://dfe-portal.sefazvirtual.rs.gov.br/MDFe/consulta';
        $this->pdf->textBox($x + $maxW / 2, $y + 10, $maxW / 2, 6, $texto, $aFont, 'T', 'C', 0, '');

        $x1 = $x;
        $y += 20;
        $yold = $y;
        $x2 = round($maxW / 2, 0);

        if ($this->rodo) {
            $texto = 'Veículo';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $y += 5;
            $x2 = round($maxW / 4, 0);
            $tamanho = 22;
            $this->pdf->textBox($x1, $y, $x2, $tamanho);
            $texto = 'Placa';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $texto = $this->veicTracao->getElementsByTagName("placa")->item(0)->nodeValue;
            $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
            $this->pdf->textBox($x1, $y + 4, $x2, 10, $texto, $aFont, 'T', 'C', 0, '', false);
            $altura = $y + 4;
            /**
             *
             *
             * @var \DOMNodeList $veicReboque
             */
            $veicReboque = $this->veicReboque;
            foreach ($veicReboque as $item) {
                /**
                 *
                 *
                 * @var \DOMElement $item
                 */
                $altura += 4;
                $texto = $item->getElementsByTagName('placa')->item(0)->nodeValue;
                $this->pdf->textBox($x1, $altura, $x2, 10, $texto, $aFont, 'T', 'C', 0, '', false);
            }
            $x1 += $x2;
            $this->pdf->textBox($x1, $y, $x2, $tamanho);
            $texto = 'RNTRC';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            // RNTRC Não informado
            if ($this->rodo->getElementsByTagName("RNTRC")->length > 0) {
                $texto = $this->rodo->getElementsByTagName("RNTRC")->item(0)->nodeValue;
            } else {
                $texto = "";
            }
            $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
            $this->pdf->textBox($x1, $y + 4, $x2, 10, $texto, $aFont, 'T', 'C', 0, '', false);
            $altura = $y + 4;
            /**
             * @var \DOMNodeList $veicTracao
             */
            $veicTracao = $this->veicTracao->getElementsByTagName('prop');
            foreach ($veicTracao as $item) {
                /**
                 * @var \DOMElement $item
                 */
                $DOMNodeList = $item->getElementsByTagName('RNTRC');
                if ($DOMNodeList->length > 0) {
                    $altura += 4;
                    $texto = $DOMNodeList->item(0)->nodeValue;
                    $this->pdf->textBox($x1, $altura, $x2, 10, $texto, $aFont, 'T', 'C', 0, '', false);
                }
            }
            /**
             *
             *
             * @var \DOMNodeList $veicReboque
             */
            $veicReboque = $this->veicReboque;
            foreach ($veicReboque as $item) {
                /**
                 *
                 *
                 * @var \DOMElement $item
                 */
                $DOMNodeList = $item->getElementsByTagName('RNTRC');
                if ($DOMNodeList->length > 0) {
                    $altura += 4;
                    $texto = $DOMNodeList->item(0)->nodeValue;
                    $this->pdf->textBox($x1, $altura, $x2, 10, $texto, $aFont, 'T', 'C', 0, '', false);
                }
            }
            $x1 = $x;
            $y += 22;
            $x2 = round($maxW / 2, 0);
            $valesPedagios = 1;
            $temVales = false;
            if ($this->valePed != "" && $this->valePed->length > 0) {
                $valesPedagios = $this->valePed->length;
                $temVales = true;
            }
            $tamanho = ($valesPedagios * 7.5);
            if (!$temVales) {
                $valesPedagios = 0;
            }
            $this->pdf->textBox($x1, $y, $x2, 11 + $tamanho / 2);
            $texto = 'Vale Pedágio';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $y += 5;
            $x2 = ($x2 / 3);
            $this->pdf->textBox($x1, $y, $x2 - 3, 6 + ($tamanho / 2));
            $texto = 'Responsável CNPJ';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2 - 4, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $altura = $y;
            for ($i = 0; $i < $valesPedagios; $i++) {
                $altura += 4;
                $texto = $this->valePed->item($i)->getElementsByTagName('CNPJForn')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
                $this->pdf->textBox($x1 + 1, $altura, $x2 - 5, 10, $texto, $aFont, 'T', 'L', 0, '', false);
            }
            $x1 += $x2 - 3;
            $this->pdf->textBox($x1, $y, $x2 - 3, 6 + ($tamanho / 2));
            $texto = 'Fornecedora CNPJ';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2 - 4, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $altura = $y;
            for ($i = 0; $i < $valesPedagios; $i++) {
                $altura += 4;
                $texto = $this->valePed->item($i)->getElementsByTagName('CNPJPg')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
                $this->pdf->textBox($x1 + 1, $altura, $x2 - 5, 10, $texto, $aFont, 'T', 'L', 0, '', false);
            }
            $x1 += $x2 - 3;
            $this->pdf->textBox($x1, $y, $x2 + 6, 6 + ($tamanho / 2));
            $texto = 'Nº Comprovante';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2 + 6, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $altura = $y;
            for ($i = 0; $i < $valesPedagios; $i++) {
                $altura += 4;
                $texto = $this->valePed->item($i)->getElementsByTagName('nCompra')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
                $this->pdf->textBox($x1 + 1, $altura, $x2 + 5, 10, $texto, $aFont, 'T', 'L', 0, '', false);
            }
            if (!$temVales) {
                $altura += 4;
            }
            $this->condutor = $this->veicTracao->getElementsByTagName('condutor');
            $x1 = round($maxW / 2, 0) + 7;
            $y = $yold;
            $x2 = round($maxW / 2, 0);
            $texto = 'Condutor';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $y += 5;
            $x2 = ($maxW / 6);
            $this->pdf->textBox($x1, $y, $x2, 33 + ($tamanho / 2));
            $texto = 'CPF';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $yold = $y;
            for ($i = 0; $i < $this->condutor->length; $i++) {
                $y += 4;
                $texto = $this->condutor->item($i)->getElementsByTagName('CPF')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
                $this->pdf->textBox($x1 + 1, $y, $x2 - 1, 10, $texto, $aFont, 'T', 'L', 0, '', false);
            }
            $y = $yold;
            $x1 += $x2;
            $x2 = $x2 * 2;
            $this->pdf->textBox($x1, $y, $x2, 33 + ($tamanho / 2));
            $texto = 'Nome';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            for ($i = 0; $i < $this->condutor->length; $i++) {
                $y += 4;
                $texto = $this->condutor->item($i)->getElementsByTagName('xNome')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1 + 1, $y, $x2 - 1, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            }
        }

        if ($this->aereo) {
            $altura = $y + 4;
        }

        if ($this->aquav) {
            $x1 = $x;
            $x2 = $maxW;

            $initial = $y;
            $initialA = $y + 2;
            $initialB = $y + 2;

            $texto = 'Carregamento';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x, $initial + 2, ($x2 / 2), 8, $texto, $aFont, 'T', 'L', 0, '', false);
            foreach ($this->aquav->getElementsByTagName('infTermCarreg') as $item) {
                $initialA += 4.5;

                $texto = $item->getElementsByTagName('cTermCarreg')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1 + 1, $initialA, ($x2 / 2) - 1, 10, $texto, $aFont, 'T', 'L', 0, '', false);

                $texto = $item->getElementsByTagName('xTermCarreg')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1 + 25, $initialA, ($x2 / 2) - 25, 10, $texto, $aFont, 'T', 'L', 0, '', false);

                if (strlen($texto) > 50) {
                    $initialA += 2;
                }
            }
            if ($this->aquav->getElementsByTagName('infTermCarreg')->item(0) != null) {
                $this->pdf->textBox($x1, $initial + 6, ($x2 / 2), $initialA - $y);
            }

            $texto = 'Descarregamento';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x1 + ($x2 / 2), $initial + 2, $x2 / 2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            foreach ($this->aquav->getElementsByTagName('infTermDescarreg') as $item) {
                $initialB += 4.5;

                $texto = $item->getElementsByTagName('cTermDescarreg')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox(
                    ($x1 + ($x2 / 2)) + 1,
                    $initialB,
                    ($x2 / 2) - 1,
                    10,
                    $texto,
                    $aFont,
                    'T',
                    'L',
                    0,
                    '',
                    false
                );

                $texto = $item->getElementsByTagName('xTermDescarreg')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');

                $this->pdf->textBox(
                    ($x1 + ($x2 / 2)) + 25,
                    $initialB,
                    ($x2 / 2) - 25,
                    10,
                    $texto,
                    $aFont,
                    'T',
                    'L',
                    0,
                    '',
                    false
                );

                if (strlen($texto) > 50) {
                    $initialB += 2;
                }
            }
            if ($this->aquav->getElementsByTagName('infTermDescarreg')->item(0) != null) {
                $this->pdf->textBox(($x1 + ($x2 / 2)), $initial + 6, ($x2 / 2), $initialB - $y);
            }

            $altura = $initialA > $initialB ? $initialA : $initialB;
            $altura += 6;

            $y = $altura + 3;

            $initial = $y;
            $initialA = $y + 2;
            $initialB = $y + 2;

            $texto = 'Unidade de Transporte';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x, $initial + 2, ($x2 / 2), 8, $texto, $aFont, 'T', 'L', 0, '', false);

            $texto = 'Unidade de Carga';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x1 + ($x2 / 4), $initial + 2, ($x2 / 2), 8, $texto, $aFont, 'T', 'L', 0, '', false);

            foreach ($this->aquav->getElementsByTagName('infUnidCargaVazia') as $item) {
                $initialA += 4.5;

                $texto = $item->getElementsByTagName('idUnidCargaVazia')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1 + 1, $initialA, ($x2 / 2) - 1, 10, $texto, $aFont, 'T', 'L', 0, '', false);

                $texto = $item->getElementsByTagName('tpUnidCargaVazia')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox(
                    $x1 + ($x2 / 4),
                    $initialA,
                    ($x2 / 2) - 25,
                    10,
                    $texto,
                    $aFont,
                    'T',
                    'L',
                    0,
                    '',
                    false
                );

                if (strlen($texto) > 50) {
                    $initialA += 2;
                }
            }
            if ($this->aquav->getElementsByTagName('infUnidCargaVazia')->item(0) != null) {
                $this->pdf->textBox($x1, $initial + 6, ($x2 / 2), $initialA - $y);
            }

            $texto = 'Unidade de Transporte';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x1 + ($x2 / 2), $initial + 2, $x2 / 2, 8, $texto, $aFont, 'T', 'L', 0, '', false);

            $texto = 'Unidade de Carga';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x1 + ($x2 / 1.33), $initial + 2, ($x2 / 2), 8, $texto, $aFont, 'T', 'L', 0, '', false);

            foreach ($this->aquav->getElementsByTagName('infUnidTranspVazia') as $item) {
                $initialB += 4.5;

                $texto = $item->getElementsByTagName('idUnidTranspVazia')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');

                $this->pdf->textBox(
                    ($x1 + ($x2 / 2)) + 1,
                    $initialB,
                    ($x2 / 2) - 1,
                    10,
                    $texto,
                    $aFont,
                    'T',
                    'L',
                    0,
                    '',
                    false
                );

                $texto = $item->getElementsByTagName('tpUnidTranspVazia')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox(
                    ($x1 + ($x2 / 1.33)),
                    $initialB,
                    ($x2 / 2) - 25,
                    10,
                    $texto,
                    $aFont,
                    'T',
                    'L',
                    0,
                    '',
                    false
                );

                if (strlen($texto) > 50) {
                    $initialB += 2;
                }
            }
            if ($this->aquav->getElementsByTagName('infUnidTranspVazia')->item(0) != null) {
                $this->pdf->textBox(($x1 + ($x2 / 2)), $initial + 6, ($x2 / 2), $initialB - $y);
            }

            $altura = $initialA > $initialB ? $initialA : $initialB;
            $altura += 6;
        }

        if ($this->ferrov) {
            $altura = $y + 4;
        }

        return $altura + 10;
    }


    protected function qrCodeDamdfe($y = 0)
    {
        $margemInterna = $this->margemInterna;
        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj(
            'QRCODE,M',
            $this->qrCodMDFe,
            -4,
            -4,
            'black',
            array(-2, -2, -2, -2)
        )->setBackgroundColor('white');
        $qrcode = $bobj->getPngData();
        $wQr = 35;
        $hQr = 35;
        $yQr = ($y + $margemInterna);
        if ($this->orientacao == 'P') {
            $xQr = 160;
        } else {
            $xQr = 235;
        }
        // prepare a base64 encoded "data url"
        $pic = 'data://text/plain;base64,' . base64_encode($qrcode);
        $this->pdf->image($pic, $xQr, $yQr, $wQr, $hQr, 'PNG');
    }


    /**
     * footerMDFe
     *
     * @param float $x
     * @param float $y
     */
    private function footerMDFe($x, $y)
    {
        $maxW = $this->wPrint;
        $x2 = $maxW;
        $this->pdf->textBox($x, $y, $x2, 30);
        $texto = 'Observação
        ' . $this->infCpl;
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $y = $this->hPrint - 4;
        $texto = "Impresso em  " . date('d/m/Y H:i:s');
        $w = $this->wPrint - 4;
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'I');
        $this->pdf->textBox($x, $y, $w, 4, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->creditos . "  Powered by NFePHP®";
        $this->pdf->textBox($x, $y, $w, 0, $texto, $aFont, 'T', 'R', false, '');
    }

    public function monta(
        $logo = '',
        $orientacao = 'P',
        $papel = 'A4',
        $logoAlign = 'L'
    ) {
        $this->pdf = '';
        $this->logomarca = $logo;
        $this->orientacao = $orientacao;
        $this->papel = $papel;
        $this->logoAlign = $logoAlign;
        $this->buildMDFe();
    }

    /**
     * Dados brutos do PDF
     *
     * @return string
     */
    public function render()
    {
        if (empty($this->pdf)) {
            $this->monta();
        }
        return $this->pdf->getPdf();
    }

    /**
     * Add the credits to the integrator in the footer message
     *
     * @param string $message
     */
    public function creditsIntegratorFooter($message = '')
    {
        $this->creditos = trim($message);
    }
}
