<?php

namespace NFePHP\DA\NFe;

/**
 * DANFE do leiaute da Reforma Tributária do Consumo (NT 2026.010), retrato.
 *
 * Reaproveita canhoto, chave, destinatário, fatura e transporte da Danfe.
 * Redesenha o cabeçalho fiscal, os totais e a grade de itens para IBS, CBS e IS.
 * Tag ausente fica em branco: nenhum valor é inventado.
 */
class DanfeRtc extends Danfe
{
    /**
     * @param string $xml
     */
    public function __construct($xml)
    {
        parent::__construct($xml);
        $this->hCabecalho = 54;
        $this->hCabecalhoItens = 12;
        $this->hImposto = empty($this->dom) ? 18 : $this->alturaImpostoRtc();
    }

    /**
     * @return DanfeRtcPdf
     */
    protected function novoPdf()
    {
        return new DanfeRtcPdf($this->orientacao, 'mm', $this->papel);
    }

    /**
     * Inclui CRT e reserva o quadro de regime de apuração do IBS/CBS.
     *
     * @param float $x
     * @param float $y
     * @param string $pag
     * @param string $totPag
     * @return float
     */
    protected function header($x = 0, $y = 0, $pag = '1', $totPag = '1')
    {
        $y = parent::header($x, $y, $pag, $totPag);
        $maxW = $this->larguraUtil($pag);
        $h = 7;
        // Mesma grade da linha de IE/IM logo acima: coluna de 25% e valor centralizado.
        $wCrt = round($maxW * 0.250, 0);
        $crt = $this->getTagValue($this->emit, 'CRT');
        $this->caixa($x, $y, $wCrt, $h, 'CÓDIGO DE REGIME TRIBUTÁRIO', $this->rotuloCrt($crt), 10, 'C');
        $this->caixa(
            $x + $wCrt,
            $y,
            $maxW - $wCrt,
            $h,
            'TIPO DE REGIME DE APURAÇÃO DO IBS E DA CBS',
            '',
            10,
            'C'
        );

        return $y + $h;
    }

    /**
     * Totais em três blocos: produtos/nota, ICMS/IPI e IBS/CBS/IS.
     *
     * @param float $x
     * @param float $y
     * @return float
     */
    protected function imposto($x, $y)
    {
        $w = $this->larguraUtil(1);
        $y = $this->tituloBloco($x, $y, $w, 'TOTAL DOS PRODUTOS E DA NOTA');
        $y = $this->linhaTotais($x, $y, $w, [
            ['PRODUTOS', $this->textoNumero($this->ICMSTot, 'vProd')],
            ['FRETE', $this->textoNumero($this->ICMSTot, 'vFrete')],
            ['SEGURO', $this->textoNumero($this->ICMSTot, 'vSeg')],
            ['DESCONTO', $this->textoNumero($this->ICMSTot, 'vDesc')],
            ['OUTRAS DESPESAS', $this->textoNumero($this->ICMSTot, 'vOutro')],
            ['TOTAL DA NOTA', $this->textoNumero($this->ICMSTot, 'vNF')],
        ]);

        $y = $this->tituloBloco($x, $y, $w, 'TOTAL DO ICMS / IPI');
        $y = $this->linhaTotais($x, $y, $w, [
            ['BASE DE CÁLC. DO ICMS', $this->textoNumero($this->ICMSTot, 'vBC')],
            ['VALOR DO ICMS', $this->textoNumero($this->ICMSTot, 'vICMS')],
            ['BASE DE CÁLC. ICMS ST', $this->textoNumero($this->ICMSTot, 'vBCST')],
            ['VALOR DO ICMS ST', $this->textoNumero($this->ICMSTot, 'vST')],
            ['VALOR TOTAL IPI', $this->textoNumero($this->ICMSTot, 'vIPI')],
        ]);
        $opcionais = $this->camposIcmsOpcionais();
        foreach (array_chunk($opcionais, 4) as $linha) {
            $y = $this->linhaTotais($x, $y, $w, $linha);
        }

        $ibs = $this->dom->getElementsByTagName('IBSCBSTot')->item(0);
        $isTot = $this->dom->getElementsByTagName('ISTot')->item(0);
        $y = $this->tituloBloco($x, $y, $w, 'TOTAL DO IBS / CBS / IS');
        $y = $this->linhaTotais($x, $y, $w, [
            ['VALOR DA CBS', $this->textoNumero($ibs, 'vCBS')],
            ['VALOR DO IBS UF', $this->textoNumero($ibs, 'vIBSUF')],
            ['VALOR DO IBS MUNICÍPIO', $this->textoNumero($ibs, 'vIBSMun')],
            ['VALOR DO IMPOSTO SELETIVO', $this->textoNumero($isTot, 'vIS')],
        ]);
        $mono = $this->camposMonoOpcionais();
        if (!empty($mono)) {
            $y = $this->linhaTotais($x, $y, $w, $mono);
        }

        return $y;
    }

