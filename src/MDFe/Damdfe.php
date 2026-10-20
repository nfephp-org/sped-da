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
use NFePHP\DA\Common\DaCommon;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\Common\Keys;

class Damdfe extends DaCommon
{

    protected $xml; // string XML MDFe
    protected $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";
    protected $margemInterna = 2;
    protected $id;
    protected $chMDFe;
    protected $tpAmb;
    protected $ide;
    protected $dhEvento;
    protected $cStat;
    protected $mdfeProc;
    protected $nProt;
    protected $tpEmis;
    protected $qrCodMDFe;
    protected $baseFont = array('font' => 'arial', 'size' => 8, 'style' => '');
    protected $infMDFe;
    protected $emit;
    protected $CPF;
    protected $CNPJ;
    protected $IE;
    protected $xNome;
    protected $enderEmit;
    protected $xLgr;
    protected $nro;
    protected $xBairro;
    protected $UF;
    protected $xMun;
    protected $CEP;
    protected $mod;
    protected $serie;
    protected $dhEmi;
    protected $UFIni;
    protected $UFFim;
    protected $nMDF;
    protected $tot;
    protected $qMDFe;
    protected $qNFe;
    protected $qNF;
    protected $qCTe;
    protected $qCT;
    protected $qCarga;
    protected $cUnid;
    protected $infModal;
    protected $rodo;
    protected $aereo;
    protected $aquav;
    protected $ferrov;
    protected $RNTRC;
    protected $infCIOT;
    protected $veicTracao;
    protected $veicReboque;
    protected $valePed;
    protected $infCpl;
    protected $seg;
    protected $infAdFisco;
    protected $dhRecbto;
    protected $condutor;
    protected $infPercurso;

    /**
     * @var string
     */
    protected $logoAlign = 'L';
    private $dom;

    protected $flagDocs = false;
    protected $chaves = [];
    protected $quantidadeChavesLayout = 20;

    /**
     * Define se vai ou não exibir as chaves de CT-e, NF-e e MDF-e vinculadas a essa MDF-e
     */
    protected bool $exibirDocumentosVinculados = true;

    /* registros que não couberam na folha e seguem no quadro de continuação */
    protected $valePedRestantes = array();
    protected $condutoresRestantes = array();
    protected $reboquesRestantes = array();
    protected $segurosRestantes = array();
    /* posição vertical em que o bloco de condutores termina, na metade direita da folha */
    protected $yFimCondutores = 0;

