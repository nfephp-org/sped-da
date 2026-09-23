<?php

namespace NFePHP\DA\NFe;

use DateTime;
use DOMDocument;
use Exception;
use InvalidArgumentException;
use NFePHP\DA\Legacy\Pdf;

/**
 * Classe para a impressão em PDF do DANFE Simplificado Tipo 2 (NF-e modelo 55, tpImp 6), no
 * leiaute da NT 2026.003.
 *
 * O leiaute é o mesmo do DANFE-NFC-e (`Danfce`): a classe herda a montagem (ordem dos blocos,
 * cálculo da altura da bobina, altura de item) e sobrescreve apenas o que muda de texto ou de
 * divisão — título, totais condicionais, divisão III-A (IBS/CBS/IS), mensagem fiscal e QR opcional.
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3 or MIT
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 */
class DanfeSimplificadoTipo2 extends Danfce
{
    /** Largura mínima de papel exigida pela NT 2026.003 (a `Danfce` exige 58). */
    const MIN_PAPER_WIDTH = 56;

    const TITULO = 'DANFE Simplificado Tipo 2';

    const MENSAGEM_HOMOLOGACAO = 'EMITIDA EM AMBIENTE DE HOMOLOGAÇÃO - SEM VALOR FISCAL';

    /** indPres de operação não presencial: nome e endereço de entrega passam a ser obrigatórios. */
    const IND_PRES_NAO_PRESENCIAL = ['2', '3', '4', '9'];

    const ALTURA_LINHA = 3.5;

    protected $entrega;

    protected $infAdFisco = '';

    /**
     * `tpAmb` do xml, guardado à parte. O `tpAmb` herdado é zerado no fim de cada via (ver
     * `blocoX`) para o `monta()` da `Danfce` não desenhar a marca d'água cinza — fora da NT.
     */
    protected $ambiente;

    /** @var array|null valores da divisão III-A (vCBS, vIBS, vIS), ou null quando o xml não tem o grupo */
    protected $totaisRtc;

    /**
     * Construtor
     *
     * @param string $xml
     *
     * @throws InvalidArgumentException
     */
    public function __construct($xml)
    {
        $this->confereModeloAceito($xml);

        parent::__construct($xml);

        $this->ambiente = $this->tpAmb;
        $this->entrega = $this->dom->getElementsByTagName('entrega')->item(0);
        $this->infAdFisco = !empty($this->infAdic) ? $this->getTagValue($this->infAdic, 'infAdFisco') : '';
        $this->totaisRtc = $this->carregaTotaisRtc();

        $this->bloco2H = $this->emContingenciaPendente() ? 12.0 : 6.0;
        $this->bloco4H = $this->alturaTotais();
        $this->bloco7H = $this->alturaConsumidor();
        $this->bloco8H = empty($this->qrCode) ? 0.0 : 50.0;
    }

