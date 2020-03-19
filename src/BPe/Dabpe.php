<?php

namespace NFePHP\DA\BPe;

/**
 * Classe para a impressão em PDF do Documento Auxiliar de Bilhete de Passagem eletronico
 * NOTA: Esta classe não é a indicada para quem faz uso de impressoras térmicas ESCPOS
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @copyright 2009-2019 NFePHP
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

class Dabpe extends Common
{
    protected $papel;
    protected $paperwidth = 80;
    protected $creditos;
    protected $xml; // string XML BPe
    protected $logomarca = ''; // path para logomarca em jpg
    protected $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";
    protected $debugMode = 0; //ativa ou desativa o modo de debug
    protected $tpImp; //ambiente
    protected $fontePadrao = 'Times';
    protected $nfeProc;
    protected $infBPeSupl;
    protected $nfe;
    protected $infBPe;
    protected $ide;
    protected $enderDest;
    protected $ICMSTot;
    protected $imposto;
    protected $agencia;
    protected $enderAgencia;
    protected $emit;
    protected $enderEmit;
    protected $qrCode;
    protected $urlChave;
    protected $det;
    protected $infAdic;
    protected $textoAdic;
    protected $tpEmis;
    protected $Comp;
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
    protected $protBPe;
    protected $ICMSSN;
    protected $dhCont;

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
        $docXML,
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

        $this->fontePadrao = empty($fonteDABPE) ? 'Times' : $fonteDABPE;
        $this->aFontTit = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
        $this->aFontTex = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        if (!empty($this->xml)) {
            $this->dom = new Dom();
            $this->dom->loadXML($this->xml);
            $this->infBPe = $this->dom->getElementsByTagName("infBPe")->item(0);
            $this->ide = $this->dom->getElementsByTagName("ide")->item(0);
            $this->agencia = $this->dom->getElementsByTagName("agencia")->item(0);
            $this->enderAgencia = $this->dom->getElementsByTagName("enderAgencia")->item(0);
            $this->emit = $this->dom->getElementsByTagName("emit")->item(0);
            $this->enderEmit = $this->dom->getElementsByTagName("enderEmit")->item(0);
            $this->infViagem = $this->dom->getElementsByTagName("infViagem")->item(0);
            $this->infPassagem = $this->dom->getElementsByTagName("infPassagem")->item(0);
            $this->infPassageiro = $this->dom->getElementsByTagName("infPassageiro")->item(0);
            $this->infValorBPe = $this->dom->getElementsByTagName("infValorBPe")->item(0);
            $this->Comp = $this->dom->getElementsByTagName("Comp");
            $this->pag = $this->dom->getElementsByTagName("pag");
            $this->infProt = $this->dom->getElementsByTagName("infProt")->item(0);
            $this->tpEmis = $this->dom->getElementsByTagName('tpEmis')->item(0)->nodeValue;
            $this->nBP = $this->dom->getElementsByTagName("nBP")->item(0);
            $this->protBPe = $this->dom->getElementsByTagName("protBPe")->item(0);
            $this->ICMSSN = $this->dom->getElementsByTagName("ICMSSN")->item(0);
            $this->dhCont = $this->getTagValue($this->ide, "dhCont") ?? '';
        }
        $this->qrCodBPe = !empty($this->dom->getElementsByTagName('qrCodBPe')->item(0)->nodeValue)
            ? $this->dom->getElementsByTagName('qrCodBPe')->item(0)->nodeValue : null;

        $this->urlChave = $this->urlConsulta($this->cUF);
        if ($this->getTagValue($this->ide, "mod") != '63') {
            throw new InvalidArgumentException("O xml do DOCUMENTO deve ser uma BP-e modelo 63");
        }
    }

    protected function urlConsulta($uf)
    {
        switch ($uf) {
            case "11": // Rondônia
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "12": //Acre
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "13": //Amazonas
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "14": //Roraima
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "15": //Pará
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "16": //Amapá
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "17": //Tocantins
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "21": //Maranhão
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "22": //Piauí
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "23": //Ceará
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "24": //Rio Grande do Norte
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "25": //Paraíba
                return "https://www.sefaz.pb.gov.br/bpe/consulta";
                break;
            case "26": //Pernambuco
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "27": //Alagoas
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "28": //Sergipe
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "29": //Bahia
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "31": //Minas Gerais
                return "https://bpe.fazenda.mg.gov.br/portalbpe/sistema/consultaarg.xhtml";
                break;
            case "32": //Espírito Santo
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "33": //Rio de Janeiro
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "35": //São Paulo
                return "https://bpe.fazenda.sp.gov.br/BPe/";
                break;
            case "41": //Paraná
                return "http://www.sped.fazenda.pr.gov.br/modules/conteudo/bpe.php?consulta=completa";
                break;
            case "42": //Santa Catarina(Não usa BPE)
                return "";
                break;
            case "43": //Rio Grande do Sul (*)
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "50": //Mato Grosso do Sul
                return "http://www.dfe.ms.gov.br/bpe/#/consulta";
                break;
            case "51": //Mato Grosso
                return "https://www.sefaz.mt.gov.br/BPe/consulta";
                break;
            case "52": //Goiás
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
            case "53": //Distrito Federal
                return "https://dfe-portal.svrs.rs.gov.br/BPE/Consulta";
                break;
        }
    }

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
            //ativar modo debug
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            //desativar modo debug
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
        if (empty($this->pdf)) {
            $this->monta();
        }
        return $this->pdf->getPdf();
    }

    public function monta(
        $logo = null,
        $depecNumReg = '',
        $logoAlign = 'C'
    ) {
        $this->logomarca = $logo;
        $qtdItens = $this->Comp->length;
        $qtdPgto = $this->pag->length;
        $hMaxLinha = $this->hMaxLinha;
        $hBoxLinha = $this->hBoxLinha;
        $hLinha = $this->hLinha;
        $tamPapelVert = 190 + (($qtdItens - 1) * $hLinha) + ($qtdPgto * $hLinha);
        // verifica se existe informações adicionais
        $this->textoAdic = '';
        if (isset($this->infAdic)) {
            $this->textoAdic .= !empty(
                $this->infAdic->getElementsByTagName('infCpl')
                ->item(0)
                ->nodeValue
            )
                ? 'Inf. Contribuinte: '
                . trim($this->anfaveaDANFE(
                    $this->infAdic->getElementsByTagName('infCpl')->item(0)->nodeValue
                ))
                : '';
            if (!empty($this->textoAdic)) {
                $this->textoAdic = str_replace(";", "\n", $this->textoAdic);
                $alinhas = explode("\n", $this->textoAdic);
                $numlinhasdados = 0;
                $tempPDF = new Pdf(); // cria uma instancia temporaria da class pdf
                $tempPDF->setFont('times', '', '8'); // seta a font do PDF
                foreach ($alinhas as $linha) {
                    $linha = trim($linha);
                    $numlinhasdados += $tempPDF->wordWrap($linha, 76 - 0.2);
                }
                $hdadosadic = round(($numlinhasdados + 1) * $tempPDF->fontSize, 0);
                if ($hdadosadic < 5) {
                    $hdadosadic = 5;
                }
                // seta o tamanho do papel
                $tamPapelVert += $hdadosadic;
            }
        }
        $this->orientacao = 'P';
        $this->papel = [$this->paperwidth, $tamPapelVert];
        $this->logoAlign = $logoAlign;
        //$this->situacao_externa = $situacaoExterna;
        $this->numero_registro_dpec = $depecNumReg;
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);

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
        $this->wPrint = $maxW - ($margEsq * 2);
        //comprimento (altura) imprimivel em mm: altura da folha menos as margens
        //superior e inferior
        $this->hPrint = $maxH - $margSup - $margInf;
        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        $this->pdf->setMargins($margEsq, $margSup); // fixa as margens
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->open(); // inicia o documento
        $this->pdf->addPage($this->orientacao, $this->papel); // adiciona a primeira página
        $this->pdf->setLineWidth(0.1); // define a largura da linha
        $this->pdf->setTextColor(0, 0, 0);
        $this->pdf->textBox(0, 0, $maxW, $maxH); // POR QUE PRECISO DESA LINHA?
        $hcabecalho = 16;//para cabeçalho (dados emitente mais logomarca)  (FIXO)
        if (strlen($this->getTagValue($this->emit, "xNome")) > 40) {
            $hcabecalho += 2;
            $tamPapelVert += 2;
        };
        $hcabecalhoSecundario = 18;//para cabeçalho secundário (cabeçalho sefaz) (FIXO)
        $hagencia = 0;
        if (!empty($this->agencia)) {
            if (strlen($this->getTagValue($this->agencia, "xNome")) > 39) {
                $hagencia += 2;
                $tamPapelVert += 2;
            };
            $hagencia += 20;
            $tamPapelVert += 18;
        }
        $hprodutos = $hLinha + ($qtdItens * $hLinha);//box poduto
        $hTotal = 12; //box total (FIXO)
        $hpagamentos = (2 * $hLinha) + ($qtdPgto * $hLinha);//para pagamentos
        if (!empty($this->vTroco)) {
            $hpagamentos += $hLinha;
        }

        $hmsgfiscal = 28; // para imposto (FIXO)
        $hcliente = !isset($this->dest) ? 6 : 12; // para cliente (FIXO)
        $hcontingencia = $this->tpEmis == 9 ? 6 : 0; // para contingência (FIXO)
        $hQRCode = 50; // para qrcode (FIXO)
        $hCabecItens = 4; //cabeçalho dos itens

        $hUsado = $hCabecItens;
        $w2 = round($this->wPrint * 0.31, 0);
        $totPag = 1;
        $pag = 1;
        $x = $xInic;
//COLOCA CABEÇALHO
        $y = $yInic;
        if (!empty($this->agencia)) {
            $y += $this->cabecalhoAgencia($x, $y, $hagencia, $pag, $totPag);
            if (!empty($this->dhCont)) {
                $hcabecalho = $hcabecalho + ($this->hLinha * 2);
            }
            $y += $this->cabecalhoDABPE($x, $y, $hcabecalho);
            $hcabecalho = $hcabecalho + $hagencia;
        } else {
            if (!empty($this->dhCont)) {
                $hcabecalho = $hcabecalho + ($this->hLinha * 2);
            }
            $y += $this->cabecalhoDABPE($x, $y, $hcabecalho, $pag, $totPag) + 2;
        }
//COLOCA CABEÇALHO SECUNDÁRIO
        $y += $this->cabecalhoSecundarioDABPE($x, $y, $hcabecalhoSecundario);
//COLOCA Componentes
        $y += $this->produtosDABPE($x, $y, $hprodutos);
//COLOCA TOTAL
        $y += $this->totalDABPE($x, $y, $hTotal);
//COLOCA PAGAMENTOS
        $y += $this->pagamentosDABPE($x, $y, $hpagamentos);
//COLOCA MENSAGEM FISCAL
        $y += $this->fiscalDABPE($x, $y, $hmsgfiscal);
//COLOCA QRCODE
        $y += $this->qrCodeDABPE($x, $y, $hQRCode);
//adiciona as informações opcionais
        if (!empty($this->textoAdic)) {
            $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos
                + $hTotal + $hpagamentos + $hmsgfiscal + $hcliente + $hQRCode;
            $hInfAdic = 0;
            $y = $this->infAdic($x, $y, $hInfAdic);
        }
    }

    /**
     *
     * Função para transformar o campo cdata do padrão ANFAVEA para
     * texto imprimível
     *
     * @param string $cdata campo CDATA
     * @return string conteúdo do campo CDATA como string
     */

    protected function cabecalhoAgencia($x = 0, $y = 0, $h = 0, $pag = '1', $totPag = '1')
    {
        $hTotal = 0;
        $agenciaRazao = $this->getTagValue($this->agencia, "xNome");
        $agenciaCnpj = $this->getTagValue($this->agencia, "CNPJ");
        $agenciaCnpj = $this->formatField($agenciaCnpj, "##.###.###/####-##");
        $agenciaLgr = $this->getTagValue($this->enderAgencia, "xLgr");
        $agenciaNro = $this->getTagValue($this->enderAgencia, "nro");
        $agenciaCpl = $this->getTagValue($this->enderAgencia, "xCpl", "");
        $agenciaBairro = $this->getTagValue($this->enderAgencia, "xBairro");
        $agenciaCEP = $this->formatField($this->getTagValue($this->enderAgencia, "CEP"), "#####-###");
        $agenciaMun = $this->getTagValue($this->enderAgencia, "xMun");
        $agenciaUF = $this->getTagValue($this->enderAgencia, "UF");
        // CONFIGURAÇÃO DE POSIÇÃO
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $h = $h - ($margemInterna);
        //COLOCA LOGOMARCA
        $xRs = $margemInterna;
        $wRs = ($maxW * 1);
        $alignEmit = 'C';
        $texto = "CNPJ: " . $agenciaCnpj . " " . "$agenciaRazao";
        $hRazao = ceil((strlen($texto) / 41)) * $this->hLinha;
        $hTotal += $hRazao;
        $endereco = "\n"
            . $agenciaLgr
            . ", "
            . $agenciaNro
            . " "
            . $agenciaCpl
            . ", "
            . $agenciaBairro
            . ". CEP:"
            . $agenciaCEP
            . ". "
            . $agenciaMun
            . "-"
            . $agenciaUF;
        $hEndereco = $this->hLinha * ceil(strlen($endereco) / 41);
        $hTotal += $hEndereco;
        //COLOCA RAZÃO SOCIAL
        $texto = $texto . $endereco;
        $texto .= "\n____________________________________________________";
        $hTotal += $this->hLinha;
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');

        $this->pdf->textBox($xRs, $y, $wRs, $hTotal, $texto, $aFont, 'T', $alignEmit, 0, '', false);

        return $hTotal;
    }

    protected function cabecalhoDABPE($x = 0, $y = 0, $h = 0, $pag = '1', $totPag = '1')
    {
        $hTotal = 0;
        $emitRazao = $this->getTagValue($this->emit, "xNome");
        $emitCnpj = $this->getTagValue($this->emit, "CNPJ");
        $emitCnpj = $this->formatField($emitCnpj, "##.###.###/####-##");
        $emitIE = $this->getTagValue($this->emit, "IE");
        $emitIM = $this->getTagValue($this->emit, "IM");
        $emitFone = $this->getTagValue($this->enderEmit, "fone");
        $foneLen = strlen($emitFone);
        if ($foneLen > 0) {
            $ddd = substr($emitFone, 0, 2);
            $fone1 = substr($emitFone, -8);
            $digito9 = ' ';
            if ($foneLen == 11) {
                $digito9 = substr($emitFone, 2, 1);
            }
            $emitFone = ' - (' . $ddd . ') ' . $digito9 . ' ' . substr($fone1, 0, 4) . '-' . substr($fone1, -4);
        } else {
            $emitFone = '';
        }
        $emitLgr = $this->getTagValue($this->enderEmit, "xLgr");
        $emitNro = $this->getTagValue($this->enderEmit, "nro");
        $emitCpl = $this->getTagValue($this->enderEmit, "xCpl", "");
        $emitBairro = $this->getTagValue($this->enderEmit, "xBairro");
        $emitCEP = $this->formatField($this->getTagValue($this->enderEmit, "CEP"), "#####-###");
        $emitMun = $this->getTagValue($this->enderEmit, "xMun");
        $emitUF = $this->getTagValue($this->enderEmit, "UF");
        // CONFIGURAÇÃO DE POSIÇÃO
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        //COLOCA LOGOMARCA
        $temLogo = !empty($this->logomarca);
        if ($temLogo) {
            $xImg = $margemInterna;
            $yImg = $margemInterna + 1;
            $type = (substr($this->logomarca, 0, 7) === 'data://') ? 'jpg' : null;
            $this->pdf->image($this->logomarca, $x, $y + $margemInterna, 30, 22.5, $type);
            $xRs = ($maxW * 0.4) + $margemInterna;
            $wRs = ($maxW * 0.6);
            $alignEmit = 'L';
            $limiteChar = 22;
        } else {
            $xRs = $margemInterna;
            $wRs = ($maxW * 1);
            $alignEmit = 'C';
            $limiteChar = 40;
        }
        //COLOCA RAZÃO SOCIAL
        $hRazao = ceil(strlen($emitRazao) / $limiteChar) * $this->hLinha;

        $hTotal += $hRazao;
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $this->pdf->textBox($xRs, $y, $wRs, $hRazao, $emitRazao, $aFont, 'T', $alignEmit, 0, '', false);

        $y += $hRazao;

        $linhaCNPJ = "CNPJ: " . $emitCnpj;
        $linhaCNPJ = $linhaCNPJ . " IE: " . $emitIE;

        $endereco = $emitLgr
            . ", "
            . $emitNro
            . " "
            . $emitCpl
            . ", "
            . $emitBairro
            . ". CEP: "
            . $emitCEP
            . " . "
            . $emitMun
            . " - " . $emitUF
            . $emitFone;

        $hEndereco = $this->hLinha * ceil(strlen($endereco) / 41);

        $linhaDabpe = "Documento Auxiliar do Bilhete de Passagem Eletrônico";
        $texto = $linhaCNPJ . "\n" . $endereco . "\n";
        $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $h = $this->hLinha + $hEndereco;
        if (empty($this->dhCont)) {
            if (empty($this->logomarca)) {
                $texto = $texto . $linhaDabpe . "\n____________________________________________________";
                $h = $h + $this->hLinha;
                $this->pdf->textBox($xRs, $y, $wRs, $h, $texto, $aFont, 'T', $alignEmit, 0, '', false);
            } else {
                if (empty($this->agencia)) {
                    $y += $this->hLinha;
                    $hTotal += $this->hLinha;
                }
                $this->pdf->textBox($xRs, $y, $wRs, $h, $texto, $aFont, 'T', $alignEmit, 0, '', false);
                $h = $h + $this->hLinha * 2;
                $y += $h;
                $texto = $linhaDabpe . "\n____________________________________________________";
                $this->pdf->textBox($x, $y, $maxW, $this->hLinha, $texto, $aFont, 'T', 'C', 0, '', false);
                $hTotal += $this->hLinha * 2;
            }
        } else {
            $texto = $texto . "\n____________________________________________________";
            $this->pdf->textBox($xRs, $y, $wRs, $this->hLinha, $texto, $aFont, 'T', $alignEmit, 0, '', false);
            $texto = "\nEMITIDA EM CONTINGÊNCIA\nPendente de autorização";
            $aFont = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
            $this->pdf->textBox(
                $xRs,
                $h - $this->hLinha,
                $wRs,
                $this->hlinha * 2,
                $texto,
                $aFont,
                'T',
                $alignEmit,
                0,
                '',
                false
            );
        }
        $hTotal += $h;
        return $hTotal;
    }

    protected function cabecalhoSecundarioDABPE($x = 0, $y = 0, $h = 0)
    {
        $hTotal = 0;
        $margemInterna = $this->margemInterna;
        $hTotal += $margemInterna;
        $y += $margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW * 1);
        $hBox1 = 12;
        $hBox2 = 11;
        $origem = $this->getTagValue($this->infPassagem, "xLocOrig");
        $uforigem = $this->getTagValue($this->ide, "UFIni");
        $texto = "\nOrigem:" . $origem . "(" . $uforigem . ")";
        $destino = $this->getTagValue($this->infPassagem, "xLocDest");
        $ufdestino = $this->getTagValue($this->ide, "UFFim");
        $texto = $texto . "\nDestino:" . $destino . "(" . $ufdestino . ")";
        $dhViagem = $this->getTagValue($this->infViagem, "dhViagem");
        $dhViagemformatado = new \DateTime($dhViagem);
        $data = $dhViagemformatado->format('d/m/Y');
        $hora = $dhViagemformatado->format('H:i:s');
        $texto = "\n" . $texto . "\nData: " . $data . " | Horário: " . $hora;
        $aFont = array('font' => $this->fontePadrao, 'size' => 10, 'style' => 'B');
        $this->pdf->textBox($x, $y, $w, $hBox1, $texto, $aFont, 'T', 'C', 0, '', false);
        $hTotal += $hBox1;
        if ($this->getTagValue($this->infViagem, "prefixo") !== null) {
            $prefixo = $this->getTagValue($this->infViagem, "prefixo");
        } else {
            $prefixo = "";
        }
        $linha = $this->getTagValue($this->infViagem, "xPercurso");
        $tpServ = $this->getTagValue($this->infViagem, "tpServ");
        switch ($tpServ) {
            case "1":
                $tipo = "Convencional com sanitário";
                break;
            case "2":
                $tipo = "Convencional sem sanitário";
                break;
            case "3":
                $tipo = "Semileito";
                break;
            case "4":
                $tipo = "Leito com ar condicionado";
                break;
            case "5":
                $tipo = "Leito sem ar condicionado";
                break;
            case "6":
                $tipo = "Executivo";
                break;
            case "7":
                $tipo = "Semiurbano";
                break;
            case "8":
                $tipo = "Longitudinal";
                break;
            case "9":
                $tipo = "Travessia";
                break;
        }

        $texto = "\n(Poltrona: "
            . $this->getTagValue($this->infViagem, "poltrona")
            . " Plataforma: "
            . $this->getTagValue($this->infViagem, "plataforma")
            . ")";
        $texto = $texto
            . "\nPrefixo: "
            . $prefixo
            . "  Linha: "
            . $linha
            . "\nTipo: "
            . $tipo
            . "\n_____________________________________________________________";
        $aFont = array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B');
        $y += $hBox1;
        $this->pdf->textBox($x, $y, $w, $hBox2, $texto, $aFont, 'T', 'C', 0, '', false);
        $hTotal += $hBox2;
        return $hTotal;
    }

    protected function produtosDABPE($x = 0, $y = 0, $h = 0)
    {
        $hTotal = 0;
        $qtdItens = $this->Comp->length;
        $w = $this->wPrint;
        $cont = 0;
        $aFontComp = array('font' => $this->fontePadrao, 'size' => 7, 'style' => 'B');
        if ($qtdItens > 0) {
            foreach ($this->Comp as $compI) {
                $item = $compI;
                $tpComp = $this->getTagValue($item, "tpComp");
                switch ($tpComp) {
                    case "01":
                        $xtpComp = "Tarifa";
                        break;
                    case "02":
                        $xtpComp = "Pedagio";
                        break;
                    case "03":
                        $xtpComp = "Taxa Embarque";
                        break;
                    case "04":
                        $xtpComp = "Seguro";
                        break;
                    case "05":
                        $xtpComp = "TMR";
                        break;
                    case "06":
                        $xtpComp = "SVI";
                        break;
                    case "99":
                        $xtpComp = "Outros";
                        break;

                    default:
                        $xtpComp = "Outro";
                        break;
                }
                //COLOCA DESCRIÇÃO DO COMPONENTE(tpComp)
                $wBoxCompEsq = $w * 0.7;
                $wBoxCompDir = $w * 0.3;
                $texto = $xtpComp;
                $yBoxComp = $y + ($cont * $this->hLinha);
                $this->pdf->textBox(
                    $x,
                    $yBoxComp,
                    $wBoxCompEsq,
                    $this->hLinha,
                    $texto,
                    $aFontComp,
                    'T',
                    'L',
                    0,
                    '',
                    false
                );
                $vComp = number_format($this->getTagValue($item, "vComp"), 2, ",", ".");
                //COLOCA VALOR DO COMPONENTE

                $xBoxValor = $x + $wBoxCompEsq;
                $texto = $vComp;
                $this->pdf->textBox(
                    $xBoxValor,
                    $yBoxComp,
                    $wBoxCompDir,
                    $this->hLinha,
                    $texto,
                    $aFontComp,
                    'T',
                    'R',
                    0,
                    '',
                    false
                );
                $hTotal += $this->hLinha;
                $cont++;
            }
        }
        return $hTotal;
    }

    protected function totalDABPE($x = 0, $y = 0, $h = 0)
    {
        $hTotal = 0;
        $maxW = $this->wPrint;
        $hLinha = $this->hLinha;
        $wColEsq = ($maxW * 0.7);
        $wColDir = ($maxW * 0.3);
        $xValor = $x + $wColEsq;
        $qtdItens = $this->Comp->length;

        $vBP = number_format($this->getTagValue($this->infValorBPe, "vBP"), 2, ",", ".");
        $vDesconto = number_format($this->getTagValue($this->infValorBPe, "vDesconto"), 2, ",", ".");
        $vTroco = number_format($this->getTagValue($this->infValorBPe, "vTroco"), 2, ",", ".");
        $vPgto = number_format($this->getTagValue($this->infValorBPe, "vPgto"), 2, ",", ".");


        $texto = "Valor total R$";
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];
        $this->pdf->textBox($x, $y, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $vBP;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];
        $this->pdf->textBox($xValor, $y, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $hTotal += $this->hLinha;
        $y += $this->hLinha;

        $texto = "Desconto R$";
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];
        $this->pdf->textBox($x, $y, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $vDesconto;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];
        $this->pdf->textBox($xValor, $y, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $y += $this->hLinha;
        $hTotal += $this->hLinha;

        $texto = "Valor a Pagar R$";
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $vPgto;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($xValor, $y, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $hTotal += $this->hLinha;
        return $hTotal;
    }

    protected function fiscalDABPE($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW * 1);
        $hLinha = $this->hLinha;
        $aFontTit = ['font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'];
        $aFontTex = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        $chBPe = str_replace('BPe', '', $this->infBPe->getAttribute("Id"));
        $chBPe = $this->formatField($chBPe, "#### #### #### #### #### #### #### #### #### #### ####");

        if ($this->checkCancelada()) {
            //101 Cancelamento
            $this->pdf->setTextColor(255, 0, 0);
            $texto = "BPe CANCELADO";
            $this->pdf->textBox(
                $x,
                $y,
                $w,
                $h,
                $texto,
                ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'],
                'T',
                'C',
                0,
                ''
            );
            $this->pdf->setTextColor(0, 0, 0);
        }
//
//        if ($this->checkNaoAutorizada()) {
//            //'' Não Aprovada
//            $this->pdf->setTextColor(255, 0, 0);
//            $texto = "SEM VALOR FISCAL";
//            $this->pdf->textBox(
//                $x,
//                $y,
//                $w,
//                $this->hLinha*2,
//                $texto,
//                ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'],
//                'T',
//                'C',
//                0,
//                ''
//            );
//            $this->pdf->setTextColor(0, 0, 0);
//        }

        if ($this->checkSubstituto()) {
            //uso denegado
            $this->pdf->setTextColor(255, 0, 0);
            $texto = "BPe Substituto";
            $this->pdf->textBox($x, $y - 25, $w, $h, $texto, $aFontTit, 'T', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }

        $cUF = $this->getTagValue($this->ide, 'cUF');
        $nBP = $this->getTagValue($this->ide, 'nBP');
        $serieBPe = str_pad($this->getTagValue($this->ide, "serie"), 3, "0", STR_PAD_LEFT);
        $dhEmi = $this->getTagValue($this->ide, "dhEmi");
        $dhEmilocal = new \DateTime($dhEmi);
        $dhEmiLocalFormat = $dhEmilocal->format('d/m/Y H:i:s');
        $texto = "Consulte pela chave de acesso em \n";
        $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTex, 'T', 'C', 0, '', false);
        $texto = $this->urlConsulta($this->getTagValue($this->ide, 'cUF'));
        $y += $this->hLinha;
        $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'T', 'C', 0, '', false);

        $texto = $chBPe ?? 'Chave Não Localizada no XML';
        $y += $this->hLinha;
        $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTex, 'T', 'C', 0, '', false);

        $y += $this->hLinha;
        $hTex1 = $hLinha * 2;

        if (isset($this->infPassageiro)) {
            $xNome = $this->getTagValue($this->infPassageiro, "xNome");
            $nDoc = $this->getTagValue($this->infPassageiro, "nDoc");
            $texto = "PASSAGEIRO: DOC: " . $nDoc . " - " . $xNome;
            $this->pdf->textBox($x, $y, $w, $hLinha * 2, $texto, $aFontTex, 'T', 'C', 0, '', false);
            $y += $this->hLinha;
        }

        if (!empty($this->getTagValue($this->infValorBPe, "tpDesconto"))) {
            switch ($this->getTagValue($this->infValorBPe, "tpDesconto")) {
                case "01":
                    $xtpDesconto = "Tarifa promocional";
                    break;
                case "02":
                    $xtpDesconto = "Idoso";
                    break;
                case "03":
                    $xtpDesconto = "Criança";
                    break;
                case "04":
                    $xtpDesconto = "Deficiente";
                    break;
                case "05":
                    $xtpDesconto = "Estudante";
                    break;
                case "06":
                    $xtpDesconto = "Animal Doméstico";
                    break;
                case "07":
                    $xtpDesconto = "Acordo Coletivo";
                    break;
                case "08":
                    $xtpDesconto = "Profissional em Deslocamento";
                    break;
                case "09":
                    $xtpDesconto = "Profissional da Empresa";
                    break;
                case "10":
                    $xtpDesconto = "Jovem";
                    break;
                case "99":
                    $xtpDesconto = "Outros";
                    break;
            }
            $y += $this->hLinha * 3;
            $texto = "TIPO DE DESCONTO: " . $xtpDesconto;
            $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTex, 'T', 'C', 0, '', false);
        }

        $texto = "BP-e nº " . $nBP . " Série " . $serieBPe . " " . $dhEmiLocalFormat;
        $y += $this->hLinha;
        $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'T', 'C', 0, '', false);
        $y += $this->hLinha * 2;
        if (empty($this->dhCont)) {
            $nProt = $this->getTagValue($this->protBPe, "nProt");
            $dhRecbto = $this->getTagValue($this->protBPe, "dhRecbto");
            $dhRecbto = new \DateTime($dhRecbto);
            $texto = "Protocolo de autorização: " . $nProt;
            $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTex, 'T', 'C', 0, '', false);
            $y += $this->hLinha * 2;
            $texto = "\nData de autorização: " . $dhRecbto->format('d/m/Y H:i:s');
            $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTex, 'T', 'C', 0, '', false);
        } else {
            $texto = "EMITIDA EM CONTINGÊNCIA";
            $aFontTex = ['font' => $this->fontePadrao, 'size' => 12, 'style' => 'B'];
            $this->pdf->textBox($x, $yTex3 + $hLinha, $w, $hLinha, $texto, $aFontTex, 'T', 'C', 0, '', false);
            $yTex4 = $y + ($hLinha * 8);
            $texto = "\nPendente de autorização ";
            $aFontTex = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
            $this->pdf->textBox($x, $yTex4 + $hLinha, $w, $hLinha, $texto, $aFontTex, 'T', 'C', 0, '', false);
        }
    }

    protected function checkCancelada()
    {
        if (!isset($this->infProt)) {
            return false;
        }
        $cStat = $this->getTagValue($this->infProt, "cStat");
        return $cStat == '101';
    }

    protected function checkNaoAutorizada()
    {
        if (!isset($this->infProt)) {
            $cStat = $this->getTagValue($this->infProt, "cStat");
            return $cStat == '';
        }
    }

    protected function checkSubstituto()
    {
        if (!isset($this->infProt)) {
            return false;
        }
        //NÃO ERA NECESSÁRIO ESSA FUNÇÃO POIS SÓ SE USA
        //1 VEZ NO ARQUIVO INTEIRO
        $cStat = $this->getTagValue($this->infProt, "cStat");
        return $cStat == '102';
    }

    protected function qrCodeDABPE($x = 0, $y = 0, $h = 0)
    {
        $y += 30;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW * 1) + 4;
        $hLinha = $this->hLinha;
        $hBoxLinha = $this->hBoxLinha;
        $aFontTit = array('font' => $this->fontePadrao, 'size' => 8, 'style' => 'B');
        $aFontTex = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        $dhRecbto = '';
        $nProt = '';
        if (isset($this->infBPeSupl)) {
            $nProt = $this->getTagValue($this->nfeProc, "nProt");
            $dhRecbto = $this->getTagValue($this->nfeProc, "dhRecbto");
        }

        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj(
            'QRCODE,M',
            $this->qrCodBPe,
            -4,
            -4,
            'black',
            array(-2, -2, -2, -2)
        )->setBackgroundColor('white');
        $qrcode = $bobj->getPngData();
        $wQr = 50;
        $hQr = 50;
        $yQr = ($y + $margemInterna);
        $xQr = ($w / 2) - ($wQr / 2);
        // prepare a base64 encoded "data url"
        $pic = 'data://text/plain;base64,' . base64_encode($qrcode);
        $info = getimagesize($pic);
        $this->pdf->image($pic, $xQr, $yQr, $wQr, $hQr, 'PNG');
        $dt = new DateTime($dhRecbto);
        $yQr = ($yQr + $hQr + $margemInterna);

        if (isset($this->ICMSSN)) {
            $vTotTrib = $this->getTagValue($this->ICMSSN, "vTotTrib");
            $this->pdf->textBox(
                $x,
                $yQr,
                $w - 4,
                $hBoxLinha,
                "Tributos Totais Incidentes(Lei Federal 12.741/2012): R$"
                . $vTotTrib,
                $aFontTex,
                'T',
                'C',
                0,
                '',
                false
            );
        }
    }

    protected function infAdic($x = 0, $y = 0, $h = 0)
    {
        $y += 17;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW * 1);
        $hLinha = $this->hLinha;
        $aFontTit = ['font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'];
        $aFontTex = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        // seta o textbox do titulo
        $texto = "INFORMAÇÃO ADICIONAL";
        $heigthText = $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);

        // seta o textbox do texto adicional
        $this->pdf->textBox($x, $y + 3, $w - 2, $hLinha - 3, $this->textoAdic, $aFontTex, 'T', 'L', 0, '', false);
    }

    public function paperWidth($width = 80)
    {
        if (is_int($width) && $width > 60) {
            $this->paperwidth = $width;
        }
        return $this->paperwidth;
    }

    protected function pagamentosDABPE($x = 0, $y = 0, $h = 0)
    {
        $hTotal = 0;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $qtdPgto = $this->pag->length;
        $w = ($maxW * 1);
        $hLinha = $this->hLinha;
        $wColEsq = ($maxW * 0.7);
        $wColDir = ($maxW * 0.3);
        $xValor = $x + $wColEsq;

        $aFontPgto = array('font' => $this->fontePadrao, 'size' => 7, 'style' => '');
        $wBoxEsq = $w * 0.7;
        $texto = "FORMA PAGAMENTO";
        $this->pdf->textBox($x, $y, $wBoxEsq, $this->hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
        $wBoxDir = $w * 0.3;
        $xBoxDescricao = $x + $wBoxEsq;
        $texto = "VALOR PAGO";
        $this->pdf->textBox($xBoxDescricao, $y, $wBoxDir, $hLinha, $texto, $aFontPgto, 'T', 'R', 0, '', false);
        $y += $this->hLinha;
        $hTotal += $this->hLinha;

        $cont = 0;
        if ($qtdPgto > 0) {
            foreach ($this->pag as $pagI) {
                $tPag = $this->getTagValue($pagI, "tPag");
                switch ($tPag) {
                    case "01":
                        $tPagNome = "Dinheiro R$";
                        break;
                    case "02":
                        $tPagNome = "Cheque R$";
                        break;
                    case "03":
                        $tPagNome = "Cartão de Crédito R$";
                        break;
                    case "04":
                        $tPagNome = "Cartão de Débito R$";
                        break;
                    case "05":
                        $tPagNome = "Vale Transporte R$";
                        break;
                    case "99":
                        $tPagNome = "Outros";
                        break;
                }
                $vPag = number_format($this->getTagValue($pagI, "vPag"), 2, ",", ".");
                $texto = $tPagNome;
                $this->pdf->textBox($x, $y, $wBoxEsq, $hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
                //COLOCA PRODUTO DESCRIÇÃO
                $xBoxDescricao = $wBoxEsq + $x;
                $texto = $vPag;
                $this->pdf->textBox(
                    $xBoxDescricao,
                    $y,
                    $wBoxDir,
                    $this->hLinha,
                    $texto,
                    $aFontPgto,
                    'T',
                    'R',
                    0,
                    '',
                    false
                );

                $y += $this->hLinha;
                $hTotal += $this->hLinha;
                $cont++;
            }
        }
        $texto = "Troco";
        $this->pdf->textBox($x, $y, $wBoxEsq, $this->hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);

        $vTroco = number_format($this->getTagValue($this->infValorBPe, "vTroco"), 2, ",", ".");
        $texto = $vTroco;
        $this->pdf->textBox(
            $xBoxDescricao,
            $y,
            $wBoxDir,
            $this->hLinha,
            $texto,
            $aFontPgto,
            'T',
            'R',
            0,
            '',
            false
        );
        $hTotal += $this->hLinha * 2;
        return $hTotal;
    }
}