    /**
     * __construct
     *
     * @param string $xml Arquivo XML da MDFe
     */
    public function __construct(
        $xml
    )
    {
        $this->loadDoc($xml);
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
            if (!empty($this->rodo)) {
                $this->RNTRC = "";
                $infANTT = $this->rodo->getElementsByTagName("infANTT")->item(0);
                if (isset($infANTT)) {
                    if (isset($infANTT->getElementsByTagName("RNTRC")->item(0)->nodeValue)) {
                        $this->RNTRC = $infANTT->getElementsByTagName("RNTRC")->item(0)->nodeValue;
                    }
                }
            }
            $this->infCIOT = [];
            if ($this->dom->getElementsByTagName('infCIOT')->item(0) != "") {
                $this->infCIOT = $this->dom->getElementsByTagName('infCIOT');
            }
            $this->veicTracao = $this->dom->getElementsByTagName("veicTracao")->item(0);
            $this->veicReboque = $this->dom->getElementsByTagName("veicReboque");
            $this->valePed = "";
            if ($this->dom->getElementsByTagName("valePed")->item(0) != "") {
                $this->valePed = $this->dom->getElementsByTagName("valePed")->item(0)->getElementsByTagName("disp");
            }
            $this->seg = "";
            if ($this->dom->getElementsByTagName("seg")->item(0) != "") {
                $this->seg = $this->dom->getElementsByTagName("seg");
            }
            $this->infCpl = ($infCpl = $this->dom->getElementsByTagName('infCpl')->item(0)) ? $infCpl->nodeValue : "";
            $this->infAdFisco = ($infAdFisco = $this->dom->getElementsByTagName('infAdFisco')->item(0)) ? $infAdFisco->nodeValue : "";
            $this->chMDFe = Keys::extractAccessKey($this->infMDFe->getAttribute("Id"));
            $this->qrCodMDFe = $this->dom->getElementsByTagName('qrCodMDFe')->item(0) ?
                $this->dom->getElementsByTagName('qrCodMDFe')->item(0)->nodeValue : 'SEM INFORMAÇÃO DE QRCODE';
            if (is_object($this->mdfeProc)) {
                $this->nProt = !empty($this->mdfeProc->getElementsByTagName("nProt")->item(0)->nodeValue) ?
                    $this->mdfeProc->getElementsByTagName("nProt")->item(0)->nodeValue : '';
                $this->dhRecbto = $this->mdfeProc->getElementsByTagName("dhRecbto")->item(0)->nodeValue;
            }
            $this->infPercurso = $this->dom->getElementsByTagName("infPercurso");
        }
    }

    protected function monta(
        $logo = ''
    )
    {
        $this->pdf = '';
        if (!empty($logo)) {
            $this->logomarca = $this->adjustImage($logo);
        }
        //pega o orientação do documento
        if (empty($this->orientacao)) {
            $this->orientacao = 'P';
        }
        $this->buildMDFe();
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
        $x = $xInic;
        $y = $yInic;
        //coloca o cabeçalho Paisagem
        if ($this->orientacao == 'P') {
            $y = $this->headerMDFeRetrato($x, $y);
        } else {
            $y = $this->headerMDFePaisagem($x, $y);
        }
        //coloca os dados da MDFe
        $y = $this->bodyMDFe($x, $y);
        //coloca os dados da MDFe
        $this->footerMDFe($x, $y);

        /* os quadros que não couberam seguem em folhas de continuação, na ordem em que
           aparecem no corpo do documento */
        $this->reboquesContinuacao();
        $this->valePedContinuacao();
        $this->condutoresContinuacao();
        $this->seguroContinuacao();

        if ($this->flagDocs && $this->exibirDocumentosVinculados) {
            $this->addPage();
        }
    }

    /**
     * headerMDFePaisagem
     *
     * @param float $x
     * @param float $y
     * @return string
     */
    private function headerMDFePaisagem($x, $y)
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
        $this->pdf->textBox($x, $y, $w, $h, '', $this->baseFont, 'T', 'L', 0);
        if (!empty($this->logomarca)) {
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
                $yImg = $y + 4;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            }
            if ($this->logoAlign == 'R') {
                $nImgW = round((round($maxW * 0.50, 0)) / 3, 0);
                $nImgH = round(($h - $y) - 2, 0) + $y;
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
        if ($this->qrCodMDFe !== null) {
            $this->qrCodeDamdfe($y - 3);
        }
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $texto = $this->xNome;
        $this->pdf->textBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'L', 0, '');
        if (isset($this->CPF)) {
            $cpfcnpj = 'CPF: ' . $this->formatField($this->CPF, "###.###.###-##");
        } else {
            $cpfcnpj = 'CNPJ: ' . $this->formatField($this->CNPJ, "##.###.###/####-##");
        }
        $ie = 'IE: ' . (strlen($this->IE) == 9
                ? $this->formatField($this->IE, '###/#######')
                : $this->formatField($this->IE, '###.###.###.###'));
        $rntrc = empty($this->RNTRC) ? '' : ' - RNTRC: ' . $this->RNTRC;
        $lgr = 'Logradouro: ' . $this->xLgr;
        $nro = 'Nº: ' . $this->nro;
        $bairro = 'Bairro: ' . $this->xBairro;
        $CEP = $this->CEP;
        $CEP = 'CEP: ' . $this->formatField($CEP, "##.###-###");
        $UF = 'UF: ' . $this->UF;
        $mun = 'Municipio: ' . $this->xMun;

        $texto = $cpfcnpj . ' - ' . $ie . $rntrc . "\n";
        $texto .= $lgr . ' - ' . $nro . "\n";
        $texto .= $bairro . "\n";
        $texto .= $UF . ' - ' . $mun . ' - ' . $CEP;
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $this->pdf->textBox($x1, $y1 + 6, $tw, 8, $texto, $aFont, 'T', 'L', 0, '');
        //##################################################
        $w = round($maxW * 0.70, 0);
        $y = $h + 9;
        $this->pdf->textBox($x, $y, $w, 6, '', $this->baseFont, 'T', 'L', 0);
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
        $resp = $this->statusMDFe();
        if (!$resp['status']) {
            $n = count($resp['message']);
            $alttot = $n * 15;
            $x = 10;
            $y = $this->hPrint / 2 - ($alttot - 80) / 2;
            $h = 15;
            $w = $maxW - (2 * $x);
            $this->pdf->settextcolor(200, 200, 200);
            foreach ($resp['message'] as $msg) {
                $aFont = ['font' => $this->fontePadrao, 'size' => 48, 'style' => 'B'];
                $this->pdf->textBox($x, $y, $w, $h, $msg, $aFont, 'C', 'C', 0, '');
                $y += $h;
            }
            $texto = $resp['submessage'];
            if (!empty($texto)) {
                $y += 3;
                $h = 5;
                $aFont = ['font' => $this->fontePadrao, 'size' => 20, 'style' => 'B'];
                $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                $y += $h;
            }
            if (!$resp['valida']) {
                $y += 5;
                $w = $maxW - (2 * $x);
                $texto = "SEM VALOR FISCAL";
                $aFont = ['font' => $this->fontePadrao, 'size' => 48, 'style' => 'B'];
                $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            }
            $this->pdf->settextcolor(0, 0, 0);
        }

        $y = $this->hPrint + 8;
        $x = $this->wPrint - 5;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'I'];
        $this->pdf->textBox($x, $y, 12, 8, 'Page ' . $this->pdf->PageNo() . '/{nb}', $aFont, 'T', 0, 0);

        return $oldY + 8;
    }

    /**
     * Verifica o status da MDFe
     *
     * @return array
     */
    protected function statusMDFe()
    {
        $resp = [
            'status' => true,
            'valida' => true,
            'message' => [],
            'submessage' => ''
        ];
        if (($this->tpEmis == 2 || $this->tpEmis == 5) and empty($this->nProt)) {
            $resp['status'] = false;
            $resp['message'][] = "MDF-e Emitido em Contingência";
            $resp['message'][] = "devido à problemas técnicos";
            return $resp;
        }
        if (!isset($this->mdfeProc)) {
            $resp['status'] = false;
            $resp['message'][] = 'MDFe NÃO PROTOCOLADA';
        } else {
            if ($this->getTagValue($this->ide, "tpAmb") == '2') {
                $resp['status'] = false;
                $resp['valida'] = false;
                $resp['message'][] = "MDFe EMITIDA EM HOMOLOGAÇÃO";
            }
            $retEvento = $this->mdfeProc->getElementsByTagName('retEventoMDFe')->item(0);
            $cStat = $this->getTagValue($this->mdfeProc, "cStat");
            $tpEvento = $this->getTagValue($this->mdfeProc, "tpEvento");
            if ($cStat == '101'
                || $cStat == '151'
                || $cStat == '135'
                || $cStat == '155'
                || $this->cancelFlag === true
            ) {
                $resp['status'] = false;
                $resp['valida'] = false;
                $resp['message'][] = "MDFe CANCELADA";
            } elseif (($cStat == '103'
                    || $cStat == '136'
                    || $cStat == '135'
                    || $cStat == '155'
                    || $tpEvento === '110112')
                and empty($retEvento)
            ) {
                $resp['status'] = false;
                $resp['message'][] = "MDFe ENCERRADA";
            } elseif (!empty($retEvento)) {
                $infEvento = $retEvento->getElementsByTagName('infEvento')->item(0);
                $cStat = $this->getTagValue($infEvento, "cStat");
                $tpEvento = $this->getTagValue($infEvento, "tpEvento");
                $dhEvento = date("d/m/Y H:i:s", $this->toTimestamp($this->getTagValue($infEvento, "dhRegEvento")));
                $nProt = $this->getTagValue($infEvento, "nProt");
                if ($tpEvento == '110111'
                    && ($cStat == '101'
                        || $cStat == '151'
                        || $cStat == '135'
                        || $cStat == '155'
                    )) {
                    $resp['status'] = false;
                    $resp['valida'] = false;
                    $resp['message'][] = "MDFe CANCELADA";
                    $resp['submessage'] = "{$dhEvento} - {$nProt}";
                } elseif ($tpEvento == '110112' && ($cStat == '136' || $cStat == '135' || $cStat == '155')) {
                    $resp['status'] = false;
                    $resp['message'][] = "MDFe ENCERRADA";
                    $resp['submessage'] = "{$dhEvento} - {$nProt}";
                }
            }
        }
        return $resp;
    }

    /**
     * headerMDFeRetrato
     *
     * @param float $x
     * @param float $y
     * @return string
     */
    private function headerMDFeRetrato($x, $y)
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
        $this->pdf->textBox($x, $y, $w, $h, '', $this->baseFont, 'T', 'L', 0);
        if (!empty($this->logomarca)) {
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
                $yImg = $y - 1;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            }
            if ($this->logoAlign == 'R') {
                $nImgW = round((round($maxW * 0.50, 0)) / 3, 0);
                $nImgH = round(($h - $y) - 2, 0) + $y;
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
            $cpfcnpj = 'CNPJ: ' . $this->formatField($this->CNPJ, "##.###.###/####-##");
        }
        $ie = 'IE: ' . (strlen($this->IE) == 9
                ? $this->formatField($this->IE, '###/#######')
                : $this->formatField($this->IE, '###.###.###.###'));
        $rntrc = empty($this->RNTRC) ? '' : ' - RNTRC: ' . $this->RNTRC;
        $lgr = 'Logradouro: ' . $this->xLgr;
        $nro = 'Nº: ' . $this->nro;
        $bairro = 'Bairro: ' . $this->xBairro;
        $CEP = $this->CEP;
        $CEP = 'CEP: ' . $this->formatField($CEP, "##.###-###");
        $mun = 'Municipio: ' . $this->xMun;
        $UF = 'UF: ' . $this->UF;
        $texto = $cpfcnpj . ' - ' . $ie . $rntrc . "\n";
        $texto .= $lgr . ' - ' . $nro . "\n";
        $texto .= $bairro . "\n";
        $texto .= $UF . ' - ' . $mun . ' - ' . $CEP;
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $this->pdf->textBox($x1, $y1 + 4, $tw, 8, $texto, $aFont, 'T', 'L', 0, '');
        //##################################################
        $w = round($maxW * 0.70, 0);
        $y = $h + 9;
        $this->pdf->textBox($x, $y, $w, 6, '', $this->baseFont, 'T', 'L', 0);
        $aFont = array('font' => $this->fontePadrao, 'size' => 12, 'style' => 'I');
        $this->pdf->textBox(
            $x,
            $y,
            $w,
            8,
            'DAMDFE - Documento Auxiliar de Manifesto Eletronico de Documentos Fiscais',
            $aFont,
            'T',
            'L',
            0,
            ''
        );
        $resp = $this->statusMDFe();
        if (!$resp['status']) {
            $n = count($resp['message']);
            $alttot = $n * 15;
            $x = 10;
            $y = $this->hPrint / 2 - ($alttot + 45) / 2;
            $h = 15;
            $w = $maxW - (2 * $x);
            $this->pdf->settextcolor(200, 200, 200);
            foreach ($resp['message'] as $msg) {
                $aFont = ['font' => $this->fontePadrao, 'size' => 48, 'style' => 'B'];
                $this->pdf->textBox($x, $y, $w, $h, $msg, $aFont, 'C', 'C', 0, '');
                $y += $h;
            }
            $texto = $resp['submessage'];
            if (!empty($texto)) {
                $y += 3;
                $h = 5;
                $aFont = ['font' => $this->fontePadrao, 'size' => 20, 'style' => 'B'];
                $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                $y += $h;
            }
            if (!$resp['valida']) {
                $y += 5;
                $w = $maxW - (2 * $x);
                $texto = "SEM VALOR FISCAL";
                $aFont = ['font' => $this->fontePadrao, 'size' => 48, 'style' => 'B'];
                $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            }
            $this->pdf->settextcolor(0, 0, 0);
        }

        $y = $this->hPrint + 8;
        $x = $this->wPrint - 5;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'I'];
        $this->pdf->textBox($x, $y, 12, 8, 'Page ' . $this->pdf->PageNo() . '/{nb}', $aFont, 'T', 0, 0);

        return $oldY + 8;
    }

    /**
     * bodyMDFe
     *
     * @param float $x
     * @param float $y
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
        $this->pdf->setFillColor(188, 224, 246);
        $this->pdf->settextcolor(0, 0, 0);
        $x2 = ($maxW / 6);
        $x1 = $x2;
        $this->pdf->textBox($x, $y, $x2 - 22, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);
        $texto = 'Modelo';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x, $y, $x2 - 22, 2, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $this->mod;
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
        $this->pdf->textBox($x, $y + 4, $x2 - 22, 4, $texto, $aFont, 'T', 'L', 0, '', false);

        if ($this->orientacao == 'P') {
            $x1 += $x2 - 47.5;
        } else {
            $x1 += $x2 - 57.5;
        }
        $this->pdf->textBox($x1, $y, $x2 - 22, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);
        $texto = 'Série';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 22, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $this->serie;
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
        $this->pdf->textBox($x1, $y + 4, $x2 - 22, 4, $texto, $aFont, 'T', 'L', 0, '', false);

        $x1 += $x2 - 22;
        $this->pdf->textBox($x1, $y, $x2 - 6, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);
        $texto = 'Número';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 6, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $this->formatField(str_pad($this->nMDF, 9, '0', STR_PAD_LEFT), '###.###.###');
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
        $this->pdf->textBox($x1, $y + 4, $x2 - 6, 4, $texto, $aFont, 'T', 'L', 0, '', false);
        $x1 += $x2 - 5;
        $this->pdf->textBox($x1, $y, $x2 - 23, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);
        $texto = 'FL';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 23, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = '1/1';
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
        $this->pdf->textBox($x1, $y + 4, $x2 - 23, 4, $texto, $aFont, 'T', 'L', 0, '', false);
        $x1 += $x2 - 22;
        if ($this->orientacao == 'P') {
            $x3 = $x2 + 10.5;
        } else {
            $x3 = $x2 + 3;
        }
        $this->pdf->textBox($x1, $y, $x3 - 1, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);
        $texto = 'Data e Hora de Emissão';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x3 - 1, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $data = explode('T', $this->dhEmi);
        $texto = $this->ymdTodmy($data[0]) . ' - ' . $data[1];
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x3 - 1, 4, $texto, $aFont, 'T', 'L', 0, '', false);
        $x1 += $x3;

        $this->pdf->textBox($x1, $y, $x2 - 16, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);
        $texto = 'UF Carreg.';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 16, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $this->UFIni;
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x2 - 16, 4, $texto, $aFont, 'T', 'L', 0, '', false);
        $maxW = $this->wPrint;

        $x1 += $x2 - 15;
        $this->pdf->textBox($x1, $y, $x2 - 16, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);
        $texto = 'UF Descar.';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 16, 4, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $this->UFFim;
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x2 - 16, 4, $texto, $aFont, 'T', 'L', 0, '', false);
        $maxW = $this->wPrint;

        $this->pdf->setFillColor(255, 255, 255);
        if ($this->aquav) {
            $x1 = $x;
            $x2 = $maxW;
            $y += 14;
            $this->pdf->textBox($x1, $y, $x2, 10, '', $this->baseFont, 'T', 'L', 0);
            $texto = 'Embarcação';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $texto = $this->aquav->getElementsByTagName('cEmbar')->item(0)->nodeValue;
            $texto .= ' - ';
            $texto .= $this->aquav->getElementsByTagName('xEmbar')->item(0)->nodeValue;
            $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
            $this->pdf->textBox($x1, $y + 4, $x2, 10, $texto, $aFont, 'T', 'L', 0, '', false);
        }

        $x1 = $x;
        $x2 = $maxW;
        $y += 13;
        $this->pdf->textBox($x1, $y, $x2, 43, '', $this->baseFont, 'T', 'L', 0);
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
        $x2 = ($maxW / 8);
        $y += 8;
        $this->pdf->setFillColor(235, 236, 238);
        $this->pdf->textBox($x1, $y, $x2 - 1, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);
        $texto = 'Qtd. CT-e';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 1, 10, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = str_pad($this->qCTe, 3, '0', STR_PAD_LEFT);
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x2 - 2, 4, $texto, $aFont, 'T', 'L', 0, '', false);
        $x1 += $x2;
        $this->pdf->textBox($x1, $y, $x2 - 1, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);
        $texto = 'Qtd. NF-e';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 1, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = str_pad($this->qNFe, 3, '0', STR_PAD_LEFT);
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x2 - 1, 4, $texto, $aFont, 'T', 'L', 0, '', false);
        $x1 += $x2;
        $this->pdf->textBox($x1, $y, $x2 - 1, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);
        $texto = 'Qtd. MDF-e';
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $this->pdf->textBox($x1, $y, $x2 - 1, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = str_pad($this->qMDFe, 3, '0', STR_PAD_LEFT);
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
        $this->pdf->textBox($x1, $y + 4, $x2 - 1, 4, $texto, $aFont, 'T', 'L', 0, '', false);
        $x1 += $x2;
        $this->pdf->textBox($x1, $y, $x2, 10, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);

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
            $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
            $this->pdf->textBox($x1, $y + 4, $x2, 4, $texto, $aFont, 'T', 'L', 0, '', false);
        }
        $this->pdf->setFillColor(255, 255, 255);

        // codigo de barras da chave
        $x1 += $x2;
        //$y = $y + 8;
        $this->pdf->textBox($x1, $y, $maxW / 2, 20, '', $this->baseFont, 'T', 'L', 0);
        $bH = 16;
        $w = $maxW;
        $this->pdf->setFillColor(0, 0, 0);
        $this->pdf->code128($x1 + 5, $y + 2, $this->chMDFe, ($maxW / 2) - 10, $bH);
        $this->pdf->setFillColor(255, 255, 255);

        $temPercursos = ($this->infPercurso->length > 0);
        if ($temPercursos) {
            $x1 = $x;
            $y = $y + 12;
            $texto = 'Percursos';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x1, $y, $x2, 4, $texto, $aFont, 'T', 'L', 0, '', false);

            $wp = ($maxW / 2);
            $y = $y + 5;
            $this->pdf->setFillColor(235, 236, 238);
            $this->pdf->textBox($x1, $y, $wp - 1, 5, '', $this->baseFont, 'T', 'L', 0, '', 0, 0, 0, 1);

            $percursos = [];
            foreach ($this->infPercurso as $per) {
                $percursos[] = $per->nodeValue;
            }
            $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
            $this->pdf->textBox($x1, $y + 0.5, $wp - 1, 4, implode(', ', $percursos), $aFont, 'T', 'L', 0, '', false);

            $y = $y + 7;
        } else {
            $y = $y + 24;
        }

        // protocolo de autorização
        $this->pdf->textBox($x, $y, $maxW / 2, 13, '', $this->baseFont, 'T', 'L', 0);
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $texto = 'Protocolo de Autorização';
        $this->pdf->textBox($x, $y, $maxW / 2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
        if (is_object($this->mdfeProc)) {
            $tsHora = $this->toTimestamp($this->dhRecbto);
            $texto = $this->nProt . ' - ' . date('d/m/Y H:i:s', $tsHora);
        } else {
            $texto = 'DAMDFE impresso em contingência - ' . date('d/m/Y   H:i:s');
        }
        $this->pdf->textBox($x, $y + 4, $maxW / 2, 4, $texto, $aFont, 'T', 'L', 0, '');

        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $this->pdf->textBox($x, $y + 8.5, $x2, 4, 'CIOT', $aFont, 'T', 'L', 0, '', false);
        $ciots = [];
        foreach ($this->infCIOT as $ciot) {
            $ciots[] = $ciot->getElementsByTagName('CIOT')->item(0)->nodeValue;
        }
        $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
        $this->pdf->textBox($x, $y + 11.5, $maxW / 2, 4, implode(', ', $ciots), $aFont, 'T', 'L', 0, '');

        $y -= 4;

        // chave de acesso
        $this->pdf->textBox($x + $maxW / 2, $y + 4, $maxW / 2, 17, '', $this->baseFont, 'T', 'L', 0);
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $texto = 'Chave de Acesso';
        $this->pdf->textBox($x + $maxW / 2, $y + 4, $maxW / 2, 6, $texto, $aFont, 'T', 'L', 0, '');
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
        $texto = $this->formatField($this->chMDFe, $this->formatoChave);
        $this->pdf->textBox($x + $maxW / 2, $y + 8, $maxW / 2, 6, $texto, $aFont, 'T', 'L', 0, '');
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $texto = 'Consulte em https://dfe-portal.svrs.rs.gov.br/MDFe/consulta';
        $this->pdf->textBox($x + $maxW / 2, $y + 12, $maxW / 2, 6, $texto, $aFont, 'T', 'L', 0, '');

        $x1 = $x;
        $y += 20;
        $yold = $y;
        $x2 = round($maxW / 2, 0);

        if ($this->rodo) {
            $texto = 'Veículo';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $y += 5;
            $x2 = round($maxW / 4, 0);
            /* o quadro do Veículo divide a folha com os quadros impressos abaixo dele, por
               isso a lista de reboques recebe um limite: o excedente segue para a folha de
               continuação, e a moldura acompanha só o que é impresso aqui */
            $this->reboquesRestantes = array();
            $totalReboques = $this->veicReboque->length;
            $cabemReboques = $this->linhasReboquesMDFe($y);
            if ($totalReboques > $cabemReboques) {
                for ($i = $cabemReboques; $i < $totalReboques; $i++) {
                    $this->reboquesRestantes[] = $this->veicReboque->item($i);
                }
                $totalReboques = $cabemReboques;
            }
            $tamanho = max(22, 8 + (($totalReboques + 1) * 4));
            $yQuadroVeiculo = $y;
            $this->pdf->textBox($x1, $y, $x2, $tamanho, '', $this->baseFont, 'T', 'L', 0);
            $texto = 'Placa';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $texto = $this->veicTracao->getElementsByTagName("placa")->item(0)->nodeValue;
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x1, $y + 4, $x2, 10, $texto, $aFont, 'T', 'L', 0, '', false);

            $altura = $y + 4;
            for ($i = 0; $i < $totalReboques; $i++) {
                $item = $this->veicReboque->item($i);
                $altura += 4;
                $texto = $item->getElementsByTagName('placa')->item(0)->nodeValue;
                $this->pdf->textBox($x1, $altura, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            }
            $x1 += $x2;
            $this->pdf->textBox($x1, $y, $x2, $tamanho, '', $this->baseFont, 'T', 'L', 0);
            $texto = 'RNTRC';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $prop = $this->veicTracao->getElementsByTagName("prop")->item(0);
            if (!empty($prop)) {
                $texto = $prop->getElementsByTagName("RNTRC")->item(0)->nodeValue ?? '';
            } else {
                $texto = $this->RNTRC ?? '';
            }
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x1, $y + 4, $x2, 10, $texto, $aFont, 'T', 'L', 0, '', false);
            $altura = $y + 4;
            for ($i = 0; $i < $totalReboques; $i++) {
                $item = $this->veicReboque->item($i);
                $DOMNodeList = $item->getElementsByTagName('RNTRC');
                if ($DOMNodeList->length > 0) {
                    $altura += 4;
                    $texto = $DOMNodeList->item(0)->nodeValue ?? '';
                    $this->pdf->textBox($x1, $altura, $x2, 10, $texto, $aFont, 'T', 'L', 0, '', false);
                }
            }
            $x1 = $x;
            /* a moldura do Veiculo tem altura variavel, conforme a quantidade de reboques: o
               quadro seguinte comeca abaixo dela, e nunca antes do ponto em que a lista
               de placas terminou */
            $y = max($altura, $yQuadroVeiculo + $tamanho);
            $x2 = round($maxW / 2, 0);
            $valesPedagios = 0;
            $temVales = false;
            if ($this->valePed != "" && $this->valePed->length > 0) {
                $valesPedagios = $this->valePed->length;
                $temVales = true;
            }
            /* o quadro é impresso acima do de Observação, que tem posição fixa na folha:
               o que não couber segue para a folha de continuação */
            $this->valePedRestantes = array();
            if ($temVales) {
                $cabemVales = $this->linhasDisponiveisMDFe($y + 10);
                if ($valesPedagios > $cabemVales) {
                    for ($i = $cabemVales; $i < $valesPedagios; $i++) {
                        $this->valePedRestantes[] = $this->valePed->item($i);
                    }
                    $valesPedagios = $cabemVales;
                }
            }
            $tamanho = ($valesPedagios * 7.5);
            if ($temVales) {
                $y += 5;
                $this->pdf->textBox($x1, $y, $x2, 11 + $tamanho / 2, '', $this->baseFont, 'T', 'L', 0);
                $texto = 'Vale Pedágio';
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
                $this->pdf->textBox($x1, $y, $x2, 4, $texto, $aFont, 'T', 'L', 0, '', false);
                /* o quadro ocupa a metade esquerda da folha e tem quatro colunas:
                   responsavel, fornecedora, comprovante e valor */
                $x2 = ($x2 / 4);
                $this->pdf->textBox($x1, $y, $x2 - 3, 4 + ($tamanho / 2), '', $this->baseFont, 'T', 'L', 0);
                $y += 5;
                $texto = 'Responsável CNPJ/CPF';
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1, $y, $x2, 4, $texto, $aFont, 'T', 'L', 0, '', false);
                $altura = $y;
                for ($i = 0; $i < $valesPedagios; $i++) {
                    $altura += 4;
                    /* o responsável pelo pagamento é informado por CNPJ ou por CPF, conforme
                       o xs:choice do schema, por isso o CPF é buscado na falta do CNPJ */
                    $pgNode = $this->valePed->item($i)->getElementsByTagName('CNPJPg');
                    if ($pgNode->length == 0) {
                        $pgNode = $this->valePed->item($i)->getElementsByTagName('CPFPg');
                    }
                    $texto = $pgNode->length == 0 ? '' : $pgNode->item(0)->nodeValue;
                    $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
                    $this->pdf->textBox($x1, $altura, $x2, 4, $texto, $aFont, 'T', 'L', 0, '', false);
                }
                $x1 += $x2;
                $this->pdf->textBox($x1, $y, $x2, 6 + ($tamanho / 2), '', $this->baseFont, 'T', 'L', 0);
                $texto = 'Fornecedora CNPJ';
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1, $y, $x2, 4, $texto, $aFont, 'T', 'L', 0, '', false);
                $altura = $y;
                for ($i = 0; $i < $valesPedagios; $i++) {
                    $altura += 4;
                    $pgNode = $this->valePed->item($i)->getElementsByTagName('CNPJForn');
                    $texto = $pgNode->length == 0 ? '' : $pgNode->item(0)->nodeValue;
                    $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
                    $this->pdf->textBox($x1, $altura, $x2 , 4, $texto, $aFont, 'T', 'L', 0, '', false);
                }
                $x1 += $x2;
                $this->pdf->textBox($x1, $y, $x2 + 6, 6 + ($tamanho / 2), '', $this->baseFont, 'T', 'L', 0);
                $texto = 'Nº Comprovante';
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1, $y, $x2, 4, $texto, $aFont, 'T', 'L', 0, '', false);
                $altura = $y;
                for ($i = 0; $i < $valesPedagios; $i++) {
                    $altura += 4;
                    /* o nCompra é opcional no schema (minOccurs="0"), por isso a existência
                       é verificada antes da leitura */
                    $compraNode = $this->valePed->item($i)->getElementsByTagName('nCompra');
                    $texto = $compraNode->length == 0 ? '' : $compraNode->item(0)->nodeValue;
                    $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
                    $this->pdf->textBox($x1, $altura, $x2, 4, $texto, $aFont, 'T', 'L', 0, '', false);
                }
                $x1 += $x2;
                $this->pdf->textBox($x1, $y, $x2, 6 + ($tamanho / 2), '', $this->baseFont, 'T', 'L', 0);
                $texto = 'Valor R$';
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1, $y, $x2, 4, $texto, $aFont, 'T', 'L', 0, '', false);
                $altura = $y;
                for ($i = 0; $i < $valesPedagios; $i++) {
                    $altura += 4;
                    /* o vValePed é obrigatório no schema, mas a verificação evita que um XML
                       fora do padrão interrompa a geração do documento */
                    $valorNode = $this->valePed->item($i)->getElementsByTagName('vValePed');
                    $texto = $valorNode->length == 0
                        ? ''
                        : number_format($valorNode->item(0)->nodeValue, 2, ',', '.');
                    $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
                    $this->pdf->textBox($x1, $altura, $x2, 4, $texto, $aFont, 'T', 'L', 0, '', false);
                }
            }
            /* o quadro de seguro ocupa a largura inteira da folha e por isso é impresso
               adiante, depois dos condutores e das chaves de acesso */
            $yFimValePed = $altura;
            $this->condutor = $this->veicTracao->getElementsByTagName('condutor');
            $x1 = round($maxW / 2, 0) + 7;
            $y = $yold;
            $x2 = round($maxW / 2, 0);
            $texto = 'Condutor';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $y += 5;
            /* a coluna do CPF tem a largura do seu próprio conteúdo: reservar um sexto da
               folha deixava um vão vazio e espremia a coluna Nome ao lado, que passava a
               quebrar nomes que caberiam em uma linha */
            $x2 = min($maxW / 6, $this->larguraCpfMDFe());
            /* a coluna de chaves de acesso é impressa mais à direita: a coluna Nome vai só
               até onde ela começa, para não invadi-la */
            $xNome = $x1 + $x2;
            $xChaves = $this->orientacao == 'L' ? 149 : round($maxW / 2, 0) + 7;
            $wNome = $this->orientacao == 'L' ? ($xChaves - $xNome - 1) : (($maxW / 6) * 2);
            /* a altura de cada condutor não é fixa: o nome pode não caber na largura da
               coluna e quebrar, por isso é medida antes de imprimir e vale para as duas
               colunas, que assim permanecem alinhadas */
            $alturasCondutores = array();
            $linhasCondutores = 0;
            $totalCondutores = 0;
            $cabemLinhas = $this->linhasDisponiveisMDFe($yold + 5);
            $this->condutoresRestantes = array();
            for ($i = 0; $i < $this->condutor->length; $i++) {
                $nome = $this->condutor->item($i)->getElementsByTagName('xNome')->item(0)->nodeValue;
                $linhas = $this->linhasTextoMDFe($nome, $wNome - 1);
                if (count($this->condutoresRestantes) > 0
                    || ($linhasCondutores + $linhas > $cabemLinhas && $totalCondutores > 0)
                ) {
                    /* o que não couber segue para a folha de continuação */
                    $this->condutoresRestantes[] = $this->condutor->item($i);
                    continue;
                }
                $alturasCondutores[$totalCondutores] = $linhas * 4;
                $linhasCondutores += $linhas;
                $totalCondutores++;
            }
            /* a moldura acompanha a maior das duas listas impressas lado a lado: o
               vale-pedágio, à esquerda, e os condutores, aqui */
            $hCondutores = max(33 + ($tamanho / 2), 9 + ($linhasCondutores * 4));
            $this->pdf->textBox($x1, $y, $x2, $hCondutores, '', $this->baseFont, 'T', 'L', 0);
            $texto = 'CPF';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $yold = $y;
            for ($i = 0; $i < $totalCondutores; $i++) {
                $y += 4;
                $texto = $this->condutor->item($i)->getElementsByTagName('CPF')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1, $y, $x2 - 1, 10, $texto, $aFont, 'T', 'L', 0, '', false);
                $y += $alturasCondutores[$i] - 4;
            }
            $y = $yold;
            $x1 = $xNome;
            $x2 = $wNome;
            $this->pdf->textBox($x1, $y, $x2, $hCondutores, '', $this->baseFont, 'T', 'L', 0);
            $texto = 'Nome';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            for ($i = 0; $i < $totalCondutores; $i++) {
                $y += 4;
                $texto = $this->condutor->item($i)->getElementsByTagName('xNome')->item(0)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                /* o nome longo quebra em mais de uma linha, no tamanho normal da fonte: a
                   altura já foi medida, e o condutor seguinte começa abaixo dela */
                $this->pdf->textBox(
                    $x1,
                    $y,
                    $x2 - 1,
                    $alturasCondutores[$i],
                    $texto,
                    $aFont,
                    'T',
                    'L',
                    0,
                    '',
                    false
                );
                $y += $alturasCondutores[$i] - 4;
                /* o quadro de seguro, impresso depois, ocupa a largura inteira: por isso
                   precisa saber até onde a lista de condutores desceu */
                $this->yFimCondutores = $y;
            }
        }
        $y += 10;
        $x1 = round($maxW / 2, 0) + 7;
        $x2 = ($maxW / 6);
        $this->quantidadeChavesLayout = 30 - $this->condutor->length;
        if ($this->orientacao == 'L') {
            $x1 = 149;
            $this->quantidadeChavesLayout = 13 - $this->condutor->length;
        }

        if ($this->exibirDocumentosVinculados) {
            $texto = 'Chaves de acesso';
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox($x1, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $y = $y + 2;
            $chavesNFe = $this->dom->getElementsByTagName('infDoc')->item(0)->getElementsByTagName('chNFe');
            $chavesCTe = $this->dom->getElementsByTagName('infDoc')->item(0)->getElementsByTagName('chCTe');
            $chavesMDFe = $this->dom->getElementsByTagName('infDoc')->item(0)->getElementsByTagName('chMDFe');
            $chaves = [];
            for ($i = 0; $i < $chavesNFe->length; $i++) {
                $chaves[] = $chavesNFe->item($i)->nodeValue;
            }
            for ($i = 0; $i < $chavesCTe->length; $i++) {
                $chaves[] = $chavesCTe->item($i)->nodeValue;
            }
            for ($i = 0; $i < $chavesMDFe->length; $i++) {
                $chaves[] = $chavesMDFe->item($i)->nodeValue;
            }
            $this->chaves = array_slice($chaves, $this->quantidadeChavesLayout);
            $contadorChaves = 0;
            for ($i = 0; $i < $chavesNFe->length; $i++) {
                $y += 4;
                $texto = $chavesNFe->item($i)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1, $y, 70, 8, $texto, $aFont, 'T', 'L', 0, '', false);
                $contadorChaves++;
                if ($contadorChaves >= $this->quantidadeChavesLayout) {
                    $this->flagDocs = true;
                    break;
                }
            }
            for ($i = 0; $i < $chavesCTe->length; $i++) {
                $y += 4;
                $texto = $chavesCTe->item($i)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1, $y, 70, 8, $texto, $aFont, 'T', 'L', 0, '', false);
                $contadorChaves++;
                if ($contadorChaves >= $this->quantidadeChavesLayout) {
                    $this->flagDocs = true;
                    break;
                }
            }
            for ($i = 0; $i < $chavesMDFe->length; $i++) {
                $y += 4;
                $texto = $chavesMDFe->item($i)->nodeValue;
                $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
                $this->pdf->textBox($x1, $y, 70, 8, $texto, $aFont, 'T', 'L', 0, '', false);
                $contadorChaves++;
                if ($contadorChaves >= $this->quantidadeChavesLayout) {
                    $this->flagDocs = true;
                    break;
                }
            }
        }
        if ($this->rodo && $this->seg != "" && $this->seg->length > 0) {
            /* o quadro ocupa a largura inteira da folha, então começa abaixo de tudo o que
               foi impresso acima dele: o vale-pedágio, à esquerda, e os condutores e as
               chaves de acesso, à direita */
            $ySeguro = max($yFimValePed, $y, $this->yFimCondutores) + 10;
            $grupos = $this->cortaGruposSeguroMDFe($this->agrupaSegurosMDFe($this->seg), $ySeguro);
            $altura = $this->quadroSeguroMDFe($grupos, $ySeguro);
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
                $this->pdf->textBox($x1, $initial + 6, ($x2 / 2), $initialA - $y, '', $this->baseFont, 'T', 'L', 0);
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
                $this->pdf->textBox(
                    ($x1 + ($x2 / 2)),
                    $initial + 6,
                    ($x2 / 2),
                    $initialB - $y,
                    '',
                    $this->baseFont,
                    'T',
                    'L',
                    0
                );
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
                $this->pdf->textBox($x1, $initial + 6, ($x2 / 2), $initialA - $y, '', $this->baseFont, 'T', 'L', 0);
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
                $this->pdf->textBox(
                    ($x1 + ($x2 / 2)),
                    $initial + 6,
                    ($x2 / 2),
                    $initialB - $y,
                    '',
                    $this->baseFont,
                    'T',
                    'L',
                    0
                );
            }

            $altura = $initialA > $initialB ? $initialA : $initialB;
            $altura += 6;
        }

        if ($this->ferrov) {
            $altura = $y + 4;
        }

        return $altura + 10;
    }

    /**
     * linhasDisponiveisMDFe
     * Quantas linhas de 4mm cabem entre uma posição e o quadro de Observação, que é impresso
     * em posição fixa pelo footerMDFe. Serve aos quadros cuja lista cresce com a quantidade
     * de registros do XML: vale-pedágio, condutores e reboques.
     *
     * @param float $y Posição vertical em que a lista começa
     * @param float $reservado Espaço, em mm, que precisa sobrar abaixo da lista
     * @return number
     */
    protected function linhasDisponiveisMDFe($y, $reservado = 4)
    {
        /* posicao fixa do quadro de Observacao, definida em footerMDFe() */
        $yLimite = $this->orientacao == 'P' ? 240 : 180;
        $linhas = (int) floor((($yLimite - $reservado) - $y) / 4);
        return $linhas > 0 ? $linhas : 0;
    }

    /**
     * linhasReboquesMDFe
     * Quantos reboques cabem no quadro do Veículo sem empurrar os quadros seguintes para fora
     * da folha. Abaixo do Veículo ainda são impressos o Vale Pedágio, os condutores, as chaves
     * de acesso e o seguro, por isso é reservado o espaço que o desenho original já reservava.
     *
     * @param float $y Posição vertical em que o quadro do Veículo começa
     * @return number
     */
    protected function linhasReboquesMDFe($y)
    {
        $reservado = $this->orientacao == 'P' ? 58 : 30;
        return $this->linhasDisponiveisMDFe($y + 8, $reservado) - 1;
    }

    /**
     * larguraCpfMDFe
     * Largura necessária para a coluna de CPF do condutor, medida com a fonte da impressão.
     * O campo tem tamanho fixo, por isso a medida é feita sobre um CPF de exemplo, com uma
     * folga para separar da coluna seguinte.
     *
     * @return float
     */
    protected function larguraCpfMDFe()
    {
        $this->pdf->setFont($this->fontePadrao, '', 8);
        return $this->pdf->getStringWidth('000.000.000-00') + 4;
    }

    /**
     * linhasTextoMDFe
     * Quantas linhas um texto ocupa ao ser impresso numa determinada largura, medido com a
     * mesma fonte usada na impressão.
     *
     * @param string $texto
     * @param float $largura
     * @return number
     */
    protected function linhasTextoMDFe($texto, $largura)
    {
        if ($texto === '' || $largura <= 0) {
            return 1;
        }
        $this->pdf->setFont($this->fontePadrao, '', 8);
        $copia = $texto;
        $linhas = $this->pdf->wordWrap($copia, $largura);
        return $linhas > 0 ? $linhas : 1;
    }

    /**
     * agrupaSegurosMDFe
     * Separa os seguros pelo responsável e monta os grupos exibidos no quadro. O respSeg é
     * obrigatório no modal rodoviário e vale 1 para o emitente do MDF-e e 2 para o
     * contratante do serviço de transporte. Havendo os dois, cada um forma um bloco com o
     * seu próprio título; havendo apenas um, a lista segue distribuída nas colunas.
     *
     * @param \DOMNodeList $seguros
     * @return array
     */
    protected function agrupaSegurosMDFe($seguros)
    {
        $segEmitente = array();
        $segContratante = array();
        for ($i = 0; $i < $seguros->length; $i++) {
            $item = $seguros->item($i);
            $noResp = $item->getElementsByTagName('respSeg');
            $respSeg = $noResp->length == 0 ? '' : $noResp->item(0)->nodeValue;
            if ($respSeg == '1') {
                $segEmitente[] = $item;
            } else {
                $segContratante[] = $item;
            }
        }
        if (count($segEmitente) > 0 && count($segContratante) > 0) {
            return array(
                array('titulo' => 'Emitente do MDF-e', 'itens' => $segEmitente),
                array('titulo' => 'Contratante', 'itens' => $segContratante),
            );
        }
        $titulo = count($segContratante) > 0 ? 'Contratante' : 'Emitente do MDF-e';
        return array(
            array('titulo' => $titulo, 'itens' => array_merge($segEmitente, $segContratante)),
        );
    }

    /**
     * colunasSeguroMDFe
     * Posições e larguras dos pares seguradora/apólice. A quantidade de pares acompanha a
     * largura da folha: em retrato cabem dois e, em paisagem, mais larga, cabem três — o que
     * reduz a altura ocupada pela lista.
     *
     * @return array
     */
    protected function colunasSeguroMDFe()
    {
        $wApol = 30;
        /* largura mínima da coluna da seguradora: abaixo dela o nome quebraria em linhas
           demais, anulando o ganho de uma coluna a mais */
        $wSeg = 55;
        $espaco = 4;
        $largura = $this->wPrint;
        $pares = (int) floor($largura / ($wSeg + 3 + $wApol + $espaco));
        if ($pares < 1) {
            $pares = 1;
        }
        /* o que sobra é distribuído na coluna da seguradora, que é a que pode quebrar */
        $porPar = $largura / $pares;
        $wSeg = $porPar - 3 - $wApol - $espaco;
        $x = array();
        for ($i = 0; $i < $pares; $i++) {
            $x[] = 7 + ($i * $porPar);
        }
        return array('x' => $x, 'wSeg' => $wSeg, 'wApol' => $wApol);
    }

    /**
     * dadosSeguroMDFe
     * Seguradora e número da apólice de um registro de seguro. Tanto o grupo infSeg quanto o
     * nApol são opcionais no schema, por isso ambos são verificados antes da leitura.
     *
     * @param \DOMElement $seguro
     * @return array
     */
    protected function dadosSeguroMDFe($seguro)
    {
        $xSeg = '';
        $cnpj = '';
        $infSeg = $seguro->getElementsByTagName('infSeg')->item(0);
        if ($infSeg !== null) {
            $noSeg = $infSeg->getElementsByTagName('xSeg');
            $xSeg = $noSeg->length == 0 ? '' : $noSeg->item(0)->nodeValue;
            $noCnpj = $infSeg->getElementsByTagName('CNPJ');
            $cnpj = $noCnpj->length == 0 ? '' : $noCnpj->item(0)->nodeValue;
        }
        $noApol = $seguro->getElementsByTagName('nApol');
        $apolice = $noApol->length == 0 ? '' : $noApol->item(0)->nodeValue;
        return array(trim("{$cnpj} {$xSeg}"), $apolice);
    }

    /**
     * linhasLinhaSeguroMDFe
     * Altura, em linhas de 4mm, de uma linha do quadro: é a do maior nome de seguradora
     * daquela linha, porque as colunas avançam juntas para permanecerem alinhadas.
     *
     * @param array $linha Seguros impressos lado a lado
     * @return number
     */
    protected function linhasLinhaSeguroMDFe($linha)
    {
        $colunas = $this->colunasSeguroMDFe();
        $maior = 1;
        foreach ($linha as $item) {
            list($seguradora) = $this->dadosSeguroMDFe($item);
            $linhas = $this->linhasTextoMDFe($seguradora, $colunas['wSeg']);
            if ($linhas > $maior) {
                $maior = $linhas;
            }
        }
        return $maior;
    }

    /**
     * cortaGruposSeguroMDFe
     * Separa o que cabe na folha corrente do que segue para a de continuação, guardando o
     * excedente em $segurosRestantes.
     *
     * @param array $grupos
     * @param float $yLista Posição vertical em que o quadro começa
     * @return array Grupos que cabem na folha corrente
     */
    protected function cortaGruposSeguroMDFe($grupos, $yLista)
    {
        $divisao = $this->divideGruposSeguroMDFe($grupos, $this->linhasDisponiveisMDFe($yLista));
        $this->segurosRestantes = $divisao['resto'];
        return $divisao['folha'];
    }

    /**
     * divideGruposSeguroMDFe
     * Divide os grupos entre o que cabe no espaço informado e o que sobra. A altura de cada
     * linha não é fixa: o nome da seguradora pode não caber na largura da coluna e quebrar,
     * por isso as linhas são medidas com o mesmo critério usado na impressão.
     *
     * @param array $grupos
     * @param number $disponivel Linhas de 4mm disponíveis
     * @return array array('folha' => ..., 'resto' => ...)
     */
    protected function divideGruposSeguroMDFe($grupos, $disponivel)
    {
        $resto = array();
        $folha = array();
        $porLinha = count($this->colunasSeguroMDFe()['x']);
        foreach ($grupos as $grupo) {
            /* título e cabeçalho das colunas antecedem a lista: sem espaço para eles mais
               uma linha de seguro, o grupo inteiro segue para a folha seguinte */
            $cabe = $disponivel - 2;
            if ($cabe < 1) {
                $resto[] = $grupo;
                continue;
            }
            $impressos = 0;
            $usadas = 0;
            foreach (array_chunk($grupo['itens'], $porLinha) as $linha) {
                $altura = $this->linhasLinhaSeguroMDFe($linha);
                if ($usadas + $altura > $cabe) {
                    break;
                }
                $usadas += $altura;
                $impressos += count($linha);
            }
            if ($impressos == 0) {
                $resto[] = $grupo;
                continue;
            }
            $sobra = array_slice($grupo['itens'], $impressos);
            if (count($sobra) > 0) {
                /* o título acompanha o grupo, porque a continuação repete o mesmo cabeçalho */
                $resto[] = array('titulo' => $grupo['titulo'], 'itens' => $sobra);
            }
            $grupo['itens'] = array_slice($grupo['itens'], 0, $impressos);
            $folha[] = $grupo;
            /* o próximo grupo começa abaixo deste: descontam-se o cabeçalho, as linhas
               usadas pela lista e uma de intervalo entre os blocos */
            $disponivel -= 2 + $usadas + 1;
        }
        return array('folha' => $folha, 'resto' => $resto);
    }

    /**
     * quadroSeguroMDFe
     * Desenha o quadro de seguro: cada responsável é um bloco que ocupa a largura inteira e
     * começa abaixo do anterior, repetindo o seu título e o cabeçalho das colunas.
     *
     * @param array $grupos
     * @param float $y
     * @return float Posição vertical após o quadro
     */
    protected function quadroSeguroMDFe($grupos, $y)
    {
        $colunas = $this->colunasSeguroMDFe();
        $xColuna = $colunas['x'];
        $wSeg = $colunas['wSeg'];
        $wApol = $colunas['wApol'];
        $aFontTitulo = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $yFinal = $y;
        foreach ($grupos as $grupo) {
            $yCabecalho = $y + 4;
            $this->pdf->textBox(
                $xColuna[0],
                $y,
                90,
                8,
                'Responsável pelo Seguro: ' . $grupo['titulo'],
                $aFontTitulo,
                'T',
                'L',
                0,
                '',
                false
            );
            foreach ($xColuna as $xCol) {
                $this->pdf->textBox($xCol, $yCabecalho, $wSeg, 8, 'Seguradora', $aFont, 'T', 'L', 0, '', false);
                $this->pdf->textBox(
                    $xCol + $wSeg + 3,
                    $yCabecalho,
                    $wApol,
                    8,
                    'Número da Apólice',
                    $aFont,
                    'T',
                    'L',
                    0,
                    '',
                    false
                );
            }
            $yGrupo = $yCabecalho;
            foreach (array_chunk($grupo['itens'], count($xColuna)) as $linha) {
                $yGrupo += 4;
                $ocupadas = 1;
                foreach ($linha as $i => $item) {
                    $xCol = $xColuna[$i];
                    list($seguradora, $apolice) = $this->dadosSeguroMDFe($item);
                    $linhasSeg = $this->linhasTextoMDFe($seguradora, $wSeg);
                    $this->pdf->textBox(
                        $xCol,
                        $yGrupo,
                        $wSeg,
                        $linhasSeg * 4,
                        $seguradora,
                        $aFont,
                        'T',
                        'L',
                        0,
                        '',
                        false
                    );
                    $this->pdf->textBox(
                        $xCol + $wSeg + 3,
                        $yGrupo,
                        $wApol,
                        8,
                        $apolice,
                        $aFont,
                        'T',
                        'L',
                        0,
                        '',
                        false
                    );
                    if ($linhasSeg > $ocupadas) {
                        $ocupadas = $linhasSeg;
                    }
                }
                /* a primeira linha já foi contada no avanço acima */
                $yGrupo += ($ocupadas - 1) * 4;
            }
            if ($yGrupo > $yFinal) {
                $yFinal = $yGrupo;
            }
            /* o bloco seguinte começa uma linha abaixo do último seguro deste */
            $y = $yGrupo + 8;
        }
        return $yFinal;
    }

    /**
     * seguroContinuacao
     * Imprime, em folhas de continuação, os seguros que não couberam.
     *
     * @return void
     */
    protected function seguroContinuacao()
    {
        while (count($this->segurosRestantes) > 0) {
            $y = $this->novaFolhaContinuacaoMDFe('SEGURO - CONTINUAÇÃO');
            $grupos = $this->cortaGruposSeguroMDFe($this->segurosRestantes, $y);
            if (count($grupos) == 0) {
                /* nem uma linha cabe nesta folha: evita repetir folhas vazias */
                break;
            }
            $this->quadroSeguroMDFe($grupos, $y);
        }
    }

    /**
     * novaFolhaContinuacaoMDFe
     * Abre uma folha de continuação com o cabeçalho do documento e o título do quadro, e
     * devolve a posição vertical em que a lista pode começar.
     *
     * @param string $titulo
     * @return float
     */
    protected function novaFolhaContinuacaoMDFe($titulo)
    {
        $x = 3;
        $y = 7;
        $this->pdf->addPage($this->orientacao, $this->papel);
        if ($this->orientacao == 'P') {
            $y = $this->headerMDFeRetrato($x, $y);
        } else {
            $y = $this->headerMDFePaisagem($x, $y);
        }
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pdf->textBox($x, $y, 180, 8, $titulo, $aFont, 'T', 'C', 0, '');
        return $y + 5;
    }

    /**
     * reboquesContinuacao
     * Imprime, em folhas de continuação, os reboques que não couberam no quadro do Veículo.
     *
     * @return void
     */
    protected function reboquesContinuacao()
    {
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        while (count($this->reboquesRestantes) > 0) {
            $y = $this->novaFolhaContinuacaoMDFe('VEÍCULO - CONTINUAÇÃO');
            $x = 7;
            $wCol = round($this->wPrint / 4, 0);
            $this->pdf->textBox($x, $y, $wCol, 8, 'Placa', $aFont, 'T', 'L', 0, '', false);
            $this->pdf->textBox($x + $wCol, $y, $wCol, 8, 'RNTRC', $aFont, 'T', 'L', 0, '', false);
            $cabem = $this->linhasDisponiveisMDFe($y);
            $desta = array_slice($this->reboquesRestantes, 0, $cabem);
            $this->reboquesRestantes = array_slice($this->reboquesRestantes, $cabem);
            foreach ($desta as $item) {
                $y += 4;
                $placa = $item->getElementsByTagName('placa')->item(0)->nodeValue;
                $this->pdf->textBox($x, $y, $wCol - 1, 8, $placa, $aFont, 'T', 'L', 0, '', false);
                $lista = $item->getElementsByTagName('RNTRC');
                if ($lista->length > 0) {
                    $rntrc = $lista->item(0)->nodeValue ?? '';
                    $this->pdf->textBox($x + $wCol, $y, $wCol - 1, 8, $rntrc, $aFont, 'T', 'L', 0, '', false);
                }
            }
        }
    }

    /**
     * valePedContinuacao
     * Imprime, em folhas de continuação, os dispêndios de vale-pedágio que não couberam.
     *
     * @return void
     */
    protected function valePedContinuacao()
    {
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        while (count($this->valePedRestantes) > 0) {
            $y = $this->novaFolhaContinuacaoMDFe('VALE PEDÁGIO - CONTINUAÇÃO');
            $x = 7;
            $wCol = round($this->wPrint / 4, 0);
            $rotulos = array('Responsável CNPJ/CPF', 'Fornecedora CNPJ', 'Nº Comprovante', 'Valor R$');
            foreach ($rotulos as $indice => $rotulo) {
                $this->pdf->textBox($x + ($indice * $wCol), $y, $wCol, 8, $rotulo, $aFont, 'T', 'L', 0, '', false);
            }
            $cabem = $this->linhasDisponiveisMDFe($y);
            $desta = array_slice($this->valePedRestantes, 0, $cabem);
            $this->valePedRestantes = array_slice($this->valePedRestantes, $cabem);
            foreach ($desta as $disp) {
                $y += 4;
                foreach ($rotulos as $indice => $rotulo) {
                    $texto = $this->conteudoValePedMDFe($disp, $indice);
                    $this->pdf->textBox(
                        $x + ($indice * $wCol),
                        $y,
                        $wCol - 3,
                        8,
                        $texto,
                        $aFont,
                        'T',
                        'L',
                        0,
                        '',
                        false
                    );
                }
            }
        }
    }

    /**
     * conteudoValePedMDFe
     * Conteúdo de uma coluna do quadro de vale-pedágio, na ordem em que são impressas. O
     * responsável pelo pagamento é informado por CNPJ ou por CPF, conforme o xs:choice do
     * schema, e o número do comprovante é opcional: por isso ambos são verificados.
     *
     * @param \DOMElement $disp Dispêndio do vale-pedágio
     * @param integer $coluna Índice da coluna impressa
     * @return string
     */
    protected function conteudoValePedMDFe($disp, $coluna)
    {
        if ($coluna == 0) {
            $node = $disp->getElementsByTagName('CNPJPg');
            if ($node->length == 0) {
                $node = $disp->getElementsByTagName('CPFPg');
            }
        } elseif ($coluna == 1) {
            $node = $disp->getElementsByTagName('CNPJForn');
        } elseif ($coluna == 2) {
            $node = $disp->getElementsByTagName('nCompra');
        } else {
            $node = $disp->getElementsByTagName('vValePed');
            return $node->length == 0
                ? ''
                : number_format($node->item(0)->nodeValue, 2, ',', '.');
        }
        return $node->length == 0 ? '' : $node->item(0)->nodeValue;
    }

    /**
     * condutoresContinuacao
     * Imprime, em folhas de continuação, os condutores que não couberam.
     *
     * @return void
     */
    protected function condutoresContinuacao()
    {
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        while (count($this->condutoresRestantes) > 0) {
            $y = $this->novaFolhaContinuacaoMDFe('CONDUTOR - CONTINUAÇÃO');
            $x = 7;
            $wCpf = min($this->wPrint / 6, $this->larguraCpfMDFe());
            $wNome = ($this->wPrint - 7) - $wCpf;
            $this->pdf->textBox($x, $y, $wCpf, 8, 'CPF', $aFont, 'T', 'L', 0, '', false);
            $this->pdf->textBox($x + $wCpf, $y, $wNome, 8, 'Nome', $aFont, 'T', 'L', 0, '', false);
            /* o espaço é medido em linhas: um nome que não cabe na largura da coluna quebra
               e ocupa mais de uma */
            $cabem = $this->linhasDisponiveisMDFe($y);
            $desta = array();
            $usadas = 0;
            foreach ($this->condutoresRestantes as $condutor) {
                $nome = $condutor->getElementsByTagName('xNome')->item(0)->nodeValue;
                $linhas = $this->linhasTextoMDFe($nome, $wNome - 1);
                if ($usadas + $linhas > $cabem && count($desta) > 0) {
                    break;
                }
                $usadas += $linhas;
                $desta[] = $condutor;
            }
            $this->condutoresRestantes = array_slice($this->condutoresRestantes, count($desta));
            foreach ($desta as $condutor) {
                $y += 4;
                $nome = $condutor->getElementsByTagName('xNome')->item(0)->nodeValue;
                $altura = $this->linhasTextoMDFe($nome, $wNome - 1) * 4;
                $cpf = $condutor->getElementsByTagName('CPF')->item(0)->nodeValue;
                $this->pdf->textBox($x, $y, $wCpf - 1, 8, $cpf, $aFont, 'T', 'L', 0, '', false);
                $this->pdf->textBox($x + $wCpf, $y, $wNome - 1, $altura, $nome, $aFont, 'T', 'L', 0, '', false);
                $y += $altura - 4;
            }
        }
    }

    protected function addPage()
    {
        if (empty($this->chaves)) {
            return;
        }
        $x = 3;
        $y = 7;
        // adiciona a primeira página
        $this->pdf->addPage($this->orientacao, $this->papel);
        //coloca o cabeçalho Paisagem
        if ($this->orientacao == 'P') {
            $y = $this->headerMDFeRetrato($x, $y);
        } else {
            $y = $this->headerMDFePaisagem($x, $y);
        }
        $texto = 'CHAVES DE ACESSO - CONTINUACÃO';
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pdf->textBox($x, $y, 180, 240, $texto, $aFont, 'T', 'C', 0, '');
        $y = $y + 5;
        $aFont = array('font' => $this->fontePadrao, 'size' => 7, 'style' => '');
        for ($c = 0; $c < count($this->chaves); $c++) {
            $y += 4;
            $x = 7;
            $texto = $this->chaves[$c];
            $this->pdf->textBox($x, $y, 70, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            $c++;
            if (isset($this->chaves[$c])) {
                $x = 73;
                $texto = $this->chaves[$c];
                $this->pdf->textBox($x, $y, 70, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            }
            $c++;
            if (isset($this->chaves[$c])) {
                $x = 138;
                $texto = $this->chaves[$c];
                $this->pdf->textBox($x, $y, 70, 8, $texto, $aFont, 'T', 'L', 0, '', false);
            }
            if ($this->orientacao == 'L') {
                $c++;
                if (isset($this->chaves[$c])) {
                    $x = 204;
                    $texto = $this->chaves[$c];
                    $this->pdf->textBox($x, $y, 70, 8, $texto, $aFont, 'T', 'L', 0, '', false);
                }
            }
        }
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
        if ($this->orientacao == 'P') {
            $h = 50;
            $y = 240;
        } else {
            $h = 20;
            $y = 180;
        }
        $this->pdf->textBox($x, $y, $x2, $h, '', $this->baseFont, 'T', 'L', 1);
        $texto = "Observações\n{$this->infCpl}";
        if (!empty($this->infAdFisco)) {
            $texto .= "\n{$this->infAdFisco}";
        }
        $aFont = array('font' => $this->fontePadrao, 'size' => 7, 'style' => '');
        $this->pdf->textBox($x, $y, $x2, 8, $texto, $aFont, 'T', 'L', 0, '', false);
        //$y = $this->hPrint - 4;
        $y = $this->hPrint + 8;
        $texto = "Impresso em  " . date('d/m/Y H:i:s') . ' ' . $this->creditos;
        $w = $this->wPrint - 15;
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'I');
        $this->pdf->textBox($x, $y, $w, 4, $texto, $aFont, 'T', 'L', 0, '');
        $texto = '';
        if ($this->powered) {
            $texto = "Powered by NFePHP®";
        }
        $this->pdf->textBox($x, $y, $w, 8, $texto, $aFont, 'T', 'R', false, '');
    }

    public function setExibirDocumentosVinculados(bool $exibirDocumentosVinculados): void
    {
        $this->exibirDocumentosVinculados = $exibirDocumentosVinculados;
    }
}
