<?php

namespace NFePHP\DA\CTe;

/**
 * Esta classe gera a carta de correção em PDF
 * NOTA: Esse documento NÃO É NORMALIZADO, nem requerido pela SEFAZ
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @name      Dacce.php
 * @copyright 2009-2019 NFePHP
 * @license   http://www.gnu.org/licenses/lgpl.html GNU/LGPL v.3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Roberto L. Machado <linux.rlm at gmail dot com>
 */
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;

class Dacce extends Common
{

    public $chCTe;
    protected $logoAlign = 'C';
    protected $yDados = 0;
    protected $debugmode = false;
    protected $dadosEmitente = array();
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
    protected $version = '0.1.1';
    protected $wPrint;
    protected $hPrint;
    protected $wCanhoto;
    protected $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";
    protected $id;
    protected $tpAmb;
    protected $cOrgao;
    protected $infCorrecao;
    protected $xCondUso;
    protected $dhEvento;
    protected $cStat;
    protected $xMotivo;
    protected $dhRegEvento;
    protected $nProt;
    protected $creditos;

    private $dom;
    private $procEventoCTe;
    private $eventoCTe;
    private $infEvento;
    private $retEventoCTe;
    private $retInfEvento;

    /**
     * __construct
     *
     * @param string $docXML      Arquivo XML (diretório ou string)
     * @param string $sOrientacao (Opcional) Orientação da impressão P-retrato L-Paisagem
     * @param string $sPapel      Tamanho do papel (Ex. A4)
     * @param string $sPathLogo   Caminho para o arquivo do logo
     * @param string $sDestino    Destino do PDF I-browser D-download S-string F-salva
     * @param array  $aEnd        array com o endereço do emitente
     * @param string $sDirPDF     Caminho para o diretorio de armazenamento dos arquivos PDF
     * @param string $fonteDANFE  Nome da fonte alternativa do DAnfe
     * @param number $mododebug   0-Não 1-Sim e 2-nada (2 default)
     */
    public function __construct($xml, $dadosEmitente)
    {
        $this->dadosEmitente = $dadosEmitente;
        $this->debugMode();
        $this->loadDoc($xml);
    }

    /**
     * Ativa ou desativa o modo debug
     * @param bool $activate
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

    /**
     * Add the credits to the integrator in the footer message
     * @param string $message
     */
    public function creditsIntegratorFooter($message = '')
    {
        $this->creditos = trim($message);
    }

    /**
     * Dados brutos do PDF
     * @return string
     */
    public function render()
    {
        if ($this->pdf == null) {
            $this->buildDACCE();
        }
        return $this->pdf->getPdf();
    }

    protected function loadDoc($xml)
    {
        $this->xml = $xml;
        $this->dom = new Dom();
        $this->dom->loadXML($this->xml);
        if (empty($this->dom->getElementsByTagName("eventoCTe")->item(0))) {
            throw new \Exception("Este xml não é um evento do CTe");
        }
        $this->procEventoCTe = $this->dom->getElementsByTagName("procEventoCTe")->item(0);
        $this->eventoCTe = $this->procEventoCTe->getElementsByTagName("eventoCTe")->item(0);
        $this->retEventoCTe = $this->procEventoCTe->getElementsByTagName("retEventoCTe")->item(0);
        $this->infEvento = $this->eventoCTe->getElementsByTagName("infEvento")->item(0);
        $this->retInfEvento = $this->retEventoCTe->getElementsByTagName("infEvento")->item(0);
        $tpEvento = $this->infEvento->getElementsByTagName("tpEvento")->item(0)->nodeValue;
        if ($tpEvento != '110110') {
            throw new \Exception('Um evento de CC-e deve ser passado.');
        }
        $this->id = str_replace('ID', '', $this->infEvento->getAttribute("Id"));
        $this->chCTe = $this->infEvento->getElementsByTagName("chCTe")->item(0)->nodeValue;
        $this->tpAmb = $this->infEvento->getElementsByTagName("tpAmb")->item(0)->nodeValue;
        $this->cOrgao = $this->infEvento->getElementsByTagName("cOrgao")->item(0)->nodeValue;
        $this->infCorrecao = $this->infEvento->getElementsByTagName("infCorrecao");
        $this->xCondUso = $this->infEvento->getElementsByTagName("xCondUso")->item(0)->nodeValue;
        $this->dhEvento = $this->infEvento->getElementsByTagName("dhEvento")->item(0)->nodeValue;
        $this->cStat = $this->retInfEvento->getElementsByTagName("cStat")->item(0)->nodeValue;
        $this->xMotivo = $this->retInfEvento->getElementsByTagName("xMotivo")->item(0)->nodeValue;
        $this->CNPJDest = !empty($this->retInfEvento->getElementsByTagName("CNPJDest")->item(0)->nodeValue) ?
            $this->retInfEvento->getElementsByTagName("CNPJDest")->item(0)->nodeValue : '';
        $this->CPFDest = !empty($this->retInfEvento->getElementsByTagName("CPFDest")->item(0)->nodeValue) ?
            $this->retInfEvento->getElementsByTagName("CPFDest")->item(0)->nodeValue : '';
        $this->dhRegEvento = $this->retInfEvento->getElementsByTagName("dhRegEvento")->item(0)->nodeValue;
        $this->nProt = $this->retInfEvento->getElementsByTagName("nProt")->item(0)->nodeValue;
    }

