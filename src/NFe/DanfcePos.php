<?php

namespace NFePHP\DA\NFe;

/**
 * Classe para a impressão do Documento Auxiliar de NFe Consumidor
 * em impressoras térmicas ESCPOS
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @name      DanfcePos.php
 * @copyright 2009-2016 NFePHP
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Roberto L. Machado <linux dot rlm at gmail dot com>
 */

use Posprint\Printers\PrinterInterface;
use InvalidArgumentException;

class DanfcePos
{
    /**
     * NFCe
     * @var SimpleXMLElement
     */
    protected $nfce = '';
    /**
     * protNFe
     * @var SimpleXMLElement
     */
    protected $protNFe = '';
    /**
     * Printer
     * @var PrinterInterface
     */
    protected $printer;
    /**
     * Documento montado
     * @var array
     */
    protected $da = array();
    /**
     * Total de itens da NFCe
     * @var integer
     */
    protected $totItens = 0;
    
    /**
     * URI referente a pagina de consulta da NFCe pela chave de acesso
     * @var string
     */
    protected $uri = '';
    
    protected $aURI = [
      'AC' => 'http://sefaznet.ac.gov.br/nfce/consulta.xhtml',
      'AM' => 'http://sistemas.sefaz.am.gov.br/nfceweb/formConsulta.do',
      'BA' => 'http://nfe.sefaz.ba.gov.br/servicos/nfce/Modulos/Geral/NFCEC_consulta_chave_acesso.aspx',
      'MT' => 'https://www.sefaz.mt.gov.br/nfce/consultanfce',
      'MA' => 'http://www.nfce.sefaz.ma.gov.br/portal/consultaNFe.do?method=preFilterCupom&',
      'PA' => 'https://appnfc.sefa.pa.gov.br/portal/view/consultas/nfce/consultanfce.seam',
      'PB' => 'https://www.receita.pb.gov.br/ser/servirtual/documentos-fiscais/nfc-e/consultar-nfc-e',
      'PR' => 'http://www.sped.fazenda.pr.gov.br/modules/conteudo/conteudo.php?conteudo=100',
      'RJ' => 'http://www4.fazenda.rj.gov.br/consultaDFe/paginas/consultaChaveAcesso.faces',
      'RS' => 'https://www.sefaz.rs.gov.br/NFE/NFE-COM.aspx',
      'RO' => 'http://www.nfce.sefin.ro.gov.br/home.jsp',
      'RR' => 'https://www.sefaz.rr.gov.br/nfce/servlet/wp_consulta_nfce',
      'SE' => 'http://www.nfce.se.gov.br/portal/portalNoticias.jsp?jsp=barra-menu/servicos/consultaDANFENFCe.htm',
      'SP' => 'https://www.nfce.fazenda.sp.gov.br/NFCeConsultaPublica/Paginas/ConsultaPublica.aspx'
    ];

    /**
     * Carrega a impressora a ser usada
     * a mesma deverá já ter sido pré definida inclusive seu
     * conector
     *
     * @param PrinterInterface $this->printer
     */
    public function __construct(PrinterInterface $printer)
    {
        $this->printer = $printer;
    }
    
    /**
     * Carrega a NFCe
     * @param string $nfcexml
     */
    public function loadNFCe($nfcexml)
    {
        $xml = $nfcexml;
        if (is_file($nfcexml)) {
            $xml = @file_get_contents($nfcexml);
        }
        if (empty($xml)) {
            throw new InvalidArgumentException('Não foi possivel ler o documento.');
        }
        $nfe = simplexml_load_string($xml, null, LIBXML_NOCDATA);
        $this->protNFe = $nfe->protNFe;
        $this->nfce = $nfe->NFe;
        if (empty($this->protNFe)) {
            //NFe sem protocolo
            $this->nfce = $nfe;
        }
    }
    
    /**
     * Monta a DANFCE para uso de impressoras POS
     */
    public function monta()
    {
        $this->parteI();
        $this->parteII();
        $this->parteIII();
        $this->parteIV();
        $this->parteV();
        $this->parteVI();
        $this->parteVII();
        $this->parteVIII();
        $this->parteIX();
    }
    
    /**
     * Manda os dados para a impressora ou
     * retorna os comandos em ordem e legiveis
     * para a tela
     */
    public function printDanfe()
    {
        $resp = $this->printer->send();
        if (!empty($resp)) {
            echo str_replace("\n", "<br>", $resp);
        }
    }
    
    /**
     * Recupera a sequencia de comandos para envio
     * posterior para a impressora por outro
     * meio como o QZ.io (tray)
     *
     * @return string
     */
    public function getCommands()
    {
        $aCmds = $this->printer->getBuffer('binA');
        return implode("\n", $aCmds);
    }
    