    /**
     * Confere modelo e tpImp antes do `parent::__construct`, com uma mensagem própria — a
     * `Danfce` recusaria com "NFC-e modelo 65", que não descreve o caso do T2.
     *
     * @param string $xml
     *
     * @throws InvalidArgumentException
     */
    private function confereModeloAceito($xml)
    {
        if (empty($xml)) {
            throw new InvalidArgumentException('O XML da NF-e deve ser passado ao DANFE Simplificado Tipo 2.');
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $ide = $dom->getElementsByTagName('ide')->item(0);
        $mod = !empty($ide) ? $ide->getElementsByTagName('mod')->item(0) : null;
        $tpImp = !empty($ide) ? $ide->getElementsByTagName('tpImp')->item(0) : null;

        $modeloAceito = !empty($mod) && $mod->nodeValue == '55';
        $tpImpAceito = !empty($tpImp) && $tpImp->nodeValue == '6';

        if (empty($ide) || !$modeloAceito || !$tpImpAceito) {
            throw new InvalidArgumentException(
                'O XML não é de NF-e com DANFE Simplificado Tipo 2 (modelo 55, tpImp 6).'
            );
        }
    }

    /**
     * Modelo aceito pelo T2: NF-e (mod 55) com tpImp 6 — não o mod 65 da `Danfce`.
     *
     * @return bool
     */
    protected function isModeloAceito()
    {
        return $this->getTagValue($this->ide, 'mod') == '55'
            && $this->getTagValue($this->ide, 'tpImp') == '6';
    }

    /**
     * Seta a largura do papel de impressão em mm
     *
     * @param int $width
     */
    public function setPaperWidth($width = 80)
    {
        if ($width < self::MIN_PAPER_WIDTH) {
            throw new Exception(
                'O DANFE Simplificado Tipo 2 exige papel de no mínimo ' . self::MIN_PAPER_WIDTH . ' mm.'
            );
        }
        $this->paperwidth = $width;
    }

    /**
     * Divisão III-A: CBS, IBS e IS do total, quando o xml traz o grupo.
     *
     * @return array|null
     */
    private function carregaTotaisRtc()
    {
        $tot = $this->dom->getElementsByTagName('IBSCBSTot')->item(0);
        $isTot = $this->dom->getElementsByTagName('ISTot')->item(0);

        if (empty($tot) && empty($isTot)) {
            return null;
        }

        $gCBS = !empty($tot) ? $tot->getElementsByTagName('gCBS')->item(0) : null;
        $gIBS = !empty($tot) ? $tot->getElementsByTagName('gIBS')->item(0) : null;

        return [
            'vCBS' => (float) (!empty($gCBS) ? $this->getTagValue($gCBS, 'vCBS') : 0),
            'vIBS' => (float) (!empty($gIBS) ? $this->getTagValue($gIBS, 'vIBS') : 0),
            'vIS' => (float) (!empty($isTot) ? $this->getTagValue($isTot, 'vIS') : 0),
        ];
    }

    private function emContingenciaPendente()
    {
        return $this->tpEmis == 9 && empty($this->infProt);
    }

    private function naoPresencial()
    {
        return in_array($this->getTagValue($this->ide, 'indPres'), self::IND_PRES_NAO_PRESENCIAL, true);
    }

    /**
     * Uma linha de texto centralizado, na altura de linha da divisão (título, contingência,
     * consumidor).
     *
     * @param float $y
     * @param string $texto
     * @param int $size
     * @param string $style
     * @param string $align
     *
     * @return float
     */
    private function imprimeTexto($y, $texto, $size, $style = '', $align = 'C')
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => $size, 'style' => $style];

