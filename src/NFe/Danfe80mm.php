<?php

namespace NFePHP\DA\NFe;

use Exception;
use InvalidArgumentException;
use NFePHP\DA\Common\DaCommon;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\NFe\Traits\Reduced\{
    Bloco1,
    Bloco2,
    Bloco3,
    Bloco4,
    Bloco5,
    Bloco6,
    Bloco7,
    Bloco8,
    Setters,
    Helper
};

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
class Danfe80mm extends DaCommon
{

    use Bloco1, Bloco2, Bloco3, Bloco4, Bloco5, Bloco6, Bloco7, Bloco8, Setters, Helper;

    protected $papel;
    protected $paperwidth = 80; //mm
    protected $descPercent = 0.38;
    protected $email = null;
    protected $xml; // string XML NFe
    protected $dom;
    protected $logomarca = ''; // path para logomarca em jpg
    protected $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";
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
    protected $pdf;
    protected $margem = 3;
    protected $hMaxLinha = 5;
    protected $hBoxLinha = 6;
    protected $hLinha = 3;
    protected $fontePadrao = 'arial';
    protected $aFont = [];
    protected $canceled = false;
    protected $submessage = null;
    protected $totalProducts;
    protected $totalDesc;
    protected $bloco1 = 11.77;
    protected $bloco2 = 31;
    protected $bloco3 = 37.05;
    protected $bloco4 = 4.37;
    protected $bloco5 = 19.38;
    protected $bloco6 = 0;
    protected $bloco7 = 15;
    protected $bloco8 = 25;
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

        $this->loadXml();
        $this->aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
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

    private function configPDF()
    {
        if (!empty($logo)) {
            $this->logomarca = $this->adjustImage($logo, true);
        }
        $tamPapelVert = $this->calculatePaperLength();

        $margSup = $this->margem;
        $margEsq = $this->margem;
        $margInf = $this->margem;
        $maxW = $this->paperwidth;
        $maxH = $tamPapelVert;
        $this->wPrint = $maxW - ($margEsq * 2);

        $this->hPrint = $maxH - $margSup - $margInf;
        $this->orientacao = 'P';
        $this->papel = [$this->paperwidth, $tamPapelVert];
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
    }

    protected function monta(
        $logo = ''
    ) {
        $this->configPDF();

        $this->pdf->textBox(
            $this->margem,
            $this->margem,
            $this->wPrint,
            $this->hPrint,
            '',
            $this->aFont,
            'T',
            'L',
            false
        );

        $this->buildBlocks();
    }

    public function buildBlocks()
    {
        $yInic = $this->margem;

        $y = $this->bloco1($yInic);     // Texto DANFE Simplificado
        $y = $this->bloco2($y);            // Código de barras e linha dig.
        $y = $this->bloco3($y);         // Dados do emitent
        $y = $this->bloco4($y);         // Tipo de emissão entrada ou saida, número, série e emissão
        $y = $this->bloco5($y);         // Dados do destinatárioa
        $y = $this->bloco6($y);         // Informação dos itens
        $y = $this->bloco7($y);            // Totais
        $y = $this->bloco8($y);         //Observações
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
        $this->ICMSTot = $this->dom->getElementsByTagName("ICMSTot")->item(0);

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
            if (!in_array($cStat, [100, 150])) {
                $this->canceled = true;
            } elseif (!empty($retEvento = $this->nfeProc->getElementsByTagName('retEvento')->item(0))) {
                $infEvento = $retEvento->getElementsByTagName('infEvento')->item(0);
                $cStat = $this->getTagValue($infEvento, "cStat");
                $tpEvento = $this->getTagValue($infEvento, "tpEvento");
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
