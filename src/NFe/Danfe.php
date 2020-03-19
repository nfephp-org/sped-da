<?php

namespace NFePHP\DA\NFe;

use InvalidArgumentException;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;

class Danfe extends Common
{

    /**
     * alinhamento padrão do logo (C-Center)
     *
     * @var string
     */
    protected $logoAlign = 'C';
    /**
     * Posição
     * @var float
     */
    protected $yDados = 0;
    /**
     * Numero DPEC
     *
     * @var string
     */
    protected $numero_registro_dpec = '';
     /**
     * Parâmetro para exibir ou ocultar os valores do PIS/COFINS.
     * @var boolean
     */
    protected $qCanhoto = 1;
    /**
     * Define a exbição dos valores de PIS e Cofins
     * @var bool
     */
    protected $exibirPIS = true;
    /**
     * Parâmetro para exibir ou ocultar os valores do ICMS Interestadual e Valor Total dos Impostos.
     * @var boolean
     */
    protected $exibirIcmsInterestadual = true;
    /**
     * Parâmetro para exibir ou ocultar o texto sobre valor aproximado dos tributos.
     * @var boolean
     */
    protected $exibirValorTributos = true;
    /**
     * Parâmetro para exibir ou ocultar o texto adicional sobre a forma de pagamento
     * e as informações de fatura/duplicata.
     * @var boolean
     */
    protected $exibirTextoFatura = false;
    /**
     * Parâmetro do controle se deve concatenar automaticamente informações complementares
     * na descrição do produto, como por exemplo, informações sobre impostos.
     * @var boolean
     */
    public $descProdInfoComplemento = true;
    /**
     * Parâmetro do controle se deve gerar quebras de linha com "\n" a partir de ";" na descrição do produto.
     * @var boolean
     */
    protected $descProdQuebraLinha = true;
    /**
     * objeto fpdf()
     * @var \NFePHP\DA\Legacy\Pdf
     */
    protected $pdf;
    /**
     * XML NFe
     * @var string
     */
    protected $xml;
    /**
     * path para logomarca em jpg
     * @var string
     */
    protected $logomarca = '';
    /**
     * mesagens de erro
     * @var string
     */
    protected $errMsg = '';
    /**
     * status de erro true um erro ocorreu false sem erros
     * @var boolean
     */
    protected $errStatus = false;
    /**
     * orientação da DANFE
     * P-Retrato ou L-Paisagem
     * @var string
     */
    protected $orientacao = 'P';
    /**
     * formato do papel
     * @var string
     */
    protected $papel = 'A4';
    /**
     * Nome da Fonte para gerar o DANFE
     * @var string
     */
    protected $fontePadrao = 'Times';
    /**
     * Texto adicional da DANFE
     * @var string
     */
    protected $textoAdic = '';
    /**
     * Largura
     * @var float
     */
    protected $wAdic = 0;
    /**
     * largura imprimivel, em milímetros
     * @var float
     */
    protected $wPrint;
    /**
     * Comprimento (altura) imprimivel, em milímetros
     * @var float
     */
    protected $hPrint;
    /**
     * Altura maxima
     * @var float
     */
    protected $maxH;
    /**
     * Largura maxima
     * @var float
     */
    protected $maxW;
    /**
     * largura do canhoto (25mm) apenas para a formatação paisagem
     * @var float
     */
    protected $wCanhoto = 25;
    /**
     * Formato chave
     * @var string
     */
    protected $formatoChave = "#### #### #### #### #### #### #### #### #### #### ####";
    /**
     * quantidade de itens já processados na montagem do DANFE
     * @var integer
     */
    protected $qtdeItensProc;
    /**
     * Dom Document
     * @var \NFePHP\DA\Legacy\Dom
     */
    protected $dom;
    /**
     * Node
     * @var \DOMNode
     */
    protected $infNFe;
    /**
     * Node
     * @var \DOMNode
     */
    protected $ide;
    /**
     * Node
     * @var \DOMNode
     */
    protected $entrega;
    /**
     * Node
     * @var \DOMNode
     */
    protected $retirada;
    /**
     * Node
     * @var \DOMNode
     */
    protected $emit;
    /**
     * Node
     * @var \DOMNode
     */
    protected $dest;
    /**
     * Node
     * @var \DOMNode
     */
    protected $enderEmit;
    /**
     * Node
     * @var \DOMNode
     */
    protected $enderDest;
    /**
     * Node
     * @var \DOMNode
     */
    protected $det;
    /**
     * Node
     * @var \DOMNode
     */
    protected $cobr;
    /**
     * Node
     * @var \DOMNode
     */
    protected $dup;
    /**
     * Node
     * @var \DOMNode
     */
    protected $ICMSTot;
    /**
     * Node
     * @var \DOMNode
     */
    protected $ISSQNtot;
    /**
     * Node
     * @var \DOMNode
     */
    protected $transp;
    /**
     * Node
     * @var \DOMNode
     */
    protected $transporta;
    /**
     * Node
     * @var \DOMNode
     */
    protected $veicTransp;
    /**
     * Node reboque
     * @var \DOMNode
     */
    protected $reboque;
    /**
     * Node infAdic
     * @var \DOMNode
     */
    protected $infAdic;
    /**
     * Tipo de emissão
     * @var integer
     */
    protected $tpEmis;
    /**
     * Node infProt
     * @var \DOMNode
     */
    protected $infProt;
    /**
     * 1-Retrato/ 2-Paisagem
     * @var integer
     */
    protected $tpImp;
    /**
     * Node compra
     * @var \DOMNode
     */
    protected $compra;
    /**
     * ativa ou desativa o modo de debug
     * @var integer
     */
    protected $debugmode = false;
    /**
     * Creditos para integrador
     * @var string
     */
    protected $creditos = '';
    
    protected $textadicfontsize;

    /**
     * __construct
     *
     * @name  __construct
     * @param string  $xml Conteúdo XML da NF-e (com ou sem a tag nfeProc)
     */
    public function __construct($xml)
    {
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

    /**
     * monta
     * Monta a DANFE conforme as informações fornecidas para a classe durante sua
     * construção. Constroi DANFEs com até 3 páginas podendo conter até 56 itens.
     * A definição de margens e posições iniciais para a impressão são estabelecidas
     * pelo conteúdo da funçao e podem ser modificados.
     *
     * @param  string $orientacao (Opcional) Estabelece a orientação da impressão
     *  (ex. P-retrato), se nada for fornecido será usado o padrão da NFe
     * @param  string $papel      (Opcional) Estabelece o tamanho do papel (ex. A4)
     * @return string O ID da NFe numero de 44 digitos extraido do arquivo XML
     */
    public function monta(
        $logo = '',
        $orientacao = '',
        $papel = 'A4',
        $logoAlign = 'C',
        $depecNumReg = '',
        $margSup = 2,
        $margEsq = 2,
        $margInf = 2
    ) {
        $this->pdf = '';
        $this->logomarca = $logo;
        //se a orientação estiver em branco utilizar o padrão estabelecido na NF
        if ($orientacao == '') {
            if ($this->tpImp == '2') {
                $orientacao = 'L';
            } else {
                $orientacao = 'P';
            }
        }
        $this->orientacao = $orientacao;
        $this->papel = $papel;
        $this->logoAlign = $logoAlign;
        $this->numero_registro_dpec = $depecNumReg;
        //instancia a classe pdf
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        //margens do PDF, em milímetros. Obs.: a margem direita é sempre igual à
        //margem esquerda. A margem inferior *não* existe na FPDF, é definida aqui
        //apenas para controle se necessário ser maior do que a margem superior
        // posição inicial do conteúdo, a partir do canto superior esquerdo da página
        $xInic = $margEsq;
        if ($this->orientacao == 'P') {
            if ($papel == 'A4') {
                $this->maxW = 210;
                $this->maxH = 297;
            }
        } else {
            if ($papel == 'A4') {
                $this->maxW = 297;
                $this->maxH = 210;
                $xInic = $margEsq+10;
                //se paisagem multiplica a largura do canhoto pela quantidade de canhotos
                //$this->wCanhoto *= $this->qCanhoto;
            }
        }
        //total inicial de paginas
        $totPag = 1;
        //largura imprimivel em mm: largura da folha menos as margens esq/direita
        $this->wPrint = $this->maxW-($margEsq * 2);
        //comprimento (altura) imprimivel em mm: altura da folha menos as margens
        //superior e inferior
        $this->hPrint = $this->maxH-$margSup-$margInf;
        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        // fixa as margens
        $this->pdf->setMargins($margEsq, $margSup);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        // inicia o documento
        $this->pdf->open();
        // adiciona a primeira página
        $this->pdf->addPage($this->orientacao, $this->papel);
        $this->pdf->setLineWidth(0.1);
        $this->pdf->settextcolor(0, 0, 0);

        //##################################################################
        // CALCULO DO NUMERO DE PAGINAS A SEREM IMPRESSAS
        //##################################################################
        //Verificando quantas linhas serão usadas para impressão das duplicatas
        $linhasDup = 0;
        $qtdPag = 0;
        if (isset($this->dup) && $this->dup->length > 0) {
            $qtdPag = $this->dup->length;
        } elseif (isset($this->detPag) && $this->detPag->length > 0) {
            $qtdPag = $this->detPag->length;
        }
        if (($qtdPag > 0) && ($qtdPag <= 7)) {
            $linhasDup = 1;
        } elseif (($qtdPag > 7) && ($qtdPag <= 14)) {
            $linhasDup = 2;
        } elseif (($qtdPag > 14) && ($qtdPag <= 21)) {
            $linhasDup = 3;
        } elseif ($qtdPag > 21) {
            // chinnonsantos 11/05/2016: Limite máximo de impressão de duplicatas na NFe,
            // só vai ser exibito as 21 primeiras duplicatas (parcelas de pagamento),
            // se não oculpa espaço d+, cada linha comporta até 7 duplicatas.
            $linhasDup = 3;
        }
        //verifica se será impressa a linha dos serviços ISSQN
        $linhaISSQN = 0;
        if ((isset($this->ISSQNtot)) && ($this->getTagValue($this->ISSQNtot, 'vServ') > 0)) {
            $linhaISSQN = 1;
        }
        //calcular a altura necessária para os dados adicionais
        if ($this->orientacao == 'P') {
            $this->wAdic = round($this->wPrint*0.66, 0);
        } else {
            $this->wAdic = round(($this->wPrint-$this->wCanhoto)*0.5, 0);
        }
        $fontProduto = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>''];
        $this->textoAdic = '';
        if (isset($this->retirada)) {
            $txRetCNPJ = $this->getTagValue($this->retirada, "CNPJ");
            $txRetxLgr = $this->getTagValue($this->retirada, "xLgr");
            $txRetnro = $this->getTagValue($this->retirada, "nro");
            $txRetxCpl = $this->getTagValue($this->retirada, "xCpl", " - ");
            $txRetxBairro = $this->getTagValue($this->retirada, "xBairro");
            $txRetxMun = $this->getTagValue($this->retirada, "xMun");
            $txRetUF = $this->getTagValue($this->retirada, "UF");
            $this->textoAdic .= "LOCAL DE RETIRADA : ".
                    $txRetCNPJ.
                    '-' .
                    $txRetxLgr .
                    ', ' .
                    $txRetnro .
                    ' ' .
                    $txRetxCpl .
                    ' - ' .
                    $txRetxBairro .
                    ' ' .
                    $txRetxMun .
                    ' - ' .
                    $txRetUF .
                    "\r\n";
        }
        //dados do local de entrega da mercadoria
        if (isset($this->entrega)) {
            $txRetCNPJ = $this->getTagValue($this->entrega, "CNPJ");
            $txRetxLgr = $this->getTagValue($this->entrega, "xLgr");
            $txRetnro = $this->getTagValue($this->entrega, "nro");
            $txRetxCpl = $this->getTagValue($this->entrega, "xCpl", " - ");
            $txRetxBairro = $this->getTagValue($this->entrega, "xBairro");
            $txRetxMun = $this->getTagValue($this->entrega, "xMun");
            $txRetUF = $this->getTagValue($this->entrega, "UF");
            if ($this->textoAdic != '') {
                $this->textoAdic .= ". \r\n";
            }
            $this->textoAdic .= "LOCAL DE ENTREGA : ".$txRetCNPJ.'-'.$txRetxLgr.', '.$txRetnro.' '.$txRetxCpl.
               ' - '.$txRetxBairro.' '.$txRetxMun.' - '.$txRetUF."\r\n";
        }
        //informações adicionais
        $this->textoAdic .= $this->geraInformacoesDasNotasReferenciadas();
        if (isset($this->infAdic)) {
            $i = 0;
            if ($this->textoAdic != '') {
                $this->textoAdic .= ". \r\n";
            }
            $this->textoAdic .= ! empty($this->getTagValue($this->infAdic, "infCpl"))
            ? 'Inf. Contribuinte: ' . $this->anfaveaDANFE($this->getTagValue($this->infAdic, "infCpl"))
            : '';
            $infPedido = $this->geraInformacoesDaTagCompra();
            if ($infPedido != "") {
                $this->textoAdic .= $infPedido;
            }
            $this->textoAdic .= $this->getTagValue($this->dest, "email", ' Email do Destinatário: ');
            $this->textoAdic .= ! empty($this->getTagValue($this->infAdic, "infAdFisco"))
            ? "\r\n Inf. fisco: " . $this->getTagValue($this->infAdic, "infAdFisco")
            : '';
            $obsCont = $this->infAdic->getElementsByTagName("obsCont");
            if (isset($obsCont)) {
                foreach ($obsCont as $obs) {
                    $campo =  $obsCont->item($i)->getAttribute("xCampo");
                    $xTexto = ! empty($obsCont->item($i)->getElementsByTagName("xTexto")->item(0)->nodeValue)
                    ? $obsCont->item($i)->getElementsByTagName("xTexto")->item(0)->nodeValue
                    : '';
                    $this->textoAdic .= "\r\n" . $campo . ':  ' . trim($xTexto);
                    $i++;
                }
            }
        }
        //INCLUSO pela NT 2013.003 Lei da Transparência
        //verificar se a informação sobre o valor aproximado dos tributos
        //já se encontra no campo de informações adicionais
        if ($this->exibirValorTributos) {
            $flagVTT = strpos(strtolower(trim($this->textoAdic)), 'valor');
            $flagVTT = $flagVTT || strpos(strtolower(trim($this->textoAdic)), 'vl');
            $flagVTT = $flagVTT && strpos(strtolower(trim($this->textoAdic)), 'aprox');
            $flagVTT = $flagVTT && (strpos(strtolower(trim($this->textoAdic)), 'trib') ||
                    strpos(strtolower(trim($this->textoAdic)), 'imp'));
            $vTotTrib = $this->getTagValue($this->ICMSTot, 'vTotTrib');
            if ($vTotTrib != '' && !$flagVTT) {
                $this->textoAdic .= "\n Valor Aproximado dos Tributos : R$ "
                    . number_format($vTotTrib, 2, ",", ".");
            }
        }
        //fim da alteração NT 2013.003 Lei da Transparência
        $this->textoAdic = str_replace(";", "\n", $this->textoAdic);
        $alinhas = explode("\n", $this->textoAdic);
        $numlinhasdados = 0;
        foreach ($alinhas as $linha) {
            $numlinhasdados += $this->pdf->getNumLines($linha, $this->wAdic, $fontProduto);
        }
        $this->textadicfontsize = $this->pdf->fontSize;
        $hdadosadic = round(($numlinhasdados+3) * $this->textadicfontsize, 0);
        if ($hdadosadic > 70) {
            for ($per=1; $per>=0.01; $per=$per-0.01) {
                $this->textadicfontsize = $this->pdf->fontSize*$per;
                $hdadosadic = round(($numlinhasdados+3) * $this->textadicfontsize, 0);
                if ($hdadosadic <= 90) {
                    $hdadosadic = 70;
                    break;
                }
            }
        }
        
        if ($hdadosadic < 10) {
            $hdadosadic = 10;
        }
        //altura disponivel para os campos da DANFE
        $hcabecalho = 47;//para cabeçalho
        $hdestinatario = 25;//para destinatario
        $hduplicatas = 12;//para cada grupo de 7 duplicatas
        if (isset($this->entrega)) {
            $hlocalentrega = 25;
        } else {
            $hlocalentrega = 0;
        }
        if (isset($this->retirada)) {
            $hlocalretirada = 25;
        } else {
            $hlocalretirada = 0;
        }
        $himposto = 18;// para imposto
        $htransporte = 25;// para transporte
        $hissqn = 11;// para issqn
        $hfooter = 5;// para rodape
        $hCabecItens = 4;//cabeçalho dos itens
        //alturas disponiveis para os dados
        $hDispo1 = $this->hPrint - 10 - ($hcabecalho +
            //$hdestinatario + ($linhasDup * $hduplicatas) + $himposto + $htransporte +
            $hdestinatario + $hlocalentrega + $hlocalretirada +
            ($linhasDup * $hduplicatas) + $himposto + $htransporte +
            ($linhaISSQN * $hissqn) + $hdadosadic + $hfooter + $hCabecItens +
            $this->sizeExtraTextoFatura());
        if ($this->orientacao == 'P') {
            $hDispo1 -= 24 * $this->qCanhoto;//para canhoto
            $w = $this->wPrint;
        } else {
            $hcanhoto = $this->hPrint;//para canhoto
            $w = $this->wPrint - $this->wCanhoto;
        }
        $hDispo2 = $this->hPrint - 10 - ($hcabecalho + $hfooter + $hCabecItens)-4;
        //Contagem da altura ocupada para impressão dos itens
        $fontProduto = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>''];
        $numlinhas = 0;
        $hUsado = $hCabecItens;
        $w2 = round($w*0.28, 0);
        $hDispo = $hDispo1;
        $totPag = 1;
        $i = 0;
        while ($i < $this->det->length) {
            $texto = $this->descricaoProduto($this->det->item($i));
            $numlinhas = $this->pdf->getNumLines($texto, $w2, $fontProduto);
            $hUsado += round(($numlinhas * $this->pdf->fontSize) + ($numlinhas * 0.5), 2);
            if ($hUsado > $hDispo) {
                $totPag++;
                $hDispo = $hDispo2;
                $hUsado = $hCabecItens;
                // Remove canhoto para páginas secundárias em modo paisagem ('L')
                $w2 = round($this->wPrint*0.28, 0);
                $i--; // decrementa para readicionar o item que não coube nessa pagina na outra.
            }
            $i++;
        } //fim da soma das areas de itens usadas
        $qtdeItens = $i; //controle da quantidade de itens no DANFE
        //montagem da primeira página
        $pag = 1;
        