    /**
     * Parte I - Emitente
     * Dados do emitente
     * Campo Obrigatório
     */
    protected function parteI()
    {
        $razao = (string) $this->nfce->infNFe->emit->xNome;
        $cnpj = (string) $this->nfce->infNFe->emit->CNPJ;
        $ie = (string) $this->nfce->infNFe->emit->IE;
        $im = (string) $this->nfce->infNFe->emit->IM;
        $log = (string) $this->nfce->infNFe->emit->enderEmit->xLgr;
        $nro = (string) $this->nfce->infNFe->emit->enderEmit->nro;
        $bairro = (string) $this->nfce->infNFe->emit->enderEmit->xBairro;
        $mun = (string) $this->nfce->infNFe->emit->enderEmit->xMun;
        $uf = (string) $this->nfce->infNFe->emit->enderEmit->UF;
        if (array_key_exists($uf, $this->aURI)) {
            $this->uri = $this->aURI[$uf];
        }
        $this->printer->setAlign('C');
        $this->printer->text($razao);
        $this->printer->text('CNPJ: '.$cnpj.'     '.'IE: ' . $ie);
        $this->printer->text('IM: '.$im);
        $this->printer->setAlign('L');
        //o que acontece quando o texto é maior que o numero de carecteres
        //da linha ??
        $this->printer->text($log . ', ' . $nro . ' ' . $bairro . ' ' . $mun . ' ' . $uf);
        //linha divisória ??
    }
    
    /**
     * Parte II - Informações Gerais
     * Campo Obrigatório
     */
    protected function parteII()
    {
        $this->printer->setAlign('C');
        $this->printer->text('DANFE NFC-e Documento Auxiliar');
        $this->printer->text('da Nota Fiscal eletrônica para consumidor final');
        $this->printer->setBold();
        $this->printer->text('Não permite aproveitamento de crédito de ICMS');
        $this->printer->setBold();
        //linha divisória ??
    }
    
    /**
     * Parte III - Detalhes da Venda
     * Campo Opcional
     */
    protected function parteIII()
    {
        $this->printer->setAlign('L');
        $this->printer->text('Item Cod   Desc         Qtd    V.Unit  V.Total');
        //obter dados dos itens da NFCe
        $det = $this->nfce->infNFe->det;
        $this->totItens = $det->count();
        for ($x=0; $x<=$this->totItens-1; $x++) {
            $nItem = (int) $det[$x]->attributes()->{'nItem'};
            $cProd = (string) $det[$x]->prod->cProd;
            $xProd = (string) $det[$x]->prod->xProd;
            $qCom = (float) $det[$x]->prod->qCom;
            $uCom = (string) $det[$x]->prod->uCom;
            $vUnCom = (float) $det[$x]->prod->vUnCom;
            $vProd = (float) $det[$x]->prod->vProd;
            //falta formatar os campos e o espaçamento entre eles
            $this->printer->text($nItem .  $cProd. $xProd . $qCom . $uCom . $vUnCom . $vProd);
        }
        //linha divisória ??
    }
    
    /**
     * Parte V - Informação de tributos
     * Campo Obrigatório
     */
    protected function parteIV()
    {
        $vTotTrib = (float) $this->nfce->infNFe->total->ICMSTot->vTotTrib;
        $this->printer->setAlign('L');
        $this->printer->text('Informação dos Tributos Totais:' . '' . 'R$ ' .  $vTotTrib);
        $this->printer->text('Incidentes (Lei Federal 12.741 /2012) - Fonte IBPT');
        //linha divisória ??
    }
    
    /**
     * Parte IV - Totais da Venda
     * Campo Obrigatório
     */
    protected function parteV()
    {
        $vNF = (float) $this->nfce->infNFe->total->ICMSTot->vNF;
        $this->printer->setAlign('L');
        $this->printer->text('QTD. TOTAL DE ITENS' . ' ' . $this->totItens);
        $this->printer->text('VALOR TOTAL            R$ ' . $vNF);
        $this->printer->text('FORMA PAGAMENTO          VALOR PAGO');
        $pag = $this->nfce->infNFe->pag;
        $tot = $pag->count();
        foreach ($pag as $pg) {
            $std = json_decode(json_encode($pg));
            $tPag = (string) $this->tipoPag($std->tPag);
            $vPag = (float) $std->vPag;
            $this->printer->text($tPag . '                  R$ '. $vPag);
        }
        //linha divisória ??
    }
    
    /**
     * Parte VI - Mensagem de Interesse do Contribuinte
     * conteudo de infCpl
     * Campo Opcional
     */
    protected function parteVI()
    {
        $infCpl = (string) $this->nfce->infNFe->infAdic->infCpl;
        $this->printer->setAlign('L');
        $this->printer->text($infCpl);
        $this->printer->lineFeed();
        //linha divisória ??
    }
    
