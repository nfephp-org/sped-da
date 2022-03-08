<?php
/*
 * Author Newton Pasqualini Filho (newtonpasqualini at gmail dot com)
 */
namespace NFePHP\DA\NFe;

use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Common\DaCommon;

class DanfeSimples extends DaCommon
{

    /**
     * Tamanho do Papel
     *
     * @var string
     */
    public $papel = 'A5';
    /**
     * XML NFe
     *
     * @var string
     */
    protected $xml;
    /**
     * mesagens de erro
     *
     * @var string
     */
    protected $errMsg = '';
    /**
     * status de erro true um erro ocorreu false sem erros
     *
     * @var boolean
     */
    protected $errStatus = false;
    /**
     * Dom Document
     *
     * @var \NFePHP\DA\Legacy\Dom
     */
    protected $dom;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $infNFe;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $ide;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $entrega;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $retirada;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $emit;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $dest;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $enderEmit;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $enderDest;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $det;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $cobr;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $dup;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $ICMSTot;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $ISSQNtot;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $transp;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $transporta;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $veicTransp;
    /**
     * Node reboque
     *
     * @var \DOMNode
     */
    protected $reboque;
    /**
     * Node infAdic
     *
     * @var \DOMNode
     */
    protected $infAdic;
    /**
     * Tipo de emissão
     *
     * @var integer
     */
    protected $tpEmis;
    /**
     * Node infProt
     *
     * @var \DOMNode
     */
    protected $infProt;
    /**
     * 1-Retrato/ 2-Paisagem
     *
     * @var integer
     */
    protected $tpImp;
    /**
     * Node compra
     *
     * @var \DOMNode
     */
    protected $compra;

    /*
     * Guarda a estrutura da NF como Array para
     * interagir de maneira nativa com os dados
     * do XML da NFe
     */
    protected $nfeArray = [];

    /**
     * __construct
     *
     * @name  __construct
     *
     * @param string $xml Conteúdo XML da NF-e (com ou sem a tag nfeProc)
     */
    public function __construct($xml, $orientacao = 'P')
    {
        $this->loadDoc($xml);
        $this->orientacao = $orientacao;
    }

    private function loadDoc($xml)
    {
        $this->xml = $xml;
        if (!empty($xml)) {
            $stdClass = simplexml_load_string($xml);
            $json = json_encode($stdClass, JSON_OBJECT_AS_ARRAY);
            $this->nfeArray = json_decode($json, JSON_OBJECT_AS_ARRAY);
            if (!isset($this->nfeArray['NFe']['infNFe']['@attributes']['Id'])) {
                throw new Exception('XML não parece ser uma NF-e!');
            }
            if ($this->nfeArray['protNFe']['infProt']['cStat'] != '100') {
                throw new Exception('NF-e não autorizada!');
            }
        }
    }

