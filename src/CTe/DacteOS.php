<?php

namespace NFePHP\DA\CTe;

/**
 * Classe para ageração do PDF da CTe, conforme regras e estruturas
 * estabelecidas pela SEFAZ.
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @name      Dacte .php
 * @copyright 2009-2019 NFePHP
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Roberto L. Machado <linux dot rlm at gmail dot com>
 */

use Com\Tecnick\Barcode\Barcode;
use Exception;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;

class DacteOS extends Common
{

    protected $logoAlign = 'C';
    protected $yDados = 0;
    protected $numero_registro_dpec = '';
    protected $pdf;
    protected $xml;
    protected $logomarca = '';
    protected $errMsg = '';
    protected $errStatus = false;
    protected $orientacao = 'P';
    protected $papel = 'A4';
    protected $fontePadrao = 'Times';
    protected $wPrint;
    protected $hPrint;
    protected $dom;
    protected $infCte;
    protected $infPercurso;
    protected $infCteComp;
    protected $chaveCTeRef;
    protected $tpCTe;
    protected $ide;
    protected $emit;
    protected $enderEmit;
    protected $infCarga;
    protected $infQ;
    protected $seg;
    protected $modal;
    protected $rodo;
    protected $moto;
    protected $veic;
    protected $ferrov;
    protected $Comp;
    protected $infNF;
    protected $infNFe;
    protected $compl;
    protected $ICMS;
    protected $imp;
    protected $toma4;
    protected $toma03;
    protected $tpEmis;
    protected $tpImp;
    protected $tpAmb;
    protected $vPrest;
    protected $infServico;
    protected $wAdic = 150;
    protected $textoAdic = '';
    protected $debugmode = false;
    protected $formatPadrao;
    protected $formatNegrito;
    protected $aquav;
    protected $preVisualizar;
    protected $flagDocOrigContinuacao;
    protected $arrayNFe = array();
    protected $siteDesenvolvedor;
    protected $nomeDesenvolvedor;
    protected $totPag;
    protected $idDocAntEle = [];
    protected $lota;
    protected $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";
    protected $margemInterna = 0;
    private $creditos;

    /**
     * __construct
     *
     * @param string $xml Arquivo XML da CTe
     */
    public function __construct(
        $xml = ''
    ) {
        $this->debugMode();
        $this->loadDoc($xml);
    }

    /**
     * Ativa ou desativa o modo debug
     * @param bool $activate
     * @return bool
     */
    private function debugMode($activate = null)
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
            $this->cteProc = $this->dom->getElementsByTagName("cteOSProc")->item(0);
            if (empty($this->dom->getElementsByTagName("infCte")->item(0))) {
                throw new \Exception('Isso não é um CT-e.');
            }
            $this->infCte = $this->dom->getElementsByTagName("infCte")->item(0);
            $this->ide = $this->dom->getElementsByTagName("ide")->item(0);
            if ($this->getTagValue($this->ide, "mod") != '67') {
                throw new \Exception("O xml deve ser CT-e modelo 67.");
            }
            $this->emit = $this->dom->getElementsByTagName("emit")->item(0);
            $this->enderEmit = $this->dom->getElementsByTagName("enderEmit")->item(0);
            $this->infPercurso = $this->dom->getElementsByTagName("infPercurso");
            $this->infCarga = $this->dom->getElementsByTagName("infCarga")->item(0);
            $this->infQ = $this->dom->getElementsByTagName("infQ");
            $this->seg = $this->dom->getElementsByTagName("seg")->item(0);
            $this->rodo = $this->dom->getElementsByTagName("rodoOS")->item(0);
            $this->veic = $this->dom->getElementsByTagName("veic");
            $this->ferrov = $this->dom->getElementsByTagName("ferrov")->item(0);
            // adicionar outros modais
            $this->infCteComp = $this->dom->getElementsByTagName("infCteComp")->item(0);
            $this->chaveCTeRef = $this->getTagValue($this->infCteComp, "chave");
            $this->vPrest = $this->dom->getElementsByTagName("vPrest")->item(0);
            $this->Comp = $this->dom->getElementsByTagName("Comp");
            $this->infNF = $this->dom->getElementsByTagName("infNF");
            $this->infNFe = $this->dom->getElementsByTagName("infNFe");
            $this->infOutros = $this->dom->getElementsByTagName("infOutros");
            $this->infServico = $this->dom->getElementsByTagName("infServico");
            $this->compl = $this->dom->getElementsByTagName("compl");
            $this->ICMS = $this->dom->getElementsByTagName("ICMS")->item(0);
            $this->ICMSSN = $this->dom->getElementsByTagName("ICMSSN")->item(0);
            $this->imp = $this->dom->getElementsByTagName("imp")->item(0);