    /**
     * monta
     *
     * @param string $logo
     * @param string $orientacao
     * @param string $papel
     * @param string $logoAlign
     */
    public function monta($logo = '', $orientacao = 'P', $papel = 'A4', $logoAlign = 'C')
    {
        $this->orientacao = $orientacao;
        $this->papel = $papel;
        $this->logoAlign = $logoAlign;
        $this->logomarca = $logo;
        $this->buildDACCE();
    }

    /**
     * buildDACCE
     */
    private function buildDACCE()
    {
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        if ($this->orientacao == 'P') {
            $margSup = 2;
            $margEsq = 2;
            $margDir = 2;
            $xInic = 1;
            $yInic = 1;
            if ($this->papel == 'A4') { // A4 210x297mm
                $maxW = 210;
                $maxH = 297;
            }
        } else {
            $margSup = 3;
            $margEsq = 3;
            $margDir = 3;
            $xInic = 5;
            $yInic = 5;
            if ($this->papel == 'A4') { // A4 210x297mm
                $maxH = 210;
                $maxW = 297;
            }
        }
        // largura imprimivel em mm
        $this->wPrint = $maxW - ($margEsq + $xInic);
        // comprimento imprimivel em mm
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
        // montagem da página
        $pag = 1;
        $x = $xInic;
        $y = $yInic;
        // coloca o cabeçalho
        $y = $this->header($x, $y, $pag);
        // coloca os dados da CCe
        $y = $this->body($x, $y + 15);
        // coloca o rodapé
        $y = $this->footer($x, $y + $this->hPrint - 20);
    }