        return $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint,
            self::ALTURA_LINHA,
            $texto,
            $aFont,
            'T',
            $align,
            false,
            '',
            false
        );
    }

    /**
     * Uma linha rótulo à esquerda / valor à direita (totais, pagamento).
     *
     * @param float $y
     * @param string $rotulo
     * @param string $valor
     * @param int $size
     * @param string $style
     *
     * @return float
     */
    private function imprimeLinha($y, $rotulo, $valor, $size = 8, $style = '')
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => $size, 'style' => $style];
        $meio = $this->wPrint / 2;
        $this->pdf->textBox($this->margem, $y, $meio, 3, $rotulo, $aFont, 'T', 'L', false, '', false);

        return $this->pdf->textBox($this->margem + $meio, $y, $meio, 3, $valor, $aFont, 'T', 'R', false, '', false);
    }

    // ---------------------------------------------------------------- divisão I

    protected function blocoI()
    {
        $this->tpAmb = $this->ambiente;

        $cnpj = $this->getTagValue($this->emit, 'CNPJ');
        $doc = !empty($cnpj)
            ? 'CNPJ: ' . $this->formatField($cnpj, '##.###.###/####-##')
            : 'CPF: ' . $this->formatField($this->getTagValue($this->emit, 'CPF'), '###.###.###-##');
        $ie = $this->getTagValue($this->emit, 'IE');

        $complemento = $this->getTagValue($this->enderEmit, 'xCpl');
        $rua = trim(
            $this->getTagValue($this->enderEmit, 'xLgr') . ', ' . $this->getTagValue($this->enderEmit, 'nro')
            . ($complemento ? ' - ' . $complemento : '')
        );
        $cep = $this->getTagValue($this->enderEmit, 'CEP');
        $bairro = $this->getTagValue($this->enderEmit, 'xBairro')
            . ($cep ? ' - CEP ' . $this->formatField($cep, '#####-###') : '');
        $fone = $this->getTagValue($this->enderEmit, 'fone');
        $municipio = $this->getTagValue($this->enderEmit, 'xMun') . '-' . $this->getTagValue($this->enderEmit, 'UF')
            . ($fone ? '  Fone: ' . $fone : '');

        $x = $this->margem;
        $w = $this->wPrint;
        $align = 'C';
        if (!empty($this->logomarca)) {
            $info = getimagesize($this->logomarca);
            $imgW = $this->wPrint / 4;
            $imgH = min($this->bloco1H - 4, round(($info[1] / 72 * 25.4) * ($imgW / ($info[0] / 72 * 25.4))));
            $this->pdf->image($this->logomarca, $this->margem, $this->margem + 1, $imgW, $imgH, 'jpeg');
            $x += $imgW + 2;
            $w -= $imgW + 2;
            $align = 'L';
        }

        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'];
        $y = $this->margem;
        $razaoSocial = $this->getTagValue($this->emit, 'xNome');
        $y += $this->pdf->textBox($x, $y, $w, 3, $razaoSocial, $aFont, 'T', $align, false, '', false);

        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];
        foreach ([$doc . ($ie ? " IE: {$ie}" : ''), $rua, $bairro, $municipio] as $linha) {
            $y += $this->pdf->textBox($x, $y, $w, 3, $linha, $aFont, 'T', $align, false, '', false);
        }

        $this->pdf->dashedHLine($this->margem, $this->bloco1H, $this->wPrint, 0.1, 30);

        return $this->bloco1H;
    }

    // ------------------------------------------- título (div. I) e contingência (div. VIII, 1º local)

    protected function blocoII($y)
    {
        $y1 = $y + 1;
        $y1 += $this->imprimeTexto($y1, self::TITULO, 9, 'B');

        if ($this->emContingenciaPendente()) {
            $y1 += $this->imprimeTexto($y1, 'EMITIDA EM CONTINGÊNCIA', 10, 'B');
            $this->imprimeTexto($y1, 'Pendente de autorização', 8, 'I');
        }

        $this->pdf->dashedHLine($this->margem, $this->bloco2H + $y, $this->wPrint, 0.1, 30);

        return $this->bloco2H + $y;
    }

    // ------------------------------------------------------------ divisão II

    protected function calculateHeightItens($descriptionWidth)
    {
        $height = parent::calculateHeightItens($descriptionWidth);

        // Quantidade com vírgula decimal (NT, div. II/III) — a biblioteca imprime o float cru ("1.5").
        foreach ($this->itens as &$item) {
            $item['qtd'] = rtrim(rtrim(number_format((float) $item['qtd'], 4, ',', '.'), '0'), ',');
        }
        unset($item);

        return $height;
    }

    // ------------------------------------------------------------ divisão III

    private function acrescimos()
    {
        return (float) $this->getTagValue($this->ICMSTot, 'vFrete')
            + (float) $this->getTagValue($this->ICMSTot, 'vSeg')
            + (float) $this->getTagValue($this->ICMSTot, 'vOutro');
    }

    private function desconto()
    {
        return (float) $this->getTagValue($this->ICMSTot, 'vDesc');
    }

    private function alturaTotais()
    {
        $linhas = 2;
        if ($this->acrescimos() > 0) {
            $linhas++;
        }
        if ($this->desconto() > 0) {
            $linhas++;
        }
        if ($this->acrescimos() > 0 || $this->desconto() > 0) {
            $linhas++;
        }

        return $linhas * self::ALTURA_LINHA + 1;
    }

    protected function blocoIV($y)
    {
        $z = $y;
        $valorProdutos = number_format((float) $this->getTagValue($this->ICMSTot, 'vProd'), 2, ',', '.');
        $z += $this->imprimeLinha($z, 'Qtde. total de itens', (string) $this->det->length);
        $z += $this->imprimeLinha($z, 'Valor total R$', $valorProdutos);

        $acrescimos = $this->acrescimos();
        $desconto = $this->desconto();
        if ($acrescimos > 0) {
            $z += $this->imprimeLinha($z, 'Acréscimos R$', number_format($acrescimos, 2, ',', '.'));
        }
        if ($desconto > 0) {
            $z += $this->imprimeLinha($z, 'Desconto R$', '-' . number_format($desconto, 2, ',', '.'));
        }
        if ($acrescimos > 0 || $desconto > 0) {
            $fsize = $this->paperwidth < 70 ? 8 : 10;
            $valorPagar = number_format((float) $this->getTagValue($this->ICMSTot, 'vNF'), 2, ',', '.');
            $this->imprimeLinha($z, 'Valor a Pagar R$', $valorPagar, $fsize, 'B');
        }

        $this->pdf->dashedHLine($this->margem, $this->bloco4H + $y, $this->wPrint, 0.1, 30);

        return $this->bloco4H + $y;
    }

    /** @return array rótulo => valor, das linhas da divisão III-A que devem ser impressas */
    private function linhasRtc()
    {
        if ($this->totaisRtc === null) {
            return [];
        }

        $linhas = [
            '(+) CBS R$' => $this->totaisRtc['vCBS'],
            '(+) IBS R$' => $this->totaisRtc['vIBS'],
        ];
        if ($this->totaisRtc['vIS'] > 0) {
            $linhas['(+) IS R$'] = $this->totaisRtc['vIS'];
        }

        return $linhas;
    }

    protected function calculateHeightPag()
    {
        $n = max($this->pag->length, 1);
        $rtc = $this->linhasRtc();

        return 4 + (3 * $n) + 3 + 1 + ($rtc ? count($rtc) * 3 + 2 : 0);
    }

    /** Formas de pagamento, valor pago e troco (div. III) e, quando há, a divisão III-A. */
    protected function blocoV($y)
    {
        $this->bloco5H = $this->calculateHeightPag();

        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint,
            4,
            'FORMA DE PAGAMENTO',
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );
        $y1 = $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint,
            4,
            'VALOR PAGO R$',
            $aFont,
            'T',
            'R',
            false,
            '',
            false
        );
        $z = $y + $y1;

        foreach ($this->pag as $pgto) {
            $valor = number_format((float) $this->getTagValue($pgto, 'vPag'), 2, ',', '.');
            $z += $this->imprimeLinha($z, $this->pagType((int) $this->getTagValue($pgto, 'tPag')), $valor, 7);
        }
        $z += $this->imprimeLinha($z, 'Troco R$', number_format((float) $this->vTroco, 2, ',', '.'), 7);

        $rtc = $this->linhasRtc();
        if ($rtc) {
            $z += 1;
            $this->pdf->dashedHLine($this->margem, $z, $this->wPrint, 0.1, 30);
            $z += 1;
            foreach ($rtc as $rotulo => $valor) {
                $z += $this->imprimeLinha($z, $rotulo, number_format($valor, 2, ',', '.'), 7);
            }
        }

        $this->pdf->dashedHLine($this->margem, $this->bloco5H + $y, $this->wPrint, 0.1, 30);

        return $this->bloco5H + $y;
    }

    // ------------------------------------------------------ divisões VI e VII

    /** @return array lista de [texto, tamanho, estilo] das linhas do bloco do consumidor */
    private function linhasConsumidor()
    {
        $linhas = [];

        $cnpj = $this->getTagValue($this->dest, 'CNPJ');
        $cpf = $this->getTagValue($this->dest, 'CPF');
        if (!empty($cnpj)) {
            $linhas[] = ['CONSUMIDOR CNPJ: ' . $this->formatField($cnpj, '##.###.###/####-##'), 7, 'B'];
        } elseif (!empty($cpf)) {
            $linhas[] = ['CONSUMIDOR CPF: ' . $this->formatField($cpf, '###.###.###-##'), 7, 'B'];
        } else {
            $linhas[] = ['CONSUMIDOR NÃO IDENTIFICADO', 7, 'B'];
        }

        $nome = $this->getTagValue($this->dest, 'xNome');
        if (!empty($nome)) {
            $linhas[] = [$nome, 7, ''];
        }

        if ($this->naoPresencial()) {
            $endereco = $this->entrega ?: $this->enderDest;
            if ($endereco) {
                $linhas[] = ['Entrega: ' . trim(
                    $this->getTagValue($endereco, 'xLgr') . ', ' . $this->getTagValue($endereco, 'nro')
                    . ' ' . $this->getTagValue($endereco, 'xCpl')
                ) . ' - ' . $this->getTagValue($endereco, 'xBairro')
                    . ' - ' . $this->getTagValue($endereco, 'xMun') . '-' . $this->getTagValue($endereco, 'UF'), 7, ''];
            }
        }

        $num = $this->getTagValue($this->ide, 'nNF');
        $serie = $this->getTagValue($this->ide, 'serie');
        $emissao = $this->dataHoraLocal($this->getTagValue($this->ide, 'dhEmi'));
        $via = $this->via === 'Via Estabelecimento' ? ' - Via do Estabelecimento' : '';
        $linhas[] = ["NF-e n. {$num} Série {$serie} {$emissao}{$via}", 8, 'B'];

        if ($this->emContingenciaPendente()) {
            $linhas[] = ['EMITIDA EM CONTINGÊNCIA', 10, 'B'];
            $linhas[] = ['Pendente de autorização', 8, 'I'];
        } elseif (!empty($this->infProt)) {
            $protocolo = $this->getTagValue($this->infProt, 'nProt');
            $recebimento = $this->dataHoraLocal($this->getTagValue($this->infProt, 'dhRecbto'));
            $linhas[] = ["Protocolo de autorização: {$protocolo} {$recebimento}", 7, ''];
        }

        $xMsg = !empty($this->nfeProc) ? $this->getTagValue($this->nfeProc, 'xMsg') : '';
        if (!empty($xMsg)) {
            $linhas[] = [$xMsg, 7, ''];
        }

        return $linhas;
    }

    private function alturaConsumidor()
    {
        return count($this->linhasConsumidor()) * self::ALTURA_LINHA * 1.3 + 3;
    }

    /**
     * Data/hora no horário local do emitente (NT, div. VII). O `dhEmi` já vem com o fuso do
     * emitente; o `dhRecbto` vem com o fuso que a SEFAZ autorizadora escolheu e é convertido
     * para o do `dhEmi`.
     *
     * @param string $valor
     *
     * @return string
     */
    private function dataHoraLocal($valor)
    {
        $fusoEmitente = (new DateTime($this->getTagValue($this->ide, 'dhEmi')))->getTimezone();

        return (new DateTime($valor))->setTimezone($fusoEmitente)->format('d/m/Y H:i:s');
    }

    protected function blocoVII($y)
    {
        $z = $y + 1;
        foreach ($this->linhasConsumidor() as $linhaConsumidor) {
            list($texto, $size, $style) = $linhaConsumidor;
            $z += $this->imprimeTexto($z, $texto, $size, $style);
        }

        $this->pdf->dashedHLine($this->margem, $this->bloco7H + $y, $this->wPrint, 0.1, 30);

        return $this->bloco7H + $y;
    }

    // ------------------------------------------------------------- divisão V

    protected function blocoVIII($y)
    {
        if (empty($this->qrCode)) {
            return $y;
        }

        return parent::blocoVIII($y);
    }

    // ---------------------------------------------------- divisões VIII e IX

    /** @return array mensagens da área de mensagem fiscal (homologação e infAdFisco) */
    private function mensagensFiscais()
    {
        $mensagens = [];
        if ($this->ambiente == 2) {
            $mensagens[] = self::MENSAGEM_HOMOLOGACAO;
        }
        if (!empty($this->infAdFisco)) {
            $mensagens[] = $this->infAdFisco;
        }

        return $mensagens;
    }

    protected function calculateHeighBlokIX()
    {
        $height = parent::calculateHeighBlokIX();
        $mensagens = $this->mensagensFiscais();
        if (!$mensagens) {
            return $height;
        }

        $pdf = new Pdf('P', 'mm', [$this->paperwidth, 100]);
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $numlinhas = $pdf->getNumLines(implode("\n", $mensagens), $this->paperwidth - 2 * $this->margem, $aFont);

        return $height + (int) ceil($numlinhas * self::ALTURA_LINHA) + 2;
    }

    protected function blocoIX($y)
    {
        $mensagens = $this->mensagensFiscais();
        if ($mensagens) {
            $y += 1;
            $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
            $y += $this->pdf->textBox(
                $this->margem,
                $y,
                $this->wPrint,
                self::ALTURA_LINHA,
                implode("\n", $mensagens),
                $aFont,
                'T',
                'C',
                false,
                '',
                false
            );
            $y += 1;
        }

        return parent::blocoIX($y);
    }

    /**
     * A NT exige a mensagem de homologação na área de mensagem fiscal (div. VIII), que o
     * `blocoIX` já imprime. A marca d'água cinza do `monta()` da biblioteca ("SEM VALOR FISCAL",
     * sobre os totais) não está na NT: zerar o `tpAmb` depois do último bloco de cada via impede
     * que ela seja desenhada. O `blocoI` restaura o valor para a 2ª via de contingência.
     */
    protected function blocoX($y)
    {
        $y = parent::blocoX($y);
        $this->tpAmb = null;

        return $y;
    }
}
