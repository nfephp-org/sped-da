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
    /** Par "ROTULO valor / ROTULO valor" que cabe em 5 pt nas colunas de tributos. */
    const MAX_CARACTERES_PAR = 28;

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
        $aFont = ['font' => $this->fontePadrao, 'size' => 5, 'style' => 'B'];
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
        $aFontMiudo = ['font' => $this->fontePadrao, 'size' => 5, 'style' => ''];

        $cursor = $x;
        foreach ($cols as $i => $largura) {
            if ($i > 0) {
                $this->pdf->line($cursor, $y, $cursor, $y + $hMiolo);
            }
            $cursor += $largura;
        }

        $this->pdf->textBox($x, $y, $cols[0], $lh * $medida['nNome'], $medida['nome'], $aFont, 'T', 'L', 0, '', false);
        if ($medida['info'] !== '') {
            $this->pdf->textBox(
                $x,
                $y + ($lh * $medida['nNome']),
                $cols[0],
                $lh * $medida['nInfo'],
                $medida['info'],
                $aFontMiudo,
                'T',
                'L',
                0,
                ''
            );
        }
        $this->pdf->textBox($x, $y + $hMiolo - $lh, $cols[0], $lh, $medida['rodape'], $aFontMiudo, 'B', 'L', 0, '');

        $xCst = $x + $cols[0];
        $this->pdf->textBox($xCst, $y, $cols[1], $lh, $this->cstItem($det), $aFont, 'T', 'C', 0, '');
        $this->pdf->textBox($xCst, $y + $lh, $cols[1], $lh, $this->getTagValue($prod, 'CFOP'), $aFont, 'T', 'C', 0, '');

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
            foreach ($linhas as $i => $texto) {
                $this->pdf->textBox(
                    $colunas[$c][0],
                    $y + ($i * $lh),
                    $colunas[$c][1],
                    $lh,
                    $texto,
                    $aFontMiudo,
                    'T',
                    'R',
                    0,
                    ''
                );
            }
        }

        $this->pdf->dashedHLine($x, $y + $hMiolo + 0.4, $w, 0.1, 120);
    }

    /**
     * Altura da faixa desenhada por faixaItem().
     *
     * @param \DOMElement $det
     * @return array<string, mixed> h, hMiolo, lh, nNome, nInfo, nome, info, rodape e tributos
     */
    protected function medidaFaixa($det)
    {
        $cols = $this->colunasItem($this->larguraUtil(1));
        $wDesc = max(10, $cols[0] - 1);
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $aFontMiudo = ['font' => $this->fontePadrao, 'size' => 5, 'style' => ''];
        $lh = 3.3;
        $prod = $det->getElementsByTagName('prod')->item(0);
        $nome = $prod ? $this->getTagValue($prod, 'xProd') : '';
        $info = $this->getTagValue($det, 'infAdProd');
        $nNome = max(1, (int) $this->pdf->getNumLines($nome, $wDesc, $aFont));
        $nInfo = $info === '' ? 0 : max(1, (int) $this->pdf->getNumLines($info, $wDesc, $aFontMiudo));
        $tributos = $this->tributosItem($det);
        $hTexto = ($nNome + $nInfo) * $lh;
        $hNcm = $lh;
        // Só as linhas que existem no XML: item sem tributo fica com a altura da descrição.
        $hTax = max(2, count($tributos['bases']), count($tributos['aliquotas']), count($tributos['valores'])) * $lh;
        $hMiolo = max($hTexto + $hNcm, $hTax);

        return [
            'h' => round($hMiolo + 1.2, 2),
            'hMiolo' => $hMiolo,
            'lh' => $lh,
            'nNome' => $nNome,
            'nInfo' => $nInfo,
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
        $partes = [0.27, 0.07, 0.08, 0.09, 0.09, 0.12, 0.14];
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
        // O par da retenção (grupo 1) não cabe numa linha da coluna: um por linha.
        foreach ($grupos as $i => $grupo) {
            $par = $i === 1 ? 1 : 2;
            $linhas['bases'] = array_merge($linhas['bases'], $this->linhasRotuladas($grupo[0], 1));
            $linhas['aliquotas'] = array_merge($linhas['aliquotas'], $this->linhasRotuladas($grupo[1], $par));
            $linhas['valores'] = array_merge($linhas['valores'], $this->linhasRotuladas($grupo[2], $par));
        }

        return $linhas;
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
     * "ROTULO valor", descartando o que não existe no XML; $porLinha itens por linha.
     *
     * @param array<int, array{0: string, 1: string}> $itens
     * @param int $porLinha
     * @return string[]
     */
    protected function linhasRotuladas(array $itens, $porLinha)
    {
        $textos = [];
        foreach ($itens as $item) {
            if ($item[1] !== '') {
                $textos[] = $item[0] . ' ' . $item[1];
            }
        }
        // Par que não cabe na coluna (ad rem, valores grandes) vai um por linha: o textBox
        // encolheria a fonte e o item ficaria com tamanhos diferentes.
        $linhas = [];
        for ($i = 0; $i < count($textos); $i++) {
            $par = $porLinha > 1 && isset($textos[$i + 1]) ? $textos[$i] . ' / ' . $textos[$i + 1] : '';
            if ($par !== '' && mb_strlen($par) <= self::MAX_CARACTERES_PAR) {
                $linhas[] = $par;
                $i++;
                continue;
            }
            $linhas[] = $textos[$i];
        }

        return $linhas;
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
        if (!empty($gRed)) {
            return $this->textoNumero($gRed, 'pAliqEfet');
        }

        return $this->textoNumero($grupo, $tagCheia);
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