        $x = $margEsq;
        $y = $margSup;
        //coloca o(s) canhoto(s) da NFe
        if ($this->orientacao == 'P') {
            $y = $this->canhoto($margEsq, $margSup);
        } else {
            $this->canhoto($margEsq, $margSup);
            $x = 25;
        }
        //$x = $xInic;
        //$y = $yInic;
        
        //coloca o cabeçalho
        $y = $this->header($x, $y, $pag, $totPag);
        //coloca os dados do destinatário
        $y = $this->destinatarioDANFE($x, $y+1);
        
        //coloca os dados do local de retirada
        if (isset($this->retirada)) {
            $y = $this->localRetiradaDANFE($x, $y+1);
        }
        //coloca os dados do local de entrega
        if (isset($this->entrega)) {
            $y = $this->localEntregaDANFE($x, $y+1);
        }
        
        //Verifica as formas de pagamento da nota fiscal
        $formaPag = [];
        if (isset($this->detPag) && $this->detPag->length > 0) {
            foreach ($this->detPag as $k => $d) {
                $fPag = !empty($this->detPag->item($k)->getElementsByTagName('tPag')->item(0)->nodeValue)
                ? $this->detPag->item($k)->getElementsByTagName('tPag')->item(0)->nodeValue
                : '0';
                $formaPag[$fPag] = $fPag;
            }
        }
        //caso tenha boleto imprimir fatura
        if ($this->dup->length > 0) {
            $y = $this->fatura($x, $y+1);
        } else {
            //Se somente tiver a forma de pagamento sem pagamento ou outros não imprimir nada
            if (count($formaPag)=='1' && (isset($formaPag[90]) || isset($formaPag[99]))) {
                $y = $y;
            } else {
                //caso tenha mais de uma forma de pagamento ou seja diferente de boleto exibe a
                //forma de pagamento e o valor
                $y = $this->pagamento($x, $y+1);
            }
        }
        //coloca os dados dos impostos e totais da NFe
        $y = $this->imposto($x, $y+1);
        //coloca os dados do trasnporte
        $y = $this->transporte($x, $y+1);
        //itens da DANFE
        $nInicial = 0;

        $y = $this->itens($x, $y+1, $nInicial, $hDispo1, $pag, $totPag, $hCabecItens);

        //coloca os dados do ISSQN
        if ($linhaISSQN == 1) {
            $y = $this->issqn($x, $y+4);
        } else {
            $y += 4;
        }
        //coloca os dados adicionais da NFe
        $y = $this->dadosAdicionais($x, $y, $hdadosadic);
        //coloca o rodapé da página
        if ($this->orientacao == 'P') {
            $this->rodape($xInic);
        } else {
            $this->rodape($xInic);
        }

