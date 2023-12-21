<?php

namespace NFePHP\DA\MDFe;

/**
 * Classe para geração do envento do MDFe em PDF
 * NOTA: Este documento não está NORMALIZADO, nem é requerido pela SEFAZ
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @name      Daevento.php
 * @copyright 2009-2019 NFePHP
 * @license   http://www.gnu.org/licenses/lgpl.html GNU/LGPL v.3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Cleiton Perin <cperin20 at gmail dot com>
 */

use NFePHP\DA\Common\DaCommon;
use NFePHP\DA\Legacy\Pdf;

class Daevento extends DaCommon
{
    public $chMDFe;
    protected $yDados = 0;

    protected $xml;
    protected $errMsg = '';
    protected $errStatus = false;
    protected $destino = 'I';
    protected $pdfDir = '';
    protected $version = '0.1.1';
    protected $wCanhoto;
    protected $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";

    protected $id;
    protected $dadosEmitente = array();
    private $dom;
    private $procEventoMDFe;
    private $evento;
    private $infEvento;
    private $retEvento;
    private $rinfEvento;
    protected $tpAmb;
    protected $cOrgao;
    protected $detEvento;
    protected $xCondUso;
    protected $dhEvento;
    protected $cStat;
    protected $xMotivo;
    protected $xJust;
    protected $CNPJDest = '';
    protected $CPFDest = '';
    protected $dhRegEvento;
    protected $nProt;
    protected $tpEvento;
    protected $creditos;


    /**
     * __construct
     *
     * @param string $xml Arquivo XML (diretório ou string)
     * @param array $dadosEmitente Dados do endereço do emitente
     */
    public function __construct($xml, $dadosEmitente)
    {
        $this->dadosEmitente = $dadosEmitente;
        $this->loadDoc($xml);
    }

    protected function loadDoc($xml)
    {
        $this->dom = new \DomDocument;
        $this->dom->loadXML($xml);
        $this->procEventoMDFe = $this->dom->getElementsByTagName("procEventoMDFe")->item(0);
        $this->evento = $this->dom->getElementsByTagName("eventoMDFe")->item(0);
        $this->infEvento = $this->evento->getElementsByTagName("infEvento")->item(0);
        $this->retEvento = $this->dom->getElementsByTagName("retEventoMDFe")->item(0);
        $this->rinfEvento = $this->retEvento->getElementsByTagName("infEvento")->item(0);
        $this->tpEvento = $this->infEvento->getElementsByTagName("tpEvento")->item(0)->nodeValue;
        if (!in_array($this->tpEvento, ['110114'])) {
            $this->errMsg = 'Evento não implementado ' . $this->tpEvento . ' !!';
            $this->errStatus = true;
            return false;
        }
        $this->id = str_replace('ID', '', $this->infEvento->getAttribute("Id"));
        $this->chMDFe = $this->infEvento->getElementsByTagName("chMDFe")->item(0)->nodeValue;
        $this->dadosEmitente['CNPJ'] = substr($this->chMDFe, 6, 14);
        $this->tpAmb = $this->infEvento->getElementsByTagName("tpAmb")->item(0)->nodeValue;
        $this->cOrgao = $this->infEvento->getElementsByTagName("cOrgao")->item(0)->nodeValue;
        if ($this->tpEvento == '110114') {
            $this->detEvento = $this->infEvento->getElementsByTagName("detEvento");
        }
        $this->xJust = $this->infEvento->getElementsByTagName("xJust")->item(0);
        $this->xJust = (empty($this->xJust) ? '' : $this->xJust->nodeValue);
        $this->dhEvento = $this->infEvento->getElementsByTagName("dhEvento")->item(0)->nodeValue;
        $this->cStat = $this->rinfEvento->getElementsByTagName("cStat")->item(0)->nodeValue;
        $this->xMotivo = $this->rinfEvento->getElementsByTagName("xMotivo")->item(0)->nodeValue;
        $this->dhRegEvento = $this->rinfEvento->getElementsByTagName("dhRegEvento")->item(0)->nodeValue;
        $this->nProt = $this->rinfEvento->getElementsByTagName("nProt")->item(0)->nodeValue;
    }


