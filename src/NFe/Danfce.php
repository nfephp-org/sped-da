<?php

namespace NFePHP\DA\NFe;

/**
 * Classe para a impressão em PDF do Documento Auxiliar de NFe Consumidor
 * NOTA: Esta classe não é a indicada para quem faz uso de impressoras térmicas ESCPOS
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @copyright 2009-2016 NFePHP
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Roberto Spadim <roberto at spadim dot com dot br>
 */
use Exception;
use InvalidArgumentException;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;
use Com\Tecnick\Barcode\Barcode;
use DateTime;

class Danfce extends Common
{
    protected $papel;
    protected $xml; // string XML NFe
    protected $logomarca=''; // path para logomarca em jpg
    protected $formatoChave="#### #### #### #### #### #### #### #### #### #### ####";
    protected $debugMode=0; //ativa ou desativa o modo de debug
    protected $tpImp; //ambiente
    protected $fontePadrao='Times';
    protected $nfeProc;
    protected $nfe;
    protected $infNFe;
    protected $ide;
    protected $enderDest;
    protected $ICMSTot;
    protected $imposto;
    protected $emit;
    protected $enderEmit;
    protected $qrCode;
    protected $det;
    protected $infAdic;
    protected $textoAdic;
    protected $pag;
    protected $vTroco;
    protected $dest;
    protected $imgQRCode;
    protected $urlQR = '';
    protected $pdf;
    protected $margemInterna = 2;
    protected $hMaxLinha = 9;
    protected $hBoxLinha = 6;
    protected $hLinha = 3;
    /*
     * Retorna a sigla da UF
     * @var string
     */
    protected $UFSigla = [
        '12' => 'AC',
        '27' => 'AL',
        '13' => 'AM',
        '16' => 'AP',
        '29' => 'BA',
        '23' => 'CE',
        '53' => 'DF',
        '32' => 'ES',
        '52' => 'GO',
        '31' => 'MG',
        '50' => 'MS',
        '51' => 'MT',
        '21' => 'MA',
        '15' => 'PA',
        '25' => 'PB',
        '26' => 'PE',
        '22' => 'PI',
        '41' => 'PR',
        '33' => 'RJ',
        '11' => 'RO',
        '24' => 'RN',
        '14' => 'RR',
        '43' => 'RS',
        '42' => 'SC',
        '28' => 'SE',
        '35' => 'SP',
        '17' => 'TO'
    ];
    /*
     * Fonte: http://nfce.encat.org/consumidor/consulte-sua-nota/
     * URL referente a pagina de consulta da NFCe pela chave de acesso
     * @var string
     */
    protected $urlConsulta = [
        '1' => [
            'AC' => 'www.sefaznet.ac.gov.br/nfce/consulta',
            'AL' => 'http://nfce.sefaz.al.gov.br/consultaNFCe.htm',
            'AP' => 'https://www.sefaz.ap.gov.br/sate/seg/SEGf_AcessarFuncao.jsp?cdFuncao=FIS_1261',
            'AM' => 'sistemas.sefaz.am.gov.br/nfceweb/formConsulta.do',
            'BA' => 'nfe.sefaz.ba.gov.br/servicos/nfce/default.aspx',
            'CE' => '',
            'DF' => 'http://dec.fazenda.df.gov.br/NFCE/',
            'ES' => 'http://app.sefaz.es.gov.br/ConsultaNFCe',
            'GO' => '',
            'MA' => 'http://www.nfce.sefaz.ma.gov.br/portal/consultaNFe.do?method=preFilterCupom&',
            'MT' => 'http://www.sefaz.mt.gov.br/nfce/consultanfce',
            'MS' => 'http://www.dfe.ms.gov.br/nfce',
            'MG' => '',
            'PA' => 'https://appnfc.sefa.pa.gov.br/portal/view/consultas/nfce/consultanfce.seam',
            'PB' => 'www.receita.pb.gov.br/nfce',
            'PR' => 'http://www.fazenda.pr.gov.br',
            'PE' => '',
            'PI' => 'http://webas.sefaz.pi.gov.br/nfceweb/consultarNFCe.jsf',
            'RJ' => 'www.nfce.fazenda.rj.gov.br/consulta',
            'RN' => 'http://nfce.set.rn.gov.br/consultarNFCe.aspx',
            'RS' => 'https://www.sefaz.rs.gov.br/NFCE/NFCE-COM.aspx',
            'RO' => 'http://www.nfce.sefin.ro.gov.br',
            'RR' => 'https://www.sefaz.rr.gov.br/nfce/servlet/wp_consulta_nfce',
            'SC' => '',
            'SP' => 'https://www.nfce.fazenda.sp.gov.br/NFCeConsultaPublica/Paginas/ConsultaPublica.aspx',
            'SE' => 'http://www.nfce.se.gov.br/portal/portalNoticias.jsp',
            'TO' => ''
        ],
        '2' => [
            'AC' => 'http://hml.sefaznet.ac.gov.br/nfce/consulta',
            'AL' => 'http://nfce.sefaz.al.gov.br/consultaNFCe.htm',
            'AP' => 'https://www.sefaz.ap.gov.br/sate1/seg/SEGf_AcessarFuncao.jsp?cdFuncao=FIS_1261',
            'AM' => 'homnfce.sefaz.am.gov.br/nfceweb/formConsulta.do',
            'BA' => 'http://hnfe.sefaz.ba.gov.br/servicos/nfce/default.aspx',
            'CE' => 'http://nfceh.sefaz.ce.gov.br/pages/consultaNota.jsf',
            'DF' => 'http://dec.fazenda.df.gov.br/NFCE/',
            'ES' => 'http://homologacao.sefaz.es.gov.br/ConsultaNFCe',
            'GO' => '',
            'MA' => 'http://www.hom.nfce.sefaz.ma.gov.br/portal/consultarNFCe.jsp',
            'MT' => 'http://homologacao.sefaz.mt.gov.br/nfce/consultanfce',
            'MS' => 'http://www.dfe.ms.gov.br/nfce',
            'MG' => '',
            'PA' => 'https://appnfc.sefa.pa.gov.br/portal-homologacao/view/consultas/nfce/consultanfce.seam',
            'PB' => '',
            'PR' => 'http://www.fazenda.pr.gov.br',
            'PE' => '',
            'PI' => 'http://webas.sefaz.pi.gov.br/nfceweb-homologacao/consultarNFCe.jsf',
            'RJ' => 'www.nfce.fazenda.rj.gov.br/consulta',
            'RN' => 'http://nfce.set.rn.gov.br/consultarNFCe.aspx',
            'RS' => 'https://www.sefaz.rs.gov.br/NFCE/NFCE-COM.aspx',
            'RO' => 'http://www.nfce.sefin.ro.gov.br',
            'RR' => 'http://200.174.88.103:8080/nfce/servlet/wp_consulta_nfce',
            'SC' => '',
            'SP' => 'https://www.homologacao.nfce.fazenda.sp.gov.br/NFCeConsultaPublica/Paginas/ConsultaPublica.aspx',
            'SE' => 'http://www.hom.nfe.se.gov.br/portal/portalNoticias.jsp',
            'TO' => ''
        ],
    ];
    /**
     * __contruct
     *
     * @param string $docXML
     * @param string $sPathLogo
     * @param string $mododebug
     * @param string $idToken
     * @param string $Token
     */
    public function __construct(
        $docXML = '',
        $sPathLogo = '',
        $mododebug = 0,
        // habilita os erros do sistema
        $idToken = '',
        $emitToken = '',
        $urlQR = ''
    ) {
        if (is_numeric($mododebug)) {
            $this->debugMode = $mododebug;
        }
        if ($this->debugMode) {
            //ativar modo debug
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            //desativar modo debug
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
        $this->xml = $docXML;
        $this->logomarca = $sPathLogo;
        if (empty($fonteDANFE)) {
            $this->fontePadrao = 'Times';
        } else {
            $this->fontePadrao = $fonteDANFE;
        }
        if (!empty($this->xml)) {
            $this->dom = new Dom();
            $this->dom->loadXML($this->xml);
            $this->nfeProc    = $this->dom->getElementsByTagName("nfeProc")->item(0);
            $this->nfe        = $this->dom->getElementsByTagName("NFe")->item(0);
            $this->infNFe     = $this->dom->getElementsByTagName("infNFe")->item(0);
            $this->ide        = $this->dom->getElementsByTagName("ide")->item(0);
            $this->emit       = $this->dom->getElementsByTagName("emit")->item(0);
            $this->enderEmit  = $this->dom->getElementsByTagName("enderEmit")->item(0);
            $this->det        = $this->dom->getElementsByTagName("det");
            $this->dest       = $this->dom->getElementsByTagName("dest")->item(0);
            $this->imposto    = $this->dom->getElementsByTagName("imposto")->item(0);
            $this->ICMSTot    = $this->dom->getElementsByTagName("ICMSTot")->item(0);
            $this->tpImp      = $this->ide->getElementsByTagName("tpImp")->item(0)->nodeValue;
            $this->infAdic    = $this->dom->getElementsByTagName("infAdic")->item(0);
            
            //se for o layout 4.0 busca pelas tags de detalhe do pagamento
            //senao, busca pelas tags de pagamento principal
            if ($this->infNFe->getAttribute("versao") == "4.00") {
                $this->pag = $this->dom->getElementsByTagName("detPag");
                
                $tagPag = $this->dom->getElementsByTagName("pag")->item(0);
                $this->vTroco = $this->getTagValue($tagPag, "vTroco");
            } else {
                $this->pag = $this->dom->getElementsByTagName("pag");
            }
        }
        $this->qrCode = $this->dom->getElementsByTagName('qrCode')->item(0)->nodeValue;
        if ($this->getTagValue($this->ide, "mod") != '65') {
            throw new InvalidArgumentException("O xml do DANFE deve ser uma NFC-e modelo 65");
        }
    }
    
    public function getPapel()
    {
        return $this->papel;
    }
    
    public function setPapel($aPap)
    {
        $this->papel = $aPap;
    }
    
    public function monta(
        $orientacao = 'P',
        $papel = '',
        $logoAlign = 'C',
        $classPdf = false,
        $depecNumReg = ''
    ) {
        $this->montaDANFE($orientacao, $papel, $logoAlign, $classPdf, $depecNumReg);
    }
    
    public function montaDANFE(
        $orientacao = 'P',
        $papel = '',
        $logoAlign = 'C',
        $classPdf = false,
        $depecNumReg = ''
    ) {
        $qtdItens = $this->det->length;
        $qtdPgto = $this->pag->length;
        $hMaxLinha = $this->hMaxLinha;
        $hBoxLinha = $this->hBoxLinha;
        $hLinha = $this->hLinha;
        $tamPapelVert = 160 + 16 + (($qtdItens - 1) * $hMaxLinha) + ($qtdPgto * $hLinha);
        // verifica se existe informações adicionais
        $this->textoAdic = '';
        if (isset($this->infAdic)) {
            $this->textoAdic .= !empty($this->infAdic->getElementsByTagName('infCpl')->item(0)->nodeValue) ?
            'Inf. Contribuinte: '.
            trim($this->pAnfavea($this->infAdic->getElementsByTagName('infCpl')->item(0)->nodeValue)) : '';
            if (!empty($this->textoAdic)) {
                $this->textoAdic = str_replace(";", "\n", $this->textoAdic);
                $alinhas = explode("\n", $this->textoAdic);
                $numlinhasdados = 0;
                $tempPDF = new Pdf(); // cria uma instancia temporaria da class pdf
                $tempPDF->SetFont('Times', '', '8'); // seta a font do PDF
                foreach ($alinhas as $linha) {
                    $linha = trim($linha);
                    $numlinhasdados += $tempPDF->WordWrap($linha, 76 - 0.2);
                }
                $hdadosadic = round(($numlinhasdados + 1) * $tempPDF->fontSize, 0);
                if ($hdadosadic < 5) {
                    $hdadosadic = 5;
                }
                // seta o tamanho do papel
                $tamPapelVert += $hdadosadic;
            }
        }
        //se a orientação estiver em branco utilizar o padrão estabelecido na NF
        if ($orientacao == '') {
            $orientacao = 'P';
        }
        $this->orientacao = $orientacao;
        $this->papel = array(80,$tamPapelVert);
        $this->logoAlign = $logoAlign;
        //$this->situacao_externa = $situacaoExterna;
        $this->numero_registro_dpec = $depecNumReg;
        //instancia a classe pdf
        if ($classPdf) {
            $this->pdf = $classPdf;
        } else {
            $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        }
        //margens do PDF, em milímetros. Obs.: a margem direita é sempre igual à
        //margem esquerda. A margem inferior *não* existe na FPDF, é definida aqui
        //apenas para controle se necessário ser maior do que a margem superior
        $margSup = 2;
        $margEsq = 2;
        $margInf = 2;
        // posição inicial do conteúdo, a partir do canto superior esquerdo da página
        $xInic = $margEsq;
        $yInic = $margSup;
        $maxW = 80;
        $maxH = $tamPapelVert;
        //total inicial de paginas
        $totPag = 1;
        //largura imprimivel em mm: largura da folha menos as margens esq/direita
        $this->wPrint = $maxW-($margEsq*2);
        //comprimento (altura) imprimivel em mm: altura da folha menos as margens
        //superior e inferior
        $this->hPrint = $maxH-$margSup-$margInf;
        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        $this->pdf->setMargins($margEsq, $margSup); // fixa as margens
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->open(); // inicia o documento
        $this->pdf->addPage($this->orientacao, $this->papel); // adiciona a primeira página
        $this->pdf->setLineWidth(0.1); // define a largura da linha
        $this->pdf->setTextColor(0, 0, 0);
        $this->pTextBox(0, 0, $maxW, $maxH); // POR QUE PRECISO DESA LINHA?
        $hcabecalho = 27;//para cabeçalho (dados emitente mais logomarca)  (FIXO)
        $hcabecalhoSecundario = 10;//para cabeçalho secundário (cabeçalho sefaz) (FIXO)
        $hprodutos = $hLinha + ($qtdItens*$hMaxLinha) ;//box poduto
        $hTotal = 12; //box total (FIXO)
        $hpagamentos = $hLinha + ($qtdPgto*$hLinha);//para pagamentos
        if (!empty($this->vTroco)) {
            $hpagamentos += $hLinha;
        }
        $hmsgfiscal = 21;// para imposto (FIXO)
        if (!isset($this->dest)) {
            $hcliente = 6;// para cliente (FIXO)
        } else {
            $hcliente = 12;
        }// para cliente (FIXO)};
        $hQRCode = 50;// para qrcode (FIXO)
        $hCabecItens = 4;//cabeçalho dos itens
        
        $hUsado = $hCabecItens;
        $w2 = round($this->wPrint*0.31, 0);
        $totPag = 1;
        $pag = 1;
        $x = $xInic;
        //COLOCA CABEÇALHO
        $y = $yInic;
        $y = $this->pCabecalhoDANFE($x, $y, $hcabecalho, $pag, $totPag);
        //COLOCA CABEÇALHO SECUNDÁRIO
        $y = $hcabecalho;
        $y = $this->pCabecalhoSecundarioDANFE($x, $y, $hcabecalhoSecundario);
        //COLOCA PRODUTOS
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario;
        $y = $this->pProdutosDANFE($x, $y, $hprodutos);
        //COLOCA TOTAL
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos;
        $y = $this->pTotalDANFE($x, $y, $hTotal);
        //COLOCA PAGAMENTOS
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos + $hTotal;
        $y = $this->pPagamentosDANFE($x, $y, $hpagamentos);
        //COLOCA MENSAGEM FISCAL
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos + $hTotal+ $hpagamentos;
        $y = $this->pFiscalDANFE($x, $y, $hmsgfiscal);
        //COLOCA CONSUMIDOR
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos + $hTotal + $hpagamentos + $hmsgfiscal;
        $y = $this->pConsumidorDANFE($x, $y, $hcliente);
        //COLOCA QRCODE
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos
            + $hTotal + $hpagamentos + $hmsgfiscal + $hcliente;
        $y = $this->pQRDANFE($x, $y, $hQRCode);
        
        //adiciona as informações opcionais
        if (!empty($this->textoAdic)) {
            $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos
            + $hTotal + $hpagamentos + $hmsgfiscal + $hcliente + $hQRCode;
            $hInfAdic = 0;
            $y = $this->pInfAdic($x, $y, $hInfAdic);
        }
        
        //retorna o ID na NFe
        if ($classPdf!==false) {
            $aR = [
             'id'=>str_replace('NFe', '', $this->infNFe->getAttribute("Id")),
             'classe_PDF'=>$this->pdf
            ];
            return $aR;
        } else {
            return str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        }
    }
    
    protected function pCabecalhoDANFE($x = 0, $y = 0, $h = 0, $pag = '1', $totPag = '1')
    {
        $emitRazao  = $this->getTagValue($this->emit, "xNome");
        $emitCnpj   = $this->getTagValue($this->emit, "CNPJ");
        $emitCnpj   = $this->pFormat($emitCnpj, "##.###.###/####-##");
        $emitIE     = $this->getTagValue($this->emit, "IE");
        $emitIM     = $this->getTagValue($this->emit, "IM");
        $emitFone = $this->getTagValue($this->enderEmit, "fone");
        $foneLen = strlen($emitFone);
        if ($foneLen>0) {
            $ddd = substr($emitFone, 0, 2);
            $fone1 = substr($emitFone, -8);
            $digito9 = ' ';
            if ($foneLen == 11) {
                $digito9 = substr($emitFone, 2, 1);
            }
            $emitFone = ' - ('.$ddd.') '.$digito9. ' ' . substr($fone1, 0, 4) . '-' . substr($fone1, -4);
        } else {
            $emitFone = '';
        }
        $emitLgr = $this->getTagValue($this->enderEmit, "xLgr");
        $emitNro = $this->getTagValue($this->enderEmit, "nro");
        $emitCpl = $this->getTagValue($this->enderEmit, "xCpl", "");
        $emitBairro = $this->getTagValue($this->enderEmit, "xBairro");
        $emitCEP = $this->pFormat($this->getTagValue($this->enderEmit, "CEP"), "#####-###");
        $emitMun = $this->getTagValue($this->enderEmit, "xMun");
        $emitUF = $this->getTagValue($this->enderEmit, "UF");
        // CONFIGURAÇÃO DE POSIÇÃO
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $h = $h-($margemInterna);
        //COLOCA LOGOMARCA
        if (is_file($this->logomarca)) {
            $xImg = $margemInterna;
            $yImg = $margemInterna + 1;
            $this->pdf->Image($this->logomarca, $xImg, $yImg, 30, 22.5);
            $xRs = ($maxW*0.4) + $margemInterna;
            $wRs = ($maxW*0.6);
            $alignEmit = 'L';
        } else {
            $xRs = $margemInterna;
            $wRs = ($maxW*1);
            $alignEmit = 'L';
        }
        //COLOCA RAZÃO SOCIAL
        $texto = $emitRazao;
        $texto = $texto . "\nCNPJ:" . $emitCnpj;
        $texto = $texto . "\nIE:" . $emitIE;
        if (!empty($emitIM)) {
            $texto = $texto . " - IM:" . $emitIM;
        }
        $texto = $texto . "\n" . $emitLgr . "," . $emitNro . " " . $emitCpl . "," . $emitBairro
                . ". CEP:" . $emitCEP . ". " . $emitMun . "-" . $emitUF . $emitFone;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
        $this->pTextBox($xRs, $y, $wRs, $h, $texto, $aFont, 'C', $alignEmit, 0, '', false);
    }
    
    protected function pCabecalhoSecundarioDANFE($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hBox1 = 7;
        $texto = "DANFE NFC-e\nDocumento Auxiliar da Nota Fiscal de Consumidor Eletrônica";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B');
        $this->pTextBox($x, $y, $w, $hBox1, $texto, $aFont, 'C', 'C', 0, '', false);
        $hBox2 = 4;
        $yBox2 = $y + $hBox1;
        $texto = "\nNFC-e não permite aproveitamento de crédito de ICMS";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'');
        $this->pTextBox($x, $yBox2, $w, $hBox2, $texto, $aFont, 'C', 'C', 0, '', false);
    }
    