    /**
     * O ISSQN vem logo após os totais, antes do transportador, como nos modelos da NT 2026.010
     * (retrato e paisagem), na largura útil da folha.
     *
     * @param float $x
     * @param float $y
     * @return float
     */
    protected function transporte($x, $y)
    {
        if ($this->temIssqn()) {
            $w = $this->larguraUtil(1);
            $y = $this->tituloBloco($x, $y, $w, 'CÁLCULO DO ISSQN');
            $y = $this->linhaTotais($x, $y, $w, [
                ['INSCRIÇÃO MUNICIPAL', $this->getTagValue($this->emit, 'IM')],
                ['VALOR TOTAL DOS SERVIÇOS', $this->textoNumero($this->ISSQNtot, 'vServ')],
                ['BASE DE CÁLCULO DO ISSQN', $this->textoNumero($this->ISSQNtot, 'vBC')],
                ['VALOR DO ISSQN', $this->textoNumero($this->ISSQNtot, 'vISS')],
            ]) + 1;
        }

        return parent::transporte($x, $y);
    }

    /**
     * Já impresso antes do transportador (transporte).
     *
     * @param float $x
     * @param float $y
     * @return float
     */
    protected function issqn($x, $y)
    {
        return $y;
    }

    /**
     * @return bool
     */
    protected function temIssqn()
    {
        return isset($this->ISSQNtot) && $this->getTagValue($this->ISSQNtot, 'vServ') > 0;
    }

    /**
     * Grade em faixa: identificação do item e, abaixo, base, alíquota e valor.
     *
     * @param float $x
     * @param float $y
     * @param int $nInicio
     * @param float $hmax
     * @param int $pag
     * @param int $totpag
     * @param float $hCabecItens
     * @return float
     */
    protected function itens($x, $y, &$nInicio, $hmax, $pag = 0, $totpag = 0, $hCabecItens = 7)
    {
        $this->medirFolha1($y, $hmax, $pag);
        $proximo = $this->det->item($nInicio);
        if ($proximo !== null && $hmax < $hCabecItens + $this->calculeHeight($proximo)) {
            // Nem um item cabe (paisagem com todos os quadros): a grade começa na folha seguinte,
            // em vez de uma grade sem altura sob os quadros de baixo.
            return $y;
        }
        $oldX = $x;
        $oldY = $y;
        $w = $this->larguraUtil($pag);
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $w, 4, 'DADOS DOS PRODUTOS / SERVIÇOS', $aFont, 'T', 'L', 0, '');
        $y += 3;
        $hmax += 1;
        $this->pdf->textBox($x, $y, $w, $hmax);
        $this->cabecalhoItens($x, $y, $w);
        $y += 8;

        $totItens = $this->det->length;
        $i = 0;
        $hUsado = $hCabecItens;
        foreach ($this->det as $det) {
            if ($i >= $nInicio) {
                $h = $this->calculeHeight($det, false);
                $hUsado += $h;
                if (($hmax - $hUsado) < 1 && $i < $totItens) {
                    if ($pag == $totpag) {
                        $totpag++;
                    }
                    $nInicio = $i;
                    break;
                }
                $this->faixaItem($oldX, $y, $w, $h, $det);
                $y += $h;
                $this->qtdeItensProc++;
            }
            $i++;
        }

