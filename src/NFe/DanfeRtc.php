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
        $wCrt = 62;
        $crt = $this->getTagValue($this->emit, 'CRT');
        $this->caixa($x, $y, $wCrt, $h, 'CÓDIGO DE REGIME TRIBUTÁRIO', $this->rotuloCrt($crt), 8);
        $this->caixa(
            $x + $wCrt,
            $y,
            $maxW - $wCrt,
            $h,
            'TIPO DE REGIME DE APURAÇÃO DO IBS E DA CBS',
            '',
            8
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
        if (!empty($opcionais)) {
            $y = $this->linhaTotais($x, $y, $w, $opcionais);
        }

        $ibs = $this->dom->getElementsByTagName('IBSCBSTot')->item(0);
        $isTot = $this->dom->getElementsByTagName('ISTot')->item(0);
        $y = $this->tituloBloco($x, $y, $w, 'TOTAL DO IBS / CBS / IS');
        $y = $this->linhaTotais($x, $y, $w, [
            ['IBS UF', $this->textoNumero($ibs, 'vIBSUF')],
            ['IBS MUNICIPAL', $this->textoNumero($ibs, 'vIBSMun')],
            ['CBS', $this->textoNumero($ibs, 'vCBS')],
            ['IS', $this->textoNumero($isTot, 'vIS')],
        ]);

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
        $linhasOpcionais = (int) ceil(count($this->camposIcmsOpcionais()) / 6);
        $h = (3 + 7) * 3;
        $h += $linhasOpcionais * 7;

        return $h + 1;
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    protected function camposIcmsOpcionais()
    {
        $campos = [];
        $mapa = [
            'vFCP' => 'FCP',
            'vICMSUFDest' => 'ICMS UF DEST.',
            'vICMSUFRemet' => 'ICMS UF REMET.',
            'vFCPUFDest' => 'FCP UF DEST.',
            'vICMSMono' => 'ICMS MONO',
        ];
        foreach ($mapa as $tag => $titulo) {
            $valor = $this->textoNumero($this->ICMSTot, $tag);
            if ($valor !== '') {
                $campos[] = [$titulo, $valor];
            }
        }
        if (empty($this->dom)) {
            return $campos;
        }
        $tot = $this->dom->getElementsByTagName('IBSCBSTot')->item(0);
        $gMono = $tot ? $tot->getElementsByTagName('gMono')->item(0) : null;
        if ($gMono) {
            foreach (['vIBSMono' => 'IBS MONO', 'vCBSMono' => 'CBS MONO'] as $tag => $titulo) {
                $valor = $this->textoNumero($gMono, $tag);
                if ($valor !== '') {
                    $campos[] = [$titulo, $valor];
                }
            }
        }

        return $campos;
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
            $this->caixa($cursor, $y, $largura, $h, $campo[0], $campo[1], 9);
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
     * @return void
     */
    protected function caixa($x, $y, $w, $h, $titulo, $valor, $tamanhoValor)
    {
        $fontTitulo = ['font' => $this->fontePadrao, 'size' => 5, 'style' => ''];
        $fontValor = ['font' => $this->fontePadrao, 'size' => $tamanhoValor, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $w, $h, $titulo, $fontTitulo, 'T', 'L', 1, '');
        $this->pdf->textBox($x, $y, $w, $h, $valor, $fontValor, 'B', 'R', 0, '');
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

        $this->pdf->textBox($x, $y, $cols[0], $lh * $medida['nNome'], $medida['nome'], $aFont, 'T', 'L', 0, '');
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
        foreach ($this->linhasTributoFaixa($det) as $i => $linha) {
            $yLinha = $y + ($i * $lh);
            $this->pdf->textBox($xBase, $yLinha, $cols[5], $lh, $linha[0], $aFontMiudo, 'T', 'R', 0, '');
            $this->pdf->textBox($xAliq, $yLinha, $cols[6], $lh, $linha[1], $aFontMiudo, 'T', 'C', 0, '');
            $this->pdf->textBox($xValor, $yLinha, $cols[7], $lh, $linha[2], $aFontMiudo, 'T', 'R', 0, '');
        }

        $this->pdf->dashedHLine($x, $y + $hMiolo + 0.4, $w, 0.1, 120);
    }

    /**
     * Altura da faixa desenhada por faixaItem().
     *
     * @param \DOMElement $det
     * @return array{h: float, hMiolo: float, lh: float, nNome: int, nInfo: int, nome: string, info: string, rodape: string}
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
        $hTexto = ($nNome + $nInfo) * $lh;
        $hNcm = $lh;
        $hTax = 4 * $lh;
        $hMiolo = max($hTexto + $hNcm, $hTax + $hNcm);

        return [
            'h' => round($hMiolo + 1.2, 2),
            'hMiolo' => $hMiolo,
            'lh' => $lh,
            'nNome' => $nNome,
            'nInfo' => $nInfo,
            'nome' => $nome,
            'info' => $info,
            'rodape' => $this->rodapeItem($det),
        ];
    }

    /**
     * @param float $w
     * @return float[]
     */
    protected function colunasItem($w)
    {
        $partes = [0.28, 0.09, 0.09, 0.10, 0.11, 0.11, 0.12];
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
     * Quatro linhas fixas. Tag ausente permanece em branco.
     *
     * @param \DOMElement $det
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    protected function linhasTributoFaixa($det)
    {
        $imposto = $det->getElementsByTagName('imposto')->item(0);
        $icms = $imposto ? $imposto->getElementsByTagName('ICMS')->item(0) : null;
        $ipi = $imposto ? $imposto->getElementsByTagName('IPI')->item(0) : null;
        $is = $this->primeiroFilho($imposto, 'IS');
        $gIbsCbs = $this->primeiroFilho($this->primeiroFilho($imposto, 'IBSCBS'), 'gIBSCBS');
        $gIbsUf = $this->primeiroFilho($gIbsCbs, 'gIBSUF');
        $gIbsMun = $this->primeiroFilho($gIbsCbs, 'gIBSMun');
        $gCbs = $this->primeiroFilho($gIbsCbs, 'gCBS');

        return [
            [
                $this->textoNumero($icms, 'vBC'),
                $this->textoPercentual($icms, 'pICMS'),
                $this->textoNumero($icms, 'vICMS'),
            ],
            [
                $this->textoNumero($gIbsCbs, 'vBC'),
                $this->parTexto(
                    $this->textoPercentual($gIbsUf, 'pIBSUF'),
                    $this->textoPercentual($gCbs, 'pCBS')
                ),
                $this->parTexto(
                    $this->textoNumero($gIbsUf, 'vIBSUF'),
                    $this->textoNumero($gCbs, 'vCBS')
                ),
            ],
            [
                $this->textoNumero($is, 'vBCIS'),
                $this->parTexto(
                    $this->textoPercentual($gIbsMun, 'pIBSMun'),
                    $this->textoPercentual($is, 'pIS')
                ),
                $this->parTexto(
                    $this->textoNumero($gIbsMun, 'vIBSMun'),
                    $this->textoNumero($is, 'vIS')
                ),
            ],
            [
                $this->textoNumero($ipi, 'vBC'),
                $this->textoPercentual($ipi, 'pIPI'),
                $this->textoNumero($ipi, 'vIPI'),
            ],
        ];
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
     * @param string $esquerda
     * @param string $direita
     * @return string
     */
    protected function parTexto($esquerda, $direita)
    {
        if ($esquerda === '' && $direita === '') {
            return '';
        }
        if ($esquerda === '') {
            return $direita;
        }
        if ($direita === '') {
            return $esquerda;
        }

        return $esquerda . ' / ' . $direita;
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