    protected function pProdutosDANFE($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $qtdItens = $this->det->length;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $aFontCabProdutos = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $wBoxCod = $w*0.17;
        $texto = "CÓDIGO";
        $this->pTextBox($x, $y, $wBoxCod, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $wBoxDescricao = $w*0.43;
        $xBoxDescricao = $wBoxCod + $x;
        $texto = "DESCRICÃO";
        $this->pTextBox(
            $xBoxDescricao,
            $y,
            $wBoxDescricao,
            $hLinha,
            $texto,
            $aFontCabProdutos,
            'T',
            'L',
            0,
            '',
            false
        );
        $wBoxQt = $w*0.08;
        $xBoxQt = $wBoxDescricao + $xBoxDescricao;
        $texto = "QT";
        $this->pTextBox($xBoxQt, $y, $wBoxQt, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $wBoxUn = $w*0.06;
        $xBoxUn = $wBoxQt + $xBoxQt;
        $texto = "UN";
        $this->pTextBox($xBoxUn, $y, $wBoxUn, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $wBoxVl = $w*0.13;
        $xBoxVl = $wBoxUn + $xBoxUn;
        $texto = "VALOR";
        $this->pTextBox($xBoxVl, $y, $wBoxVl, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $wBoxTotal = $w*0.13;
        $xBoxTotal = $wBoxVl + $xBoxVl;
        $texto = "TOTAL";
        $this->pTextBox($xBoxTotal, $y, $wBoxTotal, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $hBoxLinha = $this->hBoxLinha;
        $hMaxLinha = $this->hMaxLinha;
        $cont = 0;
        $aFontProdutos = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'');
        if ($qtdItens > 0) {
            foreach ($this->det as $detI) {
                $thisItem   = $detI;
                $prod       = $thisItem->getElementsByTagName("prod")->item(0);
                $nitem      = $thisItem->getAttribute("nItem");
                $cProd      = $this->getTagValue($prod, "cProd");
                $xProd      = $this->getTagValue($prod, "xProd");
                $qCom       = number_format($this->getTagValue($prod, "qCom"), 2, ",", ".");
                $uCom       = $this->getTagValue($prod, "uCom");
                $vUnCom     = number_format($this->getTagValue($prod, "vUnCom"), 2, ",", ".");
                $vProd      = number_format($this->getTagValue($prod, "vProd"), 2, ",", ".");
                //COLOCA PRODUTO
                $yBoxProd = $y + $hLinha + ($cont*$hMaxLinha);
                //COLOCA PRODUTO CÓDIGO
                $wBoxCod = $w*0.17;
                $texto = $cProd;
                $this->pTextBox($x, $yBoxProd, $wBoxCod, $hMaxLinha, $texto, $aFontProdutos, 'C', 'C', 0, '', false);
                //COLOCA PRODUTO DESCRIÇÃO
                $wBoxDescricao = $w*0.43;
                $xBoxDescricao = $wBoxCod + $x;
                $texto = $xProd;
                $this->pTextBox(
                    $xBoxDescricao,
                    $yBoxProd,
                    $wBoxDescricao,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'L',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO QUANTIDADE
                $wBoxQt = $w*0.08;
                $xBoxQt = $wBoxDescricao + $xBoxDescricao;
                $texto = $qCom;
                $this->pTextBox(
                    $xBoxQt,
                    $yBoxProd,
                    $wBoxQt,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'C',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO UNIDADE
                $wBoxUn = $w*0.06;
                $xBoxUn = $wBoxQt + $xBoxQt;
                $texto = $uCom;
                $this->pTextBox(
                    $xBoxUn,
                    $yBoxProd,
                    $wBoxUn,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'C',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO VL UNITÁRIO
                $wBoxVl = $w*0.13;
                $xBoxVl = $wBoxUn + $xBoxUn;
                $texto = $vUnCom;
                $this->pTextBox(
                    $xBoxVl,
                    $yBoxProd,
                    $wBoxVl,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'R',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO VL TOTAL
                $wBoxTotal = $w*0.13;
                $xBoxTotal = $wBoxVl + $xBoxVl;
                $texto = $vProd;
                $this->pTextBox(
                    $xBoxTotal,
                    $yBoxProd,
                    $wBoxTotal,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'R',
                    0,
                    '',
                    false
                );
                
                $cont++;
            }
        }
    }
    
    protected function pTotalDANFE($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $hLinha = 3;
        $wColEsq = ($maxW*0.7);
        $wColDir = ($maxW*0.3);
        $xValor = $x + $wColEsq;
        $qtdItens = $this->det->length;
        $vProd = $this->getTagValue($this->ICMSTot, "vProd");
        $vNF = $this->getTagValue($this->ICMSTot, "vNF");
        $vDesc  = $this->getTagValue($this->ICMSTot, "vDesc");
        $vFrete = $this->getTagValue($this->ICMSTot, "vFrete");
        $vTotTrib = $this->getTagValue($this->ICMSTot, "vTotTrib");
        $texto = "Qtd. Total de Itens";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($x, $y, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $qtdItens;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($xValor, $y, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotal = $y + ($hLinha);
        $texto = "Total de Produtos";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($x, $yTotal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = "R$ " . number_format($vProd, 2, ",", ".");
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($xValor, $yTotal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yDesconto = $y + ($hLinha*2);
        $texto = "Descontos";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($x, $yDesconto, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = "R$ " . $vDesc;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($xValor, $yDesconto, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yFrete= $y + ($hLinha*3);
        $texto = "Frete";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($x, $yFrete, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = "R$ " . $vFrete;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($xValor, $yFrete, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotalFinal = $y + ($hLinha*4);
        $texto = "Total";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = "R$ " . $vNF;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotalFinal = $y + ($hLinha*5);
        $texto = "Informação dos Tributos Totais Incidentes";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'');
        $this->pTextBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = "R$ " . $vTotTrib;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pTextBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
    }
    
    protected function pPagamentosDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $qtdPgto = $this->pag->length;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $wColEsq = ($maxW*0.7);
        $wColDir = ($maxW*0.3);
        $xValor = $x + $wColEsq;
        $aFontPgto = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $wBoxEsq = $w*0.7;
        $texto = "FORMA DE PAGAMENTO";
        $this->pTextBox($x, $y, $wBoxEsq, $hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
        $wBoxDir = $w*0.3;
        $xBoxDescricao = $x + $wBoxEsq;
        $texto = "VALOR PAGO";
        $this->pTextBox($xBoxDescricao, $y, $wBoxDir, $hLinha, $texto, $aFontPgto, 'T', 'R', 0, '', false);
        $cont = 0;
        if ($qtdPgto > 0) {
            foreach ($this->pag as $pagI) {
                $tPag = $this->getTagValue($pagI, "tPag");
                $tPagNome = $this->tipoPag($tPag);
                $tPnome = $tPagNome;
                $vPag = number_format($this->getTagValue($pagI, "vPag"), 2, ",", ".");
                $card = $pagI->getElementsByTagName("card")->item(0);
                $cardCNPJ = '';
                $tBand = '';
                $tBandNome = '';
                if (isset($card)) {
                    $cardCNPJ = $this->getTagValue($card, "CNPJ");
                    $tBand    = $this->getTagValue($card, "tBand");
                    $cAut = $this->getTagValue($card, "cAut");
                    $tBandNome = self::getCardName($tBand);
                }
                //COLOCA PRODUTO
                $yBoxProd = $y + $hLinha + ($cont*$hLinha);
                //COLOCA PRODUTO CÓDIGO
                $texto = $tPagNome;
                $this->pTextBox($x, $yBoxProd, $wBoxEsq, $hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
                //COLOCA PRODUTO DESCRIÇÃO
                $xBoxDescricao = $wBoxEsq + $x;
                $texto = "R$ " . $vPag;
                $this->pTextBox(
                    $xBoxDescricao,
                    $yBoxProd,
                    $wBoxDir,
                    $hLinha,
                    $texto,
                    $aFontPgto,
                    'C',
                    'R',
                    0,
                    '',
                    false
                );
                $cont++;
            }
            
            if (!empty($this->vTroco)) {
                $yBoxProd = $y + $hLinha + ($cont*$hLinha);
                //COLOCA PRODUTO CÓDIGO
                $texto = 'Troco';
                $this->pTextBox($x, $yBoxProd, $wBoxEsq, $hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
                //COLOCA PRODUTO DESCRIÇÃO
                $xBoxDescricao = $wBoxEsq + $x;
                $texto = "R$ " . number_format($this->vTroco, 2, ",", ".");
                $this->pTextBox(
                    $xBoxDescricao,
                    $yBoxProd,
                    $wBoxDir,
                    $hLinha,
                    $texto,
                    $aFontPgto,
                    'C',
                    'R',
                    0,
                    '',
                    false
                );
            }
        }
    }
    
    protected function pFiscalDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $aFontTit = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B');
        $aFontTex = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
        $digVal = $this->getTagValue($this->nfe, "DigestValue");
        $chNFe = str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        $tpAmb = $this->getTagValue($this->ide, 'tpAmb');
        
        if ($this->pNotaCancelada()) {
            //101 Cancelamento
            $this->pdf->SetTextColor(255, 0, 0);
            $texto = "NFCe CANCELADA";
            $this->pTextBox($x, $y - 25, $w, $h, $texto, $aFontTit, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }
        
        if ($this->pNotaDenegada()) {
            //uso denegado
            $this->pdf->SetTextColor(255, 0, 0);
            $texto = "NFCe CANCELADA";
            $this->pTextBox($x, $y - 25, $w, $h, $texto, $aFontTit, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }
        
        $cUF = $this->getTagValue($this->ide, 'cUF');
        $nNF = $this->getTagValue($this->ide, 'nNF');
        $serieNF = str_pad($this->getTagValue($this->ide, "serie"), 3, "0", STR_PAD_LEFT);
        $dhEmi = $this->getTagValue($this->ide, "dhEmi");
        $dhEmilocal = new \DateTime($dhEmi);
        $dhEmiLocalFormat = $dhEmilocal->format('d/m/Y H:i:s');
        $urlChave = $this->urlConsulta[$tpAmb][$this->UFSigla[$cUF]];
        $texto = "ÁREA DE MENSAGEM FISCAL";
        $this->pTextBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
        $yTex1 = $y + ($hLinha*1);
        $hTex1 = $hLinha*2;
        $texto = "Número " . $nNF . " Série " . $serieNF . " " .$dhEmiLocalFormat . " - Via Consumidor";
        $this->pTextBox($x, $yTex1, $w, $hTex1, $texto, $aFontTex, 'C', 'C', 0, '', false);
        $yTex2 = $y + ($hLinha*3);
        $hTex2 = $hLinha*2;
        $texto = "Consulte pela Chave de Acesso em " . $urlChave;
        $this->pTextBox($x, $yTex2, $w, $hTex2, $texto, $aFontTex, 'C', 'C', 0, '', false);
        $texto = "CHAVE DE ACESSO";
        $yTit2 = $y + ($hLinha*5);
        $this->pTextBox($x, $yTit2, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
        $yTex3 = $y + ($hLinha*6);
        $texto = $chNFe;
        $this->pTextBox($x, $yTex3, $w, $hLinha, $texto, $aFontTex, 'C', 'C', 0, '', false);
    }
    
    protected function pConsumidorDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $aFontTit = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B');
        $aFontTex = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
        $texto = "CONSUMIDOR";
        $this->pTextBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
        if (isset($this->dest)) {
            $considEstrangeiro = !empty($this->dest->getElementsByTagName("idEstrangeiro")->item(0)->nodeValue)
                    ? $this->dest->getElementsByTagName("idEstrangeiro")->item(0)->nodeValue
                    : '';
            $consCPF = !empty($this->dest->getElementsByTagName("CPF")->item(0)->nodeValue)
                    ? $this->dest->getElementsByTagName("CPF")->item(0)->nodeValue
                    : '';
            $consCNPJ = !empty($this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue)
                    ? $this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue
                    : '';
            $cDest = $consCPF.$consCNPJ.$considEstrangeiro; //documentos do consumidor
            $enderDest = $this->dest->getElementsByTagName("enderDest")->item(0);
            $consNome = $this->getTagValue($this->dest, "xNome");
            $consLgr = $this->getTagValue($enderDest, "xLgr");
            $consNro = $this->getTagValue($enderDest, "nro");
            $consCpl = $this->getTagValue($enderDest, "xCpl", " - ");
            $consBairro = $this->getTagValue($enderDest, "xBairro");
            $consCEP = $this->pFormat($this->getTagValue($enderDest, "CEP"));
            $consMun = $this->getTagValue($enderDest, "xMun");
            $consUF = $this->getTagValue($enderDest, "UF");
            $considEstrangeiro = $this->getTagValue($this->dest, "idEstrangeiro");
            $consCPF = $this->getTagValue($this->dest, "CPF");
            $consCNPJ = $this->getTagValue($this->dest, "CNPJ");
            $consDoc = "";
            if (!empty($consCNPJ)) {
                $consDoc = "CNPJ: $consCNPJ";
            } elseif (!empty($consCPF)) {
                $consDoc = "CPF: $consCPF";
            } elseif (!empty($considEstrangeiro)) {
                $consDoc = "id: $considEstrangeiro";
            }
            $consEnd = "";
            if (!empty($consLgr)) {
                $consEnd = $consLgr
                    . ","
                    . $consNro
                    . " "
                    . $consCpl
                    . ","
                    . $consBairro
                    . ". CEP:"
                    . $consCEP
                    . ". "
                    . $consMun
                    . "-"
                    . $consUF;
            }
            $yTex1 = $y + $hLinha;
            $texto = $consNome;
            if (!empty($consDoc)) {
                $texto .= " - ". $consDoc . "\n" . $consEnd;
                $this->pTextBox($x, $yTex1, $w, $hLinha*3, $texto, $aFontTex, 'C', 'C', 0, '', false);
            }
        } else {
            $yTex1 = $y + $hLinha;
            $texto = "Consumidor não identificado";
            $this->pTextBox($x, $yTex1, $w, $hLinha, $texto, $aFontTex, 'C', 'C', 0, '', false);
        }
    }
    
    protected function pQRDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1)+4;
        $hLinha = $this->hLinha;
        $hBoxLinha = $this->hBoxLinha;
        $aFontTit = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B');
        $aFontTex = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
        $dhRecbto = '';
        $nProt = '';
        if (isset($this->nfeProc)) {
            $nProt = $this->getTagValue($this->nfeProc, "nProt");
            $dhRecbto  = $this->getTagValue($this->nfeProc, "dhRecbto");
        }
        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj(
            'QRCODE,M',
            $this->qrCode,
            -4,
            -4,
            'black',
            array(-2, -2, -2, -2)
        )->setBackgroundColor('white');
        $qrcode = $bobj->getPngData();
        $wQr = 50;
        $hQr = 50;
        $yQr = ($y+$margemInterna);
        $xQr = ($w/2) - ($wQr/2);
        // prepare a base64 encoded "data url"
        $pic = 'data://text/plain;base64,' . base64_encode($qrcode);
        $info = getimagesize($pic);
        $this->pdf->image($pic, $xQr, $yQr, $wQr, $hQr, 'PNG');
        $dt = new DateTime($dhRecbto);
        $yQr = ($yQr+$hQr+$margemInterna);
        $this->pTextBox($x, $yQr, $w-4, $hBoxLinha, "Protocolo de Autorização: " . $nProt . "\n"
            . $dt->format('d/m/Y H:i:s'), $aFontTex, 'C', 'C', 0, '', false);
    }
   
    protected function pInfAdic($x = 0, $y = 0, $h = 0)
    {
        $y += 17;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW * 1);
        $hLinha = $this->hLinha;
        $aFontTit = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $aFontTex = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        // seta o textbox do titulo
        $texto = "INFORMAÇÃO ADICIONAL";
        $heigthText = $this->pTextBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
                
        // seta o textbox do texto adicional
        $this->pTextBox($x, $y+3, $w-2, $hLinha-3, $this->textoAdic, $aFontTex, 'T', 'L', 0, '', false);
    }
    
    /**
     * printDANFE
     * Esta função envia a DANFE em PDF criada para o dispositivo informado.
     * O destino da impressão pode ser :
     * I-browser
     * D-browser com download
     * F-salva em um arquivo local com o nome informado
     * S-retorna o documento como uma string e o nome é ignorado.
     * Para enviar o pdf diretamente para uma impressora indique o
     * nome da impressora e o destino deve ser 'S'.
     *
     * @param  string $nome    Path completo com o nome do arquivo pdf
     * @param  string $destino Direção do envio do PDF
     * @param  string $printer Identificação da impressora no sistema
     * @return string Caso o destino seja S o pdf é retornado como uma string
     * @todo   Rotina de impressão direta do arquivo pdf criado
     */
    public function printDANFE($nome = '', $destino = 'I', $printer = '')
    {
        $arq = $this->pdf->Output($nome, $destino);
        if ($destino == 'S') {
            //aqui pode entrar a rotina de impressão direta
        }
        return $arq;
    }
    /**
     * Dados brutos do PDF
     * @return string
     */
    public function render()
    {
        return $this->pdf->getPdf();
    }
    
    /**
     * anfavea
     * Função para transformar o campo cdata do padrão ANFAVEA para
     * texto imprimível
     *
     * @param  string $cdata campo CDATA
     * @return string conteúdo do campo CDATA como string
     */
    protected function pAnfavea($cdata = '')
    {
        if ($cdata == '') {
            return '';
        }
        //remove qualquer texto antes ou depois da tag CDATA
        $cdata = str_replace('<![CDATA[', '<CDATA>', $cdata);
        $cdata = str_replace(']]>', '</CDATA>', $cdata);
        $cdata = preg_replace('/\s\s+/', ' ', $cdata);
        $cdata = str_replace("> <", "><", $cdata);
        $len = strlen($cdata);
        $startPos = strpos($cdata, '<');
        if ($startPos === false) {
            return $cdata;
        }
        for ($x=$len; $x>0; $x--) {
            if (substr($cdata, $x, 1) == '>') {
                $endPos = $x;
                break;
            }
        }
        if ($startPos > 0) {
            $parte1 = substr($cdata, 0, $startPos);
        } else {
            $parte1 = '';
        }
        $parte2 = substr($cdata, $startPos, $endPos-$startPos+1);
        if ($endPos < $len) {
            $parte3 = substr($cdata, $endPos + 1, $len - $endPos - 1);
        } else {
            $parte3 = '';
        }
        $texto = trim($parte1).' '.trim($parte3);
        if (strpos($parte2, '<CDATA>') === false) {
            $cdata = '<CDATA>'.$parte2.'</CDATA>';
        } else {
            $cdata = $parte2;
        }
        //carrega o xml CDATA em um objeto DOM
        $dom = new Dom();
        $dom->loadXML($cdata, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        //$xml = $dom->saveXML();
        //grupo CDATA infADprod
        $id = $dom->getElementsByTagName('id')->item(0);
        $div = $dom->getElementsByTagName('div')->item(0);
        $entg = $dom->getElementsByTagName('entg')->item(0);
        $dest = $dom->getElementsByTagName('dest')->item(0);
        $ctl = $dom->getElementsByTagName('ctl')->item(0);
        $ref = $dom->getElementsByTagName('ref')->item(0);
        if (isset($id)) {
            if ($id->hasAttributes()) {
                foreach ($id->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($div)) {
            if ($div->hasAttributes()) {
                foreach ($div->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($entg)) {
            if ($entg->hasAttributes()) {
                foreach ($entg->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($dest)) {
            if ($dest->hasAttributes()) {
                foreach ($dest->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($ctl)) {
            if ($ctl->hasAttributes()) {
                foreach ($ctl->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($ref)) {
            if ($ref->hasAttributes()) {
                foreach ($ref->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        //grupo CADATA infCpl
        $t = $dom->getElementsByTagName('transmissor')->item(0);
        $r = $dom->getElementsByTagName('receptor')->item(0);
        $versao = ! empty($dom->getElementsByTagName('versao')->item(0)->nodeValue) ?
        'Versao:'.$dom->getElementsByTagName('versao')->item(0)->nodeValue.' ' : '';
        $especieNF = ! empty($dom->getElementsByTagName('especieNF')->item(0)->nodeValue) ?
        'Especie:'.$dom->getElementsByTagName('especieNF')->item(0)->nodeValue.' ' : '';
        $fabEntrega = ! empty($dom->getElementsByTagName('fabEntrega')->item(0)->nodeValue) ?
        'Entrega:'.$dom->getElementsByTagName('fabEntrega')->item(0)->nodeValue.' ' : '';
        $dca = ! empty($dom->getElementsByTagName('dca')->item(0)->nodeValue) ?
        'dca:'.$dom->getElementsByTagName('dca')->item(0)->nodeValue.' ' : '';
        $texto .= "".$versao.$especieNF.$fabEntrega.$dca;
        if (isset($t)) {
            if ($t->hasAttributes()) {
                $texto .= " Transmissor ";
                foreach ($t->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($r)) {
            if ($r->hasAttributes()) {
                $texto .= " Receptor ";
                foreach ($r->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        return $texto;
    }
    
    /**
     * str2Hex
     * Converte string para haxadecimal ASCII
     *
     * @param  string $str
     * @return string
     */
    protected static function str2Hex($str)
    {
        if ($str == '') {
            return '';
        }
        $hex = "";
        $iCount = 0;
        do {
            $hex .= sprintf("%02x", ord($str{$iCount}));
            $iCount++;
        } while ($iCount < strlen($str));
        return $hex;
    }//fim str2Hex
    
    protected static function getCardName($tBand)
    {
        switch ($tBand) {
            case '01':
                $tBandNome = 'VISA';
                break;
            case '02':
                $tBandNome = 'MASTERCARD';
                break;
            case '03':
                $tBandNome = 'AMERICAM EXPRESS';
                break;
            case '04':
                $tBandNome = 'SOROCRED';
                break;
            case '99':
                $tBandNome = 'OUTROS';
                break;
            default:
                $tBandNome = '';
        }
        return $tBandNome;
    }
    
    /**
     * hex2Str
     * Converte hexadecimal ASCII para string
     *
     * @param  string $str
     * @return string
     */
    protected static function hex2Str($str)
    {
        if ($str == '') {
            return '';
        }
        $bin = "";
        $iCount = 0;
        do {
            $bin .= chr(hexdec($str{$iCount}.$str{($iCount + 1)}));
            $iCount += 2;
        } while ($iCount < strlen($str));
        return $bin;
    }
    
    protected function makeQRCode(
        $chNFe,
        $url,
        $tpAmb,
        $cDest = '',
        $dhEmi = '',
        $vNF = '',
        $vICMS = '',
        $digVal = '',
        $idToken = '000001',
        $token = ''
    ) {
        $nVersao = '100';
        $dhHex = self::str2Hex($dhEmi);
        $digHex = self::str2Hex($digVal);
        $seq = '';
        $seq .= 'chNFe=' . $chNFe;
        $seq .= '&nVersao=' . $nVersao;
        $seq .= '&tpAmb=' . $tpAmb;
        if ($cDest != '') {
            $seq .= '&cDest=' . $cDest;
        }
        $seq .= '&dhEmi=' . strtolower($dhHex);
        $seq .= '&vNF=' . $vNF;
        $seq .= '&vICMS=' . $vICMS;
        $seq .= '&digVal=' . strtolower($digHex);
        $seq .= '&cIdToken=' . $idToken;
        //o hash code é calculado com o Token incluso
        $hash = sha1($seq.$token);
        $seq .= '&cHashQRCode='. strtoupper($hash);
        if (strpos($url, '?') === false) {
            $seq = $url.'?'.$seq;
        } else {
            $seq = $url.''.$seq;
        }
        return $seq;
    }
    
    protected function pNotaCancelada()
    {
        if (!isset($this->nfeProc)) {
            return false;
        }
        $cStat = $this->getTagValue($this->nfeProc, "cStat");
        return $cStat == '101' ||
                $cStat == '151' ||
                $cStat == '135' ||
                $cStat == '155';
    }

    protected function pNotaDenegada()
    {
        if (!isset($this->nfeProc)) {
            return false;
        }
        //NÃO ERA NECESSÁRIO ESSA FUNÇÃO POIS SÓ SE USA
        //1 VEZ NO ARQUIVO INTEIRO
        $cStat = $this->getTagValue($this->nfeProc, "cStat");
        return $cStat == '110' ||
               $cStat == '301' ||
               $cStat == '302';
    }
}