        return $oldY + $hmax;
    }

    /**
     * @param \DOMElement $item
     * @param bool $mostrarUnidadeTributavel
     * @return float
     */
    protected function calculeHeight($item, $mostrarUnidadeTributavel = false)
    {
        return $this->medidaFaixa($item)['h'];
    }

    /**
     * @return int
     */
    protected function alturaImpostoRtc()
    {
        $linhasOpcionais = (int) ceil(count($this->camposIcmsOpcionais()) / 4);
        $h = (3 + 7) * 3;
        $h += $linhasOpcionais * 7;
        if (!empty($this->camposMonoOpcionais())) {
            $h += 7;
        }

        return $h + 1;
    }

    /**
     * Campos condicionados do bloco ICMS/IPI (NT 2026.010, item 4.4): só com valor no XML.
     * A lib grava esses totais zerados mesmo sem a operação, então zero não conta.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    protected function camposIcmsOpcionais()
    {
        $campos = [];
        $mapa = [
            'vFCP' => ['VALOR DO FCP', false],
            'vFCPST' => ['VALOR DO FCP RETIDO POR ST', false],
            'vICMSUFDest' => ['VALOR DO DIFAL NA UF DE DESTINO', false],
            'vFCPUFDest' => ['VALOR DO FCP NA UF DE DESTINO', false],
            'qBCMono' => ['BC DO ICMS MONOFÁSICO', true],
            'vICMSMono' => ['VALOR DO ICMS MONOFÁSICO', false],
            'qBCMonoReten' => ['BC DO ICMS MONOFÁSICO POR RETENÇÃO', true],
            'vICMSMonoReten' => ['VALOR DO ICMS MONOFÁSICO POR RETENÇÃO', false],
        ];
        foreach ($mapa as $tag => $def) {
            if ($this->valorPositivo($this->ICMSTot, $tag)) {
                $campos[] = [$def[0], $def[1]
                    ? $this->textoQuantidade($this->ICMSTot, $tag)
                    : $this->textoNumero($this->ICMSTot, $tag)];
            }
        }

        return $campos;
    }

    /**
     * Monofasia do bloco IBS/CBS/IS (NT 2026.010, itens 4.1 e 4.4): o par normal com o
     * grupo gMono, o par por retenção quando houver retenção. A lib grava gMono zerado
     * em toda nota, então o grupo só conta com algum valor.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    protected function camposMonoOpcionais()
    {
        if (empty($this->dom)) {
            return [];
        }
        $tot = $this->dom->getElementsByTagName('IBSCBSTot')->item(0);
        $gMono = $this->primeiroFilho($tot, 'gMono');
        $campos = [];
        $pares = [
            [['vIBSMono', 'VALOR DO IBS MONOFÁSICO'], ['vCBSMono', 'VALOR DA CBS MONOFÁSICA']],
            [
                ['vIBSMonoReten', 'VALOR DO IBS MONOFÁSICO POR RETENÇÃO'],
                ['vCBSMonoReten', 'VALOR DA CBS MONOFÁSICA POR RETENÇÃO'],
            ],
        ];
        foreach ($pares as $par) {
            if (!$this->valorPositivo($gMono, $par[0][0]) && !$this->valorPositivo($gMono, $par[1][0])) {
                continue;
            }
            foreach ($par as $campo) {
                $campos[] = [$campo[1], $this->textoNumero($gMono, $campo[0])];
            }
        }

        return $campos;
    }

    /**
     * @param \DOMNode|null $node
     * @param string $tag
     * @return bool
     */
    protected function valorPositivo($node, $tag)
    {
        $campo = $this->primeiroFilho($node, $tag);

        return !empty($campo) && (float) $campo->nodeValue > 0;
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $w
     * @param string $texto
     * @return float
     */
    protected function tituloBloco($x, $y, $w, $texto)
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $w, 4, $texto, $aFont, 'T', 'L', 0, '');

        return $y + 3;
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $w
     * @param array<int, array{0: string, 1: string}> $campos
     * @return float
     */
    protected function linhaTotais($x, $y, $w, array $campos)
    {
        $n = max(1, count($campos));
        $largura = $w / $n;
        $h = 7;
        $cursor = $x;
        foreach ($campos as $campo) {
            $this->caixa($cursor, $y, $largura, $h, $campo[0], $campo[1], 10);
            $cursor += $largura;
        }

        return $y + $h;
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $w
     * @param float $h
     * @param string $titulo
     * @param string $valor
     * @param int $tamanhoValor
     * @param string $alinhamento L, C ou R, como no textBox
     * @return void
     */
    protected function caixa($x, $y, $w, $h, $titulo, $valor, $tamanhoValor, $alinhamento = 'R')
    {
        // Mesmo desenho dos quadros herdados da Danfe.
        $fontTitulo = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $fontValor = ['font' => $this->fontePadrao, 'size' => $tamanhoValor, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $w, $h, $titulo, $fontTitulo, 'T', 'L', 1, '');
        $this->pdf->textBox($x, $y, $w, $h, $valor, $fontValor, 'B', $alinhamento, 0, '');
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $w
     * @return void
     */
    protected function cabecalhoItens($x, $y, $w)
    {
        $cols = $this->colunasItem($w);
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'B'];
        $h = 8;
        $labels = [
            "DESCRIÇÃO DO PRODUTO / SERVIÇO",
            "CST / CFOP",
            "QTD / UN",
            "VLR UNIT",
            "VLR TOTAL",
            "BASES DE CÁLCULO",
            "ALÍQUOTAS",
            "VALOR DOS TRIBUTOS",
        ];
        foreach ($cols as $i => $largura) {
            $this->pdf->textBox($x, $y, $largura, $h, $labels[$i], $aFont, 'C', 'C', 1, '', false);
            $x += $largura;
        }
    }

    /**
     * Uma faixa por item: identificação à esquerda e quatro linhas de tributo à direita.
     *
     * @param float $x
     * @param float $y
     * @param float $w
     * @param float $h
     * @param \DOMElement $det
     * @return void
     */
    protected function faixaItem($x, $y, $w, $h, $det)
    {
        $medida = $this->medidaFaixa($det);
        $cols = $this->colunasItem($w);
        $lh = $medida['lh'];
        $hMiolo = $medida['hMiolo'];
        $prod = $det->getElementsByTagName('prod')->item(0);
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];

        $cursor = $x;
        foreach ($cols as $i => $largura) {
            if ($i > 0) {
                $this->pdf->line($cursor, $y, $cursor, $y + $hMiolo);
            }
            $cursor += $largura;
        }

        $this->pdf->textBox($x, $y, $cols[0], $medida['hNome'], $medida['nome'], $aFont, 'T', 'L', 0, '', false);
        if ($medida['info'] !== '') {
            // false: quebra em linhas mantendo a fonte; o padrão (true) encolhe a fonte até caber numa linha.
            $this->pdf->textBox(
                $x,
                $y + $medida['hNome'],
                $cols[0],
                $medida['hInfo'],
                $medida['info'],
                $aFont,
                'T',
                'L',
                0,
                '',
                false
            );
        }
        $this->pdf->textBox($x, $y + $hMiolo - $lh, $cols[0], $lh, $medida['rodape'], $aFont, 'B', 'L', 0, '');

        $xCst = $x + $cols[0];
        $this->desenhaGuia($xCst, $y, $cols[1], $lh, ['CST', $this->cstItem($det)]);
        $this->desenhaGuia($xCst, $y + $lh, $cols[1], $lh, ['CFOP', $this->getTagValue($prod, 'CFOP')]);

        $xQtd = $xCst + $cols[1];
        $un = $this->getTagValue($prod, 'uCom');
        $this->pdf->textBox($xQtd, $y, $cols[2], $lh, $this->textoQuantidade($prod, 'qCom'), $aFont, 'T', 'C', 0, '');
        $this->pdf->textBox($xQtd, $y + $lh, $cols[2], $lh, $un === '' ? '' : '[' . $un . ']', $aFont, 'T', 'C', 0, '');

        $xUnit = $xQtd + $cols[2];
        $this->pdf->textBox($xUnit, $y, $cols[3], $lh, $this->textoNumero($prod, 'vUnCom'), $aFont, 'T', 'R', 0, '');
        $xTotal = $xUnit + $cols[3];
        $this->pdf->textBox($xTotal, $y, $cols[4], $lh, $this->textoNumero($prod, 'vProd'), $aFont, 'T', 'R', 0, '');

        $xBase = $xTotal + $cols[4];
        $xAliq = $xBase + $cols[5];
        $xValor = $xAliq + $cols[6];
        $colunas = [[$xBase, $cols[5]], [$xAliq, $cols[6]], [$xValor, $cols[7]]];
        foreach (array_values($medida['tributos']) as $c => $linhas) {
            foreach ($linhas as $i => $celulas) {
                $this->desenhaLinha($colunas[$c][0], $y + ($i * $lh), $colunas[$c][1], $lh, $celulas);
            }
        }

        $this->pdf->dashedHLine($x, $y + $hMiolo + 0.4, $w, 0.1, 120);
    }

    /**
     * Altura da faixa desenhada por faixaItem().
     *
     * @param \DOMElement $det
     * @return array<string, mixed> h, hMiolo, lh, hNome, hInfo, nome, info, rodape e tributos
     */
    protected function medidaFaixa($det)
    {
        $cols = $this->colunasItem($this->larguraUtil(1));
        $wDesc = max(10, $cols[0] - 1);
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $lh = 3.3;
        $prod = $det->getElementsByTagName('prod')->item(0);
        $nome = $prod ? $this->getTagValue($prod, 'xProd') : '';
        $codigo = $prod ? $this->getTagValue($prod, 'cProd') : '';
        // O modelo da NT não tem a coluna do código, que o MOC (Anexo II, 3.1.7) não deixa suprimir:
        // vai no início da descrição, que quebra em linhas sem encolher a fonte.
        $nome = $codigo === '' ? $nome : $codigo . ' - ' . $nome;
        $info = $this->getTagValue($det, 'infAdProd');
        // O textBox sem $force avança uma linha por tamanho da fonte (fontSize, em mm): as alturas
        // saem da fonte usada, não de um valor fixo. A 1ª linha do nome ocupa a linha da grade ($lh).
        $nNome = max(1, (int) $this->pdf->getNumLines($nome, $wDesc, $aFont));
        $hNome = $lh + ($nNome - 1) * $this->pdf->fontSize;
        $nInfo = $info === '' ? 0 : max(1, (int) $this->pdf->getNumLines($info, $wDesc, $aFont));
        $hInfo = $nInfo * $this->pdf->fontSize;
        $tributos = $this->tributosItem($det);
        $hTexto = $hNome + $hInfo;
        $hNcm = $lh;
        // Só as linhas que existem no XML: item sem tributo fica com a altura da descrição.
        $hTax = max(2, count($tributos['bases']), count($tributos['aliquotas']), count($tributos['valores'])) * $lh;
        $hMiolo = max($hTexto + $hNcm, $hTax);

        return [
            'h' => round($hMiolo + 1.2, 2),
            'hMiolo' => $hMiolo,
            'lh' => $lh,
            'hNome' => $hNome,
            'hInfo' => $hInfo,
            'nome' => $nome,
            'info' => $info,
            'rodape' => $this->rodapeItem($det),
            'tributos' => $tributos,
        ];
    }

    /**
     * @param float $w
     * @return float[]
     */
    protected function colunasItem($w)
    {
        // Descrição, CST/CFOP, qtd/un, unitário, total, bases, alíquotas; valores fica com o resto.
        $partes = [0.24, 0.065, 0.075, 0.085, 0.085, 0.11, 0.17];
        $cols = [];
        $usado = 0;
        foreach ($partes as $parte) {
            $largura = round($w * $parte, 0);
            $cols[] = $largura;
            $usado += $largura;
        }
        $cols[] = $w - $usado;

        return $cols;
    }

    /**
     * Base, alíquota e valor do item no modelo da NT 2026.010 (item 4.3): cada linha
     * leva o rótulo do tributo e só entra o que existe no XML. Bases uma por linha;
     * alíquotas e valores em pares, na ordem do modelo (ICMS, CBS, IBS UF, IPI,
     * IBS MUN, IS). Monofasia (CST 620) vem de gIBSCBSMono, que não tem gIBSCBS.
     *
     * @param \DOMElement $det
     * @return array{bases: string[], aliquotas: string[], valores: string[]}
     */
    protected function tributosItem($det)
    {
        $imposto = $det->getElementsByTagName('imposto')->item(0);
        $icms = $imposto ? $imposto->getElementsByTagName('ICMS')->item(0) : null;
        $ipi = $imposto ? $imposto->getElementsByTagName('IPI')->item(0) : null;
        $is = $this->primeiroFilho($imposto, 'IS');
        $ibscbs = $this->primeiroFilho($imposto, 'IBSCBS');
        $gIbsCbs = $this->primeiroFilho($ibscbs, 'gIBSCBS');
        $gIbsUf = $this->primeiroFilho($gIbsCbs, 'gIBSUF');
        $gIbsMun = $this->primeiroFilho($gIbsCbs, 'gIBSMun');
        $gCbs = $this->primeiroFilho($gIbsCbs, 'gCBS');

        $bases = [
            ['ICMS', $this->textoNumero($icms, 'vBC')],
            ['IBS/CBS', $this->textoNumero($gIbsCbs, 'vBC')],
            ['IS', $this->textoNumero($is, 'vBCIS')],
            ['IPI', $this->textoNumero($ipi, 'vBC')],
        ];
        $aliquotas = [
            ['ICMS', $this->textoPercentual($icms, 'pICMS')],
            ['CBS', $this->textoPercentual($gCbs, 'pCBS')],
            ['IBS UF', $this->textoPercentual($gIbsUf, 'pIBSUF')],
            ['IPI', $this->textoPercentual($ipi, 'pIPI')],
            ['IBS MUN', $this->textoPercentual($gIbsMun, 'pIBSMun')],
            ['IS', $this->textoPercentual($is, 'pIS')],
        ];
        $valores = [
            ['ICMS', $this->textoNumero($icms, 'vICMS')],
            ['CBS', $this->textoNumero($gCbs, 'vCBS')],
            ['IBS UF', $this->textoNumero($gIbsUf, 'vIBSUF')],
            ['IPI', $this->textoNumero($ipi, 'vIPI')],
            ['IBS MUN', $this->textoNumero($gIbsMun, 'vIBSMun')],
            ['IS', $this->textoNumero($is, 'vIS')],
        ];
        $linhas = ['bases' => [], 'aliquotas' => [], 'valores' => []];
        $prod = $det->getElementsByTagName('prod')->item(0);
        $unidade = $prod ? $this->getTagValue($prod, 'uTrib') : '';
        $grupos = $this->tributosMonoItem($this->primeiroFilho($ibscbs, 'gIBSCBSMono'), $unidade);
        $grupos[0] = [
            array_merge($bases, $grupos[0][0]),
            array_merge($aliquotas, $grupos[0][1]),
            array_merge($valores, $grupos[0][2]),
        ];
        // Cada grupo fecha os próprios pares: retenção não divide linha com o tributo normal.
        $cols = $this->colunasItem($this->larguraUtil(1));
        foreach ($grupos as $grupo) {
            $linhas['bases'] = array_merge($linhas['bases'], $this->linhasRotuladas(array_chunk($grupo[0], 1), 0));
            $linhas['aliquotas'] = array_merge(
                $linhas['aliquotas'],
                $this->linhasRotuladas($this->pares($grupo[1]), $cols[6])
            );
            $linhas['valores'] = array_merge(
                $linhas['valores'],
                $this->linhasRotuladas($this->pares($grupo[2]), $cols[7])
            );
        }

        return $linhas;
    }

    /**
     * Pares na ordem do modelo da NT (ICMS, CBS, IBS UF, IPI, IBS MUN, IS): só o que existe no
     * XML se junta, para a ausência não deixar meia linha vazia.
     *
     * @param array<int, array{0: string, 1: string}> $itens
     * @return array<int, array<int, array{0: string, 1: string}>>
     */
    protected function pares(array $itens)
    {
        return array_chunk(array_values(array_filter($itens, static function ($item) {
            return $item[1] !== '';
        })), 2);
    }

    /**
     * Monofasia do item em três grupos de [bases, alíquotas, valores]: padrão, retenção
     * (gMonoReten) e retido anteriormente (gMonoRet). Ad valorem traz base em R$ e
     * alíquota %; ad rem, quantidade e R$ por unidade. O padrão segue a ordem do modelo.
     *
     * @param \DOMElement|null $mono gIBSCBSMono
     * @param string $unidade uTrib do item: unidade da base e da alíquota ad rem
     * @return array<int, array<int, array<int, array{0: string, 1: string}>>>
     */
    protected function tributosMonoItem($mono, $unidade = '')
    {
        $vazio = [[[], [], []], [[], [], []], [[], [], []]];
        if (empty($mono)) {
            return $vazio;
        }
        $ibs = $this->primeiroFilho($mono, 'gIBSMonoAdValorem') ?: $this->primeiroFilho($mono, 'gIBSMonoAdRem');
        $cbs = $this->primeiroFilho($mono, 'gCBSMonoAdValorem') ?: $this->primeiroFilho($mono, 'gCBSMonoAdRem');

        $ibsPad = $this->primeiroFilho($ibs, 'gMonoPadrao');
        $cbsPad = $this->primeiroFilho($cbs, 'gMonoPadrao');
        $ufMun = $this->primeiroFilho($ibsPad, 'vIBSMonoUF') !== null;
        $padrao = [
            [['MONO', $this->baseMono($ibsPad ?: $cbsPad, 'vBCMono', 'qBCMono', $unidade)]],
            [
                ['CBS', $this->textoPercentual($cbsPad, 'pAliqMonoCBS')],
                ['CBS', $this->textoAdRem($cbsPad, 'adRemCBS', $unidade)],
                ['IBS UF', $this->textoPercentual($ibsPad, 'pAliqMonoUF')],
                ['IBS', $this->textoAdRem($ibsPad, 'adRemIBS', $unidade)],
                ['IBS MUN', $this->textoPercentual($ibsPad, 'pAliqMonoMun')],
            ],
            [
                ['CBS', $this->textoNumero($cbsPad, 'vCBSMono')],
                ['IBS UF', $this->textoNumero($ibsPad, 'vIBSMonoUF')],
                ['IBS', $ufMun ? '' : $this->textoNumero($ibsPad, 'vIBSMono')],
                ['IBS MUN', $this->textoNumero($ibsPad, 'vIBSMonoMun')],
            ],
        ];

        $ibsReten = $this->primeiroFilho($ibs, 'gMonoReten');
        $cbsReten = $this->primeiroFilho($cbs, 'gMonoReten');
        $reten = [
            [['RETEN', $this->baseMono($ibsReten ?: $cbsReten, 'vBCMonoReten', 'qBCMonoReten', $unidade)]],
            [
                ['CBS RETEN', $this->textoPercentual($cbsReten, 'pAliqMonoReten')],
                ['CBS RETEN', $this->textoAdRem($cbsReten, 'adRemCBSReten', $unidade)],
                ['IBS RETEN', $this->textoPercentual($ibsReten, 'pAliqMonoReten')],
                ['IBS RETEN', $this->textoAdRem($ibsReten, 'adRemIBSReten', $unidade)],
            ],
            [
                ['CBS RETEN', $this->textoNumero($cbsReten, 'vCBSMonoReten')],
                ['IBS RETEN', $this->textoNumero($ibsReten, 'vIBSMonoReten')],
            ],
        ];

        $retAnt = [[], [], [
            ['CBS RET ANT', $this->textoNumero($this->primeiroFilho($cbs, 'gMonoRet'), 'vCBSMonoRet')],
            ['IBS RET ANT', $this->textoNumero($this->primeiroFilho($ibs, 'gMonoRet'), 'vIBSMonoRet')],
        ]];

        return [$padrao, $reten, $retAnt];
    }

    /**
     * @param \DOMElement|null $grupo
     * @param string $tagValor base em R$ (ad valorem)
     * @param string $tagQtd base em quantidade (ad rem)
     * @param string $unidade
     * @return string
     */
    protected function baseMono($grupo, $tagValor, $tagQtd, $unidade = '')
    {
        if ($this->primeiroFilho($grupo, $tagValor)) {
            return $this->textoNumero($grupo, $tagValor);
        }
        if (!$this->primeiroFilho($grupo, $tagQtd)) {
            return '';
        }

        return trim($this->textoQuantidade($grupo, $tagQtd) . ' ' . $unidade);
    }

    /**
     * Alíquota ad rem é em R$ por unidade tributável (ex.: R$ 1,0530/L), não percentual.
     *
     * @param \DOMElement|null $grupo
     * @param string $tag
     * @param string $unidade
     * @return string
     */
    protected function textoAdRem($grupo, $tag, $unidade = '')
    {
        $campo = $this->primeiroFilho($grupo, $tag);
        if (empty($campo) || $campo->nodeValue === '') {
            return '';
        }

        $unidade = $unidade !== '' ? $unidade : 'un';

        return 'R$ ' . number_format((float) $campo->nodeValue, 4, ',', '.') . '/' . $unidade;
    }

    /**
     * Linhas da coluna de tributos: cada uma com uma ou duas células [rótulo, valor]. O que não
     * existe no XML não entra (NT 2026.010, item 4.4); par sem nenhum dos dois não ocupa linha.
     * Se um par não cabe, todos vão um por linha: a coluna do item não mistura par e item solto.
     *
     * @param array<int, array<int, array{0: string, 1: string}>> $pares
     * @param float $largura largura da coluna em mm; 0 = um por linha
     * @return array<int, array<int, array{0: string, 1: string}>>
     */
    protected function linhasRotuladas(array $pares, $largura = 0)
    {
        $pares = array_map(static function (array $par) {
            return array_values(array_filter($par, static function ($item) {
                return $item[1] !== '';
            }));
        }, $pares);
        $juntos = $largura > 0;
        foreach ($pares as $par) {
            if (count($par) === 2 && !$this->parCabe($par, $largura)) {
                $juntos = false;
            }
        }
        $linhas = [];
        foreach ($pares as $par) {
            if ($juntos && count($par) === 2) {
                $linhas[] = $par;
                continue;
            }
            foreach ($par as $item) {
                $linhas[] = [$item];
            }
        }

        return $linhas;
    }

    /**
     * Par que não cabe na coluna (ad rem, retenção, valores grandes) vai um por linha: o textBox
     * encolheria a fonte abaixo de 6 pt. A medida é a do texto na fonte do item, com o pontilhado mínimo.
     *
     * @param array<int, array{0: string, 1: string}> $par
     * @param float $largura
     * @return bool
     */
    protected function parCabe(array $par, $largura)
    {
        $metade = ($largura - $this->larguraBarra()) / 2;
        foreach ($par as $item) {
            if ($this->larguraGuia($item) > $metade) {
                return false;
            }
        }

        return true;
    }

    /**
     * Rótulo, pontilhado mínimo e valor, com o respiro da borda, em mm (6 pt).
     *
     * @param array{0: string, 1: string} $item
     * @return float
     */
    protected function larguraGuia(array $item)
    {
        $this->pdf->setFont($this->fontePadrao, '', 6);

        return $this->pdf->getStringWidth($item[0] . str_repeat('.', self::PONTOS_MINIMOS) . $item[1])
            + 2 * DanfeRtcPdf::RESPIRO_X;
    }

    /**
     * @return float
     */
    protected function larguraBarra()
    {
        $this->pdf->setFont($this->fontePadrao, '', 6);

        return $this->pdf->getStringWidth(' / ');
    }

    /** Pontos que sempre separam o rótulo do valor. */
    const PONTOS_MINIMOS = 2;

    /**
     * Uma linha da coluna: uma célula na largura toda ou duas metades separadas por "/".
     *
     * @param float $x
     * @param float $y
     * @param float $w
     * @param float $h
     * @param array<int, array{0: string, 1: string}> $celulas
     * @return void
     */
    protected function desenhaLinha($x, $y, $w, $h, array $celulas)
    {
        if (count($celulas) === 1) {
            $this->desenhaGuia($x, $y, $w, $h, $celulas[0]);
            return;
        }
        $barra = $this->larguraBarra();
        $metade = ($w - $barra) / 2;
        $this->desenhaGuia($x, $y, $metade, $h, $celulas[0]);
        $this->pdf->setTextColor(...self::CINZA);
        $this->pdf->textBox($x + $metade, $y, $barra, $h, '/', $this->fonteItem(), 'T', 'C', 0, '', false);
        $this->pdf->setTextColor(0, 0, 0);
        $this->desenhaGuia($x + $metade + $barra, $y, $metade, $h, $celulas[1]);
    }

    /**
     * Como no modelo da NT: rótulo e pontilhado em cinza, valor em preto à direita ("ICMS.....63,00").
     * Se nem o pontilhado mínimo couber, sai "ROTULO valor" como antes (o textBox ajusta a fonte).
     *
     * @param float $x
     * @param float $y
     * @param float $w
     * @param float $h
     * @param array{0: string, 1: string} $item
     * @return void
     */
    protected function desenhaGuia($x, $y, $w, $h, array $item)
    {
        list($rotulo, $valor) = $item;
        if ($valor === '') {
            return;
        }
        $fonte = $this->fonteItem();
        if ($this->larguraGuia($item) > $w) {
            $this->pdf->textBox($x, $y, $w, $h, $rotulo . ' ' . $valor, $fonte, 'T', 'R', 0, '');
            return;
        }
        $this->pdf->setFont($this->fontePadrao, '', 6);
        $livre = $w - 2 * DanfeRtcPdf::RESPIRO_X - $this->pdf->getStringWidth($rotulo . $valor);
        $pontos = max(self::PONTOS_MINIMOS, (int) floor($livre / $this->pdf->getStringWidth('.')));
        $this->pdf->setTextColor(...self::CINZA);
        $this->pdf->textBox($x, $y, $w, $h, $rotulo . str_repeat('.', $pontos), $fonte, 'T', 'L', 0, '', false);
        $this->pdf->setTextColor(0, 0, 0);
        $this->pdf->textBox($x, $y, $w, $h, $valor, $fonte, 'T', 'R', 0, '', false);
    }

    /** Cinza dos rótulos, como no modelo da NT. */
    const CINZA = [110, 110, 110];

    /**
     * @return array{font: string, size: int, style: string}
     */
    protected function fonteItem()
    {
        return ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
    }

    /**
     * @param \DOMElement $det
     * @return string
     */
    protected function rodapeItem($det)
    {
        $prod = $det->getElementsByTagName('prod')->item(0);
        $ncm = $prod ? $this->getTagValue($prod, 'NCM') : '';
        $classe = $this->cClassTribItem($det);
        $partes = [];
        if ($ncm !== '') {
            $partes[] = '[NCM ' . $ncm . ']';
        }
        if ($classe !== '') {
            $partes[] = '[cClassTrib ' . $classe . ']';
        }

        return implode(' ', $partes);
    }

    /**
     * @param \DOMElement|null $grupo
     * @param string $tagCheia
     * @return string
     */
    protected function textoPercentual($grupo, $tagCheia)
    {
        $numero = $this->textoAliquota($grupo, $tagCheia);

        return $numero === '' ? '' : $numero . '%';
    }

    /**
     * @param \DOMElement $det
     * @return string
     */
    protected function cstItem($det)
    {
        $ibscbs = $this->primeiroFilho($det->getElementsByTagName('imposto')->item(0), 'IBSCBS');
        $cst = $this->getTagValue($ibscbs, 'CST');
        if ($cst !== '') {
            return $cst;
        }
        $icms = $det->getElementsByTagName('ICMS')->item(0);
        $cst = $this->getTagValue($icms, 'CST');
        if ($cst === '') {
            $cst = $this->getTagValue($icms, 'CSOSN');
        }

        return $cst;
    }

    /**
     * @param \DOMElement $det
     * @return string
     */
    protected function cClassTribItem($det)
    {
        $imposto = $det->getElementsByTagName('imposto')->item(0);
        $ibscbs = $this->primeiroFilho($imposto, 'IBSCBS');

        return $this->getTagValue($ibscbs, 'cClassTrib');
    }

    /**
     * Com gRed, a alíquota impressa é a efetiva. Sem a tag, usa a alíquota cheia.
     *
     * @param \DOMElement|null $grupo
     * @param string $tagCheia
     * @return string
     */
    protected function textoAliquota($grupo, $tagCheia)
    {
        if (empty($grupo)) {
            return '';
        }
        $gRed = $this->primeiroFilho($grupo, 'gRed');
        $origem = empty($gRed) ? $grupo : $gRed;
        $tag = empty($gRed) ? $tagCheia : 'pAliqEfet';
        $campo = $this->primeiroFilho($origem, $tag) ?: $origem->getElementsByTagName($tag)->item(0);
        if (empty($campo) || $campo->nodeValue === '') {
            return '';
        }
        // Alíquotas são 3v2-4: até 4 casas (0,0723% não vira 0,07%), mínimo 2.
        $texto = number_format((float) $campo->nodeValue, 4, ',', '.');

        return preg_replace('/(,\d\d)0{1,2}$/', '$1', $texto);
    }

    /**
     * @param \DOMNode|null $node
     * @param string $tag
     * @return string
     */
    protected function textoNumero($node, $tag)
    {
        if (empty($node)) {
            return '';
        }
        $direto = $this->primeiroFilho($node, $tag);
        $campo = $direto ?: $node->getElementsByTagName($tag)->item(0);
        if (empty($campo) || $campo->nodeValue === '') {
            return '';
        }

        return number_format((float) $campo->nodeValue, 2, ',', '.');
    }

    /**
     * @param \DOMNode|null $node
     * @param string $tag
     * @return string
     */
    protected function textoQuantidade($node, $tag)
    {
        if (empty($node)) {
            return '';
        }
        $campo = $this->primeiroFilho($node, $tag);
        if (empty($campo)) {
            $campo = $node->getElementsByTagName($tag)->item(0);
        }
        if (empty($campo) || $campo->nodeValue === '') {
            return '';
        }
        $texto = number_format((float) $campo->nodeValue, 4, ',', '.');

        return rtrim(rtrim($texto, '0'), ',');
    }

    /**
     * @param \DOMNode|null $node
     * @param string $nome
     * @return \DOMElement|null
     */
    protected function primeiroFilho($node, $nome)
    {
        if (empty($node) || empty($node->childNodes)) {
            return null;
        }
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === $nome) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @param string $crt
     * @return string
     */
    protected function rotuloCrt($crt)
    {
        $mapa = [
            '1' => '1 - Simples Nacional',
            '2' => '2 - Simples Nacional, excesso',
            '3' => '3 - Regime Normal',
            '4' => '4 - MEI',
        ];

        return isset($mapa[$crt]) ? $mapa[$crt] : (string) $crt;
    }

    /**
     * @param int|string $pag
     * @return float
     */
    protected function larguraUtil($pag)
    {
        if ($this->orientacao === 'P' || (int) $pag !== 1) {
            return $this->wPrint;
        }

        return $this->wPrint - $this->wCanhoto;
    }
}
