<?php

namespace NFePHP\DA\CTe;

/**
 * Classe para ageração do PDF da CTe, conforme regras e estruturas
 * estabelecidas pela SEFAZ.
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @name      Dacte .php
 * @copyright 2009-2016 NFePHP
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Roberto L. Machado <linux dot rlm at gmail dot com>
 */

use Exception;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;

class DacteOSV3 extends Common
{
    const NFEPHP_SITUACAO_EXTERNA_CANCELADA = 1;
    const NFEPHP_SITUACAO_EXTERNA_DENEGADA = 2;
    const SIT_DPEC = 3;

    protected $logoAlign = 'C';
    protected $yDados = 0;
    protected $situacao_externa = 0;
    protected $numero_registro_dpec = '';
    protected $pdf;
    protected $xml;
    protected $logomarca = '';
    protected $errMsg = '';
    protected $errStatus = false;
    protected $orientacao = 'P';
    protected $papel = 'A4';
    protected $destino = 'I';
    protected $pdfDir = '';
    protected $fontePadrao = 'Times';
    protected $version = '1.3.0';
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
    protected $debugMode = 2;
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

    /**
     * __construct
     *
     * @param string $docXML Arquivo XML da CTe
     * @param string $sOrientacao (Opcional) Orientação da impressão P ou L
     * @param string $sPapel Tamanho do papel (Ex. A4)
     * @param string $sPathLogo Caminho para o arquivo do logo
     * @param string $sDestino Estabelece a direção do envio do documento PDF
     * @param string $sDirPDF Caminho para o diretorio de armaz. dos PDF
     * @param string $fonteDACTE Nome da fonte a ser utilizada
     * @param number $mododebug 0-Não 1-Sim e 2-nada (2 default)
     * @param string $preVisualizar 0-Não 1-Sim
     */
    public function __construct(
        $docXML = '',
        $sOrientacao = '',
        $sPapel = '',
        $sPathLogo = '',
        $sDestino = 'I',
        $sDirPDF = '',
        $fonteDACTE = '',
        $mododebug = 2,
        $preVisualizar = false,
        $nomeDesenvolvedor = 'Powered by NFePHP (GNU/GPLv3 GNU/LGPLv3) © www.nfephp.org',
        $siteDesenvolvedor = 'http://www.nfephp.org'
    ) {

        if (is_numeric($mododebug)) {
            $this->debugMode = $mododebug;
        }
        if ($mododebug == 1) {
            //ativar modo debug
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } elseif ($mododebug == 0) {
            //desativar modo debug
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
        $this->orientacao = $sOrientacao;
        $this->papel = $sPapel;
        $this->pdf = '';
        $this->xml = $docXML;
        $this->logomarca = $sPathLogo;
        $this->destino = $sDestino;
        $this->pdfDir = $sDirPDF;
        $this->preVisualizar = $preVisualizar;
        $this->siteDesenvolvedor = $siteDesenvolvedor;
        $this->nomeDesenvolvedor = $nomeDesenvolvedor;
        // verifica se foi passa a fonte a ser usada
        if (!empty($fonteDACTE)) {
            $this->fontePadrao = $fonteDACTE;
        }
        $this->formatPadrao = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->formatNegrito = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => 'B');
        //se for passado o xml
        if (!empty($this->xml)) {
            $this->dom = new Dom();
            $this->dom->loadXML($this->xml);
            $this->cteProc = $this->dom->getElementsByTagName("cteOSProc")->item(0);
            $this->infCte = $this->dom->getElementsByTagName("infCte")->item(0);
            $this->ide = $this->dom->getElementsByTagName("ide")->item(0);
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
            $this->chaveCTeRef = $this->pSimpleGetValue($this->infCteComp, "chave");
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

            $vTrib = $this->pSimpleGetValue($this->imp, "vTotTrib");
            if (!is_numeric($vTrib)) {
                $vTrib = 0;
            }
            $textoAdic = number_format($vTrib, 2, ",", ".");

            $this->textoAdic = "o valor aproximado de tributos incidentes sobre o preço deste serviço é de R$"
                    .$textoAdic;
            $this->toma = $this->dom->getElementsByTagName("toma")->item(0);
            $this->enderToma = $this->pSimpleGetValue($this->toma, "enderToma");
            //modal aquaviário
            $this->aquav = $this->dom->getElementsByTagName("aquav")->item(0);

            $seguro = $this->pSimpleGetValue($this->seg, "respSeg");
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
            $this->tpEmis = $this->pSimpleGetValue($this->ide, "tpEmis");
            $this->tpImp = $this->pSimpleGetValue($this->ide, "tpImp");
            $this->tpAmb = $this->pSimpleGetValue($this->ide, "tpAmb");
            $this->tpCTe = $this->pSimpleGetValue($this->ide, "tpCTe");
            $this->protCTe = $this->dom->getElementsByTagName("protCTe")->item(0);
            //01-Rodoviário; //02-Aéreo; //03-Aquaviário; //04-Ferroviário;//05-Dutoviário
            $this->modal = $this->pSimpleGetValue($this->ide, "modal");
        }
    }

    /**
     * monta
     * @param string $orientacao L ou P
     * @param string $papel A4
     * @param string $logoAlign C, L ou R
     * @param Pdf $classPDF
     * @return string montagem
     */
    public function monta(
        $orientacao = '',
        $papel = 'A4',
        $logoAlign = 'C',
        $classPDF = false
    ) {

        return $this->montaDACTE($orientacao, $papel, $logoAlign, $classPDF);
    }

    /**
     * printDocument
     * @param string $nome
     * @param string $destino
     * @param string $printer
     * @return
     */
    public function printDocument($nome = '', $destino = 'I', $printer = '')
    {
        return $this->printDACTE($nome, $destino, $printer);
    }

    /**
     * Dados brutos do PDF
     * @return string
     */
    public function render()
    {
        return $this->pdf->getPdf();
    }


