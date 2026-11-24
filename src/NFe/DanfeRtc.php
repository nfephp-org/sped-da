<?php

namespace NFePHP\DA\NFe;

use Com\Tecnick\Barcode\Barcode;

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
        $this->bordaQuadrada = true;
        $this->rotuloCinza = true;
        $this->hCabecalho = 54;
        $this->hCabecalhoItens = 8;
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
        $this->caixa($x, $y, $wCrt, $h, 'CÓDIGO DE REGIME TRIBUTÁRIO', $this->rotuloCrt($crt), 8, 'C', 'C');
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
        $y = $this->tituloBloco($x, $y, $w, 'TOTAL DOS PRODUTOS E TOTAL DA NOTA');
        $y = $this->linhaTotais($x, $y, $w, [
            ['VALOR TOTAL DOS PRODUTOS', $this->textoNumero($this->ICMSTot, 'vProd')],
            ['VALOR DO FRETE', $this->textoNumero($this->ICMSTot, 'vFrete')],
            ['VALOR DO SEGURO', $this->textoNumero($this->ICMSTot, 'vSeg')],
            ['DESCONTO', $this->textoNumero($this->ICMSTot, 'vDesc')],
            ['OUTRAS DESPESAS', $this->textoNumero($this->ICMSTot, 'vOutro')],
        ]);
        $y = $this->linhaTotais($x, $y, $w, [
            ['VALOR TOTAL DA NOTA', $this->textoNumero($this->ICMSTot, 'vNF')],
        ]);

        $y += 2;
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
        $gMono = $ibs ? $ibs->getElementsByTagName('gMono')->item(0) : null;
        $y += 2;
        $y = $this->tituloBloco($x, $y, $w, 'TOTAL DO IBS / CBS / IS');
        $y = $this->linhaTotais($x, $y, $w, [
            ['VALOR DA CBS', $this->textoNumero($ibs, 'vCBS')],
            ['VALOR DO IBS UF', $this->textoNumero($ibs, 'vIBSUF')],
            ['VALOR DO IBS MUNICÍPIO', $this->textoNumero($ibs, 'vIBSMun')],
            ['VALOR DO IMPOSTO SELETIVO', $this->textoNumero($isTot, 'vIS')],
        ]);
        $y = $this->linhaTotais($x, $y, $w, [
            ['VALOR DO IBS MONOFÁSICO', $this->textoNumero($gMono, 'vIBSMono')],
            ['VALOR DA CBS MONOFÁSICA', $this->textoNumero($gMono, 'vCBSMono')],
            ['VALOR DO IBS MONOFÁSICO POR RETENÇÃO', $this->textoNumero($gMono, 'vIBSMonoReten')],
            ['VALOR DA CBS MONOFÁSICA POR RETENÇÃO', $this->textoNumero($gMono, 'vCBSMonoReten')],
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
        $this->tituloSecao($x, $y, $w, 'DADOS DOS PRODUTOS / SERVIÇOS');
        $y += 3;
        $hmax += 1;
        $this->pdf->textBox($x, $y, $w, $hmax);
        $this->cabecalhoItens($x, $y, $w);
        $y += 4;

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
        $h += 7;
        $h += 7;
        $h += 4;
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
        $this->tituloSecao($x, $y, $w, $texto);

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
            $this->caixa($cursor, $y, $largura, $h, $campo[0], $campo[1], 9, 'L', 'B');
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
    protected function caixa($x, $y, $w, $h, $titulo, $valor, $tamanhoValor, $alignValor = 'R', $vAlignValor = 'B')
    {
        $fontTitulo = ['font' => $this->fontePadrao, 'size' => 5, 'style' => ''];
        $fontValor = ['font' => $this->fontePadrao, 'size' => $tamanhoValor, 'style' => 'B'];
        $this->pdf->textBox($x, $y, $w, $h, $titulo, $fontTitulo, 'T', 'L', 1, '');
        $this->pdf->textBox($x, $y, $w, $h, $valor, $fontValor, $vAlignValor, $alignValor, 0, '');
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
        $h = 4;
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
                $this->pdf->line($cursor, $y, $cursor, $y + $h);
            }
            $cursor += $largura;
        }

        $passo = 2.5;
        $this->pdf->textBox($x + 0.6, $y, $cols[0] - 1, $passo * $medida['nNome'], $medida['nome'], $aFont, 'T', 'L', 0, '', false);
        $yInfo = $y + ($passo * $medida['nNome']);
        if ($medida['info'] !== '') {
            $this->pdf->textBox(
                $x + 0.6,
                $yInfo,
                $cols[0] - 1,
                $passo * $medida['nInfo'],
                $medida['info'],
                $aFontMiudo,
                'T',
                'L',
                0,
                '',
                false
            );
        }
        $this->desenhaRodapeItem(
            $x,
            $yInfo + ($passo * $medida['nInfo']),
            $cols[0],
            $passo,
            $det
        );

        $xCst = $x + $cols[0];
        $this->desenhaGuia($xCst, $y, $cols[1], $lh, 'CST', $this->cstItem($det));
        $this->desenhaGuia($xCst, $y + $lh, $cols[1], $lh, 'CFOP', $this->getTagValue($prod, 'CFOP'));

        $xQtd = $xCst + $cols[1];
        $un = strtoupper($this->getTagValue($prod, 'uCom'));
        $this->pdf->textBox($xQtd, $y, $cols[2], $lh, $this->textoQuantidade($prod, 'qCom'), $aFont, 'T', 'C', 0, '');
        $this->pdf->textBox($xQtd, $y + $lh, $cols[2], $lh, $un === '' ? '' : '[' . $un . ']', $aFont, 'T', 'C', 0, '');

        $xUnit = $xQtd + $cols[2];
        $this->pdf->textBox($xUnit, $y, $cols[3], $lh, $this->textoNumero($prod, 'vUnCom'), $aFont, 'T', 'C', 0, '');
        $xTotal = $xUnit + $cols[3];
        $this->pdf->textBox($xTotal, $y, $cols[4], $lh, $this->textoNumero($prod, 'vProd'), $aFont, 'T', 'C', 0, '');

        $xBase = $xTotal + $cols[4];
        $xAliq = $xBase + $cols[5];
        $xValor = $xAliq + $cols[6];
        $xs = [$xBase, $xAliq, $xValor];
        $ws = [$cols[5], $cols[6], $cols[7]];
        foreach ($this->linhasTributoFaixa($det) as $i => $linha) {
            $yLinha = $y + ($i * $lh);
            foreach ($linha as $c => $celula) {
                if (isset($celula[0]) && is_array($celula[0])) {
                    $this->desenhaParGuia(
                        $xs[$c],
                        $yLinha,
                        $ws[$c],
                        $lh,
                        $celula[0][0],
                        $celula[0][1],
                        $celula[1][0],
                        $celula[1][1]
                    );
                    continue;
                }
                $this->desenhaGuia($xs[$c], $yLinha, $ws[$c], $lh, $celula[0], $celula[1]);
            }
        }
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->line($x, $y + $h, $x + $w, $y + $h);
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
        $passo = 2.5;
        $hTexto = ($nNome + $nInfo + 1) * $passo;
        $hTax = 4 * $lh;
        $hMiolo = max($hTexto, $hTax);

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
        $partes = [0.24, 0.08, 0.08, 0.09, 0.09, 0.14, 0.15];
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
     * Quatro linhas no formato do modelo: rótulo, pontilhado e valor.
     * O número só entra quando a tag existe.
     *
     * @param \DOMElement $det
     * @return array
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
                ['ICMS', $this->valorOuZero($this->textoNumero($icms, 'vBC'))],
                [
                    ['ICMS', $this->valorOuZero($this->textoPercentual($icms, 'pICMS'), true)],
                    ['CBS', $this->valorOuZero($this->textoPercentual($gCbs, 'pCBS'), true)],
                ],
                [
                    ['ICMS', $this->valorOuZero($this->textoNumero($icms, 'vICMS'))],
                    ['CBS', $this->valorOuZero($this->textoNumero($gCbs, 'vCBS'))],
                ],
            ],
            [
                ['IBS / CBS', $this->valorOuZero($this->textoNumero($gIbsCbs, 'vBC'))],
                [
                    ['IBS UF', $this->valorOuZero($this->textoPercentual($gIbsUf, 'pIBSUF'), true)],
                    ['IPI', $this->valorOuZero($this->textoPercentual($ipi, 'pIPI'), true)],
                ],
                [
                    ['IBS UF', $this->valorOuZero($this->textoNumero($gIbsUf, 'vIBSUF'))],
                    ['IPI', $this->valorOuZero($this->textoNumero($ipi, 'vIPI'))],
                ],
            ],
            [
                ['IS', $this->valorOuZero($this->textoNumero($is, 'vBCIS'))],
                [
                    ['IBS MUN', $this->valorOuZero($this->textoPercentual($gIbsMun, 'pIBSMun'), true)],
                    ['IS', $this->valorOuZero($this->textoPercentual($is, 'pIS'), true)],
                ],
                [
                    ['IBS MUN', $this->valorOuZero($this->textoNumero($gIbsMun, 'vIBSMun'))],
                    ['IS', $this->valorOuZero($this->textoNumero($is, 'vIS'))],
                ],
            ],
            [
                ['IPI', $this->valorOuZero($this->textoNumero($ipi, 'vBC'))],
                ['IPI', $this->valorOuZero($this->textoPercentual($ipi, 'pIPI'), true)],
                ['IPI', $this->valorOuZero($this->textoNumero($ipi, 'vIPI'))],
            ],
        ];
    }

    /**
     * Rótulo e pontilhado em cinza; valor em preto forte, alinhado à direita.
     *
     * @param float $x
     * @param float $y
     * @param float $w
     * @param float $h
     * @param string $rotulo
     * @param string $valor
     * @param float $tamanho
     * @return void
     */
    protected function desenhaGuia($x, $y, $w, $h, $rotulo, $valor, $tamanho = 5)
    {
        $pad = 0.6;
        $x += $pad;
        $w = max(6, $w - ($pad * 2));
        $guia = $this->linhaGuia($rotulo, $valor, $w, $tamanho);
        $valor = (string) $valor;
        $prefixo = substr($guia, 0, strlen($guia) - strlen($valor));
        $fonte = ['font' => $this->fontePadrao, 'size' => $tamanho, 'style' => ''];
        $fonteValor = ['font' => $this->fontePadrao, 'size' => $tamanho, 'style' => 'B'];
        $this->pdf->SetTextColor(120, 120, 120);
        $this->pdf->textBox($x, $y, $w, $h, $prefixo, $fonte, 'T', 'L', 0, '', false);
        $this->pdf->SetTextColor(0, 0, 0);
        if ($valor !== '') {
            $this->pdf->textBox($x, $y, $w, $h, $valor, $fonteValor, 'T', 'R', 0, '', false);
        }
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $w
     * @param float $h
     * @param string $rotuloE
     * @param string $valorE
     * @param string $rotuloD
     * @param string $valorD
     * @return void
     */
    protected function desenhaParGuia($x, $y, $w, $h, $rotuloE, $valorE, $rotuloD, $valorD)
    {
        $this->pdf->setFont($this->fontePadrao, '', 5);
        $wBarra = max(2.2, $this->pdf->GetStringWidth(' / '));
        $metade = ($w - $wBarra) / 2;
        $this->desenhaGuia($x, $y, $metade, $h, $rotuloE, $valorE);
        $fonte = ['font' => $this->fontePadrao, 'size' => 5, 'style' => ''];
        $this->pdf->SetTextColor(120, 120, 120);
        $this->pdf->textBox($x + $metade, $y, $wBarra, $h, '/', $fonte, 'T', 'C', 0, '', false);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->desenhaGuia($x + $metade + $wBarra, $y, $w - $metade - $wBarra, $h, $rotuloD, $valorD);
    }

    /**
     * [NCM ...] e [cClassTrib ...] com a chave cinza e o código em preto.
     *
     * @param float $x
     * @param float $y
     * @param float $w
     * @param float $h
     * @param \DOMElement $det
     * @return void
     */
    protected function desenhaRodapeItem($x, $y, $w, $h, $det)
    {
        $prod = $det->getElementsByTagName('prod')->item(0);
        $ncm = $prod ? $this->getTagValue($prod, 'NCM') : '';
        $classe = $this->cClassTribItem($det);
        $cursor = $x + 0.4;
        if ($ncm !== '') {
            $cursor = $this->desenhaChaveValor($cursor, $y, $h, 'NCM', $ncm);
        }
        if ($classe !== '') {
            $this->desenhaChaveValor($cursor + 1.2, $y, $h, 'cClassTrib', $classe);
        }
        $this->pdf->SetTextColor(0, 0, 0);
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $h
     * @param string $chave
     * @param string $valor
     * @return float
     */
    protected function desenhaChaveValor($x, $y, $h, $chave, $valor)
    {
        $tamanho = 5;
        $fonte = ['font' => $this->fontePadrao, 'size' => $tamanho, 'style' => ''];
        $fonteValor = ['font' => $this->fontePadrao, 'size' => $tamanho, 'style' => 'B'];
        $this->pdf->setFont($this->fontePadrao, '', $tamanho);
        $prefixo = '[' . $chave . ' ';
        $this->pdf->SetTextColor(120, 120, 120);
        $this->pdf->textBox($x, $y, 28, $h, $prefixo, $fonte, 'T', 'L', 0, '');
        $xValor = $x + $this->pdf->GetStringWidth($prefixo);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->textBox($xValor, $y, 24, $h, $valor, $fonteValor, 'T', 'L', 0, '');
        $this->pdf->setFont($this->fontePadrao, 'B', $tamanho);
        $xFecha = $xValor + $this->pdf->GetStringWidth($valor);
        $this->pdf->setFont($this->fontePadrao, '', $tamanho);
        $this->pdf->SetTextColor(120, 120, 120);
        $this->pdf->textBox($xFecha, $y, 3, $h, ']', $fonte, 'T', 'L', 0, '');

        return $xFecha + $this->pdf->GetStringWidth(']');
    }

    /**
     * Rótulo, pontilhado e valor na largura da coluna, como no modelo da NT.
     *
     * @param string $rotulo
     * @param string $valor
     * @param float $largura
     * @param float $tamanho
     * @return string
     */
    protected function linhaGuia($rotulo, $valor, $largura, $tamanho = 4.5)
    {
        $this->pdf->setFont($this->fontePadrao, '', $tamanho);
        $rotulo = (string) $rotulo;
        $valor = (string) $valor;
        $larguraUtil = max(6, $largura - 0.6);
        $wPonto = $this->pdf->GetStringWidth('.');
        if ($wPonto < 0.2) {
            $wPonto = 0.35;
        }
        $sobra = $larguraUtil - $this->pdf->GetStringWidth($rotulo . $valor);
        $n = (int) floor($sobra / $wPonto);
        if ($n < 1) {
            $n = 1;
        }
        if ($n > 4) {
            $n = 4;
        }

        return $rotulo . str_repeat('.', $n) . $valor;
    }

    /**
     * Dois rótulos na mesma coluna, no par do modelo.
     *
     * @param string $rotuloE
     * @param string $valorE
     * @param string $rotuloD
     * @param string $valorD
     * @param float $largura
     * @return string
     */
    protected function parGuia($rotuloE, $valorE, $rotuloD, $valorD, $largura)
    {
        $metade = $largura / 2;

        return trim(
            $this->linhaGuia($rotuloE, $valorE, $metade) . ' ' . $this->linhaGuia($rotuloD, $valorD, $metade)
        );
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
     * Campo sem tag imprime zero, no mesmo formato do valor preenchido.
     *
     * @param string $texto
     * @param bool $percentual
     * @return string
     */
    protected function valorOuZero($texto, $percentual = false)
    {
        if ($texto === '' || $texto === null) {
            return $percentual ? '0,00%' : '0,00';
        }

        return $texto;
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
        return number_format((float) $campo->nodeValue, 2, ',', '.');
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
    /**
     * Dados adicionais à esquerda e o quadro do QR Code à direita.
     *
     * @param float $x
     * @param float $y
     * @param float $h
     * @return float
     */
    protected function dadosAdicionais($x, $y, $h)
    {
        $y = $this->maxH - (7 + $h);
        $wTotal = $this->orientacao == 'P' ? $this->wPrint : $this->wPrint - $this->wCanhoto;
        $wQr = 32;
        $wEsq = $wTotal - $wQr - 1;
        $this->tituloSecao($x, $y, $wEsq, 'DADOS ADICIONAIS');

        $yBox = $y + 3;
        $wComp = round($wEsq * 0.66, 0);
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'B'];
        $this->pdf->textBox($x, $yBox, $wComp, $h, 'INFORMAÇÕES COMPLEMENTARES', $aFont, 'T', 'L', 1, '');
        $aFont = ['font' => $this->fontePadrao, 'size' => $this->textadicfontsize * $this->pdf->k, 'style' => ''];
        $this->pdf->textBox($x, $yBox + 3, $wComp - 2, $h - 3, $this->textoAdic, $aFont, 'T', 'L', 0, '', false);

        $xFisco = $x + $wComp;
        $wFisco = $wEsq - $wComp;
        $texto = 'RESERVADO AO FISCO';
        if (isset($this->nfeProc) && $this->nfeProc->getElementsByTagName('xMsg')->length) {
            $texto .= ' ' . $this->nfeProc->getElementsByTagName('xMsg')->item(0)->nodeValue;
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'B'];
        $this->pdf->textBox($xFisco, $yBox, $wFisco, $h, $texto, $aFont, 'T', 'L', 1, '');

        $xQr = $x + $wEsq + 1;
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'B'];
        $this->pdf->textBox($xQr, $yBox, $wQr, $h, 'QR CODE', $aFont, 'T', 'C', 1, '');
        $this->desenhaQrCode($xQr, $yBox + 4, $wQr, $h - 5);

        return $yBox + $h;
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $w
     * @param float $h
     * @return void
     */
    protected function desenhaQrCode($x, $y, $w, $h)
    {
        $conteudo = $this->conteudoQrCode();
        if ($conteudo === '' || $h < 8 || $w < 8) {
            return;
        }
        $barcode = new Barcode();
        $png = $barcode->getBarcodeObj('QRCODE,M', $conteudo, -4, -4, 'black', [-1, -1, -1, -1])
            ->setBackgroundColor('white')
            ->getPngData();
        $lado = min($w - 2, $h - 1);
        $pic = 'data://text/plain;base64,' . base64_encode($png);
        $this->pdf->image($pic, $x + (($w - $lado) / 2), $y, $lado, $lado, 'PNG');
    }

    /**
     * @return string
     */
    protected function conteudoQrCode()
    {
        if (!empty($this->dom)) {
            $node = $this->dom->getElementsByTagName('qrCode')->item(0);
            if (!empty($node) && trim($node->nodeValue) !== '') {
                return trim($node->nodeValue);
            }
        }
        $chave = str_replace('NFe', '', $this->infNFe->getAttribute('Id'));

        return $chave;
    }

    /**
     * Faixa cinza clara e título em negrito, como no leiaute da NT.
     *
     * @param float $x
     * @param float $y
     * @param float $w
     * @param string $texto
     * @return void
     */
    protected function tituloSecao($x, $y, $w, $texto)
    {
        $h = 3.2;
        $this->pdf->SetFillColor(226, 226, 226);
        $this->pdf->rect($x, $y, $w, $h, 'F');
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x + 0.4, $y, $w - 0.8, $h, $texto, $aFont, 'C', 'L', 0, '', false);
        $this->pdf->SetFillColor(255, 255, 255);
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