            $vTrib = $this->getTagValue($this->imp, "vTotTrib");
            if (!is_numeric($vTrib)) {
                $vTrib = 0;
            }
            $textoAdic = number_format($vTrib, 2, ",", ".");
            $this->textoAdic = "o valor aproximado de tributos incidentes sobre o preço deste serviço é de R$"
                .$textoAdic;
            $this->toma = $this->dom->getElementsByTagName("toma")->item(0);
            $this->enderToma = $this->getTagValue($this->toma, "enderToma");
            //modal aquaviário
            $this->aquav = $this->dom->getElementsByTagName("aquav")->item(0);
            $seguro = $this->getTagValue($this->seg, "respSeg");
            switch ($seguro) {
                case '4':
                    $this->respSeg = 'Emitente';
                    break;
                case '5':
                    $this->respSeg = 'Tomador do Serviço';
                    break;
                default:
                    $this->respSeg = '';
                    break;
            }
            $this->tpEmis = $this->getTagValue($this->ide, "tpEmis");
            $this->tpImp = $this->getTagValue($this->ide, "tpImp");
            $this->tpAmb = $this->getTagValue($this->ide, "tpAmb");
            $this->tpCTe = $this->getTagValue($this->ide, "tpCTe");
            $this->protCTe = $this->dom->getElementsByTagName("protCTe")->item(0);
            $this->qrCodMDFe = $this->dom->getElementsByTagName('qrCodCTe')->item(0) ?
                $this->dom->getElementsByTagName('qrCodCTe')->item(0)->nodeValue : 'SEM INFORMAÇÃO DE QRCODE';
            //01-Rodoviário; //02-Aéreo; //03-Aquaviário; //04-Ferroviário;//05-Dutoviário
            $this->modal = $this->getTagValue($this->ide, "modal");
        }
    }

    /**
     * Dados brutos do PDF
     * @return string
     */
    public function render()
    {
        if (empty($this->pdf)) {
            $this->monta();
        }
        return $this->pdf->getPdf();
    }


    protected function cteDPEC()
    {
        return $this->numero_registro_dpec != '';
    }


    /**
     * montaDACTE
     * Esta função monta a DACTE conforme as informações fornecidas para a classe
     * durante sua construção.
     * A definição de margens e posições iniciais para a impressão são estabelecidas no
     * pelo conteúdo da funçao e podem ser modificados.
     *
     * @param  string $orientacao (Opcional) Estabelece a orientação da
     *                impressão (ex. P-retrato), se nada for fornecido será
     *                usado o padrão da NFe
     * @param  string $papel (Opcional) Estabelece o tamanho do papel (ex. A4)
     * @return string O ID da NFe numero de 44 digitos extraido do arquivo XML
     */
    public function monta(
        $logo = '',
        $orientacao = '',
        $papel = 'A4',
        $logoAlign = 'C'
    ) {
        $this->pdf = '';
        $this->logomarca = $logo;
        //se a orientação estiver em branco utilizar o padrão estabelecido na NF
        if ($orientacao == '') {
            if ($this->tpImp == '1') {
                $orientacao = 'P';
            } else {
                $orientacao = 'P';
            }
        }
        $this->orientacao = $orientacao;
        $this->papel = $papel;
        $this->logoAlign = $logoAlign;
        //instancia a classe pdf
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        $this->formatPadrao = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->formatNegrito = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => 'B');
        if ($this->orientacao == 'P') {
            // margens do PDF
            $margSup = 2;
            $margEsq = 2;
            $margDir = 2;
            // posição inicial do relatorio
            $xInic = 1;
            $yInic = 1;
            if ($papel == 'A4') {
                //A4 210x297mm
                $maxW = 210;
                $maxH = 297;
            }
        } else {
            // margens do PDF
            $margSup = 3;
            $margEsq = 3;
            $margDir = 3;
            // posição inicial do relatorio
            $xInic = 5;
            $yInic = 5;
            if ($papel == 'A4') {
                //A4 210x297mm
                $maxH = 210;
                $maxW = 297;
                $this->wCanhoto = 25;
            }
        }
        //total inicial de paginas
        $totPag = 1;
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
        //calculo do numero de páginas ???
        $totPag = 1;
        //montagem da primeira página
        $pag = 1;
        $x = $xInic;
        $y = $yInic;
        //coloca o cabeçalho
        //$r = $this->cabecalho($x, $y, $pag, $totPag);
        $y += 70;
        $r = $this->tomador($x, $y);
        if ($this->tpCTe == '0') {
            //Normal
            $y += 10;
            $x = $xInic;
            //$r = $this->zDocOrig($x, $y);
            $r = $this->infPrestacaoServico($x, $y);
            $y += 53;
            $x = $xInic;
            $r = $this->compValorServ($x, $y);
            $y += 25;
            $x = $xInic;
            $r = $this->impostos($x, $y);
            $y += 13;
            $x = $xInic;
            $r = $this->observacoes($x, $y);
            $y += 19;
            $x = $xInic;
            $r = $this->seguro($x, $y);
            $y = $y-12;
            switch ($this->modal) {
                case '1':
                    $y += 24.9;
                    $x = $xInic;
                    $r = $this->modalRod($x, $y);
                    break;
                case '2':
                    $y += 17.9;
                    $x = $xInic;
                    // TODO fmertins 31/10/14: este método não existe...
                    $r = $this->modalAereo($x, $y);
                    break;
                case '3':
                    $y += 17.9;
                    $x = $xInic;
                    $r = $this->modalAquaviario($x, $y);
                    break;
                case '4':
                    $y += 17.9;
                    $x = $xInic;
                    $r = $this->modalFerr($x, $y);
                    break;
                case '5':
                    $y += 17.9;
                    $x = $xInic;
                    // TODO fmertins 31/10/14: este método não existe...
                    $r = $this->modalDutoviario($x, $y);
                    break;
            }
            if ($this->modal == '1') {
                if ($this->lota == 1) {
                    $y += 37;
                } else {
                    $y += 8.9;
                }
            } elseif ($this->modal == '3') {
                $y += 24.15;
            } else {
                $y += 37;
            }
        } else {
            $r = $this->cabecalho(1, 1, $pag, $totPag);
            //Complementado
            $y += 10;
            $x = $xInic;
            $r = $this->docCompl($x, $y);
            $y += 80;
            $x = $xInic;
            $r = $this->compValorServ($x, $y);
            $y += 25;
            $x = $xInic;
            $r = $this->impostos($x, $y);
            $y += 13;
            $x = $xInic;
            $r = $this->observacoes($x, $y);
            $y += 15;
        }
        $x = $xInic;
        $y += 1;
        $r = $this->dadosAdic($x, $y, $pag, $totPag);

        $y += 21;
        //$y += 11;
        $y = $this->canhoto($x, $y);

        //coloca o rodapé da página
        if ($this->orientacao == 'P') {
            $this->rodape(2, $this->hPrint - 2);
        } else {
            $this->rodape($xInic, $this->hPrint + 2.3);
        }
        if ($this->flagDocOrigContinuacao == 1) {
            $this->docOrigContinuacao(1, 71);
        }
    }

    /**
     * cabecalho
     * Monta o cabelhalho da DACTE ( retrato e paisagem )
     *
     * @param  number $x Posição horizontal inicial, canto esquerdo
     * @param  number $y Posição vertical inicial, canto superior
     * @param  number $pag Número da Página
     * @param  number $totPag Total de páginas
     * @return number Posição vertical final
     */
    protected function cabecalho($x = 0, $y = 0, $pag = '1', $totPag = '1')
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            if ($pag == 1) {
                // primeira página
                $maxW = $this->wPrint - $this->wCanhoto;
            } else {
                // páginas seguintes
                $maxW = $this->wPrint;
            }
        }
        //##################################################################
        //coluna esquerda identificação do emitente
        $w = round($maxW * 0.42);
        if ($this->orientacao == 'P') {
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 6,
                'style' => '');
        } else {
            $aFont = $this->formatNegrito;
        }
        $w1 = $w;
        $h = 35;
        $oldY += $h;
        //desenha a caixa
        $this->pdf->textBox($x, $y, $w + 2, $h + 1);
        // coloca o logo
        if (!empty($this->logomarca)) {
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
                $y1 = round($h / 3 + $y, 0);
                $tw = round(2 * $w / 3, 0);
            } elseif ($this->logoAlign == 'C') {
                $nImgH = round($h / 3, 0);
                $nImgW = round($logoWmm * ($nImgH / $logoHmm), 0);
                $xImg = round(($w - $nImgW) / 2 + $x, 0);
                $yImg = $y + 3;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            } elseif ($this->logoAlign == 'R') {
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
        //Nome emitente
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 9,
            'style' => 'B');
        $texto = $this->getTagValue($this->emit, "xNome");
        $this->pdf->textBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        //endereço
        $y1 = $y1 + 3;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => '');
        $fone = $this->getTagValue($this->enderEmit, "fone")!=""? $this->formatFone($this->enderEmit):'';
        $lgr = $this->getTagValue($this->enderEmit, "xLgr");
        $nro = $this->getTagValue($this->enderEmit, "nro");
        $cpl = $this->getTagValue($this->enderEmit, "xCpl");
        $bairro = $this->getTagValue($this->enderEmit, "xBairro");
        $CEP = $this->getTagValue($this->enderEmit, "CEP");
        $CEP = $this->formatField($CEP, "#####-###");
        $mun = $this->getTagValue($this->enderEmit, "xMun");
        $UF = $this->getTagValue($this->enderEmit, "UF");
        $xPais = $this->getTagValue($this->enderEmit, "xPais");
        $texto = $lgr . "," . $nro . "\n" . $bairro . " - "
            . $CEP . " - " . $mun . " - " . $UF . " " . $xPais
            . "\n  Fone/Fax: " . $fone;
        $this->pdf->textBox($x1 - 5, $y1 + 2, $tw + 5, 8, $texto, $aFont, 'T', 'C', 0, '');
        //CNPJ/CPF IE
        $cpfCnpj = $this->formatCNPJCPF($this->emit);
        $ie = $this->getTagValue($this->emit, "IE");
        $texto = 'CNPJ/CPF:  ' . $cpfCnpj . '     Insc.Estadual: ' . $ie;
        $this->pdf->textBox($x1 - 1, $y1 + 12, $tw + 5, 8, $texto, $aFont, 'T', 'C', 0, '');
        //outra caixa
        $h1 = 17.5;
        $y1 = $y + $h + 1;
        $this->pdf->textBox($x, $y1, $w + 2, $h1);
        //TIPO DO CT-E
        $texto = 'TIPO DO CTE';
        $wa = 37;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($x, $y1, $w * 0.5, $h1, $texto, $aFont, 'T', 'C', 0, '');
        $tpCTe = $this->getTagValue($this->ide, "tpCTe");
        //0 - CT-e Normal,1 - CT-e de Complemento de Valores,
        //2 - CT-e de Anulação de Valores,3 - CT-e Substituto
        switch ($tpCTe) {
            case '0':
                $texto = 'Normal';
                break;
            case '1':
                $texto = 'Complemento de Valores';
                break;
            case '2':
                $texto = 'Anulação de Valores';
                break;
            case '3':
                $texto = 'Substituto';
                break;
            default:
                $texto = 'ERRO' . $tpCTe . $tpServ;
        }
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y1 + 3, $w * 0.5, $h1, $texto, $aFont, 'T', 'C', 0, '', false);
        //TIPO DO SERVIÇO
        $texto = 'TIPO DO SERVIÇO';
        $wb = 36;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($x + $wa + 4.5, $y1, $w * 0.5, $h1, $texto, $aFont, 'T', 'C', 0, '');
        $tpServ = $this->getTagValue($this->ide, "tpServ");
        //'6' => 'Transporte de Pessoas', '7' => 'Transporte de Valores', '8' => 'Transporte de Bagagem'
        switch ($tpServ) {
            case '6':
                $texto = 'Transporte de Pessoas';
                break;
            case '7':
                $texto = 'Transporte de Valores';
                break;
            case '8':
                $texto = 'Transporte de Bagagem';
                break;
            default:
                $texto = 'ERRO' . $tpServ;
        }
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x + $wa + 4.5, $y1 + 3, $w * 0.5, $h1, $texto, $aFont, 'T', 'C', 0, '', false);
        $this->pdf->line($w * 0.5, $y1, $w * 0.5, $y1 + $h1);
        //TOMADOR DO SERVIÇO
        $aFont = $this->formatNegrito;

        //##################################################################
        //coluna direita
        $x += $w + 2;
        $w = round($maxW * 0.335);
        $w1 = $w;
        $h = 11;
        $this->pdf->textBox($x, $y, $w + 10, $h + 1);
        $texto = "DACTE OS";
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 10,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 1, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 9,
            'style' => '');
        $texto = "Documento Auxiliar do Conhecimento\nde Transporte Eletrônico para Outros Serviços";
        $h = 10;
        $this->pdf->textBox($x, $y + 4, $w, $h, $texto, $aFont, 'T', 'C', 0, '', false);
        $x1 = $x + $w + 2;
        $w = round($maxW * 0.22, 0);
        $w2 = $w;
        $h = 11;
        $this->pdf->textBox($x1 + 8, $y, $w - 7.5, $h + 1);
        $texto = "MODAL";
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 10,
            'style' => '');
        $this->pdf->textBox($x1, $y + 1, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
        switch ($this->modal) {
            case '1':
                $texto = 'Rodoviário';
                break;
            case '2':
                $texto = 'Aéreo';
                break;
            case '3':
                $texto = 'Aquaviário';
                break;
            case '4':
                $texto = 'Ferroviário';
                break;
            case '5':
                $texto = 'Dutoviário';
                break;
        }
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 10,
            'style' => 'B');
        $this->pdf->textBox($x1, $y + 5, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
        //outra caixa
        $y += 12;
        $h = 9;
        $w = $w1 + 10;
        $this->pdf->textBox($x, $y, $w, $h + 1);
        //modelo
        $wa = 12;
        $xa = $x;
        $texto = 'MODELO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($xa, $y + 1, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = $this->getTagValue($this->ide, "mod");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($xa, $y + 5, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->line($x + $wa, $y, $x + $wa, $y + $h + 1);
        //serie
        $xa += $wa;
        $texto = 'SÉRIE';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($xa, $y + 1, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = $this->getTagValue($this->ide, "serie");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($xa, $y + 5, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->line($xa + $wa, $y, $xa + $wa, $y + $h + 1);
        //numero
        $xa += $wa;
        $wa = 20;
        $texto = 'NÚMERO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($xa, $y + 1, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = $this->getTagValue($this->ide, "nCT");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($xa, $y + 5, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->line($xa + $wa, $y, $xa + $wa, $y + $h + 1);
        //data  hora de emissão
        $xa += $wa;
        $wa = 35;
        $texto = 'DATA E HORA DE EMISSÃO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($xa, $y + 1, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->line($xa + $wa, $y, $xa + $wa, $y + $h + 1);
        $texto = !empty($this->ide->getElementsByTagName("dhEmi")->item(0)->nodeValue) ?
            date('d/m/Y H:i:s', $this->toTimestamp($this->getTagValue($this->ide, "dhEmi"))) : '';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($xa, $y + 5, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->line($xa + $wa, $y, $xa + $wa, $y + $h + 1);
        //outra caixa
        $y += $h + 1;
        $h = 23;
        $h1 = 14;
        //$this->pdf->textBox($x, $y, $w + 0.5, $h1);
        //CODIGO DE BARRAS
        $chave_acesso = str_replace('CTe', '', $this->infCte->getAttribute("Id"));
        $bW = 75;
        $bH = 10;
        //codigo de barras
        $this->pdf->setFillColor(0, 0, 0);
        $this->pdf->code128($x + 2, $y + 2, $chave_acesso, $bW, $bH);
        $this->pdf->line($xa + $wa, $y, $xa + $wa, $y + $h - 1);
        $this->pdf->textBox($x, $y + $h1, $w + 0.5, $h1 - 6);
        $texto = 'CHAVE DE ACESSO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x + 1, $y + $h1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $aFont = $this->formatNegrito;
        $texto = $this->formatField($chave_acesso, $this->formatoChave);
        $this->pdf->textBox($x + 1, $y + $h1 + 3, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $this->pdf->textBox($x, $y + $h1 + 8, $w + 0.5, $h1 - 4.5);
        $texto = "Consulta de autenticidade no portal nacional do CT-e, ";
        $texto .= "no site da Sefaz Autorizadora, \r\n ou em http://www.cte.fazenda.gov.br";
        if ($this->tpEmis == 5 || $this->tpEmis == 7 || $this->tpEmis == 8) {
            $texto = "";
            $this->pdf->setFillColor(0, 0, 0);
            if ($this->tpEmis == 5) {
                $chaveContingencia = $this->geraChaveAdicCont();
                $this->pdf->code128($x + 20, $y1 + 10, $chaveContingencia, $bW * .9, $bH / 2);
            } else {
                $chaveContingencia = $this->getTagValue($this->protCTe, "nProt");
                $this->pdf->code128($x + 40, $y1 + 10, $chaveContingencia, $bW * .4, $bH / 2);
            }
            //codigo de barras
        }
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($x, $y + $h1 + 9, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
        //outra caixa
        $y += $h + 1;
        $h = 8.5;
        $wa = $w;
        $this->pdf->textBox($x, $y + 7.5, $w + 0.5, $h);
        if ($this->cteDPEC()) {
            $texto = 'NÚMERO DE REGISTRO DPEC';
        } elseif ($this->tpEmis == 5 || $this->tpEmis == 7 || $this->tpEmis == 8) {
            $texto = "DADOS DO CT-E";
        } else {
            $texto = 'PROTOCOLO DE AUTORIZAÇÃO DE USO';
        }
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y + 7.5, $wa, $h, $texto, $aFont, 'T', 'L', 0, '');
        if ($this->cteDPEC()) {
            $texto = $this->numero_registro_dpec;
        } elseif ($this->tpEmis == 5) {
            $chaveContingencia = $this->geraChaveAdicCont();
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 8,
                'style' => 'B');
            $texto = $this->formatField($chaveContingencia, "#### #### #### #### #### #### #### #### ####");
            $cStat = '';
        } else {
            $texto = $this->getTagValue($this->protCTe, "nProt") . " - ";
            // empty($volume->getElementsByTagName("qVol")->item(0)->nodeValue)
            if (!empty($this->protCTe)
                && !empty($this->protCTe->getElementsByTagName("dhRecbto")->item(0)->nodeValue)
            ) {
                $texto .= date(
                    'd/m/Y   H:i:s',
                    $this->toTimestamp($this->getTagValue($this->protCTe, "dhRecbto"))
                );
            }
            $texto = $this->getTagValue($this->protCTe, "nProt") == '' ? '' : $texto;
        }
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 12, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');

        if ($this->qrCodMDFe !== null) {
            $this->qrCodeDacteOs($y-25);
            $w = 38;
            $x += 79;
            $this->pdf->textBox($x, $y - 34, $w + 0.5, $h + 41.5);
        }
        //CFOP
        $x = $oldX;
        $h = 8.5;
        $w = round($maxW * 0.42);
        $y1 = $y + 7.5;
        $this->pdf->textBox($x, $y1, $w + 2, $h);
        $texto = 'CFOP - NATUREZA DA PRESTAÇÃO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ide, "CFOP") . ' - ' . $this->getTagValue($this->ide, "natOp");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y1 + 3.5, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //ORIGEM DA PRESTAÇÃO
        $y += $h + 7.5;
        $x = $oldX;
        $h = 8;
        $w = ($maxW * 0.33);
        $this->pdf->textBox($x, $y, $w + 0.5, $h);
        $texto = 'INÍCIO DA PRESTAÇÃO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ide, "xMunIni") . ' - ' . $this->getTagValue($this->ide, "UFIni");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3.5, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //PERCURSO DO VEÍCULO
        $x = $oldX + 69;
        $oldX = $x;
        $h = 8;
        $w = ($maxW * 0.334);
        $this->pdf->textBox($x, $y, $w + 0.5, $h);
        $texto = 'PERCURSO DO VEÍCULO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = '';
        $first = true;
        if (!empty($this->infPercurso)) {
            foreach ($this->infPercurso as $k => $d) {
                if (!$first) {
                    $texto .= ' - ';
                } else {
                    $first = false;
                }
                $texto .= $this->infPercurso->item($k)->getElementsByTagName('UFPer')->item(0)->nodeValue;
            }
        }
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3.5, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //DESTINO DA PRESTAÇÃO
        $x = $oldX + $w + 1;
        $h = 8;
        $w = $w - 1.3;
        $this->pdf->textBox($x - 0.5, $y, $w + 0.5, $h);
        $texto = 'TÉRMINO DA PRESTAÇÃO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ide, "xMunFim") . ' - ' . $this->getTagValue($this->ide, "UFFim");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3.5, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //#########################################################################
        //Indicação de CTe Homologação, cancelamento e falta de protocolo
        $tpAmb = $this->ide->getElementsByTagName('tpAmb')->item(0)->nodeValue;
        //indicar cancelamento
        $cStat = $this->getTagValue($this->cteProc, "cStat");
        if ($cStat == '101' || $cStat == '135') {
            //101 Cancelamento
            $x = 10;
            $y = $this->hPrint - 130;
            $h = 25;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "CTe CANCELADO";
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 48,
                'style' => 'B');
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        }
        $cStat = $this->getTagValue($this->cteProc, "cStat");
        if ($cStat == '110' ||
            $cStat == '301' ||
            $cStat == '302'
        ) {
            //110 Denegada
            $x = 10;
            $y = $this->hPrint - 130;
            $h = 25;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "CTe USO DENEGADO";
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 48,
                'style' => 'B');
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $y += $h;
            $h = 5;
            $w = $maxW - (2 * $x);
            $texto = "SEM VALOR FISCAL";
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 48,
                'style' => 'B');
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        }
        //indicar sem valor
        if ($tpAmb != 1) { // caso não seja uma DA de produção
            $x = 10;
            if ($this->orientacao == 'P') {
                $y = round($this->hPrint / 2, 0);
            } else {
                $y = round($this->hPrint / 2, 0);
            }
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 48,
                'style' => 'B');
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 30,
                'style' => 'B');
            $texto = "AMBIENTE DE HOMOLOGAÇÃO";
            $this->pdf->textBox($x, $y + 14, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        } elseif ($this->preVisualizar=='1') { // caso seja uma DA de Pré-Visualização
            $h = 5;
            $w = $maxW - (2 * 10);
            $x = 55;
            $y = 240;
            $this->pdf->setTextColor(255, 100, 100);
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 40,
                'style' => 'B');
            $texto = "Pré-visualização";
            $this->pdf->textBox90($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(255, 100, 100);
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 41,
                'style' => 'B');
            $texto = "Sem Validade Jurídica";
            $this->pdf->textBox90($x+20, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 48,
                'style' => 'B');
            $this->pdf->textBox90($x+40, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0); // voltar a cor default
        } else {
            $x = 10;
            if ($this->orientacao == 'P') {
                $y = round($this->hPrint * 2 / 3, 0);
            } else {
                $y = round($this->hPrint / 2, 0);
            } //fim orientacao
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            //indicar FALTA DO PROTOCOLO se NFe não for em contingência
            if (($this->tpEmis == 5 || $this->tpEmis == 7 || $this->tpEmis == 8) && !$this->cteDPEC()) {
                //Contingência
                $texto = "DACTE Emitido em Contingência";
                $aFont = array(
                    'font' => $this->fontePadrao,
                    'size' => 48,
                    'style' => 'B');
                $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                $aFont = array(
                    'font' => $this->fontePadrao,
                    'size' => 30,
                    'style' => 'B');
                $texto = "devido à problemas técnicos";
                $this->pdf->textBox($x, $y + 12, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            } else {
                if (!isset($this->protCTe)) {
                    if (!$this->cteDPEC()) {
                        $texto = "SEM VALOR FISCAL";
                        $aFont = array(
                            'font' => $this->fontePadrao,
                            'size' => 48,
                            'style' => 'B');
                        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    }
                    $aFont = array(
                        'font' => $this->fontePadrao,
                        'size' => 30,
                        'style' => 'B');
                    $texto = "FALTA PROTOCOLO DE APROVAÇÃO DA SEFAZ";
                    if (!$this->cteDPEC()) {
                        $this->pdf->textBox($x, $y + 12, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    } else {
                        $this->pdf->textBox($x, $y + 25, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    }
                } //fim cteProc
                if ($this->tpEmis == 4) {
                    //DPEC
                    $x = 10;
                    $y = $this->hPrint - 130;
                    $h = 25;
                    $w = $maxW - (2 * $x);
                    $this->pdf->setTextColor(200, 200, 200); // 90,90,90 é muito escuro
                    $texto = "DACTE impresso em contingência -\n"
                        . "DPEC regularmente recebido pela Receita\n"
                        . "Federal do Brasil";
                    $aFont = array(
                        'font' => $this->fontePadrao,
                        'size' => 48,
                        'style' => 'B');
                    $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    $this->pdf->setTextColor(0, 0, 0);
                }
            } //fim tpEmis
            $this->pdf->setTextColor(0, 0, 0);
        }
        return $oldY;
    }

    /**
     * rodapeDACTE
     * Monta o rodape no final da DACTE ( retrato e paisagem )
     *
     * @param number $xInic Posição horizontal canto esquerdo
     * @param number $yFinal Posição vertical final para impressão
     */
    protected function rodape($x, $y)
    {
        $texto = "Impresso em  " . date('d/m/Y   H:i:s');
        $w = $this->wPrint - 4;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x-1, $y+2, $w, 4, $texto, $aFont, 'T', 'L', 0, '');

        $texto = $this->creditos .  "  Powered by NFePHP®";
        $this->pdf->textBox($x, $y, $w, 0, $texto, $aFont, 'T', 'R', false, '');
    }

    /**
     * tomador
     * Monta o campo com os dados do remetente na DACTE. ( retrato  e paisagem  )
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function tomador($x = 0, $y = 0)
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 10;
        $texto = 'TOMADOR DO SERVIÇO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $aFont = $this->formatNegrito;
        $texto = $this->getTagValue($this->toma, "xNome");
        $this->pdf->textBox($x + 29, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $maxW * 0.60;
        $texto = 'MUNICÍPIO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->toma, "xMun");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x + 15, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $maxW * 0.85;
        $texto = 'UF';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->toma, "UF");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x + 4, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w - 18;
        $texto = 'CEP';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->formatField($this->getTagValue($this->toma, "CEP"), "#####-###");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x + 6, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $y += 3;
        $x = $oldX;
        $texto = 'ENDEREÇO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $aFont = $this->formatNegrito;
        $texto = $this->getTagValue($this->toma, "xLgr") . ',';
        $texto .= $this->getTagValue($this->toma, "nro");
        $texto .= ($this->getTagValue($this->toma, "xCpl") != "") ?
            ' - ' . $this->getTagValue($this->toma, "xCpl") : '';
        $texto .= ' - ' . $this->getTagValue($this->toma, "xBairro");
        $this->pdf->textBox($x + 16, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $y += 3;
        $texto = 'CNPJ/CPF';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->formatCNPJCPF($this->toma);
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x + 13, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $x + 65;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->toma, "IE");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x + 28, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.75;
        $texto = 'PAÍS';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->toma, "xPais") != "" ?
            $this->getTagValue($this->toma, "xPais") : 'BRASIL';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x + 6, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w - 27;
        $texto = 'FONE';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->toma, "fone")!=""? $this->formatFone($this->toma):'';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x + 8, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
    }

    /**
     * compValorServ
     * Monta o campo com os componentes da prestação de serviços.
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function compValorServ($x = 0, $y = 0)
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 25;
        $texto = 'COMPONENTES DO VALOR DA PRESTAÇÃO DO SERVIÇO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y);
        $texto = 'NOME';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yIniDados = $y;
        $x = $w * 0.14;
        $texto = 'VALOR';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.28;
        $this->pdf->line($x, $y, $x, $y + 21.5);
        $texto = 'NOME';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.42;
        $texto = 'VALOR';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.56;
        $this->pdf->line($x, $y, $x, $y + 21.5);
        $texto = 'NOME';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.70;
        $texto = 'VALOR';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.86;
        $this->pdf->line($x, $y, $x, $y + 21.5);
        $y += 1;
        $texto = 'VALOR TOTAL DO SERVIÇO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = number_format($this->getTagValue($this->vPrest, "vTPrest"), 2, ",", ".");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 9,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 4, $w * 0.14, $h, $texto, $aFont, 'T', 'C', 0, '');
        $y += 10;
        $this->pdf->line($x, $y, $w + 1, $y);
        $y += 1;
        $texto = 'VALOR A RECEBER';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = number_format($this->getTagValue($this->vPrest, "vRec"), 2, ",", ".");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 9,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 4, $w * 0.14, $h, $texto, $aFont, 'T', 'C', 0, '');
        $auxX = $oldX;
        $yIniDados += 4;

        foreach ($this->Comp as $k => $d) {
            $nome = $this->Comp->item($k)->getElementsByTagName('xNome')->item(0)->nodeValue;
            $valor = number_format(
                $this->Comp->item($k)->getElementsByTagName('vComp')->item(0)->nodeValue,
                2,
                ",",
                "."
            );
            if ($auxX > $w * 0.60) {
                $yIniDados = $yIniDados + 4;
                $auxX = $oldX;
            }
            $texto = $nome;
            $aFont = $this->formatPadrao;
            $this->pdf->textBox($auxX, $yIniDados, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
            $auxX += $w * 0.14;
            $texto = $valor;
            $aFont = $this->formatPadrao;
            $this->pdf->textBox($auxX, $yIniDados, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
            $auxX += $w * 0.14;
        }
    } //fim da função compValorDACTE

    /**
     * impostos
     * Monta o campo com os dados do remetente na DACTE. ( retrato  e paisagem  )
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function impostos($x = 0, $y = 0)
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 13;
        $texto = 'INFORMAÇÕES RELATIVAS AO IMPOSTO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');

        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y);
        $texto = 'SITUAÇÃO TRIBUTÁRIA';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * 0.26;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = 'BASE DE CALCULO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');

        $wCol02=0.18;
        $x += $w * $wCol02;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = 'ALÍQ ICMS';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * $wCol02;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = 'VALOR ICMS';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * $wCol02;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = '% RED. BC ICMS';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        /*$x += $w * 0.14;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = 'ICMS ST';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
         * */

        $x = $oldX;
        $y = $y + 4;
        $texto = $this->getTagValue($this->ICMS, "CST");
        switch ($texto) {
            case '00':
                $texto = "00 - Tributação normal ICMS";
                break;
            case '20':
                $texto = "20 - Tributação com BC reduzida do ICMS";
                break;
            case '40':
                $texto = "40 - ICMS isenção";
                break;
            case '41':
                $texto = "41 - ICMS não tributada";
                break;
            case '51':
                $texto = "51 - ICMS diferido";
                break;
            case '60':
                $texto = "60 - ICMS cobrado anteriormente por substituição tributária";
                break;
            case '90':
                $texto = "90 - ICMS outros";
                break;
        }
        $texto = $this->getTagValue($this->ICMSSN, "indSN") == 1 ? 'Simples Nacional' : $texto;
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.26;

        $texto = !empty($this->ICMS->getElementsByTagName("vBC")->item(0)->nodeValue) ?
            number_format($this->getTagValue($this->ICMS, "vBC"), 2, ",", ".") : '';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = !empty($this->ICMS->getElementsByTagName("pICMS")->item(0)->nodeValue) ?
            number_format($this->getTagValue($this->ICMS, "pICMS"), 2, ",", ".") : '';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = !empty($this->ICMS->getElementsByTagName("vICMS")->item(0)->nodeValue) ?
            number_format($this->getTagValue($this->ICMS, "vICMS"), 2, ",", ".") : '';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = !empty($this->ICMS->getElementsByTagName("pRedBC")->item(0)->nodeValue) ?
            number_format($this->getTagValue($this->ICMS, "pRedBC"), 2, ",", ".").'%' :'';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        /*$x += $w * 0.14;
        $texto = '';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');*/
    } //fim da função compValorDACTE

    /**
     * geraChaveAdicCont
     *
     * @return string chave
     */
    protected function geraChaveAdicCont()
    {
        //cUF tpEmis CNPJ vNF ICMSp ICMSs DD  DV
        // Quantidade de caracteres  02   01      14  14    01    01  02 01
        $forma = "%02d%d%s%014d%01d%01d%02d";
        $cUF = $this->ide->getElementsByTagName('cUF')->item(0)->nodeValue;
        $CNPJ = "00000000000000" . $this->emit->getElementsByTagName('CNPJ')->item(0)->nodeValue;
        $CNPJ = substr($CNPJ, -14);
        $vCT = number_format($this->getTagValue($this->vPrest, "vRec"), 2, "", "") * 100;
        $ICMS_CST = $this->getTagValue($this->ICMS, "CST");
        switch ($ICMS_CST) {
            case '00':
            case '20':
                $ICMSp = '1';
                $ICMSs = '2';
                break;
            case '40':
            case '41':
            case '51':
            case '90':
                $ICMSp = '2';
                $ICMSs = '2';
                break;
            case '60':
                $ICMSp = '2';
                $ICMSs = '1';
                break;
        }
        $dd = $this->ide->getElementsByTagName('dEmi')->item(0)->nodeValue;
        $rpos = strrpos($dd, '-');
        $dd = substr($dd, $rpos + 1);
        $chave = sprintf($forma, $cUF, $this->tpEmis, $CNPJ, $vCT, $ICMSp, $ICMSs, $dd);
        $chave = $chave . $this->pModulo11($chave);
        return $chave;
    } //fim geraChaveAdicCont

    /**
     * infPrestacaoServico
     * Monta o campo com das informações da prestação do serviço
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function infPrestacaoServico($x = 0, $y = 0)
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;

        // SE FOR RODOVIARIO ( BTR-SEMPRE SERÁ )
        if ($this->modal == '1') {
            // 0 - Não; 1 - Sim Será lotação quando houver um único conhecimento de transporte por veículo,
            // ou combinação veicular, e por viagem
            $h = $this->lota == 1 ? 35 : 53;
        } elseif ($this->modal == '3') {
            $h = 37.6;
        } else {
            $h = 35;
        }
        $texto = 'INFORMAÇÕES DA PRESTAÇÃO DO SERVIÇO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $descr1 = 'QUANTIDADE';
        $descr2 = 'DESCRIÇÃO DO SERVIÇO PRESTADO';

        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y); // LINHA ABAIXO DO TEXTO: "DOCUMENTOS ORIGINÁRIOS
        $texto = $descr1;
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yIniDados = $y;

        $x += $w * 0.14;
        if ($this->modal == '1') {
            if ($this->lota == 1) {
                $this->pdf->line($x, $y, $x, $y + 31.5); // TESTE
            } else {
                $this->pdf->line($x, $y, $x, $y + 49.5); // TESTE
            }
        } elseif ($this->modal == '3') {
            $this->pdf->line($x, $y, $x, $y + 34.1);
        } else {
            $this->pdf->line($x, $y, $x, $y + 21.5);
        }

        $x += $w * 0.08;
        $texto = $descr2;
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');

        //$auxX = $oldX;
        //$yIniDados += 3;

        $x = $oldX;
        $y = $y + 4;
        $texto = number_format($this->getTagValue($this->infQ->item(0), "qCarga"), 3, ",", ".");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.26;

        $x = $oldX + 35;
        $texto = $this->getTagValue($this->infServico->item(0), "xDescServ");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.26;


        $r = $this->cabecalho(1, 1, '1', $this->totPag);
        $contador = 0;
    }

    /**
     * docCompl
     * Monta o campo com os dados do remetente na DACTE.
     *
     * @param number $x Posição horizontal canto esquerdo
     * @param number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function docCompl($x = 0, $y = 0)
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 80;
        $texto = 'DETALHAMENTO DO CT-E COMPLEMENTADO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $descr1 = 'CHAVE DO CT-E COMPLEMENTADO';
        $descr2 = 'VALOR COMPLEMENTADO';
        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y);
        $texto = $descr1;
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yIniDados = $y;
        $x += $w * 0.37;
        $texto = $descr2;
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x - 8, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.13;
        $this->pdf->line($x, $y, $x, $y + 76.5);
        $texto = $descr1;
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.3;
        $texto = $descr2;
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x + 8, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $auxX = $oldX;
        $yIniDados += 4;
        if ($auxX > $w * 0.90) {
            $yIniDados = $yIniDados + 4;
            $auxX = $oldX;
        }
        $texto = $this->chaveCTeRef;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($auxX, $yIniDados, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = number_format($this->getTagValue($this->vPrest, "vTPrest"), 2, ",", ".");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pdf->textBox($w * 0.40, $yIniDados, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
    }

    /**
     * observacoes
     * Monta o campo com os dados do remetente na DACTE.
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function observacoes($x = 0, $y = 0)
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        //$h = 18;
        $h = 18.8;
        $texto = 'OBSERVAÇÕES GERAIS';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y);
        $auxX = $oldX;
        $yIniDados = $y;
        $texto = '';
        foreach ($this->compl as $k => $d) {
            $xObs = $this->getTagValue($this->compl->item($k), "xObs");
            $texto .= $xObs;
        }
        $textoObs = explode("Motorista:", $texto);
        $textoObs[1] = isset($textoObs[1]) ? "Motorista: ".$textoObs[1]: '';
        $texto .= $this->getTagValue($this->imp, "infAdFisco", "\r\n");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7.5,
            'style' => '');
        $this->pdf->textBox($x, $y, $w, $h, $textoObs[0], $aFont, 'T', 'L', 0, '', false);
        $this->pdf->textBox($x, $y+11.5, $w, $h, $textoObs[1], $aFont, 'T', 'L', 0, '', false);
    }

    /**
     * seguro
     * Monta o campo com os dados de seguro do CT-e OS.
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function seguro($x = 0, $y = 0)
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 13;
        $texto = 'SEGURO DA VIAGEM';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');

        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y);
        $texto = 'RESPONSÁVEL';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.33, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * 0.33;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = 'NOME DA SEGURADORA';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.33, $h, $texto, $aFont, 'T', 'L', 0, '');

        $wCol02=0.33;
        $x += $w * $wCol02;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = 'NÚMERO DA APÓLICE';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x = $oldX;
        $y = $y + 4;
        $texto = $this->respSeg;
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.26;

        $texto = '';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = '';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;
    }

    /**
     * modalRod
     * Monta o campo com os dados do remetente na DACTE. ( retrato  e paisagem  )
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function modalRod($x = 0, $y = 0)
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 13;
        $texto = 'DADOS ESPECÍFICOS DO MODAL RODOVIÁRIO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');

        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y);
        $texto = 'TERMO DE AUTORIZAÇÃO DE FRETAMENTO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * 0.26;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = 'Nº DE REGISTRO ESTADUAL';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');

        $wCol02=0.18;
        $x += $w * $wCol02;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = 'PLACA DO VEÍCULO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * $wCol02;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = 'RENAVAM DO VEÍCULO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * $wCol02;
        $this->pdf->line($x, $y, $x, $y + 9.5);
        $texto = 'CNPJ/CPF';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x = $oldX;
        $y = $y + 4;
        $texto = $this->getTagValue($this->rodo, "TAF");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.26;

        $texto = $this->getTagValue($this->rodo, "NroRegEstadual");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = $this->getTagValue($this->veic->item(0), "placa");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = $this->getTagValue($this->veic->item(0), "RENAVAM");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = !empty($this->getTagValue($this->veic->item(0), "CPF")) ?
            $this->getTagValue($this->veic->item(0), "CPF") :
            (!empty($this->getTagValue($this->veic->item(0), "CNPJ")) ?
            $this->getTagValue($this->veic->item(0), "CNPJ") : '');
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        /*$x += $w * 0.14;
        $texto = '';
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');*/
    }

    /**
     * modalAquaviario
     * Monta o campo com os dados do remetente na DACTE. ( retrato  e paisagem  )
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function modalAquaviario($x = 0, $y = 0)
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 8.5;
        $texto = 'DADOS ESPECÍFICOS DO MODAL AQUAVIÁRIO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h * 3.2, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y);
        $texto = 'PORTO DE EMBARQUE';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->aquav, "prtEmb");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.50;
        $this->pdf->line($x, $y, $x, $y + 7.7);
        $texto = 'PORTO DE DESTINO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->aquav, "prtDest");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $y += 8;
        $this->pdf->line(208, $y, 1, $y);
        $x = 1;
        $texto = 'IDENTIFICAÇÃO DO NAVIO / REBOCADOR';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->aquav, "xNavio");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.50;
        $this->pdf->line($x, $y, $x, $y + 7.7);
        $texto = 'VR DA B. DE CALC. AFRMM';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->aquav, "vPrest");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.17;
        $this->pdf->line($x, $y, $x, $y + 7.7);
        $texto = 'VALOR DO AFRMM';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->aquav, "vAFRMM");
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.12;
        $this->pdf->line($x, $y, $x, $y + 7.7);
        $texto = 'TIPO DE NAVEGAÇÃO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->aquav, "tpNav");
        switch ($texto) {
            case '0':
                $texto = 'INTERIOR';
                break;
            case '1':
                $texto = 'CABOTAGEM';
                break;
        }
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.14;
        $this->pdf->line($x, $y, $x, $y + 7.7);
        $texto = 'DIREÇÃO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->aquav, "direc");
        switch ($texto) {
            case 'N':
                $texto = 'NORTE';
                break;
            case 'L':
                $texto = 'LESTE';
                break;
            case 'S':
                $texto = 'SUL';
                break;
            case 'O':
                $texto = 'OESTE';
                break;
        }
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $y += 8;
        $this->pdf->line(208, $y, 1, $y);
        $x = 1;
        $texto = 'IDENTIFICAÇÃO DOS CONTEINERS';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        if ($this->infNF->item(0) !== null && $this->infNF->item(0)->getElementsByTagName('infUnidCarga') !== null) {
            $texto = $this->infNF
                ->item(0)
                ->getElementsByTagName('infUnidCarga')
                ->item(0)
                ->getElementsByTagName('idUnidCarga')
                ->item(0)->nodeValue;
        } elseif ($this->infNFe->item(0) !== null
            && $this->infNFe->item(0)->getElementsByTagName('infUnidCarga') !== null
        ) {
            $texto = $this->infNFe
                ->item(0)
                ->getElementsByTagName('infUnidCarga')
                ->item(0)
                ->getElementsByTagName('idUnidCarga')
                ->item(0)
                ->nodeValue;
        } elseif ($this->infOutros->item(0) !== null
            && $this->infOutros->item(0)->getElementsByTagName('infUnidCarga') !== null
        ) {
            $texto = $this->infOutros
                ->item(0)
                ->getElementsByTagName('infUnidCarga')
                ->item(0)
                ->getElementsByTagName('idUnidCarga')
                ->item(0)
                ->nodeValue;
        } else {
            $texto = '';
        }
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.50;
        $this->pdf->line($x, $y, $x, $y + 7.7);
        $texto = 'IDENTIFICAÇÃO DAS BALSAS';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = '';
        if ($this->getTagValue($this->aquav, "balsa") !== '') {
            foreach ($this->aquav->getElementsByTagName('balsa') as $k => $d) {
                if ($k == 0) {
                    $texto = $this->aquav
                        ->getElementsByTagName('balsa')
                        ->item($k)
                        ->getElementsByTagName('xBalsa')
                        ->item(0)
                        ->nodeValue;
                } else {
                    $texto = $texto
                        . ' / '
                        . $this->aquav
                            ->getElementsByTagName('balsa')
                            ->item($k)
                            ->getElementsByTagName('xBalsa')
                            ->item(0)
                            ->nodeValue;
                }
            }
        }
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
    }

    /**
     * modalFerr
     * Monta o campo com os dados do remetente na DACTE. ( retrato  e paisagem  )
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function modalFerr($x = 0, $y = 0)
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 19.6;
        $texto = 'DADOS ESPECÍFICOS DO MODAL FERROVIÁRIO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y);
        $texto = 'DCL';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => 'B');
        $this->pdf->textBox($x, $y, $w * 0.25, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->line($x + 49.6, $y, $x + 49.6, $y + 3.5);
        $texto = 'VAGÕES';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => 'B');
        $this->pdf->textBox($x + 50, $y, $w * 0.5, $h, $texto, $aFont, 'T', 'C', 0, '');
        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y);
        // DCL
        $texto = 'ID TREM';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "idTrem");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.06;
        $y1 = $y + 12.5;
        $this->pdf->line($x, $y, $x, $y1);
        $texto = 'NUM';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->rem, "nDoc");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.06;
        $this->pdf->line($x, $y, $x, $y1);
        $texto = 'SÉRIE';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->rem, "serie");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.06;
        $this->pdf->line($x, $y, $x, $y1);
        $texto = 'EMISSÃO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pYmd2dmy($this->getTagValue($this->rem, "dEmi"));
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        // VAGOES
        $x += $w * 0.06;
        $this->pdf->line($x, $y, $x, $y1);
        $texto = 'NUM';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "nVag");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.06;
        $this->pdf->line($x, $y, $x, $y1);
        $texto = 'TIPO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "tpVag");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.06;
        $this->pdf->line($x, $y, $x, $y1);
        $texto = 'CAPACIDADE';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "cap");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.08;
        $this->pdf->line($x, $y, $x, $y1);
        $texto = 'PESO REAL/TON';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "pesoR");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.09;
        $this->pdf->line($x, $y, $x, $y1);
        $texto = 'PESO BRUTO/TON';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "pesoBC");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.1;
        $this->pdf->line($x, $y, $x, $y1);
        $texto = 'IDENTIFICAÇÃO DOS CONTÊINERES';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "nCont");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        // FLUXO
        $x = 1;
        $y += 12.9;
        $h1 = $h * 0.5 + 0.27;
        $wa = round($w * 0.103) + 0.5;
        $texto = 'FLUXO FERROVIARIO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $wa, $h1, $texto, $aFont, 'T', 'C', 1, '');
        $texto = $this->getTagValue($this->ferrov, "fluxo");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $wa, $h1, $texto, $aFont, 'T', 'C', 0, '');
        $y += 10;
        $texto = 'TIPO DE TRÁFEGO';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $wa, $h1, $texto, $aFont, 'T', 'C', 1, '');
        $texto = $this->convertUnidTrafego($this->getTagValue($this->ferrov, "tpTraf"));
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $wa, $h1, $texto, $aFont, 'T', 'C', 0, '');
        // Novo Box Relativo a Modal Ferroviário
        $x = 22.5;
        $y += -10.2;
        $texto = 'INFORMAÇÕES DAS FERROVIAS ENVOLVIDAS';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w - 21.5, $h1 * 2.019, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y);
        $w = $w * 0.2;
        $h = $h * 1.04;
        $texto = 'CÓDIGO INTERNO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "cInt");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = 'CNPJ';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y + 6, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "CNPJ");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 9, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += 50;
        $texto = 'NOME';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "xNome");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 3, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = 'INSCRICAO ESTADUAL';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y + 6, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->getTagValue($this->ferrov, "IE");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 9, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += 50;
        $texto = 'PARTICIPAÇÃO OUTRA FERROVIA';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y + 6, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = '';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pdf->textBox($x, $y + 9, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
    }

    /**
     * canhoto
     * Monta o campo com os dados do remetente na DACTE.
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function canhoto($x = 0, $y = 0)
    {
        $this->pdf->dashedHLine($x, $y+2, $this->wPrint, 0.1, 80);
        $y = $y + 2;
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW - 1;
        $h = 20;
        $y = $y + 1;
        $texto = 'DECLARO QUE RECEBI OS VOLUMES DESTE CONHECIMENTO EM PERFEITO ESTADO ';
        $texto .= 'PELO QUE DOU POR CUMPRIDO O PRESENTE CONTRATO DE TRANSPORTE';
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->line($x, $y, $w + 1, $y); // LINHA ABAICO DO TEXTO DECLARO QUE RECEBI...

        $texto = 'NOME';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.25, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.25;

        $this->pdf->line($x, $y, $x, $y + 16.5);

        $texto = 'ASSINATURA / CARIMBO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w * 0.25, $h - 3.4, $texto, $aFont, 'B', 'C', 0, '');
        $x += $w * 0.25;

        $this->pdf->line($x, $y, $x, $y + 16.5);

        $texto = 'TÉRMINO DA PRESTAÇÃO - DATA/HORA' . "\r\n" . "\r\n" . "\r\n". "\r\n";
        $texto .= ' INÍCIO DA PRESTAÇÃO - DATA/HORA';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x + 10, $y, $w * 0.25, $h - 3.4, $texto, $aFont, 'T', 'C', 0, '');
        $x = $oldX;
        $y = $y + 5;

        $this->pdf->line($x, $y+3, $w * 0.255, $y+3); // LINHA HORIZONTAL ACIMA DO RG ABAIXO DO NOME

        $texto = 'RG';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y+3, $w * 0.33, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.85;

        $this->pdf->line($x, $y + 11.5, $x, $y - 5); // LINHA VERTICAL PROXIMO AO CT-E

        $texto = "CT-E OS";
        $aFont = $this->formatNegrito;
        $this->pdf->textBox($x, $y - 5, $w * 0.15, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = "\r\n Nº. DOCUMENTO  " . $this->getTagValue($this->ide, "nCT") . " \n";
        $texto .= "\r\n SÉRIE  " . $this->getTagValue($this->ide, "serie");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y - 8, $w * 0.15, $h, $texto, $aFont, 'C', 'C', 0, '');
        $x = $oldX;
    }

    /**
     * dadosAdic
     * Coloca o grupo de dados adicionais da DACTE.
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @param  number $h altura do campo
     * @return number Posição vertical final
     */
    protected function dadosAdic($x, $y, $pag, $h)
    {
        $oldX = $x;
        //###########################################################################
        //DADOS ADICIONAIS DACTE
        if ($this->orientacao == 'P') {
            $w = $this->wPrint;
        } else {
            $w = $this->wPrint - $this->wCanhoto;
        }
        //INFORMAÇÕES COMPLEMENTARES
        $texto = "USO EXCLUSIVO DO EMISSOR DO CT-E";
        $y += 3;
        $w = $this->wAdic;
        $h = 20; //mudar
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        //$this->pdf->line($x, $y + 3, $w * 1.385, $y + 3);
        $this->pdf->line($x, $y + 3, $w * 1.385, $y + 3);
        //o texto com os dados adicionais foi obtido na função xxxxxx
        //e carregado em uma propriedade privada da classe
        //$this->wAdic com a largura do campo
        //$this->textoAdic com o texto completo do campo
        $y += 1;
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y + 3, $w - 2, $h - 3, $this->textoAdic, $aFont, 'T', 'L', 0, '', false);
        //RESERVADO AO FISCO
        $texto = "RESERVADO AO FISCO";
        $x += $w;
        $y -= 1;
        if ($this->orientacao == 'P') {
            $w = $this->wPrint - $w;
        } else {
            $w = $this->wPrint - $w - $this->wCanhoto;
        }
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        //inserir texto informando caso de contingência
        //1 – Normal – emissão normal;
        //2 – Contingência FS – emissão em contingência com impressão do DACTE em Formulário de Segurança;
        //3 – Contingência SCAN – emissão em contingência  – SCAN;
        //4 – Contingência DPEC - emissão em contingência com envio da Declaração Prévia de
        //Emissão em Contingência – DPEC;
        //5 – Contingência FS-DA - emissão em contingência com impressão do DACTE em Formulário de
        //Segurança para Impressão de Documento Auxiliar de Documento Fiscal Eletrônico (FS-DA).
        $xJust = $this->getTagValue($this->ide, 'xJust', 'Justificativa: ');
        $dhCont = $this->getTagValue($this->ide, 'dhCont', ' Entrada em contingência : ');
        $texto = '';
        switch ($this->tpEmis) {
            case 2:
                $texto = 'CONTINGÊNCIA FS' . $dhCont . $xJust;
                break;
            case 3:
                $texto = 'CONTINGÊNCIA SCAN' . $dhCont . $xJust;
                break;
            case 4:
                $texto = 'CONTINGÊNCIA DPEC' . $dhCont . $xJust;
                break;
            case 5:
                $texto = 'CONTINGÊNCIA FSDA' . $dhCont . $xJust;
                break;
        }
        $y += 2;
        $aFont = $this->formatPadrao;
        $this->pdf->textBox($x, $y + 2, $w - 2, $h - 3, $texto, $aFont, 'T', 'L', 0, '', false);
        return $y + $h;
    }

    /**
     * formatCNPJCPF
     * Formata campo CnpjCpf contida na CTe
     *
     * @param  string $field campo cnpjCpf da CT-e
     * @return string
     */
    protected function formatCNPJCPF($field)
    {
        if (!isset($field)) {
            return '';
        }
        $cnpj = !empty($field->getElementsByTagName("CNPJ")->item(0)->nodeValue) ?
            $field->getElementsByTagName("CNPJ")->item(0)->nodeValue : "";
        if ($cnpj != "" && $cnpj != "00000000000000") {
            $cnpj = $this->formatField($cnpj, '###.###.###/####-##');
        } else {
            $cnpj = !empty($field->getElementsByTagName("CPF")->item(0)->nodeValue) ?
                $this->formatField($field->getElementsByTagName("CPF")->item(0)->nodeValue, '###.###.###.###-##') : '';
        }
        return $cnpj;
    }

    /**
     * formatFone
     * Formata campo fone contida na CTe
     *
     * @param  string $field campo fone da CT-e
     * @return string
     */
    protected function formatFone($field)
    {
        try {
            $fone = !empty($field->getElementsByTagName("fone")->item(0)->nodeValue) ?
            $field->getElementsByTagName("fone")->item(0)->nodeValue : '';
            $foneLen = strlen($fone);
            if ($foneLen > 0) {
                $fone2 = substr($fone, 0, $foneLen - 4);
                $fone1 = substr($fone, 0, $foneLen - 8);
                $fone = '(' . $fone1 . ') ' . substr($fone2, -4) . '-' . substr($fone, -4);
            } else {
                $fone = '';
            }
            return $fone;
        } catch (Exception $exc) {
            return '';
        }
    }

    /**
     * unidade
     * Converte a imformação de peso contida na CTe
     *
     * @param  string $c unidade de trafego extraida da CTe
     * @return string
     */
    protected function unidade($c = '')
    {
        switch ($c) {
            case '00':
                $r = 'M3';
                break;
            case '01':
                $r = 'KG';
                break;
            case '02':
                $r = 'TON';
                break;
            case '03':
                $r = 'UN';
                break;
            case '04':
                $r = 'LT';
                break;
            case '05':
                $r = 'MMBTU';
                break;
            default:
                $r = '';
        }
        return $r;
    }

    /**
     * convertUnidTrafego
     * Converte a imformação de peso contida na CTe
     *
     * @param  string $U Informação de trafego extraida da CTe
     * @return string
     */
    protected function convertUnidTrafego($U = '')
    {
        if ($U) {
            switch ($U) {
                case '0':
                    $stringU = 'Próprio';
                    break;
                case '1':
                    $stringU = 'Mútuo';
                    break;
                case '2':
                    $stringU = 'Rodoferroviário';
                    break;
                case '3':
                    $stringU = 'Rodoviário';
                    break;
            }
            return $stringU;
        }
    }

    /**
     * multiUniPeso
     * Fornece a imformação multiplicação de peso contida na CTe
     *
     * @param  interger $U Informação de peso extraida da CTe
     * @return interger
     */
    protected function multiUniPeso($U = '')
    {
        if ($U === "01") {
            // tonelada
            //return 1000;
            return 1;
        }
        return 1; // M3, KG, Unidade, litros, mmbtu
    }

    protected function qrCodeDacteOs($y = 0)
    {
        $margemInterna = $this->margemInterna;
        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj(
            'QRCODE,M',
            $this->qrCodMDFe,
            -4,
            -4,
            'black',
            array(-1, -1, -1, -1)
        )->setBackgroundColor('white');
        $qrcode = $bobj->getPngData();
        $wQr = 36;
        $hQr = 36;
        $yQr = ($y + $margemInterna);
        if ($this->orientacao == 'P') {
            $xQr = 170;
        } else {
            $xQr = 250;
        }
        // prepare a base64 encoded "data url"
        $pic = 'data://text/plain;base64,' . base64_encode($qrcode);
        $this->pdf->image($pic, $xQr, $yQr, $wQr, $hQr, 'PNG');
    }

    /**
     * Add the credits to the integrator in the footer message
     * @param string $message
     */
    public function creditsIntegratorFooter($message = '')
    {
        $this->creditos = trim($message);
    }
}