    /**
     * monta
     * Esta função monta a DAEventoMDFe conforme as informações fornecidas para a classe
     * durante sua construção.
     * A definição de margens e posições iniciais para a impressão são estabelecidas no
     * pelo conteúdo da funçao e podem ser modificados.
     *
     * @param string $logo base64 da logomarca
     * @return string O ID do evento extraido do arquivo XML
     */
    protected function monta(
        $logo = ''
    ) {
        if (!empty($logo)) {
            $this->logomarca = $this->adjustImage($logo);
        }
        if (empty($this->orientacao)) {
            $this->orientacao = 'P';
        }
        // margens do PDF
        $margSup = $this->margsup;
        $margEsq = $this->margesq;
        $margDir = $this->margesq;
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        if ($this->orientacao == 'P') {
            // posição inicial do relatorio
            $xInic = 1;
            $yInic = 1;
            if ($this->papel == 'A4') { //A4 210x297mm
                $maxW = 210;
                $maxH = 297;
            }
        } else {
            // posição inicial do relatorio
            $xInic = 5;
            $yInic = 5;
            if ($papel == 'A4') {
                //A4 210x297mm
                $maxH = 210;
                $maxW = 297;
            }
        }
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
        //coloca o cabeçalho
        $y = $this->header($x, $y, $pag);
        $y = $this->body($x, $y + 15);
        $y = $this->footer($x, $y + $this->hPrint - 20);
        //retorna o ID do evento
        return $this->id;
    }