    /**
     * Parte VII - Mensagem Fiscal e Informações da Consulta via Chave de Acesso
     * Campo Obrigatório
     */
    protected function parteVII()
    {
        $tpAmb = (int) $this->nfce->infNFe->ide->tpAmb;
        if ($tpAmb == 2) {
            $this->printer->setAlign('C');
            $this->printer->text('EMITIDA EM AMBIENTE DE HOMOLOGAÇÃO - SEM VALOR FISCAL');
        }
        $tpEmis = (int) $this->nfce->infNFe->ide->tpEmis;
        if ($tpEmis != 1) {
            $this->printer->setAlign('C');
            $this->printer->text('EMITIDA EM AMBIENTE DE CONTINGẼNCIA');
        }
        $nNF = (float) $this->nfce->infNFe->ide->nNF;
        $serie = (int) $this->nfce->infNFe->ide->serie;
        $dhEmi = (string) $this->nfce->infNFe->ide->dhEmi;
        $Id = (string) $this->nfce->infNFe->attributes()->{'Id'};
        $chave = substr($Id, 3, strlen($Id)-3);
        $this->printer->setAlign('L');
        $this->printer->text('Nr. ' . $nNF. ' Serie ' .$serie . ' Emissão ' .$dhEmi . ' via Consumidor');
        $this->printer->setAlign('C');
        $this->printer->text('Consulte pela chave de acesso em');
        $this->printer->text($this->uri);
        $this->printer->text('CHAVE DE ACESSO');
        $this->printer->text($chave);
        //linha divisória ??
    }
    
    /**
     * Parte VIII - Informações sobre o Consumidor
     * Campo Opcional
     */
    protected function parteVIII()
    {
        $this->printer->setAlign('C');
        $dest = $this->nfce->infNFe->dest;
        if (empty($dest)) {
            $this->printer->text('CONSUMIDOR NÃO IDENTIFICADO');
            return;
        }
        $xNome = (string) $this->nfce->infNFe->dest->xNome;
        $this->printer->text($xNome);
        $cnpj = (string) $this->nfce->infNFe->dest->CNPJ;
        $cpf = (string) $this->nfce->infNFe->dest->CPF;
        $idEstrangeiro = (string) $this->nfce->infNFe->dest->idEstrangeiro;
        $this->printer->setAlign('L');
        if (!empty($cnpj)) {
            $this->printer->text('CNPJ ' . $cnpj);
        }
        if (!empty($cpf)) {
            $this->printer->text('CPF ' . $cpf);
        }
        if (!empty($idEstrangeiro)) {
            $this->printer->text('Extrangeiro ' . $idEstrangeiro);
        }
        $xLgr = (string) $this->nfce->infNFe->dest->enderDest->xLgr;
        $nro = (string) $this->nfce->infNFe->dest->enderDest->nro;
        $xCpl = (string) $this->nfce->infNFe->dest->enderDest->xCpl;
        $xBairro = (string) $this->nfce->infNFe->dest->enderDest->xBairro;
        $xMun = (string) $this->nfce->infNFe->dest->enderDest->xMun;
        $uf = (string) $this->nfce->infNFe->dest->enderDest->UF;
        $cep = (string) $this->nfce->infNFe->dest->enderDest->CEP;
        $this->printer->text($xLgr . '' . $nro . '' . $xCpl . '' . $xBairro . '' . $xMun . '' . $uf);
        //linha divisória ??
    }
    
    /**
     * Parte IX - QRCode
     * Consulte via Leitor de QRCode
     * Protocolo de autorização 1234567891234567 22/06/2016 14:43:51
     * Campo Obrigatório
     */
    protected function parteIX()
    {
        $this->printer->setAlign('C');
        $this->printer->text('Consulte via Leitor de QRCode');
        $qr = (string) $this->nfce->infNFeSupl->qrCode;
        $this->printer->barcodeQRCode($qr);
        if (!empty($this->protNFe)) {
            $nProt = (string) $this->protNFe->infProt->nProt;
            $dhRecbto = (string) $this->protNFe->infProt->dhRecbto;
            $this->printer->text('Protocolo de autorização ' . $nProt . $dhRecbto);
        } else {
            $this->printer->setBold();
            $this->printer->text('NOTA FISCAL INVÁLIDA - SEM PROTOCOLO DE AUTORIZAÇÃO');
            $this->printer->lineFeed();
        }
    }
    
    /**
     * Retorna o texto referente ao tipo de pagamento efetuado
     * @param int $tPag
     * @return string
     */
    private function tipoPag($tPag)
    {
        $aPag = [
            '01' => 'Dinheiro',
            '02' => 'Cheque',
            '03' => 'Cartao de Credito',
            '04' => 'Cartao de Debito',
            '05' => 'Credito Loja',
            '10' => 'Vale Alimentacao',
            '11' => 'Vale Refeicao',
            '12' => 'Vale Presente',
            '13' => 'Vale Combustivel',
            '99' => 'Outros'
        ];
        if (array_key_exists($tPag, $aPag)) {
            return $aPag[$tPag];
        }
        return '';
    }
}