    /**
     * header
     *
     * @param  number $x
     * @param  number $y
     * @param  number $pag
     * @return number
     */
    private function header($x, $y, $pag)
    {
        $oldX = $x;
        $oldY = $y;
        $maxW = $this->wPrint;
        $w = round($maxW * 0.41, 0); // 80;
        if ($this->orientacao == 'P') {
            $aFont = array(
                'font'  => $this->fontePadrao,
                'size'  => 6,
                'style' => 'I'
            );
        } else {
            $aFont = array(
                'font'  => $this->fontePadrao,
                'size'  => 8,
                'style' => 'B'
            );
        }
        $w1 = $w;
        $h = 32;
        $oldY += $h;
        $this->pdf->textBox($x, $y, $w, $h);
        $texto = 'IDENTIFICAÇÃO DO EMITENTE';
        $this->pdf->textBox($x, $y, $w, 5, $texto, $aFont, 'T', 'C', 0, '');
        if (!empty($this->logomarca)) {
            $logoInfo = getimagesize($this->logomarca);
            // largura da imagem em mm
            $logoWmm = ($logoInfo[0] / 72) * 25.4;
            $logoHmm = ($logoInfo[1] / 72) * 25.4;
            if ($this->logoAlign == 'L') {
                $nImgW = round($w / 3, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg = $x + 1;
                $yImg = round(($h - $nImgH) / 2, 0) + $y;
                // estabelecer posições do texto
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
            $this->pdf->Image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $x1 = $x;
            $y1 = round($h / 3 + $y, 0);
            $tw = $w;
        }
        // Nome emitente
        $aFont = ['font' => $this->fontePadrao, 'size' => 12, 'style' => 'B'];
        $texto = $this->dadosEmitente['razao'];
        $this->pdf->textBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        // endereço
        $y1 = $y1 + 6;
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $lgr = $this->dadosEmitente['logradouro'];
        $nro = $this->dadosEmitente['numero'];
        $cpl = $this->dadosEmitente['complemento'];
        $bairro = $this->dadosEmitente['bairro'];
        $CEP = $this->dadosEmitente['CEP'];
        // $CEP = $this->formatField($CEP, "#####-###");
        $mun = $this->dadosEmitente['municipio'];
        $UF = $this->dadosEmitente['UF'];
        $fone = $this->dadosEmitente['telefone'];
        $email = $this->dadosEmitente['email'];
        if ($email != '') {
            $email = 'Email: ' . $email;
        }
        $texto = $lgr . ", " . $nro . $cpl . "\n" . $bairro . " - " . $CEP . "\n"
            . $mun . " - " . $UF . " - " . $fone . "\n" . $email;
        $this->pdf->textBox($x1, $y1 - 2, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');

        $w2 = round($maxW - $w, 0);
        $x += $w;
        $this->pdf->textBox($x, $y, $w2, $h);
        $y1 = $y + $h;
        $aFont = ['font' => $this->fontePadrao, 'size' => 16, 'style' => 'B'];
        $this->pdf->textBox($x, $y + 2, $w2, 8, 'Representação Gráfica de CC-e', $aFont, 'T', 'C', 0, '');
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 12,
            'style' => 'I'
        );
        $this->pdf->textBox($x, $y + 7, $w2, 8, '(Carta de Correção Eletrônica)', $aFont, 'T', 'C', 0, '');
        $texto = 'ID do Evento: ' . $this->id;
        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => ''];
        $this->pdf->textBox($x, $y + 15, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $tsHora = $this->toTimestamp($this->dhEvento);
        $texto = 'Criado em : ' . date('d/m/Y H:i:s', $tsHora);
        $this->pdf->textBox($x, $y + 20, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $tsHora = $this->toTimestamp($this->dhRegEvento);
        $texto = 'Prococolo: ' . $this->nProt . '  -  Registrado na SEFAZ em: ' . date('d/m/Y   H:i:s', $tsHora);
        $this->pdf->textBox($x, $y + 25, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');

        $x = $oldX;
        $this->pdf->textBox($x, $y1, $maxW, 40);
        $sY = $y1 + 40;
        $texto = 'De acordo com as determinações legais vigentes, vimos por meio desta comunicar-lhe' .
            ' que o Conhecimento, abaixo referenciada, contêm irregularidades que estão destacadas e' .
            ' suas respectivas correções, solicitamos que sejam aplicadas essas correções ao executar' .
            ' seus lançamentos fiscais.';
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 10,
            'style' => ''
        );
        $this->pdf->textBox($x + 5, $y1, $maxW - 5, 20, $texto, $aFont, 'T', 'L', 0, '', false);
        $x = $oldX;
        $y = $y1;
        if ($this->CNPJDest != '') {
            $texto = 'CNPJ do Destinatário: ' . $this->formatField($this->CNPJDest, "##.###.###/####-##");
        }
        if ($this->CPFDest != '') {
            $texto = 'CPF do Destinatário: ' . $this->formatField($this->CPFDest, "###.###.###-##");
        }
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 12,
            'style' => 'B'
        );
        $numNF = substr($this->chCTe, 25, 9);
        $serie = substr($this->chCTe, 22, 3);
        $numNF = $this->formatField($numNF, "###.###.###");
        $texto = "Conhecimento: " . $numNF . '  -   Série: ' . $serie;
        $this->pdf->textBox($x + 2, $y + 19, $w2, 8, $texto, $aFont, 'T', 'L', 0, '');
        $bW = 87;
        $bH = 15;
        $x = 55;
        $y = $y1 + 13;
        $w = $maxW;
        $this->pdf->setFillColor(0, 0, 0);
        $this->pdf->code128($x + (($w - $bW) / 2), $y + 2, $this->chCTe, $bW, $bH);
        $this->pdf->setFillColor(255, 255, 255);
        $y1 = $y + 2 + $bH;
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 10,
            'style' => ''
        );
        $texto = $this->formatField($this->chCTe, $this->formatoChave);
        $this->pdf->textBox($x, $y1, $w - 2, $h, $texto, $aFont, 'T', 'C', 0, '');
        $x = $oldX;
        $this->pdf->textBox($x, $sY, $maxW, 15);
        $texto = $this->xCondUso;
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 8,
            'style' => 'I'
        );
        $this->pdf->textBox($x + 2, $sY + 2, $maxW - 2, 15, $texto, $aFont, 'T', 'L', 0, '', false);
        return $sY + 2;
    }