    protected function monta($logo = null)
    {
        $this->pdf = '';
        //se a orientação estiver em branco utilizar o padrão estabelecido na NF
        if (empty($this->orientacao)) {
            $this->orientacao = 'L';
        }
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        if ($this->orientacao == 'L') {
            if ($this->papel == 'A5') {
                $this->maxW = 210;
                $this->maxH = 148;
            } elseif (is_array($this->papel)) {
                $this->maxW = $this->papel[0];
                $this->maxH = $this->papel[1];
            }
        } else {
            if ($this->papel == 'A5') {
                $this->maxW = 148;
                $this->maxH = 210;
            } elseif (is_array($this->papel)) {
                $this->maxW = $this->papel[0];
                $this->maxH = $this->papel[1];
            }
        }
        //Caso a largura da etiqueta seja pequena <=110mm,
        //Definimos como pequeno, para diminuir as fontes e tamanhos das células
        if ($this->maxW <= 130) {
            $pequeno = true;
        } else {
            $pequeno = false;
        }

        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        // fixa as margens
        $this->pdf->setMargins($this->margesq, $this->margsup);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        // inicia o documento
        $this->pdf->open();
        // adiciona a primeira página
        $this->pdf->addPage($this->orientacao, $this->papel);
        $this->pdf->setLineWidth(0.1);
        $this->pdf->settextcolor(0, 0, 0);
        //Configura o pagebreak para não quebrar com 2cm do bottom.
        $this->pdf->setAutoPageBreak(true, $this->margsup);

        $volumes = [];
        $pesoL = 0.000;
        $pesoB = 0.000;
        $totalVolumes = 0;

        // Normalizar o array de volumes quando tem apenas 1 volumes
        if (!isset($this->nfeArray['NFe']['infNFe']['transp']['vol'][0])) {
            $this->nfeArray['NFe']['infNFe']['transp']['vol'] = [
                $this->nfeArray['NFe']['infNFe']['transp']['vol']
            ];
        }

        foreach ($this->nfeArray['NFe']['infNFe']['transp']['vol'] as $vol) {
            $espVolume = isset($vol['esp']) ? $vol['esp'] : 'VOLUME';
            //Caso não esteja especificado no xml, irá ser mostrado no danfe a palavra VOLUME

            if (!isset($volumes[$espVolume])) {
                $volumes[$espVolume] = 0;
            }
            
            // Caso a quantidade de volumes não esteja presente no XML, soma-se zero
            $volumes[$espVolume] += @$vol['qVol'];
            // Caso a quantidade de volumes não esteja presente no XML, soma-se zero
            $totalVolumes += @$vol['qVol'] ?: 0;
            // Caso o peso bruto não esteja presente no XML, soma-se zero
            $pesoB += @$vol['pesoB'] ?: 0;
            // Caso o peso liquido não esteja presente no XML, soma-se zero
            $pesoL += @$vol['pesoL'] ?: 0;
        }

        // LINHA 1
        $this->pdf->setFont('Arial', 'B', $pequeno ? 10 : 12);
        $this->pdf->cell(
            ($this->maxW - ($this->margesq * 2)),
            $pequeno ? 5 : 6,
            "DANFE SIMPLIFICADO - ETIQUETA",
            1,
            1,
            'C',
            1
        );

        // LINHA 2
        $dataEmissao = date('d/m/Y', strtotime("{$this->nfeArray['NFe']['infNFe']['ide']['dhEmi']}"));
        $c1 = ($this->maxW - ($this->margesq * 2)) / 4;
        $this->pdf->setFont('Arial', 'B', $pequeno ? 8 : 10);
        $this->pdf->cell($c1, $pequeno ? 4 : 5, "TIPO NF", 1, 0, 'C', 1);
        $this->pdf->setFont('Arial', '', 10);
        $this->pdf->cell(
            $c1,
            5,
            "{$this->nfeArray['NFe']['infNFe']['ide']['tpNF']} - " .
                                  ($this->nfeArray['NFe']['infNFe']['ide']['tpNF']==1 ? 'Saida':'Entrada'),
            1,
            0,
            'C',
            1
        );
        $this->pdf->setFont('Arial', 'B', $pequeno ? 8 : 10);
        $this->pdf->cell($c1, $pequeno ? 4 : 5, "DATA EMISSAO", 1, 0, 'C', 1);
        $this->pdf->setFont('Arial', '', 10);
        $this->pdf->cell($c1, $pequeno ? 4 : 5, "{$dataEmissao}", 1, 1, 'C', 1);

        // LINHA 3
        $this->pdf->setFont('Arial', 'B', $pequeno ? 8 : 10);
        $this->pdf->cell($c1, $pequeno ? 4 : 5, "NUMERO", 1, 0, 'C', 1);
        $this->pdf->setFont('Arial', '', 10);
        $this->pdf->cell($c1, $pequeno ? 4 : 5, "{$this->nfeArray['NFe']['infNFe']['ide']['nNF']}", 1, 0, 'C', 1);
        $this->pdf->setFont('Arial', 'B', $pequeno ? 8 : 10);
        $this->pdf->cell($c1, $pequeno ? 4 : 5, "SERIE", 1, 0, 'C', 1);
        $this->pdf->setFont('Arial', '', 10);
        $this->pdf->cell($c1, $pequeno ? 4 : 5, "{$this->nfeArray['NFe']['infNFe']['ide']['serie']}", 1, 1, 'C', 1);

        // LINHA 4
        $chave = substr($this->nfeArray['NFe']['infNFe']['@attributes']['Id'], 3);
        $this->pdf->setFont('Arial', 'B', $pequeno ? 7 : 10);
        $this->pdf->cell($c1, $pequeno ? 4 : 5, "CHAVE DE ACESSO", 1, 0, 'C', 1);
        $this->pdf->setFont('Arial', '', $pequeno ? 8 : 10);
        $this->pdf->cell(($c1 * 3), $pequeno ? 4 : 5, "{$chave}", 1, 1, 'C', 1);

        // LINHA 5
        $this->pdf->setFont('Arial', 'B', $pequeno ? 8 : 10);
        $this->pdf->cell($c1, $pequeno ? 4 : 5, "PROTOCOLO", 1, 0, 'C', 1);
        $this->pdf->setFont('Arial', '', 10);
        $dataProto = date("d/m/Y H:i:s", strtotime($this->nfeArray['protNFe']['infProt']['dhRecbto']));
        $this->pdf->cell(
            ($c1 * 3),
            $pequeno ? 4 : 5,
            "{$this->nfeArray['protNFe']['infProt']['nProt']} - {$dataProto}",
            1,
            1,
            'C',
            1
        );

        $this->pdf->ln();
        $y = $this->pdf->getY();
        $this->pdf->setFillColor(0, 0, 0);
        if ($pequeno) {
            //caso seja etiqueta pequena, aumenta o code128 para
            //que uma impressora de 203dpi consiga imprimir um código legível
            $this->pdf->code128($this->margesq * 2, $y, $chave, ($this->maxW - $this->margesq * 4), 15);
        } else {
            $this->pdf->code128(($c1/2), $y, $chave, ($c1 * 3), 15);
        }
        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->ln();
        $this->pdf->ln();
        $this->pdf->ln();
        $this->pdf->ln();
        $this->pdf->ln();

        // LINHA 6
        $this->pdf->setFont('Arial', 'B', $pequeno ? 10 : 12);
        $this->pdf->cell(($c1 * 4), $pequeno ? 5 : 6, "EMITENTE", 1, 1, 'C', 1);

        // LINHA 7
        $this->pdf->setFont('Arial', '', $pequeno ? 9 : 10);
        $this->pdf->multiCell(
            ($c1 * 4),
            $pequeno ? 4 : 5,
            "{$this->nfeArray['NFe']['infNFe']['emit']['xNome']}",
            1,
            'C',
            false
        );

        // LINHA 8
        $cpfCnpj = (isset($this->nfeArray['NFe']['infNFe']['emit']['CNPJ'])
            ? $this->nfeArray['NFe']['infNFe']['emit']['CNPJ']
            :$this->nfeArray['NFe']['infNFe']['emit']['CPF']);
        $this->pdf->cell(($c1 * 2), $pequeno ? 4 : 5, "CNPJ/CPF {$cpfCnpj}", 1, 0, 'C', 1);
        $this->pdf->cell(
            ($c1 * 2),
            $pequeno ? 4 : 5,
            @"RG/IE {$this->nfeArray['NFe']['infNFe']['emit']['IE']}",
            1,
            1,
            'C',
            1
        );

        $enderecoEmit  = "{$this->nfeArray['NFe']['infNFe']['emit']['enderEmit']['xMun']}"
                       . " / {$this->nfeArray['NFe']['infNFe']['emit']['enderEmit']['UF']}"
                       . " - CEP {$this->nfeArray['NFe']['infNFe']['emit']['enderEmit']['CEP']}";

        // LINHA 9
        $this->pdf->setFont('Arial', '', $pequeno ? 9 : 10);
        $this->pdf->cell(($c1 * 4), $pequeno ? 4 : 5, "{$enderecoEmit}", 1, 1, 'C', 1);

        // LINHA 10
        $this->pdf->setFont('Arial', 'B', $pequeno ? 10 : 12);
        $this->pdf->cell(($c1 * 4), $pequeno ? 5 : 6, "DESTINATARIO", 1, 1, 'C', 1);

        // LINHA 11
        $this->pdf->setFont('Arial', '', $pequeno ? 9 : 10);
        $this->pdf->multiCell(
            ($c1 * 4),
            $pequeno ? 4 : 5,
            "{$this->nfeArray['NFe']['infNFe']['dest']['xNome']}",
            1,
            'C',
            false
        );

        // LINHA 12
        $cpfCnpj = (isset($this->nfeArray['NFe']['infNFe']['dest']['CNPJ'])
            ? $this->nfeArray['NFe']['infNFe']['dest']['CNPJ']
            :$this->nfeArray['NFe']['infNFe']['dest']['CPF']);
        $this->pdf->cell(($c1 * 2), $pequeno ? 4 : 5, "CNPJ/CPF {$cpfCnpj}", 1, 0, 'C', 1);
        $this->pdf->cell(
            ($c1 * 2),
            $pequeno ? 4 : 5,
            @"RG/IE {$this->nfeArray['NFe']['infNFe']['dest']['IE']}",
            1,
            1,
            'C',
            1
        );

        if (isset($this->nfeArray['NFe']['infNFe']['entrega'])) {
            $enderecoLinha1 = "{$this->nfeArray['NFe']['infNFe']['entrega']['xLgr']}";
            if (!empty($this->nfeArray['NFe']['infNFe']['entrega']['nro'])) {
                $enderecoLinha1 .= ", {$this->nfeArray['NFe']['infNFe']['entrega']['nro']}";
            }
            $enderecoLinha2 = '';
            if (!empty($this->nfeArray['NFe']['infNFe']['entrega']['xCpl'])) {
                $enderecoLinha2 .= "{$this->nfeArray['NFe']['infNFe']['entrega']['xCpl']} - ";
            }
            $enderecoLinha2 .= "{$this->nfeArray['NFe']['infNFe']['entrega']['xMun']}"
                             . " / {$this->nfeArray['NFe']['infNFe']['entrega']['UF']}"
                             . " - CEP {$this->nfeArray['NFe']['infNFe']['entrega']['CEP']}";
        } else {
            $enderecoLinha1 = "{$this->nfeArray['NFe']['infNFe']['dest']['enderDest']['xLgr']}";
            if (!empty($this->nfeArray['NFe']['infNFe']['dest']['enderDest']['nro'])) {
                $enderecoLinha1 .= ", {$this->nfeArray['NFe']['infNFe']['dest']['enderDest']['nro']}";
            }
            $enderecoLinha2 = '';
            if (!empty($this->nfeArray['NFe']['infNFe']['dest']['enderDest']['xCpl'])) {
                $enderecoLinha2 .= "{$this->nfeArray['NFe']['infNFe']['dest']['enderDest']['xCpl']} - ";
            }
            $enderecoLinha2 .= "{$this->nfeArray['NFe']['infNFe']['dest']['enderDest']['xMun']}"
                             . " / {$this->nfeArray['NFe']['infNFe']['dest']['enderDest']['UF']}"
                             . " - CEP {$this->nfeArray['NFe']['infNFe']['dest']['enderDest']['CEP']}";
        }

        $this->pdf->setFont('Arial', '', $pequeno ? 9 : 10);
        $this->pdf->cell(($c1 * 4), $pequeno ? 4 : 5, "{$enderecoLinha1}", 1, 1, 'C', 1);

        $this->pdf->setFont('Arial', '', $pequeno ? 9 : 10);
        $this->pdf->cell(($c1 * 4), $pequeno ? 4 : 5, "{$enderecoLinha2}", 1, 1, 'C', 1);

        if ($this->nfeArray['NFe']['infNFe']['transp']['modFrete'] != 9
            && isset($this->nfeArray['NFe']['infNFe']['transp']['transporta'])
        ) {
            $this->pdf->setFont('Arial', 'B', $pequeno ? 10 : 12);
            $this->pdf->cell(($c1 * 4), $pequeno ? 5 : 6, "TRANSPORTADORA", 1, 1, 'C', 1);
            $this->pdf->setFont('Arial', '', $pequeno ? 9 : 10);
            $this->pdf->cell(
                ($c1 * 4),
                $pequeno ? 5 : 6,
                "{$this->nfeArray['NFe']['infNFe']['transp']['transporta']['xNome']}",
                1,
                1,
                'C',
                1
            );
        }

        if ($totalVolumes > 0) {
            foreach ($volumes as $esp => $qVol) {
                $this->pdf->cell(
                    ($c1 * 4),
                    $pequeno ? 5 : 6,
                    "{$esp} x {$qVol}",
                    1,
                    1,
                    'C',
                    1
                );
            }
        }

        $pesoL = number_format($pesoL, 3, ',', '.');
        $pesoB = number_format($pesoB, 3, ',', '.');

        $this->pdf->cell(
            ($c1 * 4),
            $pequeno ? 5 : 6,
            "PESO LIQ {$pesoL} / PESO BRT {$pesoB}",
            1,
            1,
            'C',
            1
        );

        $this->pdf->setFont('Arial', 'B', $pequeno ? 10 : 12);
        $this->pdf->cell(($c1 * 2), $pequeno ? 5 : 6, "TOTAL DA NF-e", 1, 0, 'C', 1);
        $this->pdf->setFont('Arial', '', $pequeno ? 8 : 10);
        $vNF = number_format($this->nfeArray['NFe']['infNFe']['total']['ICMSTot']['vNF'], 2, ',', '.');
        $this->pdf->cell(($c1 * 2), $pequeno ? 5 : 6, "R$ {$vNF}", 1, 1, 'C', 1);

        if (isset($this->nfeArray['NFe']['infNFe']['infAdic'])) {
            $this->pdf->setFont('Arial', 'B', $pequeno ? 10 : 12);
            $this->pdf->cell(($c1 * 4), $pequeno ? 5 : 6, "DADOS ADICIONAIS", 1, 1, 'C', 1);
            $this->pdf->setFont('Arial', '', $pequeno ? 8 : 10);
            $this->pdf->multiCell(
                ($c1 * 4),
                $pequeno ? 3 : 5,
                "{$this->nfeArray['NFe']['infNFe']['infAdic']['infCpl']}",
                1,
                1,
                'J',
                1
            );
        }
    }
}