    protected function zCteDPEC()
    {
        return $this->situacao_externa == self::SIT_DPEC && $this->numero_registro_dpec != '';
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
    public function montaDACTE(
        $orientacao = '',
        $papel = 'A4',
        $logoAlign = 'C',
        $classPDF = false
    ) {

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

        //$this->situacao_externa = $situacao_externa;
        //instancia a classe pdf
        if ($classPDF !== false) {
            $this->pdf = $classPDF;
        } else {
            $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        }
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
        $this->pdf->AliasNbPages();
        // fixa as margens
        $this->pdf->SetMargins($margEsq, $margSup, $margDir);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetFillColor(255, 255, 255);
        // inicia o documento
        $this->pdf->Open();
        // adiciona a primeira página
        $this->pdf->AddPage($this->orientacao, $this->papel);
        $this->pdf->SetLineWidth(0.1);
        $this->pdf->SetTextColor(0, 0, 0);
        //calculo do numero de páginas ???
        $totPag = 1;
        //montagem da primeira página
        $pag = 1;
        $x = $xInic;
        $y = $yInic;
        //coloca o cabeçalho
        //$r = $this->zCabecalho($x, $y, $pag, $totPag);
        $y += 70;
        $r = $this->zTomador($x, $y);
        if ($this->tpCTe == '0') {
            //Normal
            $y += 10;
            $x = $xInic;
            //$r = $this->zDocOrig($x, $y);
            $r = $this->zInfPrestacaoServico($x, $y);
            $y += 53;
            $x = $xInic;
            $r = $this->zCompValorServ($x, $y);
            $y += 25;
            $x = $xInic;
            $r = $this->zImpostos($x, $y);
            $y += 13;
            $x = $xInic;
            $r = $this->zObs($x, $y);
            $y += 19;
            $x = $xInic;
            $r = $this->zSeguro($x, $y);
            $y = $y-12;


            switch ($this->modal) {
                case '1':
                    $y += 24.9;
                    $x = $xInic;
                    $r = $this->zModalRod($x, $y);
                    break;
                case '2':
                    $y += 17.9;
                    $x = $xInic;
                    // TODO fmertins 31/10/14: este método não existe...
                    $r = $this->zModalAereo($x, $y);
                    break;
                case '3':
                    $y += 17.9;
                    $x = $xInic;
                    $r = $this->zModalAquaviario($x, $y);
                    break;
                case '4':
                    $y += 17.9;
                    $x = $xInic;
                    $r = $this->zModalFerr($x, $y);
                    break;
                case '5':
                    $y += 17.9;
                    $x = $xInic;
                    // TODO fmertins 31/10/14: este método não existe...
                    $r = $this->zModalDutoviario($x, $y);
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
            $r = $this->zCabecalho(1, 1, $pag, $totPag);
            //Complementado
            $y += 10;
            $x = $xInic;
            $r = $this->zDocCompl($x, $y);
            $y += 80;
            $x = $xInic;
            $r = $this->zCompValorServ($x, $y);
            $y += 25;
            $x = $xInic;
            $r = $this->zImpostos($x, $y);
            $y += 13;
            $x = $xInic;
            $r = $this->zObs($x, $y);
            $y += 15;
        }
        $x = $xInic;
        $y += 1;
        $r = $this->zDadosAdic($x, $y, $pag, $totPag);

        $y += 21;
        //$y += 11;
        $y = $this->zCanhoto($x, $y);

        //coloca o rodapé da página
        if ($this->orientacao == 'P') {
            $this->zRodape(2, $this->hPrint - 2);
        } else {
            $this->zRodape($xInic, $this->hPrint + 2.3);
        }
        if ($this->flagDocOrigContinuacao == 1) {
            $this->zdocOrigContinuacao(1, 71);
        }
        //retorna o ID na CTe
        if ($classPDF !== false) {
            $aR = array('id' => str_replace('CTe', '', $this->infCte->getAttribute("Id")), 'classe_PDF' => $this->pdf);
            return $aR;
        } else {
            return str_replace('CTe', '', $this->infCte->getAttribute("Id"));
        }
    } //fim da função montaDACTE

    /**
     * printDACTE
     * Esta função envia a DACTE em PDF criada para o dispositivo informado.
     * O destino da impressão pode ser :
     * I-browser
     * D-browser com download
     * F-salva em um arquivo local com o nome informado
     * S-retorna o documento como uma string e o nome é ignorado.
     * Para enviar o pdf diretamente para uma impressora indique o
     * nome da impressora e o destino deve ser 'S'.
     *
     * @param  string $nome Path completo com o nome do arquivo pdf
     * @param  string $destino Direção do envio do PDF
     * @param  string $printer Identificação da impressora no sistema
     * @return string Caso o destino seja S o pdf é retornado como uma string
     * @todo Rotina de impressão direta do arquivo pdf criado
     */
    public function printDACTE($nome = '', $destino = 'I', $printer = '')
    {
        $arq = $this->pdf->Output($nome, $destino);
        if ($destino == 'S') {
            //aqui pode entrar a rotina de impressão direta
        }
        return $arq;
    } //fim função printDACTE

    /**
     * zCabecalho
     * Monta o cabelhalho da DACTE ( retrato e paisagem )
     *
     * @param  number $x Posição horizontal inicial, canto esquerdo
     * @param  number $y Posição vertical inicial, canto superior
     * @param  number $pag Número da Página
     * @param  number $totPag Total de páginas
     * @return number Posição vertical final
     */
    protected function zCabecalho($x = 0, $y = 0, $pag = '1', $totPag = '1')
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
        $this->pTextBox($x, $y, $w + 2, $h + 1);
        // coloca o logo
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
            $this->pdf->Image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
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
        $texto = $this->pSimpleGetValue($this->emit, "xNome");
        $this->pTextBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        //endereço
        $y1 = $y1 + 3;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => '');
        $fone = $this->pSimpleGetValue($this->enderEmit, "fone")!=""? $this->zFormatFone($this->enderEmit):'';
        $lgr = $this->pSimpleGetValue($this->enderEmit, "xLgr");
        $nro = $this->pSimpleGetValue($this->enderEmit, "nro");
        $cpl = $this->pSimpleGetValue($this->enderEmit, "xCpl");
        $bairro = $this->pSimpleGetValue($this->enderEmit, "xBairro");
        $CEP = $this->pSimpleGetValue($this->enderEmit, "CEP");
        $CEP = $this->pFormat($CEP, "#####-###");
        $mun = $this->pSimpleGetValue($this->enderEmit, "xMun");
        $UF = $this->pSimpleGetValue($this->enderEmit, "UF");
        $xPais = $this->pSimpleGetValue($this->enderEmit, "xPais");
        $texto = $lgr . "," . $nro . "\n" . $bairro . " - "
            . $CEP . " - " . $mun . " - " . $UF . " " . $xPais
            . "\n  Fone/Fax: " . $fone;
        $this->pTextBox($x1 - 5, $y1 + 2, $tw + 5, 8, $texto, $aFont, 'T', 'C', 0, '');
        //CNPJ/CPF IE
        $cpfCnpj = $this->zFormatCNPJCPF($this->emit);
        $ie = $this->pSimpleGetValue($this->emit, "IE");
        $texto = 'CNPJ/CPF:  ' . $cpfCnpj . '     Insc.Estadual: ' . $ie;
        $this->pTextBox($x1 - 1, $y1 + 12, $tw + 5, 8, $texto, $aFont, 'T', 'C', 0, '');
        //outra caixa
        $h1 = 17.5;
        $y1 = $y + $h + 1;
        $this->pTextBox($x, $y1, $w + 2, $h1);
        //TIPO DO CT-E
        $texto = 'TIPO DO CTE';
        $wa = 37;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($x, $y1, $w * 0.5, $h1, $texto, $aFont, 'T', 'C', 0, '');
        $tpCTe = $this->pSimpleGetValue($this->ide, "tpCTe");
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
        $this->pTextBox($x, $y1 + 3, $w * 0.5, $h1, $texto, $aFont, 'T', 'C', 0, '', false);
        //TIPO DO SERVIÇO
        $texto = 'TIPO DO SERVIÇO';
        $wb = 36;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($x + $wa + 4.5, $y1, $w * 0.5, $h1, $texto, $aFont, 'T', 'C', 0, '');
        $tpServ = $this->pSimpleGetValue($this->ide, "tpServ");
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
        $this->pTextBox($x + $wa + 4.5, $y1 + 3, $w * 0.5, $h1, $texto, $aFont, 'T', 'C', 0, '', false);
        $this->pdf->Line($w * 0.5, $y1, $w * 0.5, $y1 + $h1);
        //TOMADOR DO SERVIÇO
        $aFont = $this->formatNegrito;

        //##################################################################
        //coluna direita
        $x += $w + 2;
        $w = round($maxW * 0.335);
        $w1 = $w;
        $h = 11;
        $this->pTextBox($x, $y, $w + 2, $h + 1);
        $texto = "DACTE OS";
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 10,
            'style' => 'B');
        $this->pTextBox($x, $y + 1, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $texto = "Documento Auxiliar do Conhecimento\nde Transporte Eletrônico para Outros Serviços";
        $h = 10;
        $this->pTextBox($x, $y + 4, $w, $h, $texto, $aFont, 'T', 'C', 0, '', false);
        $x1 = $x + $w + 2;
        $w = round($maxW * 0.22, 0);
        $w2 = $w;
        $h = 11;
        $this->pTextBox($x1, $y, $w + 0.5, $h + 1);
        $texto = "MODAL";
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($x1, $y + 1, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
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
        $this->pTextBox($x1, $y + 5, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
        //outra caixa
        $y += 12;
        $h = 9;
        $w = $w1 + $w2 + 2;
        $this->pTextBox($x, $y, $w + 0.5, $h + 1);
        //modelo
        $wa = 12;
        $xa = $x;
        $texto = 'MODELO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($xa, $y + 1, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = $this->pSimpleGetValue($this->ide, "mod");
        $aFont = $this->formatNegrito;
        $this->pTextBox($xa, $y + 5, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->Line($x + $wa, $y, $x + $wa, $y + $h + 1);
        //serie
        $xa += $wa;
        $texto = 'SÉRIE';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($xa, $y + 1, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = $this->pSimpleGetValue($this->ide, "serie");
        $aFont = $this->formatNegrito;
        $this->pTextBox($xa, $y + 5, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->Line($xa + $wa, $y, $xa + $wa, $y + $h + 1);
        //numero
        $xa += $wa;
        $wa = 20;
        $texto = 'NÚMERO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($xa, $y + 1, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = $this->pSimpleGetValue($this->ide, "nCT");
        $aFont = $this->formatNegrito;
        $this->pTextBox($xa, $y + 5, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->Line($xa + $wa, $y, $xa + $wa, $y + $h + 1);
        //data  hora de emissão
        $xa += $wa;
        $wa = 74;
        $texto = 'DATA E HORA DE EMISSÃO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($xa, $y + 1, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = !empty($this->ide->getElementsByTagName("dhEmi")->item(0)->nodeValue) ?
            date('d/m/Y H:i:s', $this->pConvertTime($this->pSimpleGetValue($this->ide, "dhEmi"))) : '';
        $aFont = $this->formatNegrito;
        $this->pTextBox($xa, $y + 5, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        //outra caixa
        $y += $h + 1;
        $h = 23;
        $h1 = 14;
        $this->pTextBox($x, $y, $w + 0.5, $h1);
        //CODIGO DE BARRAS
        $chave_acesso = str_replace('CTe', '', $this->infCte->getAttribute("Id"));
        $bW = 85;
        $bH = 10;
        //codigo de barras
        $this->pdf->SetFillColor(0, 0, 0);
        $this->pdf->Code128($x + (($w - $bW) / 2), $y + 2, $chave_acesso, $bW, $bH);
        $this->pTextBox($x, $y + $h1, $w + 0.5, $h1 - 6);
        $texto = 'CHAVE DE ACESSO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y + $h1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $aFont = $this->formatNegrito;
        $texto = $this->pFormat($chave_acesso, '##.####.##.###.###/####-##-##-###-###.###.###-###.###.###-#');
        $this->pTextBox($x, $y + $h1 + 3, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pTextBox($x, $y + $h1 + 8, $w + 0.5, $h1 - 4.5);
        $texto = "Consulta de autenticidade no portal nacional do CT-e, ";
        $texto .= "no site da Sefaz Autorizadora, \r\n ou em http://www.cte.fazenda.gov.br";
        if ($this->tpEmis == 5 || $this->tpEmis == 7 || $this->tpEmis == 8) {
            $texto = "";
            $this->pdf->SetFillColor(0, 0, 0);
            if ($this->tpEmis == 5) {
                $chaveContingencia = $this->zGeraChaveAdicCont();
                $this->pdf->Code128($x + 20, $y1 + 10, $chaveContingencia, $bW * .9, $bH / 2);
            } else {
                $chaveContingencia = $this->pSimpleGetValue($this->protCTe, "nProt");
                $this->pdf->Code128($x + 40, $y1 + 10, $chaveContingencia, $bW * .4, $bH / 2);
            }
            //codigo de barras
        }
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($x, $y + $h1 + 9, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
        //outra caixa
        $y += $h + 1;
        $h = 8.5;
        $wa = $w;
        $this->pTextBox($x, $y + 7.5, $w + 0.5, $h);
        if ($this->zCteDPEC()) {
            $texto = 'NÚMERO DE REGISTRO DPEC';
        } elseif ($this->tpEmis == 5 || $this->tpEmis == 7 || $this->tpEmis == 8) {
            $texto = "DADOS DO CT-E";
        } else {
            $texto = 'PROTOCOLO DE AUTORIZAÇÃO DE USO';
        }
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y + 7.5, $wa, $h, $texto, $aFont, 'T', 'L', 0, '');
        if ($this->zCteDPEC()) {
            $texto = $this->numero_registro_dpec;
        } elseif ($this->tpEmis == 5) {
            $chaveContingencia = $this->zGeraChaveAdicCont();
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 8,
                'style' => 'B');
            $texto = $this->pFormat($chaveContingencia, "#### #### #### #### #### #### #### #### ####");
            $cStat = '';
        } else {
            $texto = $this->pSimpleGetValue($this->protCTe, "nProt") . " - ";
            // empty($volume->getElementsByTagName("qVol")->item(0)->nodeValue)
            if (!empty($this->protCTe)
                && !empty($this->protCTe->getElementsByTagName("dhRecbto")->item(0)->nodeValue)
            ) {
                $texto .= date(
                    'd/m/Y   H:i:s',
                    $this->pConvertTime($this->pSimpleGetValue($this->protCTe, "dhRecbto"))
                );
            }
            $texto = $this->pSimpleGetValue($this->protCTe, "nProt") == '' ? '' : $texto;
        }
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y + 12, $wa, $h, $texto, $aFont, 'T', 'C', 0, '');
        //CFOP
        $x = $oldX;
        $h = 8.5;
        $w = round($maxW * 0.42);
        $y1 = $y + 7.5;
        $this->pTextBox($x, $y1, $w + 2, $h);
        $texto = 'CFOP - NATUREZA DA PRESTAÇÃO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ide, "CFOP") . ' - ' . $this->pSimpleGetValue($this->ide, "natOp");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y1 + 3.5, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //ORIGEM DA PRESTAÇÃO
        $y += $h + 7.5;
        $x = $oldX;
        $h = 8;
        $w = ($maxW * 0.33);
        $this->pTextBox($x, $y, $w + 0.5, $h);
        $texto = 'INÍCIO DA PRESTAÇÃO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ide, "xMunIni") . ' - ' . $this->pSimpleGetValue($this->ide, "UFIni");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y + 3.5, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //PERCURSO DO VEÍCULO
        $x = $oldX + 69;
        $oldX = $x;
        $h = 8;
        $w = ($maxW * 0.334);
        $this->pTextBox($x, $y, $w + 0.5, $h);
        $texto = 'PERCURSO DO VEÍCULO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
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
        $this->pTextBox($x, $y + 3.5, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //DESTINO DA PRESTAÇÃO
        $x = $oldX + $w + 1;
        $h = 8;
        $w = $w - 1.3;
        $this->pTextBox($x - 0.5, $y, $w + 0.5, $h);
        $texto = 'TÉRMINO DA PRESTAÇÃO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ide, "xMunFim") . ' - ' . $this->pSimpleGetValue($this->ide, "UFFim");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y + 3.5, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //#########################################################################
        //Indicação de CTe Homologação, cancelamento e falta de protocolo
        $tpAmb = $this->ide->getElementsByTagName('tpAmb')->item(0)->nodeValue;
        //indicar cancelamento
        $cStat = $this->pSimpleGetValue($this->cteProc, "cStat");
        if ($cStat == '101' || $cStat == '135' || $this->situacao_externa == self::NFEPHP_SITUACAO_EXTERNA_CANCELADA) {
            //101 Cancelamento
            $x = 10;
            $y = $this->hPrint - 130;
            $h = 25;
            $w = $maxW - (2 * $x);
            $this->pdf->SetTextColor(90, 90, 90);
            $texto = "CTe CANCELADO";
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 48,
                'style' => 'B');
            $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }
        $cStat = $this->pSimpleGetValue($this->cteProc, "cStat");
        if ($cStat == '110' ||
            $cStat == '301' ||
            $cStat == '302' ||
            $this->situacao_externa == self::NFEPHP_SITUACAO_EXTERNA_DENEGADA
        ) {
            //110 Denegada
            $x = 10;
            $y = $this->hPrint - 130;
            $h = 25;
            $w = $maxW - (2 * $x);
            $this->pdf->SetTextColor(90, 90, 90);
            $texto = "CTe USO DENEGADO";
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 48,
                'style' => 'B');
            $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $y += $h;
            $h = 5;
            $w = $maxW - (2 * $x);
            $texto = "SEM VALOR FISCAL";
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 48,
                'style' => 'B');
            $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }
        //indicar sem valor
        if ($tpAmb != 1 && $this->preVisualizar=='0') { // caso não seja uma DA de produção
            $x = 10;
            if ($this->orientacao == 'P') {
                $y = round($this->hPrint / 2, 0);
            } else {
                $y = round($this->hPrint / 2, 0);
            }
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->SetTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 48,
                'style' => 'B');
            $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 30,
                'style' => 'B');
            $texto = "AMBIENTE DE HOMOLOGAÇÃO";
            $this->pTextBox($x, $y + 14, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        } elseif ($this->preVisualizar=='1') { // caso seja uma DA de Pré-Visualização
            $h = 5;
            $w = $maxW - (2 * 10);
            $x = 55;
            $y = 240;
            $this->pdf->SetTextColor(255, 100, 100);
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 40,
                'style' => 'B');
            $texto = "Pré-visualização";
            $this->pTextBox90($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(255, 100, 100);
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 41,
                'style' => 'B');
            $texto = "Sem Validade Jurídica";
            $this->pTextBox90($x+20, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = array(
                'font' => $this->fontePadrao,
                'size' => 48,
                'style' => 'B');
            $this->pTextBox90($x+40, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0); // voltar a cor default
        } else {
            $x = 10;
            if ($this->orientacao == 'P') {
                $y = round($this->hPrint * 2 / 3, 0);
            } else {
                $y = round($this->hPrint / 2, 0);
            } //fim orientacao
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->SetTextColor(90, 90, 90);
            //indicar FALTA DO PROTOCOLO se NFe não for em contingência
            if (($this->tpEmis == 5 || $this->tpEmis == 7 || $this->tpEmis == 8) && !$this->zCteDPEC()) {
                //Contingência
                $texto = "DACTE Emitido em Contingência";
                $aFont = array(
                    'font' => $this->fontePadrao,
                    'size' => 48,
                    'style' => 'B');
                $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                $aFont = array(
                    'font' => $this->fontePadrao,
                    'size' => 30,
                    'style' => 'B');
                $texto = "devido à problemas técnicos";
                $this->pTextBox($x, $y + 12, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            } else {
                if (!isset($this->protCTe)) {
                    if (!$this->zCteDPEC()) {
                        $texto = "SEM VALOR FISCAL";
                        $aFont = array(
                            'font' => $this->fontePadrao,
                            'size' => 48,
                            'style' => 'B');
                        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    }
                    $aFont = array(
                        'font' => $this->fontePadrao,
                        'size' => 30,
                        'style' => 'B');
                    $texto = "FALTA PROTOCOLO DE APROVAÇÃO DA SEFAZ";
                    if (!$this->zCteDPEC()) {
                        $this->pTextBox($x, $y + 12, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    } else {
                        $this->pTextBox($x, $y + 25, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    }
                } //fim cteProc
                if ($this->tpEmis == 4) {
                    //DPEC
                    $x = 10;
                    $y = $this->hPrint - 130;
                    $h = 25;
                    $w = $maxW - (2 * $x);
                    $this->pdf->SetTextColor(200, 200, 200); // 90,90,90 é muito escuro
                    $texto = "DACTE impresso em contingência -\n"
                        . "DPEC regularmente recebido pela Receita\n"
                        . "Federal do Brasil";
                    $aFont = array(
                        'font' => $this->fontePadrao,
                        'size' => 48,
                        'style' => 'B');
                    $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    $this->pdf->SetTextColor(0, 0, 0);
                }
            } //fim tpEmis
            $this->pdf->SetTextColor(0, 0, 0);
        }
        return $oldY;
    } //fim zCabecalho

    /**
     * rodapeDACTE
     * Monta o rodape no final da DACTE ( retrato e paisagem )
     *
     * @param number $xInic Posição horizontal canto esquerdo
     * @param number $yFinal Posição vertical final para impressão
     */
    protected function zRodape($x, $y)
    {
        $texto = "Impresso em  " . date('d/m/Y   H:i:s');
        $w = $this->wPrint - 4;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x-1, $y+2, $w, 4, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->nomeDesenvolvedor . ' - '. $this->siteDesenvolvedor;
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x-50, $y+2, $w, 4, $texto, $aFont, 'T', 'R', 0, $this->siteDesenvolvedor);
    } //fim zRodape

    /**
     * zTomador
     * Monta o campo com os dados do remetente na DACTE. ( retrato  e paisagem  )
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zTomador($x = 0, $y = 0)
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $aFont = $this->formatNegrito;
        $texto = $this->pSimpleGetValue($this->toma, "xNome");
        $this->pTextBox($x + 29, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $maxW * 0.60;
        $texto = 'MUNICÍPIO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->toma, "xMun");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x + 15, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $maxW * 0.85;
        $texto = 'UF';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->toma, "UF");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x + 4, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w - 18;
        $texto = 'CEP';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pFormat($this->pSimpleGetValue($this->toma, "CEP"), "#####-###");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x + 6, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $y += 3;
        $x = $oldX;
        $texto = 'ENDEREÇO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $aFont = $this->formatNegrito;
        $texto = $this->pSimpleGetValue($this->toma, "xLgr") . ',';
        $texto .= $this->pSimpleGetValue($this->toma, "nro");
        $texto .= ($this->pSimpleGetValue($this->toma, "xCpl") != "") ?
            ' - ' . $this->pSimpleGetValue($this->toma, "xCpl") : '';
        $texto .= ' - ' . $this->pSimpleGetValue($this->toma, "xBairro");
        $this->pTextBox($x + 16, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $y += 3;
        $texto = 'CNPJ/CPF';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->zFormatCNPJCPF($this->toma);
        $aFont = $this->formatNegrito;
        $this->pTextBox($x + 13, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $x + 65;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->toma, "IE");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x + 28, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.75;
        $texto = 'PAÍS';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->toma, "xPais") != "" ?
            $this->pSimpleGetValue($this->toma, "xPais") : 'BRASIL';
        $aFont = $this->formatNegrito;
        $this->pTextBox($x + 6, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w - 27;
        $texto = 'FONE';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->toma, "fone")!=""? $this->zFormatFone($this->toma):'';
        $aFont = $this->formatNegrito;
        $this->pTextBox($x + 8, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
    } //fim da função tomadorDACTE

    /**
     * zCompValorServ
     * Monta o campo com os componentes da prestação de serviços.
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zCompValorServ($x = 0, $y = 0)
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y);
        $texto = 'NOME';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yIniDados = $y;
        $x = $w * 0.14;
        $texto = 'VALOR';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.28;
        $this->pdf->Line($x, $y, $x, $y + 21.5);
        $texto = 'NOME';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.42;
        $texto = 'VALOR';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.56;
        $this->pdf->Line($x, $y, $x, $y + 21.5);
        $texto = 'NOME';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.70;
        $texto = 'VALOR';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x = $w * 0.86;
        $this->pdf->Line($x, $y, $x, $y + 21.5);
        $y += 1;
        $texto = 'VALOR TOTAL DO SERVIÇO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = number_format($this->pSimpleGetValue($this->vPrest, "vTPrest"), 2, ",", ".");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 9,
            'style' => 'B');
        $this->pTextBox($x, $y + 4, $w * 0.14, $h, $texto, $aFont, 'T', 'C', 0, '');
        $y += 10;
        $this->pdf->Line($x, $y, $w + 1, $y);
        $y += 1;
        $texto = 'VALOR A RECEBER';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = number_format($this->pSimpleGetValue($this->vPrest, "vRec"), 2, ",", ".");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 9,
            'style' => 'B');
        $this->pTextBox($x, $y + 4, $w * 0.14, $h, $texto, $aFont, 'T', 'C', 0, '');
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
            $this->pTextBox($auxX, $yIniDados, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
            $auxX += $w * 0.14;
            $texto = $valor;
            $aFont = $this->formatPadrao;
            $this->pTextBox($auxX, $yIniDados, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
            $auxX += $w * 0.14;
        }
    } //fim da função compValorDACTE

    /**
     * zImpostos
     * Monta o campo com os dados do remetente na DACTE. ( retrato  e paisagem  )
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zImpostos($x = 0, $y = 0)
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');

        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y);
        $texto = 'SITUAÇÃO TRIBUTÁRIA';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * 0.26;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = 'BASE DE CALCULO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');

        $wCol02=0.18;
        $x += $w * $wCol02;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = 'ALÍQ ICMS';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * $wCol02;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = 'VALOR ICMS';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * $wCol02;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = '% RED. BC ICMS';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        /*$x += $w * 0.14;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = 'ICMS ST';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');
         * */

        $x = $oldX;
        $y = $y + 4;
        $texto = $this->pSimpleGetValue($this->ICMS, "CST");
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
        $texto = $this->pSimpleGetValue($this->ICMSSN, "indSN") == 1 ? 'Simples Nacional' : $texto;
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.26;

        $texto = !empty($this->ICMS->getElementsByTagName("vBC")->item(0)->nodeValue) ?
            number_format($this->pSimpleGetValue($this->ICMS, "vBC"), 2, ",", ".") : '';
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = !empty($this->ICMS->getElementsByTagName("pICMS")->item(0)->nodeValue) ?
            number_format($this->pSimpleGetValue($this->ICMS, "pICMS"), 2, ",", ".") : '';
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = !empty($this->ICMS->getElementsByTagName("vICMS")->item(0)->nodeValue) ?
            number_format($this->pSimpleGetValue($this->ICMS, "vICMS"), 2, ",", ".") : '';
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = !empty($this->ICMS->getElementsByTagName("pRedBC")->item(0)->nodeValue) ?
            number_format($this->pSimpleGetValue($this->ICMS, "pRedBC"), 2, ",", ".").'%' :'';
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        /*$x += $w * 0.14;
        $texto = '';
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');*/
    } //fim da função compValorDACTE

    /**
     * zGeraChaveAdicCont
     *
     * @return string chave
     */
    protected function zGeraChaveAdicCont()
    {
        //cUF tpEmis CNPJ vNF ICMSp ICMSs DD  DV
        // Quantidade de caracteres  02   01      14  14    01    01  02 01
        $forma = "%02d%d%s%014d%01d%01d%02d";
        $cUF = $this->ide->getElementsByTagName('cUF')->item(0)->nodeValue;
        $CNPJ = "00000000000000" . $this->emit->getElementsByTagName('CNPJ')->item(0)->nodeValue;
        $CNPJ = substr($CNPJ, -14);
        $vCT = number_format($this->pSimpleGetValue($this->vPrest, "vRec"), 2, "", "") * 100;
        $ICMS_CST = $this->pSimpleGetValue($this->ICMS, "CST");
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
    } //fim zGeraChaveAdicCont

    /**
     * zInfPrestacaoServico
     * Monta o campo com das informações da prestação do serviço
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zInfPrestacaoServico($x = 0, $y = 0)
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $descr1 = 'QUANTIDADE';
        $descr2 = 'DESCRIÇÃO DO SERVIÇO PRESTADO';

        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y); // LINHA ABAIXO DO TEXTO: "DOCUMENTOS ORIGINÁRIOS
        $texto = $descr1;
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yIniDados = $y;

        $x += $w * 0.14;
        if ($this->modal == '1') {
            if ($this->lota == 1) {
                $this->pdf->Line($x, $y, $x, $y + 31.5); // TESTE
            } else {
                $this->pdf->Line($x, $y, $x, $y + 49.5); // TESTE
            }
        } elseif ($this->modal == '3') {
            $this->pdf->Line($x, $y, $x, $y + 34.1);
        } else {
            $this->pdf->Line($x, $y, $x, $y + 21.5);
        }

        $x += $w * 0.08;
        $texto = $descr2;
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');

        //$auxX = $oldX;
        //$yIniDados += 3;

        $x = $oldX;
        $y = $y + 4;
        $texto = number_format($this->pSimpleGetValue($this->infQ->item(0), "qCarga"), 3, ",", ".");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.26;

        $x = $oldX + 35;
        $texto = $this->pSimpleGetValue($this->infServico->item(0), "xDescServ");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.26;


        $r = $this->zCabecalho(1, 1, '1', $this->totPag);
        $contador = 0;
    } //fim da função zInfPrestacaoServico

    /**
     * zDocCompl
     * Monta o campo com os dados do remetente na DACTE.
     *
     * @param number $x Posição horizontal canto esquerdo
     * @param number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zDocCompl($x = 0, $y = 0)
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $descr1 = 'CHAVE DO CT-E COMPLEMENTADO';
        $descr2 = 'VALOR COMPLEMENTADO';
        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y);
        $texto = $descr1;
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yIniDados = $y;
        $x += $w * 0.37;
        $texto = $descr2;
        $aFont = $this->formatPadrao;
        $this->pTextBox($x - 8, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.13;
        $this->pdf->Line($x, $y, $x, $y + 76.5);
        $texto = $descr1;
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.3;
        $texto = $descr2;
        $aFont = $this->formatPadrao;
        $this->pTextBox($x + 8, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
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
        $this->pTextBox($auxX, $yIniDados, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = number_format($this->pSimpleGetValue($this->vPrest, "vTPrest"), 2, ",", ".");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 8,
            'style' => '');
        $this->pTextBox($w * 0.40, $yIniDados, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
    } //fim da função zDocCompl

    /**
     * zObs
     * Monta o campo com os dados do remetente na DACTE.
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zObs($x = 0, $y = 0)
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y);
        $auxX = $oldX;
        $yIniDados = $y;
        $texto = '';
        foreach ($this->compl as $k => $d) {
            $xObs = $this->pSimpleGetValue($this->compl->item($k), "xObs");
            $texto .= $xObs;
        }
        $textoObs = explode("Motorista:", $texto);
        $textoObs[1] = isset($textoObs[1]) ? "Motorista: ".$textoObs[1]: '';
        $texto .= $this->pSimpleGetValue($this->imp, "infAdFisco", "\r\n");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7.5,
            'style' => '');
        $this->pTextBox($x, $y, $w, $h, $textoObs[0], $aFont, 'T', 'L', 0, '', false);
        $this->pTextBox($x, $y+11.5, $w, $h, $textoObs[1], $aFont, 'T', 'L', 0, '', false);
    } //fim da função obsDACTE

    /**
     * zSeguro
     * Monta o campo com os dados de seguro do CT-e OS.
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zSeguro($x = 0, $y = 0)
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');

        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y);
        $texto = 'RESPONSÁVEL';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.33, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * 0.33;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = 'NOME DA SEGURADORA';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.33, $h, $texto, $aFont, 'T', 'L', 0, '');

        $wCol02=0.33;
        $x += $w * $wCol02;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = 'NÚMERO DA APÓLICE';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x = $oldX;
        $y = $y + 4;
        $texto = $this->respSeg;
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.26;

        $texto = '';
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = '';
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;
    } //fim da função zSeguro

    /**
     * zModalRod
     * Monta o campo com os dados do remetente na DACTE. ( retrato  e paisagem  )
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zModalRod($x = 0, $y = 0)
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');

        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y);
        $texto = 'TERMO DE AUTORIZAÇÃO DE FRETAMENTO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * 0.26;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = 'Nº DE REGISTRO ESTADUAL';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');

        $wCol02=0.18;
        $x += $w * $wCol02;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = 'PLACA DO VEÍCULO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * $wCol02;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = 'RENAVAM DO VEÍCULO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x += $w * $wCol02;
        $this->pdf->Line($x, $y, $x, $y + 9.5);
        $texto = 'CNPJ/CPF';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        $x = $oldX;
        $y = $y + 4;
        $texto = $this->pSimpleGetValue($this->rodo, "TAF");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * 0.26, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.26;

        $texto = $this->pSimpleGetValue($this->rodo, "NroRegEstadual");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = $this->pSimpleGetValue($this->veic->item(0), "placa");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = $this->pSimpleGetValue($this->veic->item(0), "RENAVAM");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * $wCol02;

        $texto = !empty($this->pSimpleGetValue($this->veic->item(0), "CPF")) ?
            $this->pSimpleGetValue($this->veic->item(0), "CPF") :
            (!empty($this->pSimpleGetValue($this->veic->item(0), "CNPJ")) ?
            $this->pSimpleGetValue($this->veic->item(0), "CNPJ") : '');
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * $wCol02, $h, $texto, $aFont, 'T', 'L', 0, '');

        /*$x += $w * 0.14;
        $texto = '';
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y, $w * 0.14, $h, $texto, $aFont, 'T', 'L', 0, '');*/
    } //fim da função zModalRod

    /**
     * zModalAquaviario
     * Monta o campo com os dados do remetente na DACTE. ( retrato  e paisagem  )
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zModalAquaviario($x = 0, $y = 0)
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
        $this->pTextBox($x, $y, $w, $h * 3.2, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y);
        $texto = 'PORTO DE EMBARQUE';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->aquav, "prtEmb");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y + 3, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.50;
        $this->pdf->Line($x, $y, $x, $y + 7.7);
        $texto = 'PORTO DE DESTINO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->aquav, "prtDest");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $y += 8;
        $this->pdf->Line(208, $y, 1, $y);
        $x = 1;
        $texto = 'IDENTIFICAÇÃO DO NAVIO / REBOCADOR';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->aquav, "xNavio");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y + 3, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.50;
        $this->pdf->Line($x, $y, $x, $y + 7.7);
        $texto = 'VR DA B. DE CALC. AFRMM';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->aquav, "vPrest");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.17;
        $this->pdf->Line($x, $y, $x, $y + 7.7);
        $texto = 'VALOR DO AFRMM';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->aquav, "vAFRMM");
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.12;
        $this->pdf->Line($x, $y, $x, $y + 7.7);
        $texto = 'TIPO DE NAVEGAÇÃO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->aquav, "tpNav");
        switch ($texto) {
            case '0':
                $texto = 'INTERIOR';
                break;
            case '1':
                $texto = 'CABOTAGEM';
                break;
        }
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.14;
        $this->pdf->Line($x, $y, $x, $y + 7.7);
        $texto = 'DIREÇÃO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->aquav, "direc");
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
        $this->pTextBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $y += 8;
        $this->pdf->Line(208, $y, 1, $y);
        $x = 1;
        $texto = 'IDENTIFICAÇÃO DOS CONTEINERS';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
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
        $this->pTextBox($x, $y + 3, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.50;
        $this->pdf->Line($x, $y, $x, $y + 7.7);
        $texto = 'IDENTIFICAÇÃO DAS BALSAS';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w * 0.23, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = '';
        if ($this->pSimpleGetValue($this->aquav, "balsa") !== '') {
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
        $this->pTextBox($x, $y + 3, $w * 0.50, $h, $texto, $aFont, 'T', 'L', 0, '');
    } //fim da função zModalRod

    /**
     * zModalFerr
     * Monta o campo com os dados do remetente na DACTE. ( retrato  e paisagem  )
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zModalFerr($x = 0, $y = 0)
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y);
        $texto = 'DCL';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => 'B');
        $this->pTextBox($x, $y, $w * 0.25, $h, $texto, $aFont, 'T', 'C', 0, '');
        $this->pdf->Line($x + 49.6, $y, $x + 49.6, $y + 3.5);
        $texto = 'VAGÕES';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => 'B');
        $this->pTextBox($x + 50, $y, $w * 0.5, $h, $texto, $aFont, 'T', 'C', 0, '');
        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y);
        // DCL
        $texto = 'ID TREM';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "idTrem");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.06;
        $y1 = $y + 12.5;
        $this->pdf->Line($x, $y, $x, $y1);
        $texto = 'NUM';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->rem, "nDoc");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.06;
        $this->pdf->Line($x, $y, $x, $y1);
        $texto = 'SÉRIE';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->rem, "serie");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.06;
        $this->pdf->Line($x, $y, $x, $y1);
        $texto = 'EMISSÃO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pYmd2dmy($this->pSimpleGetValue($this->rem, "dEmi"));
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        // VAGOES
        $x += $w * 0.06;
        $this->pdf->Line($x, $y, $x, $y1);
        $texto = 'NUM';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "nVag");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.06;
        $this->pdf->Line($x, $y, $x, $y1);
        $texto = 'TIPO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "tpVag");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.06;
        $this->pdf->Line($x, $y, $x, $y1);
        $texto = 'CAPACIDADE';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "cap");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.08;
        $this->pdf->Line($x, $y, $x, $y1);
        $texto = 'PESO REAL/TON';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "pesoR");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.09;
        $this->pdf->Line($x, $y, $x, $y1);
        $texto = 'PESO BRUTO/TON';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "pesoBC");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w * 0.10, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.1;
        $this->pdf->Line($x, $y, $x, $y1);
        $texto = 'IDENTIFICAÇÃO DOS CONTÊINERES';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "nCont");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        // FLUXO
        $x = 1;
        $y += 12.9;
        $h1 = $h * 0.5 + 0.27;
        $wa = round($w * 0.103) + 0.5;
        $texto = 'FLUXO FERROVIARIO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $wa, $h1, $texto, $aFont, 'T', 'C', 1, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "fluxo");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $wa, $h1, $texto, $aFont, 'T', 'C', 0, '');
        $y += 10;
        $texto = 'TIPO DE TRÁFEGO';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $wa, $h1, $texto, $aFont, 'T', 'C', 1, '');
        $texto = $this->zConvertUnidTrafego($this->pSimpleGetValue($this->ferrov, "tpTraf"));
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 7,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $wa, $h1, $texto, $aFont, 'T', 'C', 0, '');
        // Novo Box Relativo a Modal Ferroviário
        $x = 22.5;
        $y += -10.2;
        $texto = 'INFORMAÇÕES DAS FERROVIAS ENVOLVIDAS';
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y, $w - 21.5, $h1 * 2.019, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y);
        $w = $w * 0.2;
        $h = $h * 1.04;
        $texto = 'CÓDIGO INTERNO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "cInt");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = 'CNPJ';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y + 6, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "CNPJ");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 9, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += 50;
        $texto = 'NOME';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "xNome");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 3, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = 'INSCRICAO ESTADUAL';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y + 6, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->pSimpleGetValue($this->ferrov, "IE");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 9, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += 50;
        $texto = 'PARTICIPAÇÃO OUTRA FERROVIA';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y + 6, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = '';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => 'B');
        $this->pTextBox($x, $y + 9, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
    } //fim da função zModalFerr

    /**
     * zCanhoto
     * Monta o campo com os dados do remetente na DACTE.
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function zCanhoto($x = 0, $y = 0)
    {
        $this->zhDashedLine($x, $y+2, $this->wPrint, 0.1, 80);
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        $y += 3.4;
        $this->pdf->Line($x, $y, $w + 1, $y); // LINHA ABAICO DO TEXTO DECLARO QUE RECEBI...

        $texto = 'NOME';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.25, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.25;

        $this->pdf->Line($x, $y, $x, $y + 16.5);

        $texto = 'ASSINATURA / CARIMBO';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y, $w * 0.25, $h - 3.4, $texto, $aFont, 'B', 'C', 0, '');
        $x += $w * 0.25;

        $this->pdf->Line($x, $y, $x, $y + 16.5);

        $texto = 'TÉRMINO DA PRESTAÇÃO - DATA/HORA' . "\r\n" . "\r\n" . "\r\n". "\r\n";
        $texto .= ' INÍCIO DA PRESTAÇÃO - DATA/HORA';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x + 10, $y, $w * 0.25, $h - 3.4, $texto, $aFont, 'T', 'C', 0, '');
        $x = $oldX;
        $y = $y + 5;

        $this->pdf->Line($x, $y+3, $w * 0.255, $y+3); // LINHA HORIZONTAL ACIMA DO RG ABAIXO DO NOME

        $texto = 'RG';
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y+3, $w * 0.33, $h, $texto, $aFont, 'T', 'L', 0, '');
        $x += $w * 0.85;

        $this->pdf->Line($x, $y + 11.5, $x, $y - 5); // LINHA VERTICAL PROXIMO AO CT-E

        $texto = "CT-E OS";
        $aFont = $this->formatNegrito;
        $this->pTextBox($x, $y - 5, $w * 0.15, $h, $texto, $aFont, 'T', 'C', 0, '');
        $texto = "\r\n Nº. DOCUMENTO  " . $this->pSimpleGetValue($this->ide, "nCT") . " \n";
        $texto .= "\r\n SÉRIE  " . $this->pSimpleGetValue($this->ide, "serie");
        $aFont = array(
            'font' => $this->fontePadrao,
            'size' => 6,
            'style' => '');
        $this->pTextBox($x, $y - 8, $w * 0.15, $h, $texto, $aFont, 'C', 'C', 0, '');
        $x = $oldX;
        //$this->zhDashedLine($x, $y + 7.5, $this->wPrint, 0.1, 80);
    } //fim da função canhotoDACTE

    /**
     * zDadosAdic
     * Coloca o grupo de dados adicionais da DACTE.
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @param  number $h altura do campo
     * @return number Posição vertical final
     */
    protected function zDadosAdic($x, $y, $pag, $h)
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        //$this->pdf->Line($x, $y + 3, $w * 1.385, $y + 3);
        $this->pdf->Line($x, $y + 3, $w * 1.385, $y + 3);
        //o texto com os dados adicionais foi obtido na função xxxxxx
        //e carregado em uma propriedade privada da classe
        //$this->wAdic com a largura do campo
        //$this->textoAdic com o texto completo do campo
        $y += 1;
        $aFont = $this->formatPadrao;
        $this->pTextBox($x, $y + 3, $w - 2, $h - 3, $this->textoAdic, $aFont, 'T', 'L', 0, '', false);
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
        $this->pTextBox($x, $y, $w, $h, $texto, $aFont, 'T', 'C', 1, '');
        //inserir texto informando caso de contingência
        //1 – Normal – emissão normal;
        //2 – Contingência FS – emissão em contingência com impressão do DACTE em Formulário de Segurança;
        //3 – Contingência SCAN – emissão em contingência  – SCAN;
        //4 – Contingência DPEC - emissão em contingência com envio da Declaração Prévia de
        //Emissão em Contingência – DPEC;
        //5 – Contingência FS-DA - emissão em contingência com impressão do DACTE em Formulário de
        //Segurança para Impressão de Documento Auxiliar de Documento Fiscal Eletrônico (FS-DA).
        $xJust = $this->pSimpleGetValue($this->ide, 'xJust', 'Justificativa: ');
        $dhCont = $this->pSimpleGetValue($this->ide, 'dhCont', ' Entrada em contingência : ');
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
        $this->pTextBox($x, $y + 2, $w - 2, $h - 3, $texto, $aFont, 'T', 'L', 0, '', false);
        return $y + $h;
    } //fim zDadosAdic

    /**
     * zhDashedLine
     * Desenha uma linha horizontal tracejada com o FPDF
     *
     * @param  number $x Posição horizontal inicial, em mm
     * @param  number $y Posição vertical inicial, em mm
     * @param  number $w Comprimento da linha, em mm
     * @param  number $h Espessura da linha, em mm
     * @param  number $n Numero de traços na seção da linha com o comprimento $w
     * @return none
     */
    protected function zhDashedLine($x, $y, $w, $h, $n)
    {
        $this->pdf->SetLineWidth($h);
        $wDash = ($w / $n) / 2; // comprimento dos traços
        for ($i = $x; $i <= $x + $w; $i += $wDash + $wDash) {
            for ($j = $i; $j <= ($i + $wDash); $j++) {
                if ($j <= ($x + $w - 1)) {
                    $this->pdf->Line($j, $y, $j + 1, $y);
                }
            }
        }
    } //fim função hDashedLine

    /**
     * zhDashedVerticalLine
     * Desenha uma linha vertical tracejada com o FPDF
     *
     * @param  number $x Posição horizontal inicial, em mm
     * @param  number $y Posição vertical inicial, em mm
     * @param  number $w Comprimento da linha, em mm
     * @param  number $yfinal Espessura da linha, em mm
     * @param  number $n Numero de traços na seção da linha com o comprimento $w
     * @return none
     */
    protected function zhDashedVerticalLine($x, $y, $w, $yfinal, $n)
    {
        $this->pdf->SetLineWidth($w);
        /* Organizando valores */
        if ($y > $yfinal) {
            $aux = $yfinal;
            $yfinal = $y;
            $y = $aux;
        }
        while ($y < $yfinal && $n > 0) {
            $this->pdf->Line($x, $y, $x, $y + 1);
            $y += 3;
            $n--;
        }
    } //fim função hDashedVerticalLine

    /**
     * zFormatCNPJCPF
     * Formata campo CnpjCpf contida na CTe
     *
     * @param  string $field campo cnpjCpf da CT-e
     * @return string
     */
    protected function zFormatCNPJCPF($field)
    {
        if (!isset($field)) {
            return '';
        }
        $cnpj = !empty($field->getElementsByTagName("CNPJ")->item(0)->nodeValue) ?
            $field->getElementsByTagName("CNPJ")->item(0)->nodeValue : "";
        if ($cnpj != "" && $cnpj != "00000000000000") {
            $cnpj = $this->pFormat($cnpj, '###.###.###/####-##');
        } else {
            $cnpj = !empty($field->getElementsByTagName("CPF")->item(0)->nodeValue) ?
                $this->pFormat($field->getElementsByTagName("CPF")->item(0)->nodeValue, '###.###.###.###-##') : '';
        }
        return $cnpj;
    } //fim formatCNPJCPF

    /**
     * zFormatFone
     * Formata campo fone contida na CTe
     *
     * @param  string $field campo fone da CT-e
     * @return string
     */
    protected function zFormatFone($field)
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
    } //fim formatFone

    /**
     * zUnidade
     * Converte a imformação de peso contida na CTe
     *
     * @param  string $c unidade de trafego extraida da CTe
     * @return string
     */
    protected function zUnidade($c = '')
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
    } //fim unidade

    /**
     * zConvertUnidTrafego
     * Converte a imformação de peso contida na CTe
     *
     * @param  string $U Informação de trafego extraida da CTe
     * @return string
     */
    protected function zConvertUnidTrafego($U = '')
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
    } //fim da função zConvertUnidTrafego

    /**
     * zMultiUniPeso
     * Fornece a imformação multiplicação de peso contida na CTe
     *
     * @param  interger $U Informação de peso extraida da CTe
     * @return interger
     */
    protected function zMultiUniPeso($U = '')
    {
        if ($U === "01") {
            // tonelada
            //return 1000;
            return 1;
        }
        return 1; // M3, KG, Unidade, litros, mmbtu
    } //fim da função zMultiUniPeso
}