        //loop para páginas seguintes
        for ($n = 2; $n <= $totPag; $n++) {
            // fixa as margens
            $this->pdf->setMargins($margEsq, $margSup);
            //adiciona nova página
            $this->pdf->addPage($this->orientacao, $this->papel);
            //ajusta espessura das linhas
            $this->pdf->setLineWidth(0.1);
            //seta a cor do texto para petro
            $this->pdf->settextcolor(0, 0, 0);
            // posição inicial do relatorio
            $x = $margEsq;
            $y = $margSup;
            //coloca o cabeçalho na página adicional
            $y = $this->header($x, $y, $n, $totPag);
            //coloca os itens na página adicional
            $y = $this->itens($x, $y+1, $nInicial, $hDispo2, $n, $totPag, $hCabecItens);
            //coloca o rodapé da página
            if ($this->orientacao == 'P') {
                $this->rodape($margEsq);
            } else {
                $this->rodape($margEsq);
            }
            //se estiver na última página e ainda restar itens para inserir, adiciona mais uma página
            if ($n == $totPag && $this->qtdeItensProc < $qtdeItens) {
                $totPag++;
            }
        }
    }

    /**
     * anfavea
     * Função para transformar o campo cdata do padrão ANFAVEA para
     * texto imprimível
     *
     * @param  string $cdata campo CDATA
     * @return string conteúdo do campo CDATA como string
     */
    protected function anfaveaDANFE($cdata = '')
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
        //Retira a tag <FONTE IBPT> (caso existir) pois não é uma estrutura válida XML
        $cdata = str_replace('<FONTE IBPT>', '', $cdata);
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
     * Verifica o status da NFe
     *
     * @return array
     */
    protected function statusNFe()
    {
        if (!isset($this->nfeProc)) {
            return ['status' => false, 'message' => 'NFe NÃO PROTOCOLADA'];
        }
        if ($this->getTagValue($this->ide, "tpAmb") == '2') {
            return ['status' => false, 'message' => 'NFe EMITIDA EM HOMOLOGAÇÃO'];
        }
        $cStat = $this->getTagValue($this->nfeProc, "cStat");
        if ($cStat == '101'
            || $cStat == '151'
            || $cStat == '135'
            || $cStat == '155'
        ) {
            return ['status' => false, 'message' => 'NFe CANCELADA'];
        }
        
        if ($cStat == '110' ||
               $cStat == '301' ||
               $cStat == '302'
               
        ) {
            return ['status' => false, 'message' => 'NFe DENEGADA'];
        }
        return ['status' => true, 'message' => ''];
    }

    protected function notaDPEC()
    {
        return $this->numero_registro_dpec != '';
    }

    /**
     *header
     * Monta o cabelhalho da DANFE (retrato e paisagem)
     *
     * @param  number $x      Posição horizontal inicial, canto esquerdo
     * @param  number $y      Posição vertical inicial, canto superior
     * @param  number $pag    Número da Página
     * @param  number $totPag Total de páginas
     * @return number Posição vertical final
     */
    protected function header($x = 0, $y = 0, $pag = '1', $totPag = '1')
    {
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
              $maxW = $this->wPrint;
        } else {
            if ($pag == 1) { // primeira página
                $maxW = $this->wPrint - $this->wCanhoto;
            } else { // páginas seguintes
                $maxW = $this->wPrint;
            }
        }
        //####################################################################################
        //coluna esquerda identificação do emitente
        $w = round($maxW*0.41, 0);
        if ($this->orientacao == 'P') {
            $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>'I'];
        } else {
            $aFont = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B'];
        }
        $w1 = $w;
        $h=32;
        $oldY += $h;
        $this->pdf->textBox($x, $y, $w, $h);
        $texto = 'IDENTIFICAÇÃO DO EMITENTE';
        $this->pdf->textBox($x, $y, $w, 5, $texto, $aFont, 'T', 'C', 0, '');
        //estabelecer o alinhamento
        //pode ser left L, center C, right R, full logo L
        //se for left separar 1/3 da largura para o tamanho da imagem
        //os outros 2/3 serão usados para os dados do emitente
        //se for center separar 1/2 da altura para o logo e 1/2 para os dados
        //se for right separa 2/3 para os dados e o terço seguinte para o logo
        //se não houver logo centraliza dos dados do emitente
        // coloca o logo
        if (!empty($this->logomarca)) {
            $logoInfo = getimagesize($this->logomarca);
            $type = strtolower(explode('/', $logoInfo['mime'])[1]);
            if ($type == 'png') {
                $this->logomarca = $this->imagePNGtoJPG($this->logomarca);
                $type == 'jpg';
            }
            //largura da imagem em mm
            $logoWmm = ($logoInfo[0]/72)*25.4;
            //altura da imagem em mm
            $logoHmm = ($logoInfo[1]/72)*25.4;
            if ($this->logoAlign=='L') {
                $nImgW = round($w/3, 0);
                $nImgH = round($logoHmm * ($nImgW/$logoWmm), 0);
                $xImg = $x+1;
                $yImg = round(($h-$nImgH)/2, 0)+$y;
                //estabelecer posições do texto
                $x1 = round($xImg + $nImgW +1, 0);
                $y1 = round($h/3+$y, 0);
                $tw = round(2*$w/3, 0);
            } elseif ($this->logoAlign=='C') {
                $nImgH = round($h/3, 0);
                $nImgW = round($logoWmm * ($nImgH/$logoHmm), 0);
                $xImg = round(($w-$nImgW)/2+$x, 0);
                $yImg = $y+3;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            } elseif ($this->logoAlign=='R') {
                $nImgW = round($w/3, 0);
                $nImgH = round($logoHmm * ($nImgW/$logoWmm), 0);
                $xImg = round($x+($w-(1+$nImgW)), 0);
                $yImg = round(($h-$nImgH)/2, 0)+$y;
                $x1 = $x;
                $y1 = round($h/3+$y, 0);
                $tw = round(2*$w/3, 0);
            } elseif ($this->logoAlign=='F') {
                $nImgH = round($h-5, 0);
                $nImgW = round($logoWmm * ($nImgH/$logoHmm), 0);
                $xImg = round(($w-$nImgW)/2+$x, 0);
                $yImg = $y+3;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1, 0);
                $tw = $w;
            }
            $type = (substr($this->logomarca, 0, 7) === 'data://') ? 'jpg' : null;
            $this->pdf->Image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, $type);
        } else {
            $x1 = $x;
            $y1 = round($h/3+$y, 0);
            $tw = $w;
        }
        // monta as informações apenas se diferente de full logo
        if ($this->logoAlign !== 'F') {
            //Nome emitente
            $aFont = ['font'=>$this->fontePadrao, 'size'=>12, 'style'=>'B'];
            $texto = $this->emit->getElementsByTagName("xNome")->item(0)->nodeValue;
            $this->pdf->textBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
            //endereço
            $y1 = $y1+5;
            $aFont = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>''];
            $fone = ! empty($this->enderEmit->getElementsByTagName("fone")->item(0)->nodeValue)
            ? $this->enderEmit->getElementsByTagName("fone")->item(0)->nodeValue
            : '';
            $lgr = $this->getTagValue($this->enderEmit, "xLgr");
            $nro = $this->getTagValue($this->enderEmit, "nro");
            $cpl = $this->getTagValue($this->enderEmit, "xCpl", " - ");
            $bairro = $this->getTagValue($this->enderEmit, "xBairro");
            $CEP = $this->getTagValue($this->enderEmit, "CEP");
            $CEP = $this->formatField($CEP, "#####-###");
            $mun = $this->getTagValue($this->enderEmit, "xMun");
            $UF = $this->getTagValue($this->enderEmit, "UF");
            $texto = $lgr . ", " . $nro . $cpl . "\n" . $bairro . " - "
                . $CEP . "\n" . $mun . " - " . $UF . " "
                . "Fone/Fax: " . $fone;
            $this->pdf->textBox($x1, $y1, $tw, 8, $texto, $aFont, 'T', 'C', 0, '');
        }

        //####################################################################################
        //coluna central Danfe
        $x += $w;
        $w=round($maxW * 0.17, 0);//35;
        $w2 = $w;
        $h = 32;
        $this->pdf->textBox($x, $y, $w, $h);
  
        $texto = "DANFE";
        $aFont = ['font'=>$this->fontePadrao, 'size'=>14, 'style'=>'B'];
        $this->pdf->textBox($x, $y+1, $w, $h, $texto, $aFont, 'T', 'C', 0, '');
        $aFont = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>''];
        $texto = 'Documento Auxiliar da Nota Fiscal Eletrônica';
        $h = 20;
        $this->pdf->textBox($x, $y+6, $w, $h, $texto, $aFont, 'T', 'C', 0, '', false);

        $aFont = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>''];
        $texto = '0 - ENTRADA';
        $y1 = $y + 14;
        $h = 8;
        $this->pdf->textBox($x+2, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $texto = '1 - SAÍDA';
        $y1 = $y + 17;
        $this->pdf->textBox($x+2, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //tipo de nF
        $aFont = ['font'=>$this->fontePadrao, 'size'=>12, 'style'=>'B'];
        $y1 = $y + 13;
        $h = 7;
        $texto = $this->ide->getElementsByTagName('tpNF')->item(0)->nodeValue;
        $this->pdf->textBox($x+27, $y1, 5, $h, $texto, $aFont, 'C', 'C', 1, '');
        //numero da NF
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $y1 = $y + 20;
        $numNF = str_pad(
            $this->ide->getElementsByTagName('nNF')->item(0)->nodeValue,
            9,
            "0",
            STR_PAD_LEFT
        );
        $numNF = $this->formatField($numNF, "###.###.###");
        $texto = "Nº. " . $numNF;
        $this->pdf->textBox($x, $y1, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
        //Série
        $y1 = $y + 23;
        $serie = str_pad(
            $this->ide->getElementsByTagName('serie')->item(0)->nodeValue,
            3,
            "0",
            STR_PAD_LEFT
        );
        $texto = "Série " . $serie;
        $this->pdf->textBox($x, $y1, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
        //numero paginas
        $aFont = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>'I'];
        $y1 = $y + 26;
        $texto = "Folha " . $pag . "/" . $totPag;
        $this->pdf->textBox($x, $y1, $w, $h, $texto, $aFont, 'C', 'C', 0, '');

        //####################################################################################
        //coluna codigo de barras
        $x += $w;
        $w = ($maxW-$w1-$w2);//85;
        $w3 = $w;
        $h = 32;
        $this->pdf->textBox($x, $y, $w, $h);
        $this->pdf->setFillColor(0, 0, 0);
        $chave_acesso = str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        $bW = 75;
        $bH = 12;
        //codigo de barras
        $this->pdf->code128($x+(($w-$bW)/2), $y+2, $chave_acesso, $bW, $bH);
        //linhas divisorias
        $this->pdf->line($x, $y+4+$bH, $x+$w, $y+4+$bH);
        $this->pdf->line($x, $y+12+$bH, $x+$w, $y+12+$bH);
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $y1 = $y+4+$bH;
        $h = 7;
        $texto = 'CHAVE DE ACESSO';
        $this->pdf->textBox($x, $y1, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $aFont = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B'];
        $y1 = $y+8+$bH;
        $texto = $this->formatField($chave_acesso, $this->formatoChave);
        $this->pdf->textBox($x+2, $y1, $w-2, $h, $texto, $aFont, 'T', 'C', 0, '');
        $y1 = $y+12+$bH;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>''];
        $chaveContingencia="";
        if ($this->notaDpec()) {
            $cabecalhoProtoAutorizacao = 'NÚMERO DE REGISTRO DPEC';
        } else {
            $cabecalhoProtoAutorizacao = 'PROTOCOLO DE AUTORIZAÇÃO DE USO';
        }
        if (($this->tpEmis == 2 || $this->tpEmis == 5) && !$this->notaDpec()) {
            $cabecalhoProtoAutorizacao = "DADOS DA NF-E";
            $chaveContingencia = $this->geraChaveAdicionalDeContingencia();
            $this->pdf->setFillColor(0, 0, 0);
            //codigo de barras
            $this->pdf->code128($x+11, $y1+1, $chaveContingencia, $bW*.9, $bH/2);
        } else {
            $texto = 'Consulta de autenticidade no portal nacional da NF-e';
            $this->pdf->textBox($x+2, $y1, $w-2, $h, $texto, $aFont, 'T', 'C', 0, '');
            $y1 = $y+16+$bH;
            $texto = 'www.nfe.fazenda.gov.br/portal ou no site da Sefaz Autorizadora';
            $this->pdf->textBox(
                $x+2,
                $y1,
                $w-2,
                $h,
                $texto,
                $aFont,
                'T',
                'C',
                0,
                'http://www.nfe.fazenda.gov.br/portal ou no site da Sefaz Autorizadora'
            );
        }

        //####################################################################################
        //Dados da NF do cabeçalho
        //natureza da operação
        $texto = 'NATUREZA DA OPERAÇÃO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $w = $w1+$w2;
        $y = $oldY;
        $oldY += $h;
        $x = $oldX;
        $h = 7;
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->ide->getElementsByTagName("natOp")->item(0)->nodeValue;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        $x += $w;
        $w = $w3;
        //PROTOCOLO DE AUTORIZAÇÃO DE USO ou DADOS da NF-E
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $cabecalhoProtoAutorizacao, $aFont, 'T', 'L', 1, '');
        // algumas NFe podem estar sem o protocolo de uso portanto sua existencia deve ser
        // testada antes de tentar obter a informação.
        // NOTA : DANFE sem protocolo deve existir somente no caso de contingência !!!
        // Além disso, existem várias NFes em contingência que eu recebo com protocolo de autorização.
        // Na minha opinião, deveríamos mostra-lo, mas o  manual  da NFe v4.01 diz outra coisa...
        if (($this->tpEmis == 2 || $this->tpEmis == 5) && !$this->notaDpec()) {
            $aFont = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B'];
            $texto = $this->formatField(
                $chaveContingencia,
                "#### #### #### #### #### #### #### #### ####"
            );
            $cStat = '';
        } else {
            $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
            if ($this->notaDpec()) {
                $texto = $this->numero_registro_dpec;
                $cStat = '';
            } else {
                if (isset($this->nfeProc)) {
                    $texto = ! empty($this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue)
                    ? $this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue
                    : '';
                    $tsHora = $this->toTimestamp(
                        $this->nfeProc->getElementsByTagName("dhRecbto")->item(0)->nodeValue
                    );
                    if ($texto != '') {
                        $texto .= "  -  " . date('d/m/Y H:i:s', $tsHora);
                    }
                    $cStat = $this->nfeProc->getElementsByTagName("cStat")->item(0)->nodeValue;
                } else {
                    $texto = '';
                    $cStat = '';
                }
            }
        }
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //####################################################################################
        //INSCRIÇÃO ESTADUAL
        $w = round($maxW * 0.250, 0);
        $y += $h;
        $oldY += $h;
        $x = $oldX;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->getTagValue($this->emit, "IE");
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //INSCRIÇÃO MUNICIPAL
        $x += $w;
        $texto = 'INSCRIÇÃO MUNICIPAL';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->getTagValue($this->emit, "IM");
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //INSCRIÇÃO ESTADUAL DO SUBST. TRIBUT.
        $x += $w;
        $texto = 'INSCRIÇÃO ESTADUAL DO SUBST. TRIBUT.';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = ! empty($this->emit->getElementsByTagName("IEST")->item(0)->nodeValue)
        ? $this->emit->getElementsByTagName("IEST")->item(0)->nodeValue
        : '';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //CNPJ
        $x += $w;
        $w = ($maxW-(3 * $w));
        $texto = 'CNPJ';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        //Pegando valor do CPF/CNPJ
        if (! empty($this->emit->getElementsByTagName("CNPJ")->item(0)->nodeValue)) {
            $texto = $this->formatField(
                $this->emit->getElementsByTagName("CNPJ")->item(0)->nodeValue,
                "###.###.###/####-##"
            );
        } else {
            $texto = ! empty($this->emit->getElementsByTagName("CPF")->item(0)->nodeValue)
            ? $this->formatField(
                $this->emit->getElementsByTagName("CPF")->item(0)->nodeValue,
                "###.###.###-##"
            )
            : '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');

        //####################################################################################
        //Indicação de NF Homologação, cancelamento e falta de protocolo
        $tpAmb = $this->ide->getElementsByTagName('tpAmb')->item(0)->nodeValue;
        //indicar cancelamento
        $resp = $this->statusNFe();
        if (!$resp['status']) {
            $x = 10;
            $y = $this->hPrint-130;
            $h = 25;
            $w = $maxW-(2*$x);
            $this->pdf->settextcolor(90, 90, 90);
            $texto = $resp['message'];
            $aFont = ['font'=>$this->fontePadrao, 'size'=>48, 'style'=>'B'];
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $y += $h;
            $h = 5;
            $w = $maxW-(2*$x);
            if (isset($this->infProt) && $resp['status']) {
                $xMotivo = $this->infProt->getElementsByTagName("xMotivo")->item(0)->nodeValue;
            } else {
                $xMotivo = '';
            }
            $texto = "SEM VALOR FISCAL\n".$xMotivo;
            $aFont = ['font'=>$this->fontePadrao, 'size'=>48, 'style'=>'B'];
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->settextcolor(0, 0, 0);
        }
        
        /*
        if ($this->pNotaCancelada()) {
            //101 Cancelamento
            $x = 10;
            $y = $this->hPrint-130;
            $h = 25;
            $w = $maxW-(2*$x);
            $this->pdf->SetTextColor(90, 90, 90);
            $texto = "NFe CANCELADA";
            $aFont = ['font'=>$this->fontePadrao, 'size'=>48, 'style'=>'B'];
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }*/

        if ($this->notaDpec() || $this->tpEmis == 4) {
            //DPEC
            $x = 10;
            $y = $this->hPrint-130;
            $h = 25;
            $w = $maxW-(2*$x);
            $this->pdf->SetTextColor(200, 200, 200);
            $texto = "DANFE impresso em contingência -\n".
                     "DPEC regularmente recebido pela Receita\n".
                     "Federal do Brasil";
            $aFont = ['font'=>$this->fontePadrao, 'size'=>48, 'style'=>'B'];
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }
        /*
        if ($this->pNotaDenegada()) {
            //110 301 302 Denegada
            $x = 10;
            $y = $this->hPrint-130;
            $h = 25;
            $w = $maxW-(2*$x);
            $this->pdf->SetTextColor(90, 90, 90);
            $texto = "NFe USO DENEGADO";
            $aFont = ['font'=>$this->fontePadrao, 'size'=>48, 'style'=>'B'];
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $y += $h;
            $h = 5;
            $w = $maxW-(2*$x);
            if (isset($this->infProt)) {
                $xMotivo = $this->infProt->getElementsByTagName("xMotivo")->item(0)->nodeValue;
            } else {
                $xMotivo = '';
            }
            $texto = "SEM VALOR FISCAL\n".$xMotivo;
            $aFont = ['font'=>$this->fontePadrao, 'size'=>48, 'style'=>'B'];
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }
         *
         */
        //indicar sem valor
        /*
        if ($tpAmb != 1) {
            $x = 10;
            if ($this->orientacao == 'P') {
                $y = round($this->hPrint*2/3, 0);
            } else {
                $y = round($this->hPrint/2, 0);
            }
            $h = 5;
            $w = $maxW-(2*$x);
            $this->pdf->SetTextColor(90, 90, 90);
            $texto = "SEM VALOR FISCAL";
            $aFont = ['font'=>$this->fontePadrao, 'size'=>48, 'style'=>'B'];
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $aFont = ['font'=>$this->fontePadrao, 'size'=>30, 'style'=>'B'];
            $texto = "AMBIENTE DE HOMOLOGAÇÃO";
            $this->pdf->textBox($x, $y+14, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        } else {
            $x = 10;
            if ($this->orientacao == 'P') {
                $y = round($this->hPrint*2/3, 0);
            } else {
                $y = round($this->hPrint/2, 0);
            }//fim orientacao
            $h = 5;
            $w = $maxW-(2*$x);
            $this->pdf->SetTextColor(90, 90, 90);
            //indicar FALTA DO PROTOCOLO se NFe não for em contingência
            if (($this->tpEmis == 2 || $this->tpEmis == 5) && !$this->notaDpec()) {
                //Contingência
                $texto = "DANFE Emitido em Contingência";
                $aFont = ['font'=>$this->fontePadrao, 'size'=>48, 'style'=>'B'];
                $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                $aFont = ['font'=>$this->fontePadrao, 'size'=>30, 'style'=>'B'];
                $texto = "devido à problemas técnicos";
                $this->pdf->textBox($x, $y+12, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
            } else {
                if (!isset($this->nfeProc)) {
                    if (!$this->notaDpec()) {
                        $texto = "SEM VALOR FISCAL";
                        $aFont = ['font'=>$this->fontePadrao, 'size'=>48, 'style'=>'B'];
                        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    }
                    $aFont = ['font'=>$this->fontePadrao, 'size'=>30, 'style'=>'B'];
                    $texto = "FALTA PROTOCOLO DE APROVAÇÃO DA SEFAZ";
                    if (!$this->notaDpec()) {
                        $this->pdf->textBox($x, $y+12, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    } else {
                        $this->pdf->textBox($x, $y+25, $w, $h, $texto, $aFont, 'C', 'C', 0, '');
                    }
                }//fim nefProc
            }//fim tpEmis
            $this->pdf->SetTextColor(0, 0, 0);
        }
         *
         */
        return $oldY;
    } //fim header

    /**
     * destinatarioDANFE
     * Monta o campo com os dados do destinatário na DANFE. (retrato e paisagem)
     *
     * @name   destinatarioDANFE
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function destinatarioDANFE($x = 0, $y = 0)
    {
        //####################################################################################
        //DESTINATÁRIO / REMETENTE
        $oldX = $x;
        $oldY = $y;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 7;
        $texto = 'DESTINATÁRIO / REMETENTE';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //NOME / RAZÃO SOCIAL
        $w = round($maxW*0.61, 0);
        $w1 = $w;
        $y += 3;
        $texto = 'NOME / RAZÃO SOCIAL';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->dest->getElementsByTagName("xNome")->item(0)->nodeValue;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        if ($this->orientacao == 'P') {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 0, '');
        } else {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 1, '');
        }
        //CNPJ / CPF
        $x += $w;
        $w = round($maxW*0.23, 0);
        $w2 = $w;
        $texto = 'CNPJ / CPF';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        //Pegando valor do CPF/CNPJ
        if (! empty($this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue)) {
            $texto = $this->formatField(
                $this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue,
                "###.###.###/####-##"
            );
        } else {
            $texto = ! empty($this->dest->getElementsByTagName("CPF")->item(0)->nodeValue)
            ? $this->formatField(
                $this->dest->getElementsByTagName("CPF")->item(0)->nodeValue,
                "###.###.###-##"
            )
            : '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //DATA DA EMISSÃO
        $x += $w;
        $w = $maxW-($w1+$w2);
        $wx = $w;
        $texto = 'DATA DA EMISSÃO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $dEmi = ! empty($this->ide->getElementsByTagName("dEmi")->item(0)->nodeValue)
        ? $this->ide->getElementsByTagName("dEmi")->item(0)->nodeValue
        : '';
        if ($dEmi == '') {
            $dEmi = ! empty($this->ide->getElementsByTagName("dhEmi")->item(0)->nodeValue)
            ? $this->ide->getElementsByTagName("dhEmi")->item(0)->nodeValue
            : '';
            $aDemi = explode('T', $dEmi);
            $dEmi = $aDemi[0];
        }
        $texto = $this->ymdTodmy($dEmi);
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        if ($this->orientacao == 'P') {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        } else {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 1, '');
        }
        //ENDEREÇO
        $w = round($maxW*0.47, 0);
        $w1 = $w;
        $y += $h;
        $x = $oldX;
        $texto = 'ENDEREÇO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->dest->getElementsByTagName("xLgr")->item(0)->nodeValue;
        $texto .= ', ' . $this->dest->getElementsByTagName("nro")->item(0)->nodeValue;
        $texto .= $this->getTagValue($this->dest, "xCpl", " - ");

        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 0, '', true);
        //BAIRRO / DISTRITO
        $x += $w;
        $w = round($maxW*0.21, 0);
        $w2 = $w;
        $texto = 'BAIRRO / DISTRITO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->dest->getElementsByTagName("xBairro")->item(0)->nodeValue;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //CEP
        $x += $w;
        $w = $maxW-$w1-$w2-$wx;
        $w2 = $w;
        $texto = 'CEP';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = ! empty($this->dest->getElementsByTagName("CEP")->item(0)->nodeValue)
        ? $this->dest->getElementsByTagName("CEP")->item(0)->nodeValue
        : '';
        $texto = $this->formatField($texto, "#####-###");
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //DATA DA SAÍDA
        $x += $w;
        $w = $wx;
        $texto = 'DATA DA SAÍDA/ENTRADA';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $dSaiEnt = ! empty($this->ide->getElementsByTagName("dSaiEnt")->item(0)->nodeValue)
            ? $this->ide->getElementsByTagName("dSaiEnt")->item(0)->nodeValue
            : '';
        if ($dSaiEnt == '') {
            $dSaiEnt = ! empty($this->ide->getElementsByTagName("dhSaiEnt")->item(0)->nodeValue)
                ? $this->ide->getElementsByTagName("dhSaiEnt")->item(0)->nodeValue
                : '';
            $aDsaient = explode('T', $dSaiEnt);
            $dSaiEnt = $aDsaient[0];
        }
        $texto = $this->ymdTodmy($dSaiEnt);
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //MUNICÍPIO
        $w = $w1;
        $y += $h;
        $x = $oldX;
        $texto = 'MUNICÍPIO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->dest->getElementsByTagName("xMun")->item(0)->nodeValue;
        if (strtoupper(trim($texto)) == "EXTERIOR"
            && $this->dest->getElementsByTagName("xPais")->length > 0
        ) {
            $texto .= " - " .  $this->dest->getElementsByTagName("xPais")->item(0)->nodeValue;
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 0, '');
        //UF
        $x += $w;
        $w = 8;
        $texto = 'UF';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->dest->getElementsByTagName("UF")->item(0)->nodeValue;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //FONE / FAX
        $x += $w;
        $w = round(($maxW -$w1-$wx-8)/2, 0);
        $w3 = $w;
        $texto = 'FONE / FAX';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = ! empty($this->dest->getElementsByTagName("fone")->item(0)->nodeValue)
        ? $this->dest->getElementsByTagName("fone")->item(0)->nodeValue
        : '';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //INSCRIÇÃO ESTADUAL
        $x += $w;
        $w = $maxW -$w1-$wx-8-$w3;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $IE = $this->dest->getElementsByTagName("IE");
        $texto = ($IE && $IE->length > 0) ? $IE->item(0)->nodeValue : '';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //HORA DA SAÍDA
        $x += $w;
        $w = $wx;
        $texto = 'HORA DA SAÍDA/ENTRADA';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $hSaiEnt = ! empty($this->ide->getElementsByTagName("hSaiEnt")->item(0)->nodeValue)
        ? $this->ide->getElementsByTagName("hSaiEnt")->item(0)->nodeValue
        : '';
        if ($hSaiEnt == '') {
            $dhSaiEnt = ! empty($this->ide->getElementsByTagName("dhSaiEnt")->item(0)->nodeValue)
            ? $this->ide->getElementsByTagName("dhSaiEnt")->item(0)->nodeValue
            : '';
            $tsDhSaiEnt = $this->toTimestamp($dhSaiEnt);
            if ($tsDhSaiEnt != '') {
                $hSaiEnt = date('H:i:s', $tsDhSaiEnt);
            }
        }
        $texto = $hSaiEnt;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        return ($y + $h);
    } //fim da função destinatarioDANFE

    /**
     * localEntregaDANFE
     * Monta o campo com os dados do local de entrega na DANFE. (retrato e paisagem)
     *
     * @name   localEntregaDANFE
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function localEntregaDANFE($x = 0, $y = 0)
    {
        //####################################################################################
        //LOCAL DE ENTREGA
        $oldX = $x;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 7;
        $texto = 'INFORMAÇÕES DO LOCAL DE ENTREGA';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //NOME / RAZÃO SOCIAL
        $w = round($maxW*0.61, 0);
        $w1 = $w;
        $y += 3;
        $texto = 'NOME / RAZÃO SOCIAL';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = '';
        if ($this->entrega->getElementsByTagName("xNome")->item(0)) {
            $texto = $this->entrega->getElementsByTagName("xNome")->item(0)->nodeValue;
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        if ($this->orientacao == 'P') {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 0, '');
        } else {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 1, '');
        }
        //CNPJ / CPF
        $x += $w;
        $w = round($maxW*0.23, 0);
        $w2 = $w;
        $texto = 'CNPJ / CPF';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        //Pegando valor do CPF/CNPJ
        if (! empty($this->entrega->getElementsByTagName("CNPJ")->item(0)->nodeValue)) {
            $texto = $this->formatField(
                $this->entrega->getElementsByTagName("CNPJ")->item(0)->nodeValue,
                "###.###.###/####-##"
            );
        } else {
            $texto = ! empty($this->entrega->getElementsByTagName("CPF")->item(0)->nodeValue) ?
                    $this->formatField(
                        $this->entrega->getElementsByTagName("CPF")->item(0)->nodeValue,
                        "###.###.###-##"
                    ) : '';
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //INSCRIÇÃO ESTADUAL
        $x += $w;
        $w = $maxW-($w1+$w2);
        $wx = $w;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = '';
        if ($this->entrega->getElementsByTagName("IE")->item(0)) {
            $texto = $this->entrega->getElementsByTagName("IE")->item(0)->nodeValue;
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        if ($this->orientacao == 'P') {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        } else {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 1, '');
        }
        //ENDEREÇO
        $w = round($maxW*0.355, 0) + $wx;
        $w1 = $w;
        $y += $h;
        $x = $oldX;
        $texto = 'ENDEREÇO';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->entrega->getElementsByTagName("xLgr")->item(0)->nodeValue;
        $texto .= ', ' . $this->entrega->getElementsByTagName("nro")->item(0)->nodeValue;
        $texto .= $this->getTagValue($this->entrega, "xCpl", " - ");
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 0, '', true);
        //BAIRRO / DISTRITO
        $x += $w;
        $w = round($maxW*0.335, 0);
        $w2 = $w;
        $texto = 'BAIRRO / DISTRITO';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->entrega->getElementsByTagName("xBairro")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //CEP
        $x += $w;
        $w = $maxW-($w1+$w2);
        $texto = 'CEP';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = ! empty($this->entrega->getElementsByTagName("CEP")->item(0)->nodeValue) ?
                $this->entrega->getElementsByTagName("CEP")->item(0)->nodeValue : '';
        $texto = $this->formatField($texto, "#####-###");
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //MUNICÍPIO
        $w = round($maxW*0.805, 0);
        $w1 = $w;
        $y += $h;
        $x = $oldX;
        $texto = 'MUNICÍPIO';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->entrega->getElementsByTagName("xMun")->item(0)->nodeValue;
        if (strtoupper(trim($texto)) == "EXTERIOR" && $this->entrega->getElementsByTagName("xPais")->length > 0) {
            $texto .= " - " .  $this->entrega->getElementsByTagName("xPais")->item(0)->nodeValue;
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 0, '');
        //UF
        $x += $w;
        $w = 8;
        $texto = 'UF';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->entrega->getElementsByTagName("UF")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //FONE / FAX
        $x += $w;
        $w = $maxW-$w-$w1;
        $texto = 'FONE / FAX';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = ! empty($this->entrega->getElementsByTagName("fone")->item(0)->nodeValue) ?
                $this->entrega->getElementsByTagName("fone")->item(0)->nodeValue : '';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        return ($y + $h);
    } //fim da função localEntregaDANFE
    
    /**
     * localretiradaDANFE
     * Monta o campo com os dados do local de entrega na DANFE. (retrato e paisagem)
     *
     * @name   localretiradaDANFE
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function localRetiradaDANFE($x = 0, $y = 0)
    {
        //####################################################################################
        //LOCAL DE RETIRADA
        $oldX = $x;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        $w = $maxW;
        $h = 7;
        $texto = 'INFORMAÇÕES DO LOCAL DE RETIRADA';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //NOME / RAZÃO SOCIAL
        $w = round($maxW*0.61, 0);
        $w1 = $w;
        $y += 3;
        $texto = 'NOME / RAZÃO SOCIAL';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = '';
        if ($this->retirada->getElementsByTagName("xNome")->item(0)) {
            $texto = $this->retirada->getElementsByTagName("xNome")->item(0)->nodeValue;
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        if ($this->orientacao == 'P') {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 0, '');
        } else {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 1, '');
        }
        //CNPJ / CPF
        $x += $w;
        $w = round($maxW*0.23, 0);
        $w2 = $w;
        $texto = 'CNPJ / CPF';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        //Pegando valor do CPF/CNPJ
        if (! empty($this->retirada->getElementsByTagName("CNPJ")->item(0)->nodeValue)) {
            $texto = $this->formatField(
                $this->retirada->getElementsByTagName("CNPJ")->item(0)->nodeValue,
                "###.###.###/####-##"
            );
        } else {
            $texto = ! empty($this->retirada->getElementsByTagName("CPF")->item(0)->nodeValue) ?
                    $this->formatField(
                        $this->retirada->getElementsByTagName("CPF")->item(0)->nodeValue,
                        "###.###.###-##"
                    ) : '';
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //INSCRIÇÃO ESTADUAL
        $x += $w;
        $w = $maxW-($w1+$w2);
        $wx = $w;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = '';
        if ($this->retirada->getElementsByTagName("IE")->item(0)) {
            $texto = $this->retirada->getElementsByTagName("IE")->item(0)->nodeValue;
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        if ($this->orientacao == 'P') {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        } else {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 1, '');
        }
        //ENDEREÇO
        $w = round($maxW*0.355, 0) + $wx;
        $w1 = $w;
        $y += $h;
        $x = $oldX;
        $texto = 'ENDEREÇO';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->retirada->getElementsByTagName("xLgr")->item(0)->nodeValue;
        $texto .= ', ' . $this->retirada->getElementsByTagName("nro")->item(0)->nodeValue;
        $texto .= $this->getTagValue($this->retirada, "xCpl", " - ");
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 0, '', true);
        //BAIRRO / DISTRITO
        $x += $w;
        $w = round($maxW*0.335, 0);
        $w2 = $w;
        $texto = 'BAIRRO / DISTRITO';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->retirada->getElementsByTagName("xBairro")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //CEP
        $x += $w;
        $w = $maxW-($w1+$w2);
        $texto = 'CEP';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = ! empty($this->retirada->getElementsByTagName("CEP")->item(0)->nodeValue) ?
                $this->retirada->getElementsByTagName("CEP")->item(0)->nodeValue : '';
        $texto = $this->formatField($texto, "#####-###");
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //MUNICÍPIO
        $w = round($maxW*0.805, 0);
        $w1 = $w;
        $y += $h;
        $x = $oldX;
        $texto = 'MUNICÍPIO';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->retirada->getElementsByTagName("xMun")->item(0)->nodeValue;
        if (strtoupper(trim($texto)) == "EXTERIOR" && $this->retirada->getElementsByTagName("xPais")->length > 0) {
            $texto .= " - " .  $this->retirada->getElementsByTagName("xPais")->item(0)->nodeValue;
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 0, '');
        //UF
        $x += $w;
        $w = 8;
        $texto = 'UF';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $this->retirada->getElementsByTagName("UF")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //FONE / FAX
        $x += $w;
        $w = $maxW-$w-$w1;
        $texto = 'FONE / FAX';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>6, 'style'=>'');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = ! empty($this->retirada->getElementsByTagName("fone")->item(0)->nodeValue) ?
                $this->retirada->getElementsByTagName("fone")->item(0)->nodeValue : '';
        $aFont = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        return ($y + $h);
    } //fim da função localRetiradaDANFE
    
     /**
     * getTextoFatura
     * Gera a String do Texto da Fatura
      *
     * @name   getTextoFatura
     * @return uma String com o texto ou "";
     */
    protected function getTextoFatura()
    {
        if (isset($this->cobr)) {
            $fat = $this->cobr->getElementsByTagName("fat")->item(0);
            if (isset($fat)) {
                if (!empty($this->getTagValue($this->ide, "indPag"))) {
                    $textoIndPag = "";
                    $indPag = $this->getTagValue($this->ide, "indPag");
                    if ($indPag === "0") {
                        $textoIndPag = "Pagamento à Vista - ";
                    } elseif ($indPag === "1") {
                        $textoIndPag = "Pagamento à Prazo - ";
                    }
                    $nFat = $this->getTagValue($fat, "nFat", "Fatura: ");
                    $vOrig = $this->getTagValue($fat, "vOrig", " Valor Original: ");
                    $vDesc = $this->getTagValue($fat, "vDesc", " Desconto: ");
                    $vLiq = $this->getTagValue($fat, "vLiq", " Valor Líquido: ");
                    $texto = $textoIndPag . $nFat . $vOrig . $vDesc . $vLiq;
                    return $texto;
                } else {
                    $pag = $this->dom->getElementsByTagName("pag");
                    if ($tPag = $this->getTagValue($pag->item(0), "tPag")) {
                        return $this->tipoPag($tPag);
                    }
                }
            }
        }
        return "";
    }

     /**
     * sizeExtraTextoFatura
     * Calcula o espaço ocupado pelo texto da fatura. Este espaço só é utilizado quando não houver duplicata.
      *
     * @name   sizeExtraTextoFatura
     * @return integer
     */
    protected function sizeExtraTextoFatura()
    {
        $textoFatura = $this->getTextoFatura();
        //verificar se existem duplicatas
        if ($this->dup->length == 0 && $textoFatura !== "") {
            return 10;
        }
        return 0;
    }

    /**
     * fatura
     * Monta o campo de duplicatas da DANFE (retrato e paisagem)
     *
     * @name   fatura
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function fatura($x, $y)
    {
        $linha = 1;
        $h = 8+3;
        $oldx = $x;
        $textoFatura = $this->getTextoFatura();
        //verificar se existem duplicatas
        if ($this->dup->length > 0 || $textoFatura !== "") {
            //#####################################################################
            //FATURA / DUPLICATA
            $texto = "FATURA / DUPLICATA";
            if ($this->orientacao == 'P') {
                $w = $this->wPrint;
            } else {
                $w = 271;
            }
            $h = 8;
            $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
            $y += 3;
            $dups = "";
            $dupcont = 0;
            $nFat = $this->dup->length;
            if ($nFat > 7) {
                $myH = 6;
                $myW = $this->wPrint;
                if ($this->orientacao == 'L') {
                    $myW -= $this->wCanhoto;
                }
                $aFont = ['font' => $this->fontePadrao, 'size' => 9, 'style' => ''];
                $texto = "Existem mais de 7 duplicatas registradas, portanto não "
                    . "serão exibidas, confira diretamente pelo XML.";
                $this->pdf->textBox($x, $y, $myW, $myH, $texto, $aFont, 'C', 'C', 1, '');
                return ($y + $h - 3);
            }
            if ($textoFatura !== "" && $this->exibirTextoFatura) {
                $myH=6;
                $myW = $this->wPrint;
                if ($this->orientacao == 'L') {
                    $myW -= $this->wCanhoto;
                }
                $aFont = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>''];
                $this->pdf->textBox($x, $y, $myW, $myH, $textoFatura, $aFont, 'C', 'L', 1, '');
                $y+=$myH+1;
            }
            if ($this->orientacao == 'P') {
                $w = round($this->wPrint/7.018, 0)-1;
            } else {
                $w = 28;
            }
            $increm = 1;
            foreach ($this->dup as $k => $d) {
                $nDup = ! empty($this->dup->item($k)->getElementsByTagName('nDup')->item(0)->nodeValue)
                ? $this->dup->item($k)->getElementsByTagName('nDup')->item(0)->nodeValue
                : '';
                $dDup = ! empty($this->dup->item($k)->getElementsByTagName('dVenc')->item(0)->nodeValue)
                ? $this->ymdTodmy($this->dup->item($k)->getElementsByTagName('dVenc')->item(0)->nodeValue)
                : '';
                $vDup = ! empty($this->dup->item($k)->getElementsByTagName('vDup')->item(0)->nodeValue)
                ? 'R$ ' . number_format(
                    $this->dup->item($k)->getElementsByTagName('vDup')->item(0)->nodeValue,
                    2,
                    ",",
                    "."
                )
                : '';
                $h = 8;
                $texto = '';
                if ($nDup!='0' && $nDup!='') {
                    $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
                    $this->pdf->textBox($x, $y, $w, $h, 'Num.', $aFont, 'T', 'L', 1, '');
                    $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
                    $this->pdf->textBox($x, $y, $w, $h, $nDup, $aFont, 'T', 'R', 0, '');
                } else {
                    $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
                    $this->pdf->textBox($x, $y, $w, $h, ($dupcont+1)."", $aFont, 'T', 'L', 1, '');
                }
                $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
                $this->pdf->textBox($x, $y, $w, $h, 'Venc.', $aFont, 'C', 'L', 0, '');
                $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
                $this->pdf->textBox($x, $y, $w, $h, $dDup, $aFont, 'C', 'R', 0, '');
                $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
                $this->pdf->textBox($x, $y, $w, $h, 'Valor', $aFont, 'B', 'L', 0, '');
                $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
                $this->pdf->textBox($x, $y, $w, $h, $vDup, $aFont, 'B', 'R', 0, '');
                $x += $w+$increm;
                $dupcont += 1;
                if ($this->orientacao == 'P') {
                    $maxDupCont = 6;
                } else {
                    $maxDupCont = 8;
                }
                if ($dupcont > $maxDupCont) {
                    $y += 9;
                    $x = $oldx;
                    $dupcont = 0;
                    $linha += 1;
                }
                if ($linha == 5) {
                    $linha = 4;
                    break;
                }
            }
            if ($dupcont == 0) {
                $y -= 9;
                $linha--;
            }
            return ($y+$h);
        } else {
            $linha = 0;
            return ($y-2);
        }
    }

    /**
     * pagamento
     * Monta o campo de pagamentos da DANFE (retrato e paisagem) (foi baseada na fatura)
     *
     * @name   pagamento
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function pagamento($x, $y)
    {
        $linha = 1;
        $h = 8+3;
        $oldx = $x;
        //verificar se existem cobranças definidas
        if (isset($this->detPag) && $this->detPag->length > 0) {
            //#####################################################################
            //Tipo de pagamento
            $texto = "PAGAMENTO";
            if ($this->orientacao == 'P') {
                $w = $this->wPrint;
            } else {
                $w = 271;
            }
            $h = 8;
            $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
            $y += 3;
            $dups = "";
            $dupcont = 0;
            if ($this->orientacao == 'P') {
                $w = round($this->wPrint/7.018, 0)-1;
            } else {
                $w = 28;
            }
            if ($this->orientacao == 'P') {
                $maxDupCont = 6;
            } else {
                $maxDupCont = 8;
            }
            $increm = 1;
            $formaPagamento = ['01'=>'Dinheiro','02'=>'Cheque','03'=>'Cartão de Crédito',
                                    '04'=>'Cartão de Débito','05'=>'Crédito Loja','10'=>'Vale Alimentação',
                                    '11'=>'Vale Refeição','12'=>'Vale Presente','13'=>'Vale Combustível',
                                    '14'=>'Duplicata Mercantil','15'=>'Boleto','90'=>'Sem pagamento','99'=>'Outros'];
            $bandeira = ['01'=>'Visa','02'=>'Mastercard','03'=>'American','04'=>'Sorocred','05'=>'Diners',
                              '06'=>'Elo','07'=>'Hipercard','08'=>'Aura','09'=>'Cabal','99'=>'Outros'];
            foreach ($this->detPag as $k => $d) {
                $fPag = !empty($this->detPag->item($k)->getElementsByTagName('tPag')->item(0)->nodeValue)
                ? $this->detPag->item($k)->getElementsByTagName('tPag')->item(0)->nodeValue
                : '0';
                $vPag = ! empty($this->detPag->item($k)->getElementsByTagName('vPag')->item(0)->nodeValue)
                ? 'R$ ' . number_format(
                    $this->detPag->item($k)->getElementsByTagName('vPag')->item(0)->nodeValue,
                    2,
                    ",",
                    "."
                )
                : '';
                $h = 6;
                $texto = '';
                if (isset($formaPagamento[$fPag])) {
                    /*Exibir Item sem pagamento ou outros?*/
                    if ($fPag=='90' || $fPag=='99') {
                        continue;
                    }
                    $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
                    $this->pdf->textBox($x, $y, $w, $h, 'Forma', $aFont, 'T', 'L', 1, '');
                    $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
                    $this->pdf->textBox($x, $y, $w, $h, $formaPagamento[$fPag], $aFont, 'T', 'R', 0, '');
                } else {
                    $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>''];
                    $this->pdf->textBox($x, $y, $w, $h, "Forma ".$fPag." não encontrado", $aFont, 'T', 'L', 1, '');
                }
                $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
                $this->pdf->textBox($x, $y, $w, $h, 'Valor', $aFont, 'B', 'L', 0, '');
                $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
                $this->pdf->textBox($x, $y, $w, $h, $vPag, $aFont, 'B', 'R', 0, '');
                $x += $w+$increm;
                $dupcont += 1;

                if ($dupcont>$maxDupCont) {
                    $y += 9;
                    $x = $oldx;
                    $dupcont = 0;
                    $linha += 1;
                }
                if ($linha == 5) {
                    $linha = 4;
                    break;
                }
            }
            if ($dupcont == 0) {
                $y -= 9;
                $linha--;
            }
            return ($y+$h);
        } else {
            $linha = 0;
            return ($y-2);
        }
    } //fim da função pagamento
    
    /**
     * impostoHelper
     * Auxilia a montagem dos campos de impostos e totais da DANFE
     *
     * @name   impostoHelper
     * @param  float $x Posição horizontal canto esquerdo
     * @param  float $y Posição vertical canto superior
     * @param  float $w Largura do campo
     * @param  float $h Altura do campo
     * @param  float $h Título do campo
     * @param  float $h Valor do imposto
     * @return float Sugestão do $x do próximo imposto
     */
    protected function impostoHelper($x, $y, $w, $h, $titulo, $campoImposto)
    {
        $valorImposto = '0, 00';
        $the_field = $this->ICMSTot->getElementsByTagName($campoImposto)->item(0);
        if (isset($the_field)) {
            $the_value = $the_field->nodeValue;
            if (!empty($the_value)) {
                $valorImposto = number_format($the_value, 2, ",", ".");
            }
        }

        $fontTitulo = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $fontValor = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $titulo, $fontTitulo, 'T', 'L', 1, '');
        $this->pdf->textBox($x, $y, $w, $h, $valorImposto, $fontValor, 'B', 'R', 0, '');

        $next_x = $x + $w;
        return $next_x;
    }

    /**
     * imposto
     * Monta o campo de impostos e totais da DANFE (retrato e paisagem)
     *
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    protected function imposto($x, $y)
    {
        $x_inicial = $x;
        //#####################################################################


        $campos_por_linha = 9;
        if (!$this->exibirPIS) {
            $campos_por_linha--;
        }
        if (!$this->exibirIcmsInterestadual) {
            $campos_por_linha -= 2;
        }

        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
            $title_size = 31;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
            $title_size = 40;
        }
        $w = $maxW / $campos_por_linha;

        $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
        $texto = "CÁLCULO DO IMPOSTO";
        $this->pdf->textBox($x, $y, $title_size, 8, $texto, $aFont, 'T', 'L', 0, '');
        $y += 3;
        $h = 7;

        $x = $this->impostoHelper($x, $y, $w, $h, "BASE DE CÁLC. DO ICMS", "vBC");
        $x = $this->impostoHelper($x, $y, $w, $h, "VALOR DO ICMS", "vICMS");
        $x = $this->impostoHelper($x, $y, $w, $h, "BASE DE CÁLC. ICMS S.T.", "vBCST");
        $x = $this->impostoHelper($x, $y, $w, $h, "VALOR DO ICMS SUBST.", "vST");
        $x = $this->impostoHelper($x, $y, $w, $h, "V. IMP. IMPORTAÇÃO", "vII");

        if ($this->exibirIcmsInterestadual) {
            $x = $this->impostoHelper($x, $y, $w, $h, "V. ICMS UF REMET.", "vICMSUFRemet");
            $x = $this->impostoHelper($x, $y, $w, $h, "V. FCP UF DEST.", "vFCPUFDest");
        }

        if ($this->exibirPIS) {
            $x = $this->impostoHelper($x, $y, $w, $h, "VALOR DO PIS", "vPIS");
        }

        $x = $this->impostoHelper($x, $y, $w, $h, "V. TOTAL PRODUTOS", "vProd");

        //

        $y += $h;
        $x = $x_inicial;

        $x = $this->impostoHelper($x, $y, $w, $h, "VALOR DO FRETE", "vFrete");
        $x = $this->impostoHelper($x, $y, $w, $h, "VALOR DO SEGURO", "vSeg");
        $x = $this->impostoHelper($x, $y, $w, $h, "DESCONTO", "vDesc");
        $x = $this->impostoHelper($x, $y, $w, $h, "OUTRAS DESPESAS", "vOutro");
        $x = $this->impostoHelper($x, $y, $w, $h, "VALOR TOTAL IPI", "vIPI");

        if ($this->exibirIcmsInterestadual) {
            $x = $this->impostoHelper($x, $y, $w, $h, "V. ICMS UF DEST.", "vICMSUFDest");
            $x = $this->impostoHelper($x, $y, $w, $h, "V. TOT. TRIB.", "vTotTrib");
        }

        if ($this->exibirPIS) {
            $x = $this->impostoHelper($x, $y, $w, $h, "VALOR DA COFINS", "vCOFINS");
        }
        $x = $this->impostoHelper($x, $y, $w, $h, "V. TOTAL DA NOTA", "vNF");

        return ($y+$h);
    } //fim imposto

    /**
     * transporte
     * Monta o campo de transportes da DANFE (retrato e paisagem)
     *
     * @name   transporte
     * @param  float $x Posição horizontal canto esquerdo
     * @param  float $y Posição vertical canto superior
     * @return float Posição vertical final
     */
    protected function transporte($x, $y)
    {
        $oldX = $x;
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }
        //#####################################################################
        //TRANSPORTADOR / VOLUMES TRANSPORTADOS
        $texto = "TRANSPORTADOR / VOLUMES TRANSPORTADOS";
        $w = $maxW;
        $h = 7;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //NOME / RAZÃO SOCIAL
        $w1 = $maxW*0.29;
        $y += 3;
        $texto = 'NOME / RAZÃO SOCIAL';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->transporta)) {
            $texto = ! empty($this->transporta->getElementsByTagName("xNome")->item(0)->nodeValue)
            ? $this->transporta->getElementsByTagName("xNome")->item(0)->nodeValue
            : '';
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'B', 'L', 0, '');
        //FRETE POR CONTA
        $x += $w1;
        $w2 = $maxW*0.15;
        $texto = 'FRETE';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 1, '');
        $tipoFrete = ! empty($this->transp->getElementsByTagName("modFrete")->item(0)->nodeValue)
        ? $this->transp->getElementsByTagName("modFrete")->item(0)->nodeValue
        : '0';
        switch ($tipoFrete) {
            case 0:
                $texto = "0-Por conta do Rem";
                break;
            case 1:
                $texto = "1-Por conta do Dest";
                break;
            case 2:
                $texto = "2-Por conta de Terceiros";
                break;
            case 3:
                $texto = "3-Próprio por conta do Rem";
                break;
            case 4:
                $texto = "4-Próprio por conta do Dest";
                break;
            case 9:
                $texto = "9-Sem Transporte";
                break;
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'C', 'C', 1, '');
        //CÓDIGO ANTT
        $x += $w2;
        $texto = 'CÓDIGO ANTT';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->veicTransp)) {
            $texto = ! empty($this->veicTransp->getElementsByTagName("RNTC")->item(0)->nodeValue)
            ? $this->veicTransp->getElementsByTagName("RNTC")->item(0)->nodeValue
            : '';
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'B', 'C', 0, '');
        //PLACA DO VEÍC
        $x += $w2;
        $texto = 'PLACA DO VEÍCULO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->veicTransp)) {
            $texto = ! empty($this->veicTransp->getElementsByTagName("placa")->item(0)->nodeValue)
            ? $this->veicTransp->getElementsByTagName("placa")->item(0)->nodeValue
            : '';
        } elseif (isset($this->reboque)) {
            $texto = ! empty($this->reboque->getElementsByTagName("placa")->item(0)->nodeValue)
            ? $this->reboque->getElementsByTagName("placa")->item(0)->nodeValue
            : '';
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'B', 'C', 0, '');
        //UF
        $x += $w2;
        $w3 = round($maxW*0.04, 0);
        $texto = 'UF';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->veicTransp)) {
            $texto = ! empty($this->veicTransp->getElementsByTagName("UF")->item(0)->nodeValue)
            ? $this->veicTransp->getElementsByTagName("UF")->item(0)->nodeValue
            : '';
        } elseif (isset($this->reboque)) {
            $texto = ! empty($this->reboque->getElementsByTagName("UF")->item(0)->nodeValue)
            ? $this->reboque->getElementsByTagName("UF")->item(0)->nodeValue
            : '';
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'B', 'C', 0, '');
        //CNPJ / CPF
        $x += $w3;
        $w = $maxW-($w1+3*$w2+$w3);
        $texto = 'CNPJ / CPF';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->transporta)) {
            $texto = ! empty($this->transporta->getElementsByTagName("CNPJ")->item(0)->nodeValue)
            ? $this->formatField(
                $this->transporta->getElementsByTagName("CNPJ")->item(0)->nodeValue,
                "##.###.###/####-##"
            )
            : '';
            if ($texto == '') {
                $texto = ! empty($this->transporta->getElementsByTagName("CPF")->item(0)->nodeValue)
                ? $this->formatField(
                    $this->transporta->getElementsByTagName("CPF")->item(0)->nodeValue,
                    "###.###.###-##"
                )
                : '';
            }
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //#####################################################################
        //ENDEREÇO
        $y += $h;
        $x = $oldX;
        $h = 7;
        $w1 = $maxW*0.44;
        $texto = 'ENDEREÇO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->transporta)) {
            $texto = ! empty($this->transporta->getElementsByTagName("xEnder")->item(0)->nodeValue)
                ? $this->transporta->getElementsByTagName("xEnder")->item(0)->nodeValue
                : '';
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'B', 'L', 0, '');
        //MUNICÍPIO
        $x += $w1;
        $w2 = round($maxW*0.30, 0);
        $texto = 'MUNICÍPIO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->transporta)) {
            $texto = ! empty($this->transporta->getElementsByTagName("xMun")->item(0)->nodeValue)
                ? $this->transporta->getElementsByTagName("xMun")->item(0)->nodeValue
                : '';
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'B', 'C', 0, '');
        //UF
        $x += $w2;
        $w3 = round($maxW*0.04, 0);
        $texto = 'UF';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->transporta)) {
            $texto = ! empty($this->transporta->getElementsByTagName("UF")->item(0)->nodeValue)
                ? $this->transporta->getElementsByTagName("UF")->item(0)->nodeValue
                : '';
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'B', 'C', 0, '');
        //INSCRIÇÃO ESTADUAL
        $x += $w3;
        $w = $maxW-($w1+$w2+$w3);
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = '';
        if (isset($this->transporta)) {
            if (! empty($this->transporta->getElementsByTagName("IE")->item(0)->nodeValue)) {
                $texto = $this->transporta->getElementsByTagName("IE")->item(0)->nodeValue;
            }
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'C', 0, '');
        //Tratar Multiplos volumes
        $volumes = $this->transp->getElementsByTagName('vol');
        $quantidade = 0;
        $especie = '';
        $marca = '';
        $numero = '';
        $texto = '';
        $pesoBruto=0;
        $pesoLiquido=0;
        foreach ($volumes as $volume) {
            $quantidade += ! empty($volume->getElementsByTagName("qVol")->item(0)->nodeValue) ?
                    $volume->getElementsByTagName("qVol")->item(0)->nodeValue : 0;
            $pesoBruto += ! empty($volume->getElementsByTagName("pesoB")->item(0)->nodeValue) ?
                    $volume->getElementsByTagName("pesoB")->item(0)->nodeValue : 0;
            $pesoLiquido += ! empty($volume->getElementsByTagName("pesoL")->item(0)->nodeValue) ?
                    $volume->getElementsByTagName("pesoL")->item(0)->nodeValue : 0;
            $texto = ! empty($this->transp->getElementsByTagName("esp")->item(0)->nodeValue) ?
                    $this->transp->getElementsByTagName("esp")->item(0)->nodeValue : '';
            if ($texto != $especie && $especie != '') {
                //tem várias especies
                $especie = 'VARIAS';
            } else {
                $especie = $texto;
            }
            $texto = ! empty($this->transp->getElementsByTagName("marca")->item(0)->nodeValue)
                ? $this->transp->getElementsByTagName("marca")->item(0)->nodeValue
                : '';
            if ($texto != $marca && $marca != '') {
                //tem várias especies
                $marca = 'VARIAS';
            } else {
                $marca = $texto;
            }
            $texto = ! empty($this->transp->getElementsByTagName("nVol")->item(0)->nodeValue)
                ? $this->transp->getElementsByTagName("nVol")->item(0)->nodeValue
                : '';
            if ($texto != $numero && $numero != '') {
                //tem várias especies
                $numero = 'VARIOS';
            } else {
                $numero = $texto;
            }
        }

        //#####################################################################
        //QUANTIDADE
        $y += $h;
        $x = $oldX;
        $h = 7;
        $w1 = round($maxW*0.10, 0);
        $texto = 'QUANTIDADE';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (!empty($quantidade)) {
            $texto = $quantidade;
            $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
            $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'B', 'C', 0, '');
        }
        //ESPÉCIE
        $x += $w1;
        $w2 = round($maxW*0.17, 0);
        $texto = 'ESPÉCIE';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $especie;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'B', 'C', 0, '');
        //MARCA
        $x += $w2;
        $texto = 'MARCA';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = ! empty($this->transp->getElementsByTagName("marca")->item(0)->nodeValue) ?
                $this->transp->getElementsByTagName("marca")->item(0)->nodeValue : '';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'B', 'C', 0, '');
        //NUMERAÇÃO
        $x += $w2;
        $texto = 'NUMERAÇÃO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'T', 'L', 1, '');
        $texto = $numero;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'B', 'C', 0, '');
        //PESO BRUTO
        $x += $w2;
        $w3 = round($maxW*0.20, 0);
        $texto = 'PESO BRUTO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (is_numeric($pesoBruto) && $pesoBruto > 0) {
            $texto = number_format($pesoBruto, 3, ",", ".");
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'B', 'R', 0, '');
        //PESO LÍQUIDO
        $x += $w3;
        $w = $maxW -($w1+3*$w2+$w3);
        $texto = 'PESO LÍQUIDO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (is_numeric($pesoLiquido) && $pesoLiquido > 0) {
            $texto = number_format($pesoLiquido, 3, ",", ".");
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'R', 0, '');
        return ($y+$h);
    } //fim transporte



    protected function descricaoProdutoHelper($origem, $campo, $formato)
    {
        $valor_original = $origem->getElementsByTagName($campo)->item(0);
        if (!isset($valor_original)) {
            return "";
        }
        $valor_original = $valor_original->nodeValue;
        $valor = ! empty($valor_original) ? number_format($valor_original, 2, ",", ".") : '';

        if ($valor != "") {
            return sprintf($formato, $valor);
        }
        return "";
    }

    /**
     * descricaoProduto
     * Monta a string de descrição de cada Produto
     *
     * @name   descricaoProduto
     * @param  DOMNode itemProd
     * @return string descricao do produto
     */
    protected function descricaoProduto($itemProd)
    {
        $prod = $itemProd->getElementsByTagName('prod')->item(0);
        $ICMS = $itemProd->getElementsByTagName("ICMS")->item(0);
        $ICMSUFDest = $itemProd->getElementsByTagName("ICMSUFDest")->item(0);
        $impostos = '';

        if (!empty($ICMS)) {
            $impostos .= $this->descricaoProdutoHelper($ICMS, "vBCFCP", " BcFcp=%s");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "pFCP", " pFcp=%s%%");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "vFCP", " vFcp=%s");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "pRedBC", " pRedBC=%s%%");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "pMVAST", " IVA/MVA=%s%%");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "pICMSST", " pIcmsSt=%s%%");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "vBCST", " BcIcmsSt=%s");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "vICMSST", " vIcmsSt=%s");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "vBCFCPST", " BcFcpSt=%s");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "pFCPST", " pFcpSt=%s%%");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "vFCPST", " vFcpSt=%s");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "vBCSTRet", " Retido na compra: BASE ICMS ST=%s");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "pST", " pSt=%s");
            $impostos .= $this->descricaoProdutoHelper($ICMS, "vICMSSTRet", " VALOR ICMS ST=%s");
        }
        if (!empty($ICMSUFDest)) {
            $impostos .= $this->descricaoProdutoHelper($ICMSUFDest, "pFCPUFDest", " pFCPUFDest=%s%%");
            $impostos .= $this->descricaoProdutoHelper($ICMSUFDest, "pICMSUFDest", " pICMSUFDest=%s%%");
            $impostos .= $this->descricaoProdutoHelper($ICMSUFDest, "pICMSInterPart", " pICMSInterPart=%s%%");
            $impostos .= $this->descricaoProdutoHelper($ICMSUFDest, "vFCPUFDest", " vFCPUFDest=%s");
            $impostos .= $this->descricaoProdutoHelper($ICMSUFDest, "vICMSUFDest", " vICMSUFDest=%s");
            $impostos .= $this->descricaoProdutoHelper($ICMSUFDest, "vICMSUFRemet", " vICMSUFRemet=%s");
        }
        $infAdProd = ! empty($itemProd->getElementsByTagName('infAdProd')->item(0)->nodeValue)
        ? substr(
            $this->anfaveaDANFE($itemProd->getElementsByTagName('infAdProd')->item(0)->nodeValue),
            0,
            500
        )
        : '';
        if (! empty($infAdProd)) {
            $infAdProd = trim($infAdProd);
            $infAdProd .= ' ';
        }
        $loteTxt ='';
        $rastro = $prod->getElementsByTagName("med");
        if (!empty($prod->getElementsByTagName("rastro"))) {
            $rastro = $prod->getElementsByTagName("rastro");
            $i = 0;
            while ($i < $rastro->length) {
                $loteTxt .= $this->getTagValue($rastro->item($i), 'nLote', ' Lote: ');
                $loteTxt .= $this->getTagValue($rastro->item($i), 'qLote', ' Quant: ');
                $loteTxt .= $this->getTagDate($rastro->item($i), 'dFab', ' Fab: ');
                $loteTxt .= $this->getTagDate($rastro->item($i), 'dVal', ' Val: ');
                $loteTxt .= $this->getTagValue($rastro->item($i), 'vPMC', ' PMC: ');
                $i++;
            }
            if ($loteTxt != '') {
                $loteTxt.= ' ';
            }
        }
        //NT2013.006 FCI
        $nFCI = (! empty($itemProd->getElementsByTagName('nFCI')->item(0)->nodeValue)) ?
                ' FCI:'.$itemProd->getElementsByTagName('nFCI')->item(0)->nodeValue : '';
        $tmp_ad=$infAdProd . ($this->descProdInfoComplemento ? $loteTxt . $impostos . $nFCI : '');
        $texto = $prod->getElementsByTagName("xProd")->item(0)->nodeValue
            . (strlen($tmp_ad)!=0?"\n    ".$tmp_ad:'');
        //decodifica os caracteres html no xml
        $texto = html_entity_decode($texto);
        if ($this->descProdQuebraLinha) {
            $texto = str_replace(";", "\n", $texto);
        }
        return $texto;
    }

    /**
     * itens
     * Monta o campo de itens da DANFE (retrato e paisagem)
     *
     * @name   itens
     * @param  float $x       Posição horizontal canto esquerdo
     * @param  float $y       Posição vertical canto superior
     * @param  float $nInicio Número do item inicial
     * @param  float $max     Número do item final
     * @param  float $hmax    Altura máxima do campo de itens em mm
     * @return float Posição vertical final
     */
    protected function itens($x, $y, &$nInicio, $hmax, $pag = 0, $totpag = 0, $hCabecItens = 7)
    {
        $oldX = $x;
        $oldY = $y;
        $totItens = $this->det->length;
        //#####################################################################
        //DADOS DOS PRODUTOS / SERVIÇOS
        $texto = "DADOS DOS PRODUTOS / SERVIÇOS ";
        if ($this->orientacao == 'P') {
            $w = $this->wPrint;
        } else {
            if ($nInicio < 2) { // primeira página
                $w = $this->wPrint - $this->wCanhoto;
            } else { // páginas seguintes
                $w = $this->wPrint;
            }
        }
        $h = 4;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        $y += 3;
        //desenha a caixa dos dados dos itens da NF
        $hmax += 1;
        $texto = '';
        $this->pdf->textBox($x, $y, $w, $hmax);
        //##################################################################################
        // cabecalho LOOP COM OS DADOS DOS PRODUTOS
        //CÓDIGO PRODUTO
        $texto = "CÓDIGO PRODUTO";
        $w1 = round($w*0.09, 0);
        $h = 4;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w1, $y, $x+$w1, $y+$hmax);
        //DESCRIÇÃO DO PRODUTO / SERVIÇO
        $x += $w1;
        $w2 = round($w*0.25, 0);
        $texto = 'DESCRIÇÃO DO PRODUTO / SERVIÇO';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w2, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w2, $y, $x+$w2, $y+$hmax);
        //NCM/SH
        $x += $w2;
        $w3 = round($w*0.06, 0);
        $texto = 'NCM/SH';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w3, $y, $x+$w3, $y+$hmax);
        //O/CST ou O/CSOSN
        $x += $w3;
        $w4 = round($w*0.05, 0);
        $texto = 'O/CSOSN';//Regime do Simples CRT = 1 ou CRT = 2
        if ($this->getTagValue($this->emit, 'CRT') == '3') {
             $texto = 'O/CST';//Regime Normal
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w4, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w4, $y, $x+$w4, $y+$hmax);
        //CFOP
        $x += $w4;
        $w5 = round($w*0.04, 0);
        $texto = 'CFOP';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w5, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w5, $y, $x+$w5, $y+$hmax);
        //UN
        $x += $w5;
        $w6 = round($w*0.03, 0);
        $texto = 'UN';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w6, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w6, $y, $x+$w6, $y+$hmax);
        //QUANT
        $x += $w6;
        $w7 = round($w*0.08, 0);
        $texto = 'QUANT';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w7, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w7, $y, $x+$w7, $y+$hmax);
        //VALOR UNIT
        $x += $w7;
        $w8 = round($w*0.06, 0);
        $texto = 'VALOR UNIT';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w8, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w8, $y, $x+$w8, $y+$hmax);
        //VALOR TOTAL
        $x += $w8;
        $w9 = round($w*0.06, 0);
        $texto = 'VALOR TOTAL';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w9, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w9, $y, $x+$w9, $y+$hmax);
        //VALOR DESCONTO
        $x += $w9;
        $w10 = round($w*0.05, 0);
        $texto = 'VALOR DESC';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w10, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w10, $y, $x+$w10, $y+$hmax);
        //B.CÁLC ICMS
        $x += $w10;
        $w11 = round($w*0.06, 0);
        $texto = 'B.CÁLC ICMS';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w11, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w11, $y, $x+$w11, $y+$hmax);
        //VALOR ICMS
        $x += $w11;
        $w12 = round($w*0.06, 0);
        $texto = 'VALOR ICMS';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w12, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w12, $y, $x+$w12, $y+$hmax);
        //VALOR IPI
        $x += $w12;
        $w13 = round($w*0.05, 0);
        $texto = 'VALOR IPI';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w13, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w13, $y, $x+$w13, $y+$hmax);
        //ALÍQ. ICMS
        $x += $w13;
        $w14 = round($w*0.04, 0);
        $texto = 'ALÍQ. ICMS';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w14, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($x+$w14, $y, $x+$w14, $y+$hmax);
        //ALÍQ. IPI
        $x += $w14;
        $w15 = $w-($w1+$w2+$w3+$w4+$w5+$w6+$w7+$w8+$w9+$w10+$w11+$w12+$w13+$w14);
        $texto = 'ALÍQ. IPI';
        $this->pdf->textBox($x, $y, $w15, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        $this->pdf->line($oldX, $y+$h+1, $oldX + $w, $y+$h+1);
        $y += 5;
        //##################################################################################
        // LOOP COM OS DADOS DOS PRODUTOS
        $i = 0;
        $hUsado = $hCabecItens;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>''];
        foreach ($this->det as $d) {
            if ($i >= $nInicio) {
                $thisItem = $this->det->item($i);
                //carrega as tags do item
                $prod = $thisItem->getElementsByTagName("prod")->item(0);
                $imposto = $this->det->item($i)->getElementsByTagName("imposto")->item(0);
                $ICMS = $imposto->getElementsByTagName("ICMS")->item(0);
                $IPI  = $imposto->getElementsByTagName("IPI")->item(0);
                $textoProduto = trim($this->descricaoProduto($thisItem));

                $linhaDescr = $this->pdf->getNumLines($textoProduto, $w2, $aFont);
                $h = round(($linhaDescr * $this->pdf->fontSize)+ ($linhaDescr * 0.5), 2);
                $hUsado += $h;

                $diffH = $hmax - $hUsado;

                if ($pag != $totpag) {
                    if (1 > $diffH && $i < $totItens) {
                        //ultrapassa a capacidade para uma única página
                        //o restante dos dados serão usados nas proximas paginas
                        $nInicio = $i;
                        break;
                    }
                }
                $y_linha=$y+$h;
                // linha entre itens
                $this->pdf->dashedHLine($oldX, $y_linha, $w, 0.1, 120);
                //corrige o x
                $x=$oldX;
                //codigo do produto
                $texto = $prod->getElementsByTagName("cProd")->item(0)->nodeValue;
                $this->pdf->textBox($x, $y, $w1, $h, $texto, $aFont, 'T', 'C', 0, '');
                $x += $w1;
                //DESCRIÇÃO
                if ($this->orientacao == 'P') {
                    $this->pdf->textBox($x, $y, $w2, $h, $textoProduto, $aFont, 'T', 'L', 0, '', false);
                } else {
                    $this->pdf->textBox($x, $y, $w2, $h, $textoProduto, $aFont, 'T', 'L', 0, '', false);
                }
                $x += $w2;
                //NCM
                $texto = ! empty($prod->getElementsByTagName("NCM")->item(0)->nodeValue) ?
                        $prod->getElementsByTagName("NCM")->item(0)->nodeValue : '';
                $this->pdf->textBox($x, $y, $w3, $h, $texto, $aFont, 'T', 'C', 0, '');
                $x += $w3;
                //CST
                if (isset($ICMS)) {
                    $origem =  $this->getTagValue($ICMS, "orig");
                    $cst =  $this->getTagValue($ICMS, "CST");
                    $csosn =  $this->getTagValue($ICMS, "CSOSN");
                    $texto = $origem.$cst.$csosn;
                    $this->pdf->textBox($x, $y, $w4, $h, $texto, $aFont, 'T', 'C', 0, '');
                }
                //CFOP
                $x += $w4;
                $texto = $prod->getElementsByTagName("CFOP")->item(0)->nodeValue;
                $this->pdf->textBox($x, $y, $w5, $h, $texto, $aFont, 'T', 'C', 0, '');
                //Unidade
                $x += $w5;
                $texto = $prod->getElementsByTagName("uCom")->item(0)->nodeValue;
                $this->pdf->textBox($x, $y, $w6, $h, $texto, $aFont, 'T', 'C', 0, '');
                $x += $w6;
                if ($this->orientacao == 'P') {
                    $alinhamento = 'R';
                } else {
                    $alinhamento = 'R';
                }
                // QTDADE
                $texto = number_format($prod->getElementsByTagName("qCom")->item(0)->nodeValue, 4, ",", ".");
                $this->pdf->textBox($x, $y, $w7, $h, $texto, $aFont, 'T', $alinhamento, 0, '');
                $x += $w7;
                // Valor Unitário
                $texto = number_format($prod->getElementsByTagName("vUnCom")->item(0)->nodeValue, 4, ",", ".");
                $this->pdf->textBox($x, $y, $w8, $h, $texto, $aFont, 'T', $alinhamento, 0, '');
                $x += $w8;
                // Valor do Produto
                $texto = "";
                if (is_numeric($prod->getElementsByTagName("vProd")->item(0)->nodeValue)) {
                    $texto = number_format($prod->getElementsByTagName("vProd")->item(0)->nodeValue, 2, ",", ".");
                }
                $this->pdf->textBox($x, $y, $w9, $h, $texto, $aFont, 'T', $alinhamento, 0, '');
                $x += $w9;
                //Valor do Desconto
                $texto = number_format($prod->getElementsByTagName("vDesc")->item(0)->nodeValue, 2, ",", ".");
                $this->pdf->textBox($x, $y, $w10, $h, $texto, $aFont, 'T', $alinhamento, 0, '');
                //Valor da Base de calculo
                $x += $w10;
                if (isset($ICMS)) {
                    $texto = ! empty($ICMS->getElementsByTagName("vBC")->item(0)->nodeValue)
                    ? number_format(
                        $ICMS->getElementsByTagName("vBC")->item(0)->nodeValue,
                        2,
                        ",",
                        "."
                    )
                    : '0, 00';
                    $this->pdf->textBox($x, $y, $w11, $h, $texto, $aFont, 'T', $alinhamento, 0, '');
                }
                //Valor do ICMS
                $x += $w11;
                if (isset($ICMS)) {
                    $texto = ! empty($ICMS->getElementsByTagName("vICMS")->item(0)->nodeValue)
                    ? number_format(
                        $ICMS->getElementsByTagName("vICMS")->item(0)->nodeValue,
                        2,
                        ",",
                        "."
                    )
                    : '0, 00';
                    $this->pdf->textBox($x, $y, $w12, $h, $texto, $aFont, 'T', $alinhamento, 0, '');
                }
                //Valor do IPI
                $x += $w12;
                if (isset($IPI)) {
                    $texto = ! empty($IPI->getElementsByTagName("vIPI")->item(0)->nodeValue)
                    ? number_format(
                        $IPI->getElementsByTagName("vIPI")->item(0)->nodeValue,
                        2,
                        ",",
                        "."
                    )
                    :'';
                } else {
                    $texto = '';
                }
                $this->pdf->textBox($x, $y, $w13, $h, $texto, $aFont, 'T', $alinhamento, 0, '');
                // %ICMS
                $x += $w13;
                if (isset($ICMS)) {
                    $texto = ! empty($ICMS->getElementsByTagName("pICMS")->item(0)->nodeValue)
                    ? number_format(
                        $ICMS->getElementsByTagName("pICMS")->item(0)->nodeValue,
                        2,
                        ",",
                        "."
                    )
                    : '0, 00';
                    $this->pdf->textBox($x, $y, $w14, $h, $texto, $aFont, 'T', 'C', 0, '');
                }
                //%IPI
                $x += $w14;
                if (isset($IPI)) {
                    $texto = ! empty($IPI->getElementsByTagName("pIPI")->item(0)->nodeValue)
                    ? number_format(
                        $IPI->getElementsByTagName("pIPI")->item(0)->nodeValue,
                        2,
                        ",",
                        "."
                    )
                    : '';
                } else {
                    $texto = '';
                }
                $this->pdf->textBox($x, $y, $w15, $h, $texto, $aFont, 'T', 'C', 0, '');


                // Dados do Veiculo Somente para veiculo 0 Km
                $veicProd = $prod->getElementsByTagName("veicProd")->item(0);
                // Tag somente é gerada para veiculo 0k, e só é permitido um veiculo por NF-e por conta do detran
                // Verifica se a Tag existe
                if (!empty($veicProd)) {
                    $this->dadosItenVeiculoDANFE($oldX, $y, $nInicio, $h, $prod);
                }


                $y += $h;
                $i++;
                //incrementa o controle dos itens processados.
                $this->qtdeItensProc++;
            } else {
                $i++;
            }
        }
        return $oldY+$hmax;
    }


    /**
     * dadosItenVeiculoDANFE
     * Coloca os dados do veiculo abaixo do item da NFe. (retrato e paisagem)
     *
     * @param float  $x    Posição horizontal
     *                     canto esquerdo
     * @param float  $y    Posição vertical
     *                     canto superior
     * @param        $nInicio
     * @param float  $h    altura do campo
     * @param object $prod Contendo todos os dados do item
     */

    protected function dadosItenVeiculoDANFE($x, $y, &$nInicio, $h, $prod)
    {
        $oldX = $x;
        $oldY = $y;

        if ($this->orientacao == 'P') {
            $w = $this->wPrint;
        } else {
            if ($nInicio < 2) { // primeira página
                $w = $this->wPrint - $this->wCanhoto;
            } else { // páginas seguintes
                $w = $this->wPrint;
            }
        }

        $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>''];

        $w1 = round($w*0.09, 0);

        // Tabela Renavam Combustivel
        $renavamCombustivel = [
            1=>'ALCOOL',
            2=>'GASOLINA',
            3=>'DIESEL',
            4=>'GASOGENIO',
            5=>'GAS METANO',
            6=>'ELETRICO/FONTE INTERNA',
            7=>'ELETRICO/FONTE EXTERNA',
            8=>'GASOL/GAS NATURAL COMBUSTIVEL',
            9=>'ALCOOL/GAS NATURAL COMBUSTIVEL',
            10=>'DIESEL/GAS NATURAL COMBUSTIVEL',
            11=>'VIDE/CAMPO/OBSERVACAO',
            12=>'ALCOOL/GAS NATURAL VEICULAR',
            13=>'GASOLINA/GAS NATURAL VEICULAR',
            14=>'DIESEL/GAS NATURAL VEICULAR',
            15=>'GAS NATURAL VEICULAR',
            16=>'ALCOOL/GASOLINA',
            17=>'GASOLINA/ALCOOL/GAS NATURAL',
            18=>'GASOLINA/ELETRICO'
        ];

        $renavamEspecie = [
            1=>'PASSAGEIRO',
            2=>'CARGA',
            3=>'MISTO',
            4=>'CORRIDA',
            5=>'TRACAO',
            6=>'ESPECIAL',
            7=>'COLECAO'
        ];

        $renavamTiposVeiculos = [
            1=>'BICICLETA',
            2=>'CICLOMOTOR',
            3=>'MOTONETA',
            4=>'MOTOCICLETA',
            5=>'TRICICLO',
            6=>'AUTOMOVEL',
            7=>'MICROONIBUS',
            8=>'ONIBUS',
            9=>'BONDE',
            10=>'REBOQUE',
            11=>'SEMI-REBOQUE',
            12=>'CHARRETE',
            13=>'CAMIONETA',
            14=>'CAMINHAO',
            15=>'CARROCA',
            16=>'CARRO DE MAO',
            17=>'CAMINHAO TRATOR',
            18=>'TRATOR DE RODAS',
            19=>'TRATOR DE ESTEIRAS',
            20=>'TRATOR MISTO',
            21=>'QUADRICICLO',
            22=>'CHASSI/PLATAFORMA',
            23=>'CAMINHONETE',
            24=>'SIDE-CAR',
            25=>'UTILITARIO',
            26=>'MOTOR-CASA'
        ];

        $renavamTipoPintura = [
            'F'=>'FOSCA',
            'S'=>'SÓLIDA',
            'P'=>'PEROLIZADA',
            'M'=>'METALICA',
        ];

        $veicProd = $prod->getElementsByTagName("veicProd")->item(0);

        $veiculoChassi = $veicProd->getElementsByTagName("chassi")->item(0)->nodeValue;
        $veiculoCor = $veicProd->getElementsByTagName("xCor")->item(0)->nodeValue;
        $veiculoCilindrada = $veicProd->getElementsByTagName("cilin")->item(0)->nodeValue;
        $veiculoCmkg = $veicProd->getElementsByTagName("CMT")->item(0)->nodeValue;
        $veiculoTipo = $veicProd->getElementsByTagName("tpVeic")->item(0)->nodeValue;

        $veiculoMotor = $veicProd->getElementsByTagName("nMotor")->item(0)->nodeValue;
        $veiculoRenavam = $veicProd->getElementsByTagName("cMod")->item(0)->nodeValue;
        $veiculoHp = $veicProd->getElementsByTagName("pot")->item(0)->nodeValue;
        $veiculoPlaca = ''; //$veiculo->getElementsByTagName("CMT")->item(0)->nodeValue;
        $veiculoTipoPintura = $veicProd->getElementsByTagName("tpPint")->item(0)->nodeValue;
        $veiculoMarcaModelo = $prod->getElementsByTagName("xProd")->item(0)->nodeValue;
        $veiculoEspecie = $veicProd->getElementsByTagName("espVeic")->item(0)->nodeValue;
        $veiculoCombustivel = $veicProd->getElementsByTagName("tpComb")->item(0)->nodeValue;
        $veiculoSerial = $veicProd->getElementsByTagName("nSerie")->item(0)->nodeValue;
        $veiculoFabricacao = $veicProd->getElementsByTagName("anoFab")->item(0)->nodeValue;
        $veiculoModelo = $veicProd->getElementsByTagName("anoMod")->item(0)->nodeValue;
        $veiculoDistancia = $veicProd->getElementsByTagName("dist")->item(0)->nodeValue;

        $x = $oldX;

        $yVeic = $y + $h;
        $texto = 'Chassi: ............: ' . $veiculoChassi;
        $this->pdf->textBox($x, $yVeic, $w1+40, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Cor...................: ' . $veiculoCor;
        $this->pdf->textBox($x, $yVeic, $w1+40, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Cilindrada........: ' . $veiculoCilindrada;
        $this->pdf->textBox($x, $yVeic, $w1+40, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Cmkg...............: ' . $veiculoCmkg;
        $this->pdf->textBox($x, $yVeic, $w1+40, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Tipo.................: ' . ($renavamTiposVeiculos[intval($veiculoTipo)] ?? $veiculoTipo);
         $this->pdf->textBox($x, $yVeic, $w1+40, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic = $y + $h;
        $xVeic = $x + 65;
        $texto = 'Nº Motor: .........: ' . $veiculoMotor;
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Renavam...........: ' . $veiculoRenavam;
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'HP.....................: ' . $veiculoHp;
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Placa.................: ' . $veiculoPlaca;
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Tipo Pintura......: ' . ($renavamEspecie[intval($veiculoTipoPintura)] ?? $veiculoTipoPintura);
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic = $y + $h;
        $xVeic = $xVeic + 55;
        $texto = 'Marca / Modelo.....: ' . $veiculoMarcaModelo;
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Especie..................: ' . ($renavamEspecie[intval($veiculoEspecie)] ?? $veiculoEspecie);
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Combustivel..........: ' . ($renavamCombustivel[intval($veiculoCombustivel)] ?? $veiculoCombustivel);
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Serial.....................: ' . $veiculoSerial;
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Ano Fab/Mod........: '. $veiculoFabricacao . '/' . $veiculoModelo;
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
        $yVeic += $h;
        $texto = 'Distancia Entre Eixos(mm)..: '. $veiculoDistancia;
        $this->pdf->textBox($xVeic, $yVeic, $w1+50, $h, $texto, $aFont, 'T', 'L', 0, '');
    }

    /**
     * issqn
     * Monta o campo de serviços do DANFE
     *
     * @name   issqn (retrato e paisagem)
     * @param  float $x Posição horizontal canto esquerdo
     * @param  float $y Posição vertical canto superior
     * @return float Posição vertical final
     */
    protected function issqn($x, $y)
    {
        $oldX = $x;
        //#####################################################################
        //CÁLCULO DO ISSQN
        $texto = "CÁLCULO DO ISSQN";
        $w = $this->wPrint;
        $h = 7;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 0, '');
        //INSCRIÇÃO MUNICIPAL
        $y += 3;
        $w = round($this->wPrint*0.23, 0);
        $texto = 'INSCRIÇÃO MUNICIPAL';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        //inscrição municipal
        $texto = ! empty($this->emit->getElementsByTagName("IM")->item(0)->nodeValue) ?
                $this->emit->getElementsByTagName("IM")->item(0)->nodeValue : '';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'L', 0, '');
        //VALOR TOTAL DOS SERVIÇOS
        $x += $w;
        $texto = 'VALOR TOTAL DOS SERVIÇOS';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->ISSQNtot)) {
            $texto = ! empty($this->ISSQNtot->getElementsByTagName("vServ")->item(0)->nodeValue) ?
                    $this->ISSQNtot->getElementsByTagName("vServ")->item(0)->nodeValue : '';
            $texto = number_format($texto, 2, ",", ".");
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'R', 0, '');
        //BASE DE CÁLCULO DO ISSQN
        $x += $w;
        $texto = 'BASE DE CÁLCULO DO ISSQN';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->ISSQNtot)) {
            $texto = ! empty($this->ISSQNtot->getElementsByTagName("vBC")->item(0)->nodeValue) ?
                    $this->ISSQNtot->getElementsByTagName("vBC")->item(0)->nodeValue : '';
            $texto = ! empty($texto) ? number_format($texto, 2, ",", ".") : '';
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'R', 0, '');
        //VALOR TOTAL DO ISSQN
        $x += $w;
        if ($this->orientacao == 'P') {
            $w = $this->wPrint - (3 * $w);
        } else {
            $w = $this->wPrint - (3 * $w)-$this->wCanhoto;
        }
        $texto = 'VALOR TOTAL DO ISSQN';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        if (isset($this->ISSQNtot)) {
            $texto = ! empty($this->ISSQNtot->getElementsByTagName("vISS")->item(0)->nodeValue) ?
                    $this->ISSQNtot->getElementsByTagName("vISS")->item(0)->nodeValue : '';
            $texto = ! empty($texto) ? number_format($texto, 2, ",", ".") : '';
        } else {
            $texto = '';
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'B', 'R', 0, '');
        return ($y+$h+1);
    }

    /**
     *dadosAdicionais
     * Coloca o grupo de dados adicionais da NFe. (retrato e paisagem)
     *
     * @name   dadosAdicionais
     * @param  float $x Posição horizontal canto esquerdo
     * @param  float $y Posição vertical canto superior
     * @param  float $h altura do campo
     * @return float Posição vertical final (eixo Y)
     */
    protected function dadosAdicionais($x, $y, $h)
    {
        //##################################################################################
        //DADOS ADICIONAIS
        $texto = "DADOS ADICIONAIS";
        if ($this->orientacao == 'P') {
              $w = $this->wPrint;
        } else {
              $w = $this->wPrint-$this->wCanhoto;
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, 8, $texto, $aFont, 'T', 'L', 0, '');
        //INFORMAÇÕES COMPLEMENTARES
        $texto = "INFORMAÇÕES COMPLEMENTARES";
        $y += 3;
        $w = $this->wAdic;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        //o texto com os dados adicionais foi obtido na função montaDANFE
        //e carregado em uma propriedade privada da classe
        //$this->wAdic com a largura do campo
        //$this->textoAdic com o texto completo do campo
        $y += 1;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>$this->textadicfontsize*$this->pdf->k, 'style'=>''];
        $this->pdf->textBox($x, $y+2, $w-2, $h-3, $this->textoAdic, $aFont, 'T', 'L', 0, '', false);
        //RESERVADO AO FISCO
        $texto = "RESERVADO AO FISCO";
        if (isset($this->nfeProc) && $this->nfeProc->getElementsByTagName("xMsg")->length) {
            $texto = $texto . ' ' . $this->nfeProc->getElementsByTagName("xMsg")->item(0)->nodeValue;
        }
        $x += $w;
        $y -= 1;
        if ($this->orientacao == 'P') {
            $w = $this->wPrint-$w;
        } else {
            $w = $this->wPrint-$w-$this->wCanhoto;
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'T', 'L', 1, '');
        //inserir texto informando caso de contingência
        // 1 - Normal - emissão normal;
        // 2 - Contingência FS - emissão em contingência com impressão do DANFE em Formulário de Segurança;
        // 3 - Contingência SCAN - emissão em contingência no Sistema de Contingência do Ambiente Nacional;
        // 4 - Contingência DPEC - emissão em contingência com envio da Declaração
        //     Prévia de Emissão em Contingência;
        // 5 - Contingência FS-DA - emissão em contingência com impressão do DANFE em Formulário de
        //     Segurança para Impressão de Documento Auxiliar de Documento Fiscal Eletrônico (FS-DA);
        // 6 - Contingência SVC-AN
        // 7 - Contingência SVC-RS
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
            case 6:
                $texto = 'CONTINGÊNCIA SVC-AN' . $dhCont . $xJust;
                break;
            case 7:
                $texto = 'CONTINGÊNCIA SVC-RS' . $dhCont . $xJust;
                break;
        }
        $y += 2;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>''];
        $this->pdf->textBox($x, $y, $w-2, $h-3, $texto, $aFont, 'T', 'L', 0, '', false);
        return $y+$h;
    }

    /**
     * rodape
     * Monta o rodapé no final da DANFE com a data/hora de impressão e informações
     * sobre a API NfePHP
     *
     * @param  float $x  Posição horizontal canto esquerdo
     *
     * @return void
     */
    protected function rodape($x)
    {
        
        $y = $this->maxH - 4;
        if ($this->orientacao == 'P') {
              $w = $this->wPrint;
        } else {
              $w = $this->wPrint-$this->wCanhoto;
              $x = $this->wCanhoto;
        }
        $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>'I'];
        $texto = "Impresso em ". date('d/m/Y') . " as " . date('H:i:s')
            . '  ' . $this->creditos;
        $this->pdf->textBox($x, $y, $w, 0, $texto, $aFont, 'T', 'L', false);
        $texto = "Powered by NFePHP®";
        $this->pdf->textBox($x, $y, $w, 0, $texto, $aFont, 'T', 'R', false, '');
    }

    /**
     * pCcanhotoDANFE
     * Monta o canhoto da DANFE (retrato e paisagem)
     *
     * @name   canhotoDANFE
     * @param  number $x Posição horizontal canto esquerdo
     * @param  number $y Posição vertical canto superior
     * @return number Posição vertical final
     *
     * TODO 21/07/14 fmertins: quando orientação L-paisagem, o canhoto está sendo gerado incorretamente
     */
    protected function canhoto($x, $y)
    {
        $oldX = $x;
        $oldY = $y;
        //#################################################################################
        //canhoto
        //identificação do tipo de nf entrada ou saida
        $tpNF = $this->ide->getElementsByTagName('tpNF')->item(0)->nodeValue;
        if ($tpNF == '0') {
            //NFe de Entrada
            $emitente = '';
            $emitente .= $this->dest->getElementsByTagName("xNome")->item(0)->nodeValue . " - ";
            $emitente .= $this->enderDest->getElementsByTagName("xLgr")->item(0)->nodeValue . ", ";
            $emitente .= $this->enderDest->getElementsByTagName("nro")->item(0)->nodeValue . " - ";
            $emitente .= $this->getTagValue($this->enderDest, "xCpl", " - ", " ");
            $emitente .= $this->enderDest->getElementsByTagName("xBairro")->item(0)->nodeValue . " ";
            $emitente .= $this->enderDest->getElementsByTagName("xMun")->item(0)->nodeValue . "-";
            $emitente .= $this->enderDest->getElementsByTagName("UF")->item(0)->nodeValue . "";
            $destinatario = $this->emit->getElementsByTagName("xNome")->item(0)->nodeValue . " ";
        } else {
            //NFe de Saída
            $emitente = $this->emit->getElementsByTagName("xNome")->item(0)->nodeValue . " ";
            $destinatario = '';
            $destinatario .= $this->dest->getElementsByTagName("xNome")->item(0)->nodeValue . " - ";
            $destinatario .= $this->enderDest->getElementsByTagName("xLgr")->item(0)->nodeValue . ", ";
            $destinatario .= $this->enderDest->getElementsByTagName("nro")->item(0)->nodeValue . " ";
            $destinatario .= $this->getTagValue($this->enderDest, "xCpl", " - ", " ");
            $destinatario .= $this->enderDest->getElementsByTagName("xBairro")->item(0)->nodeValue . " ";
            $destinatario .= $this->enderDest->getElementsByTagName("xMun")->item(0)->nodeValue . "-";
            $destinatario .= $this->enderDest->getElementsByTagName("UF")->item(0)->nodeValue . " ";
        }
        //identificação do sistema emissor
        //linha separadora do canhoto
        if ($this->orientacao == 'P') {
            $w = round($this->wPrint * 0.81, 0);
        } else {
            //linha separadora do canhoto - 238
            //posicao altura
            $y = $this->wPrint-85;
            //altura
            $w = $this->wPrint-85-24;
        }
        $h = 10;
        //desenha caixa
        $texto = '';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>''];
        $aFontSmall = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
        if ($this->orientacao == 'P') {
            $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'L', 1, '', false);
        } else {
            $this->pdf->textBox90($x, $y, $w, $h, $texto, $aFont, 'C', 'L', 1, '', false);
        }
        $numNF = str_pad($this->ide->getElementsByTagName('nNF')->item(0)->nodeValue, 9, "0", STR_PAD_LEFT);
        $serie = str_pad($this->ide->getElementsByTagName('serie')->item(0)->nodeValue, 3, "0", STR_PAD_LEFT);
        $texto = "RECEBEMOS DE ";
        $texto .= $emitente;
        $texto .= " OS PRODUTOS E/OU SERVIÇOS CONSTANTES DA NOTA FISCAL ELETRÔNICA INDICADA ";
        if ($this->orientacao == 'P') {
            $texto .= "ABAIXO";
        } else {
            $texto .= "AO LADO";
        }
        $texto .= ". EMISSÃO: ";
        $dEmi = ! empty($this->ide->getElementsByTagName("dEmi")->item(0)->nodeValue) ?
                $this->ide->getElementsByTagName("dEmi")->item(0)->nodeValue : '';
        if ($dEmi == '') {
            $dEmi = ! empty($this->ide->getElementsByTagName("dhEmi")->item(0)->nodeValue) ?
                    $this->ide->getElementsByTagName("dhEmi")->item(0)->nodeValue : '';
            $aDemi = explode('T', $dEmi);
            $dEmi = $aDemi[0];
        }
        $texto .= $this->ymdTodmy($dEmi) ." ";
        $texto .= "VALOR TOTAL: R$ ";
        $texto .= number_format($this->ICMSTot->getElementsByTagName("vNF")->item(0)->nodeValue, 2, ",", ".") . " ";
        $texto .= "DESTINATÁRIO: ";
        $texto .= $destinatario;
        if ($this->orientacao == 'P') {
            $this->pdf->textBox($x, $y, $w-1, $h, $texto, $aFont, 'C', 'L', 0, '', false);
            $x1 = $x + $w;
            $w1 = $this->wPrint - $w;
            $texto = "NF-e";
            $aFont = ['font'=>$this->fontePadrao, 'size'=>14, 'style'=>'B'];
            $this->pdf->textBox($x1, $y, $w1, 18, $texto, $aFont, 'T', 'C', 0, '');
            $texto = "Nº. " . $this->formatField($numNF, "###.###.###") . " \n";
            $texto .= "Série $serie";
            $aFont = ['font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B'];
            $this->pdf->textBox($x1, $y, $w1, 18, $texto, $aFont, 'C', 'C', 1, '');
            //DATA DE RECEBIMENTO
            $texto = "DATA DE RECEBIMENTO";
            $y += $h;
            $w2 = round($this->wPrint*0.17, 0); //35;
            $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
            $this->pdf->textBox($x, $y, $w2, 8, $texto, $aFont, 'T', 'L', 1, '');
            //IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR
            $x += $w2;
            $w3 = $w-$w2;
            $texto = "IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR";
            $this->pdf->textBox($x, $y, $w3, 8, $texto, $aFont, 'T', 'L', 1, '');
            $x = $oldX;
            $y += 9;
            $this->pdf->dashedHLine($x, $y, $this->wPrint, 0.1, 80);
            $y += 2;
            return $y;
        } else {
            $x--;
            $x = $this->pdf->textBox90($x, $y, $w-1, $h, $texto, $aFontSmall, 'C', 'L', 0, '', false);
            //NUMERO DA NOTA FISCAL LOGO NFE
            $w1 = 18;
            $x1 = $oldX;
            $y = $oldY;
            $texto = "NF-e";
            $aFont = ['font'=>$this->fontePadrao, 'size'=>14, 'style'=>'B'];
            $this->pdf->textBox($x1, $y, $w1, 18, $texto, $aFont, 'T', 'C', 0, '');
            $texto = "Nº.\n" . $this->formatField($numNF, "###.###.###") . " \n";
            $texto .= "Série $serie";
            $aFont = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B'];
            $this->pdf->textBox($x1, $y, $w1, 18, $texto, $aFont, 'C', 'C', 1, '');
            //DATA DO RECEBIMENTO
            $texto = "DATA DO RECEBIMENTO";
            $y = $this->wPrint-85;
            $x = 12;
            $w2 = round($this->wPrint*0.17, 0); //35;
            $aFont = ['font'=>$this->fontePadrao, 'size'=>6, 'style'=>''];
            $this->pdf->textBox90($x, $y, $w2, 8, $texto, $aFont, 'T', 'L', 1, '');
            //IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR
            $y -= $w2;
            $w3 = $w-$w2;
            $texto = "IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR";
            $aFont = ['font'=>$this->fontePadrao, 'size'=>5.7, 'style'=>''];
            $x = $this->pdf->textBox90($x, $y, $w3, 8, $texto, $aFont, 'T', 'L', 1, '');
            $this->pdf->dashedVLine(22, $oldY, 0.1, $this->wPrint, 69);
            return $x;
        }
    }

    /**
     * geraInformacoesDaTagCompra
     * Devolve uma string contendo informação sobre as tag <compra><xNEmp>, <xPed> e <xCont> ou string vazia.
     * Aviso: Esta função não leva em consideração dados na tag xPed do item.
     *
     * @name   pGeraInformacoesDaTagCompra
     * @return string com as informacoes dos pedidos.
     */
    protected function geraInformacoesDaTagCompra()
    {
        $saida = "";
        if (isset($this->compra)) {
            if (! empty($this->compra->getElementsByTagName("xNEmp")->item(0)->nodeValue)) {
                $saida .= " Nota de Empenho: " . $this->compra->getElementsByTagName("xNEmp")->item(0)->nodeValue;
            }
            if (! empty($this->compra->getElementsByTagName("xPed")->item(0)->nodeValue)) {
                $saida .= " Pedido: " . $this->compra->getElementsByTagName("xPed")->item(0)->nodeValue;
            }
            if (! empty($this->compra->getElementsByTagName("xCont")->item(0)->nodeValue)) {
                $saida .= " Contrato: " . $this->compra->getElementsByTagName("xCont")->item(0)->nodeValue;
            }
        }
        return $saida;
    }

    /**
     * geraChaveAdicionalDeContingencia
     *
     * @name   geraChaveAdicionalDeContingencia
     * @return string chave
     */
    protected function geraChaveAdicionalDeContingencia()
    {
        //cUF tpEmis CNPJ vNF ICMSp ICMSs DD  DV
        // Quantidade de caracteres  02   01      14  14    01    01  02 01
        $forma  = "%02d%d%s%014d%01d%01d%02d";
        $cUF    = $this->ide->getElementsByTagName('cUF')->item(0)->nodeValue;
        $CNPJ   = "00000000000000" . $this->emit->getElementsByTagName('CNPJ')->item(0)->nodeValue;
        $CNPJ   = substr($CNPJ, -14);
        $vNF    = $this->ICMSTot->getElementsByTagName("vNF")->item(0)->nodeValue * 100;
        $vICMS  = $this->ICMSTot->getElementsByTagName("vICMS")->item(0)->nodeValue;
        if ($vICMS > 0) {
            $vICMS = 1;
        }
        $icmss  = $this->ICMSTot->getElementsByTagName("vBC")->item(0)->nodeValue;
        if ($icmss > 0) {
            $icmss = 1;
        }
        $dEmi = ! empty($this->ide->getElementsByTagName("dEmi")->item(0)->nodeValue) ?
                $this->ide->getElementsByTagName("dEmi")->item(0)->nodeValue : '';
        if ($dEmi == '') {
            $dEmi = ! empty($this->ide->getElementsByTagName("dhEmi")->item(0)->nodeValue) ?
                    $this->ide->getElementsByTagName("dhEmi")->item(0)->nodeValue : '';
            $aDemi = explode('T', $dEmi);
            $dEmi = $aDemi[0];
        }
        $dd  = $dEmi;
        $rpos = strrpos($dd, '-');
        $dd  = substr($dd, $rpos +1);
        $chave = sprintf($forma, $cUF, $this->tpEmis, $CNPJ, $vNF, $vICMS, $icmss, $dd);
        $chave = $chave . $this->modulo11($chave);
        return $chave;
    }

    /**
     *  geraInformacoesDasNotasReferenciadas
     * Devolve uma string contendo informação sobre as notas referenciadas. Suporta N notas, eletrônicas ou não
     * Exemplo: NFe Ref.: série: 01 número: 01 emit: 11.111.111/0001-01
     * em 10/2010 [0000 0000 0000 0000 0000 0000 0000 0000 0000 0000 0000]
     *
     * @return string Informacoes a serem adicionadas no rodapé sobre notas referenciadas.
     */
    protected function geraInformacoesDasNotasReferenciadas()
    {
        $formaNfeRef = "\r\nNFe Ref.: série:%d número:%d emit:%s em %s [%s]";
        $formaCTeRef = "\r\nCTe Ref.: série:%d número:%d emit:%s em %s [%s]";
        $formaNfRef = "\r\nNF  Ref.: série:%d numero:%d emit:%s em %s modelo: %d";
        $formaECFRef = "\r\nECF Ref.: modelo: %s ECF:%d COO:%d";
        $formaNfpRef = "\r\nNFP Ref.: série:%d número:%d emit:%s em %s modelo: %d IE:%s";
        $saida='';
        $nfRefs = $this->ide->getElementsByTagName('NFref');
        if (0 === $nfRefs->length) {
            return $saida;
        }
        if ($nfRefs->length > 2) {
            return 'Existem mais de 2 NF/NFe/ECF/NFP/CTe referenciadas, não serão exibidas na DANFE.';
        }
        foreach ($nfRefs as $nfRef) {
            if (empty($nfRef)) {
                continue;
            }
            $refNFe = $nfRef->getElementsByTagName('refNFe');
            foreach ($refNFe as $chave_acessoRef) {
                $chave_acesso = $chave_acessoRef->nodeValue;
                $chave_acessoF = $this->formatField($chave_acesso, $this->formatoChave);
                $data = substr($chave_acesso, 4, 2)."/20".substr($chave_acesso, 2, 2);
                $cnpj = $this->formatField(substr($chave_acesso, 6, 14), "##.###.###/####-##");
                $serie  = substr($chave_acesso, 22, 3);
                $numero = substr($chave_acesso, 25, 9);
                $saida .= sprintf($formaNfeRef, $serie, $numero, $cnpj, $data, $chave_acessoF);
            }
            $refNF = $nfRef->getElementsByTagName('refNF');
            foreach ($refNF as $umaRefNFe) {
                $data = $umaRefNFe->getElementsByTagName('AAMM')->item(0)->nodeValue;
                $cnpj = $umaRefNFe->getElementsByTagName('CNPJ')->item(0)->nodeValue;
                $mod = $umaRefNFe->getElementsByTagName('mod')->item(0)->nodeValue;
                $serie = $umaRefNFe->getElementsByTagName('serie')->item(0)->nodeValue;
                $numero = $umaRefNFe->getElementsByTagName('nNF')->item(0)->nodeValue;
                $data = substr($data, 2, 2) . "/20" . substr($data, 0, 2);
                $cnpj = $this->formatField($cnpj, "##.###.###/####-##");
                $saida .= sprintf($formaNfRef, $serie, $numero, $cnpj, $data, $mod);
            }
            $refCTe = $nfRef->getElementsByTagName('refCTe');
            foreach ($refCTe as $chave_acessoRef) {
                $chave_acesso = $chave_acessoRef->nodeValue;
                $chave_acessoF = $this->formatField($chave_acesso, $this->formatoChave);
                $data = substr($chave_acesso, 4, 2)."/20".substr($chave_acesso, 2, 2);
                $cnpj = $this->formatField(substr($chave_acesso, 6, 14), "##.###.###/####-##");
                $serie  = substr($chave_acesso, 22, 3);
                $numero = substr($chave_acesso, 25, 9);
                $saida .= sprintf($formaCTeRef, $serie, $numero, $cnpj, $data, $chave_acessoF);
            }
            $refECF = $nfRef->getElementsByTagName('refECF');
            foreach ($refECF as $umaRefNFe) {
                $mod    = $umaRefNFe->getElementsByTagName('mod')->item(0)->nodeValue;
                $nECF   = $umaRefNFe->getElementsByTagName('nECF')->item(0)->nodeValue;
                $nCOO   = $umaRefNFe->getElementsByTagName('nCOO')->item(0)->nodeValue;
                $saida .= sprintf($formaECFRef, $mod, $nECF, $nCOO);
            }
            $refNFP = $nfRef->getElementsByTagName('refNFP');
            foreach ($refNFP as $umaRefNFe) {
                $data = $umaRefNFe->getElementsByTagName('AAMM')->item(0)->nodeValue;
                $cnpj = ! empty($umaRefNFe->getElementsByTagName('CNPJ')->item(0)->nodeValue) ?
                    $umaRefNFe->getElementsByTagName('CNPJ')->item(0)->nodeValue :
                    '';
                $cpf = ! empty($umaRefNFe->getElementsByTagName('CPF')->item(0)->nodeValue) ?
                        $umaRefNFe->getElementsByTagName('CPF')->item(0)->nodeValue : '';
                $mod = $umaRefNFe->getElementsByTagName('mod')->item(0)->nodeValue;
                $serie = $umaRefNFe->getElementsByTagName('serie')->item(0)->nodeValue;
                $numero = $umaRefNFe->getElementsByTagName('nNF')->item(0)->nodeValue;
                $ie = $umaRefNFe->getElementsByTagName('IE')->item(0)->nodeValue;
                $data = substr($data, 2, 2) . "/20" . substr($data, 0, 2);
                if ($cnpj == '') {
                    $cpf_cnpj = $this->formatField($cpf, "###.###.###-##");
                } else {
                    $cpf_cnpj = $this->formatField($cnpj, "##.###.###/####-##");
                }
                $saida .= sprintf($formaNfpRef, $serie, $numero, $cpf_cnpj, $data, $mod, $ie);
            }
        }
        return $saida;
    }
    
    private function loadDoc($xml)
    {
        $this->xml = $xml;
        if (!empty($xml)) {
            $this->dom = new Dom();
            $this->dom->loadXML($this->xml);
            if (empty($this->dom->getElementsByTagName("infNFe")->item(0))) {
                throw new \Exception('Isso não é um NFe.');
            }
            $this->nfeProc = $this->dom->getElementsByTagName("nfeProc")->item(0);
            $this->infNFe = $this->dom->getElementsByTagName("infNFe")->item(0);
            $this->ide = $this->dom->getElementsByTagName("ide")->item(0);
            if ($this->getTagValue($this->ide, "mod") != '55') {
                throw new \Exception("O xml deve ser NF-e modelo 55.");
            }
            $this->entrega = $this->dom->getElementsByTagName("entrega")->item(0);
            $this->retirada = $this->dom->getElementsByTagName("retirada")->item(0);
            $this->emit = $this->dom->getElementsByTagName("emit")->item(0);
            $this->dest = $this->dom->getElementsByTagName("dest")->item(0);
            $this->enderEmit = $this->dom->getElementsByTagName("enderEmit")->item(0);
            $this->enderDest = $this->dom->getElementsByTagName("enderDest")->item(0);
            $this->det = $this->dom->getElementsByTagName("det");
            $this->cobr = $this->dom->getElementsByTagName("cobr")->item(0);
            $this->dup = $this->dom->getElementsByTagName('dup');
            $this->ICMSTot = $this->dom->getElementsByTagName("ICMSTot")->item(0);
            $this->ISSQNtot = $this->dom->getElementsByTagName("ISSQNtot")->item(0);
            $this->transp = $this->dom->getElementsByTagName("transp")->item(0);
            $this->transporta = $this->dom->getElementsByTagName("transporta")->item(0);
            $this->veicTransp = $this->dom->getElementsByTagName("veicTransp")->item(0);
            $this->detPag = $this->dom->getElementsByTagName("detPag");
            $this->reboque = $this->dom->getElementsByTagName("reboque")->item(0);
            $this->infAdic = $this->dom->getElementsByTagName("infAdic")->item(0);
            $this->compra = $this->dom->getElementsByTagName("compra")->item(0);
            $this->tpEmis = $this->getTagValue($this->ide, "tpEmis");
            $this->tpImp = $this->getTagValue($this->ide, "tpImp");
            $this->infProt = $this->dom->getElementsByTagName("infProt")->item(0);
        }
    }
    
    private function imagePNGtoJPG($original)
    {
        $image = imagecreatefrompng($original);
        ob_start();
        imagejpeg($image, null, 100);
        imagedestroy($image);
        $stringdata = ob_get_contents(); // read from buffer
        ob_end_clean();
        return 'data://text/plain;base64,'.base64_encode($stringdata);
    }
}