    /**
     * body
     *
     * @param number $x
     * @param number $y
     */
    private function body($x, $y)
    {
        if ($this->orientacao == 'P') {
            $maxH = 190;
        } else {
            $maxH = 95;
        }
        $maxW = $this->wPrint;
        $texto = 'CORREÇÕES A SEREM CONSIDERADAS';
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 10,
            'style' => 'B'
        );
        $this->pdf->textBox($x, $y, $maxW, 5, $texto, $aFont, 'T', 'L', 0, '', false);
        $y += 5;
        $this->pdf->textBox($x, $y, $maxW, $maxH);
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 9,
            'style' => 'B'
        );
        $this->pdf->textBox($x, $y, $maxW = ($maxW / 5), 5, "Grupo", $aFont, 'T', 'C', 0, '', false);
        $this->pdf->textBox($x = $maxW, $y, $maxW, 5, "Campo", $aFont, 'T', 'C', 0, '', false);
        $this->pdf->textBox($x = ($maxW * 2), $y, $maxW, 5, "Número", $aFont, 'T', 'C', 0, '', false);
        $this->pdf->textBox($x = ($maxW * 3), $y, ($this->wPrint - $x), 5, "Valor", $aFont, 'T', 'C', 0, '', false);
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 9,
            'style' => ''
        );
        $i = 0;
        $numlinhas = 1;
        while ($i < $this->infCorrecao->length) {
            $x = 0;
            $y = $numlinhas == 1 ? ($y + 5) : ($y + (5 * $numlinhas));
            $maxW = $this->wPrint;
            $grupo = $this->infCorrecao->item($i)->getElementsByTagName('grupoAlterado')->item(0)->nodeValue;
            $campo = $this->infCorrecao->item($i)->getElementsByTagName('campoAlterado')->item(0)->nodeValue;
            $numero = $this->infCorrecao->item($i)->getElementsByTagName('nroItemAlterado')->item(0)->nodeValue;
            $valor = $this->infCorrecao->item($i)->getElementsByTagName('valorAlterado')->item(0)->nodeValue;

            $i++;
            $this->pdf->textBox($x, $y, $maxW = ($maxW / 5), 5, $grupo, $aFont, 'T', 'C', 0, '', false);
            $this->pdf->textBox($x = $maxW, $y, $maxW, 5, $campo, $aFont, 'T', 'C', 0, '', false);
            $this->pdf->textBox($x = ($maxW * 2), $y, $maxW, 5, $numero, $aFont, 'T', 'C', 0, '', false);
            $this->pdf->textBox($x = ($maxW * 3), $y, ($this->wPrint - $x), 5, $valor, $aFont, 'T', 'C', 0, '', false);
            $numlinhas = $this->pdf->getNumLines($valor, ($this->wPrint - $x), $aFont);
        }
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 12,
            'style' => 'B'
        );
        $maxW = $this->wPrint;
        if ($this->tpAmb != 1) {
            $x = 10;
            if ($this->orientacao == 'P') {
                $y = round($this->hPrint * 2 / 3, 0);
            } else {
                $y = round($this->hPrint * 2 / 3, 0);
            }
            $h = 5;
            $w = $maxW - (2 * $x);
            $this->pdf->setTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = array(
                'font'  => $this->fontePadrao,
                'size'  => 48,
                'style' => 'B'
            );
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $aFont = array(
                'font'  => $this->fontePadrao,
                'size'  => 30,
                'style' => 'B'
            );
            $texto = "AMBIENTE DE HOMOLOGAÇÃO";
            $this->pdf->textBox($x, $y + 14, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        }
    }

    /**
     * footer
     *
     * @param number $x
     * @param number $y
     */
    protected function footer($x, $y)
    {
        $w = $this->wPrint;
        $texto = "Este documento é uma representação gráfica da CC-e e foi impresso apenas para sua"
            . " informação e não possue validade fiscal.\n A CC-e deve ser recebida e mantida em"
            . " arquivo eletrônico XML e pode ser consultada através dos Portais das SEFAZ.";
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 10,
            'style' => 'I'
        );
        $this->pdf->textBox($x, $y, $w, 20, $texto, $aFont, 'T', 'C', 0, '', false);
        $y = $this->hPrint - 4;
        $texto = "Impresso em  " . date('d/m/Y   H:i:s') . " - " . $this->creditos;
        $w = $this->wPrint - 4;
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 6,
            'style' => 'I'
        );
        $this->pdf->textBox($x, $y, $w, 4, $texto, $aFont, 'T', 'L', 0, '');
        $texto = "Powered by NFePHP®";
        $aFont = array(
            'font'  => $this->fontePadrao,
            'size'  => 6,
            'style' => 'I'
        );
        $this->pdf->textBox($x, $y, $w, 4, $texto, $aFont, 'T', 'R', 0, '');
    }
}