    /**
     * header
     * @param integer $x
     * @param integer $y
     * @param integer $pag
     * @return integer
     */
    private function header(
        $x,
        $y,
        $pag
    ) {
        $oldX = $x;
        $oldY = $y;
        $maxW = $this->wPrint;
        //####################################################################################
        //coluna esquerda identificação do emitente
        $w = round($maxW * 0.41, 0);// 80;
        if ($this->orientacao == 'P') {
            $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'I');
        } else {
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        }
        $w1 = $w;
        $h = 32;
        $oldY += $h;
        $this->pdf->textBox($x, $y, $w, $h);
        $texto = 'IDENTIFICAÇÃO DO EMITENTE';
        $this->pdf->textBox($x, $y, $w, 5, $texto, $aFont, 'T', 'C', 0, '');
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
        //Nome emitente
        $aFont = array('font' => $this->fontePadrao, 'size' => 12, 'style' => 'B');
        $texto = (isset($this->dadosEmitente['razao']) ? $this->dadosEmitente['razao'] : '');
        $this->pdf->textBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        //endereço
        $y1 = $y1 + 6;
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $lgr = (isset($this->dadosEmitente['logradouro']) ? $this->dadosEmitente['logradouro'] : '');
        $nro = (isset($this->dadosEmitente['numero']) ? $this->dadosEmitente['numero'] : '');
        $cpl = (isset($this->dadosEmitente['complemento']) ? $this->dadosEmitente['complemento'] : '');
        $bairro = (isset($this->dadosEmitente['bairro']) ? $this->dadosEmitente['bairro'] : '');
        $CEP = (isset($this->dadosEmitente['CEP']) ? $this->dadosEmitente['CEP'] : '');
        $CEP = $this->formatField($CEP, "#####-###");
        $mun = (isset($this->dadosEmitente['municipio']) ? $this->dadosEmitente['municipio'] : '');
        $UF = (isset($this->dadosEmitente['UF']) ? $this->dadosEmitente['UF'] : '');
        $fone = (isset($this->dadosEmitente['telefone']) ? $this->dadosEmitente['telefone'] : '');
        $email = (isset($this->dadosEmitente['email']) ? $this->dadosEmitente['email'] : '');
        $foneLen = strlen($fone);
        if ($foneLen > 0) {
            $fone2 = substr($fone, 0, $foneLen - 4);
            $fone1 = substr($fone, 0, $foneLen - 8);
            $fone = '(' . $fone1 . ') ' . substr($fone2, -4) . '-' . substr($fone, -4);
        } else {
            $fone = '';
        }
        if ($email != '') {
            $email = 'Email: ' . $email;
        }
        $texto = "";
        $tmp_txt = trim(($lgr != '' ? "$lgr, " : '') . ($nro != 0 ? $nro : "SN") . ($cpl != '' ? " - $cpl" : ''));
        $tmp_txt = ($tmp_txt == 'SN' ? '' : $tmp_txt);
        $texto .= ($texto != '' && $tmp_txt != '' ? "\n" : '') . $tmp_txt;
        $tmp_txt = trim($bairro . ($bairro != '' && $CEP != '' ? " - " : '') . $CEP);
        $texto .= ($texto != '' && $tmp_txt != '' ? "\n" : '') . $tmp_txt;
        $tmp_txt = $mun;
        $tmp_txt .= ($tmp_txt != '' && $UF != '' ? " - " : '') . $UF;
        $tmp_txt .= ($tmp_txt != '' && $fone != '' ? " - " : '') . $fone;
        $texto .= ($texto != '' && $tmp_txt != '' ? "\n" : '') . $tmp_txt;
        $tmp_txt = $email;
        $texto .= ($texto != '' && $tmp_txt != '' ? "\n" : '') . $tmp_txt;
        $this->pdf->textBox($x1, $y1 - 2, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        //##################################################
        $w2 = round($maxW - $w, 0);
        $x += $w;
        $this->pdf->textBox($x, $y, $w2, $h);
        $y1 = $y + $h;
        $aFont = array('font' => $this->fontePadrao, 'size' => 16, 'style' => 'B');
        if ($this->tpEvento == '110114') {
            $texto = 'Representação Gráfica de Inclusão de Condutor';
        } else {
            $texto = 'Representação Gráfica de Evento';
        }
        $this->pdf->textBox($x, $y + 2, $w2, 8, $texto, $aFont, 'T', 'C', 0, '');
        $aFont = array('font' => $this->fontePadrao, 'size' => 12, 'style' => 'I');
        if ($this->tpEvento == '110114') {
            $texto = '(Inclusão de Condutor)';
        } elseif ($this->tpEvento == '110111') {
            $texto = '(Cancelamento de MDFe)';
        }
        $this->pdf->textBox($x, $y + 7, $w2, 8, $texto, $aFont, 'T', 'C', 0, '');
        $texto = 'ID do Evento: ' . $this->id;
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
        $this->pdf->textBox($x, $y + 15, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $tsHora = $this->toTimestamp($this->dhEvento);
        $texto = 'Criado em : ' . date('d/m/Y   H:i:s', $tsHora);
        $this->pdf->textBox($x, $y + 20, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $tsHora = $this->toTimestamp($this->dhRegEvento);
        $texto = 'Prococolo: ' . $this->nProt . '  -  Registrado na SEFAZ em: ' . date('d/m/Y   H:i:s', $tsHora);
        $this->pdf->textBox($x, $y + 25, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        //####################################################
        $x = $oldX;
        $this->pdf->textBox($x, $y1, $maxW, 40);
        $sY = $y1 + 40;
        //############################################
        $x = $oldX;
        $y = $y1;
        $aFont = array('font' => $this->fontePadrao, 'size' => 12, 'style' => 'B');
        $numNF = substr($this->chMDFe, 25, 9);
        $serie = substr($this->chMDFe, 22, 3);
        $numNF = $this->formatField($numNF, "###.###.###");
        $texto = "MDFe: " . $numNF . '  -   Série: ' . $serie;
        $this->pdf->textBox($x + 2, $y + 19, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $bW = 87;
        $bH = 15;
        $x = 55;
        $y = $y1 + 13;
        $w = $maxW;
        $this->pdf->setFillColor(0, 0, 0);
        $this->pdf->code128($x + (($w - $bW) / 2), $y + 2, $this->chMDFe, $bW, $bH);
        $this->pdf->setFillColor(255, 255, 255);
        $y1 = $y + 2 + $bH;
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => '');
        $texto = $this->formatField($this->chMDFe, $this->formatoChave);
        $this->pdf->textBox($x, $y1, $w - 2, $h, $texto, $aFont, 'T', 'C', 0, '');
        $retVal = $sY + 2;
        if ($this->tpAmb != 1) {
            $x = 10;
            if ($this->orientacao == 'P') {
                $y = round($this->hPrint * 2 / 3, 0);
            } else {
                $y = round($this->hPrint / 2, 0);
            }
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = array('font' => $this->fontePadrao, 'size' => 48, 'style' => 'B');
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $aFont = array('font' => $this->fontePadrao, 'size' => 30, 'style' => 'B');
            $texto = "AMBIENTE DE HOMOLOGAÇÃO";
            $this->pdf->textBox($x, $y + 14, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        }
        return $retVal;
    }

    /**
     * body
     * @param integer $x
     * @param integer $y
     */
    private function body($x, $y)
    {
        $maxW = $this->wPrint;
        if ($this->tpEvento == '110114') {
            $texto = 'CONDUTOR';
        } else {
            $texto = 'JUSTIFICATIVA DO CANCELAMENTO';
        }
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pdf->textBox($x, $y, $maxW, 5, $texto, $aFont, 'T', 'L', 0, '', false);
        $y += 5;
        $this->pdf->textBox($x, $y, $maxW, 190);
        if ($this->tpEvento == '110114') {
            $maxW = ($maxW / 3);
            $this->pdf->textBox($x, $y, $maxW, 5, "CPF", $aFont, 'T', 'L', 0, '', false);
            $this->pdf->textBox($maxW, $y, $maxW, 5, "Nome", $aFont, 'T', 'L', 0, '', false);

            $aFont = array('font' => $this->fontePadrao, 'size' => 9, 'style' => '');
            $y = ($y + 5);
            //$maxW = $this->wPrint;
            $campo = $this->detEvento->item(0)->getElementsByTagName('CPF')->item(0)->nodeValue;
            $grupo = $this->detEvento->item(0)->getElementsByTagName('xNome')->item(0)->nodeValue;

            $this->pdf->textBox($x, $y, $maxW, 5, $campo, $aFont, 'T', 'L', 0, '', false);
            $this->pdf->textBox($maxW, $y, $maxW, 5, $grupo, $aFont, 'T', 'L', 0, '', false);
        } elseif ($this->tpEvento == '110111') {
            $texto = $this->xJust;
            $aFont = array('font' => $this->fontePadrao, 'size' => 12, 'style' => 'B');
            $this->pdf->textBox($x + 2, $y + 2, $maxW - 2, 150, $texto, $aFont, 'T', 'L', 0, '', false);
        }
    }

    /**
     * footer
     * @param integer $x
     * @param integer $y
     */
    private function footer($x, $y)
    {
        $w = $this->wPrint;
        $texto = '';
        if ($this->tpEvento == '110114') {
            $texto = "Este documento é uma representação gráfica da incusão de condutor e foi "
                . "impresso apenas para sua informação e não possue validade fiscal."
                . "\n A inclusão deve ser recebida e mantida em arquivo eletrônico XML e "
                . "pode ser consultada através dos Portais das SEFAZ.";
        } elseif ($this->tpEvento == '110111') {
            $texto = "Este documento é uma representação gráfica do evento de MDFe e foi "
                . "impresso apenas para sua informação e não possue validade fiscal."
                . "\n O Evento deve ser recebido e mantido em arquivo eletrônico XML e "
                . "pode ser consultada através dos Portais das SEFAZ.";
        }
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'I');
        $this->pdf->textBox($x, $y, $w, 20, $texto, $aFont, 'T', 'C', 0, '', false);
        $y = $this->hPrint - 4;
        $texto = "Impresso em  " . date('d/m/Y   H:i:s') . ' ' . $this->creditos;
        $w = $this->wPrint - 4;
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'I');
        $this->pdf->textBox($x, $y, $w, 4, $texto, $aFont, 'T', 'L', 0, '');
        $texto = $this->powered ? "Powered by NFePHP®" : '';
        $aFont = array('font' => $this->fontePadrao, 'size' => 6, 'style' => 'I');
        $this->pdf->textBox($x, $y, $w, 4, $texto, $aFont, 'T', 'R', 0, 'http://www.nfephp.org');
    }
}
