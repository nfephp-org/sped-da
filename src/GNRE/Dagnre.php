<?php

namespace NFePHP\DA\GNRE;

use Com\Tecnick\Barcode\Barcode;
use NFePHP\DA\Common\DaCommon;
use NFePHP\DA\Legacy\Pdf;
use InvalidArgumentException;
use stdClass;

/**
 * Documento auxiliar da GNRE: PDF com 3 vias por guia (Banco, Contribuinte, Contribuinte/Fisco),
 * código de barras de arrecadação (2 de 5 intercalado) e QR Code PIX quando informado.
 *
 * Entrada: o retorno de Tools::sefazConsultaLote() (TResultLote_GNRE, com ou sem envelope SOAP),
 * do layout 2.00 (guias 2.00 ou 1.00) ou do lote 1.00 puro (resultado em TXT).
 * Só as guias com situacaoGuia = 0 (processadas com sucesso) são impressas.
 *
 * Origem: refatoração do nfephp-org/sped-gnre 1.0.
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 */
class Dagnre extends DaCommon
{
    protected const TIPO_CODIGO_BARRAS = 'I25';
    protected const VIAS = ['1ª via - Banco', '2ª via - Contribuinte', '3ª via - Contribuinte/Fisco'];
    protected const ALTURA_VIA = 92.0;

    /**
     * @var array<int, stdClass>
     */
    protected $guias = [];
    /**
     * @var array<int, stdClass>
     */
    protected $naoImpressas = [];

    /**
     * @param string $xmlResultado retorno de Tools::sefazConsultaLote() (TResultLote_GNRE)
     */
    public function __construct(string $xmlResultado)
    {
        foreach (ResultadoLote::ler($xmlResultado)->guias as $guia) {
            if ($guia->situacaoGuia === '0') {
                $this->guias[] = $guia;
            } else {
                $this->naoImpressas[] = $guia;
            }
        }
        if (empty($this->guias)) {
            throw new InvalidArgumentException(
                'O documento de resposta relata um erro: nenhuma guia processada com sucesso para imprimir.'
            );
        }
    }

    /**
     * Guias impressas (modelo DadosGuia)
     *
     * @return array<int, stdClass>
     */
    public function getGuias(): array
    {
        return $this->guias;
    }

    /**
     * Guias não impressas (situacaoGuia diferente de 0), com os motivos de rejeição
     *
     * @return array<int, stdClass>
     */
    public function getGuiasNaoImpressas(): array
    {
        return $this->naoImpressas;
    }

    /**
     * @param string|null $logo
     */
    protected function monta($logo = null)
    {
        if (!empty($logo)) {
            $this->logomarca = $this->adjustImage($logo);
        }
        if (empty($this->wPrint)) {
            $this->printParameters('P', 'A4', 5, 5);
        }
        $this->pdf = new Pdf('P', 'mm', 'A4');
        $this->pdf->setMargins($this->margesq, $this->margsup);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->setAutoPageBreak(false);
        $this->pdf->setLineWidth(0.1);
        $this->pdf->setTextColor(0, 0, 0);
        foreach ($this->guias as $guia) {
            $this->pdf->addPage('P', 'A4');
            $y = (float) $this->margsup;
            foreach (self::VIAS as $indice => $rotulo) {
                $this->via($guia, $rotulo, $y);
                $y += self::ALTURA_VIA;
                if ($indice < count(self::VIAS) - 1) {
                    $this->pdf->dashedHLine($this->margesq, $y - 0.6, $this->wPrint, 0.1, 80);
                }
            }
            $this->rodape($y);
        }
    }

    /**
     * Uma via da guia a partir da posição $y
     */
    protected function via(stdClass $guia, string $rotulo, float $y): void
    {
        $x = (float) $this->margesq;
        $w = (float) $this->wPrint;
        $wEsq = round($w * 0.62, 1);
        $xDir = $x + $wEsq;
        $wDir = $w - $wEsq;

        // cabeçalho
        $wLogo = 0.0;
        if (!empty($this->logomarca)) {
            $this->pdf->image($this->logomarca, $x + 1, $y + 0.5, 0, 7);
            $wLogo = 18.0;
        }
        $this->pdf->textBox($x, $y, $wEsq, 8, '', $this->fonte(8));
        $this->pdf->textBox(
            $x + $wLogo,
            $y + 1,
            $wEsq - $wLogo,
            6,
            'Guia Nacional de Recolhimento de Tributos Estaduais - GNRE',
            $this->fonte(10, 'B'),
            'C',
            'C',
            false
        );
        $this->pdf->textBox($xDir, $y, $wDir, 8, $rotulo, $this->fonte(9, 'B'), 'C', 'C', true);

        // coluna esquerda
        $yb = $y + 8;
        $this->bloco($x, $yb, $wEsq, 24, 'Dados do Contribuinte Emitente', [
            'Razão Social: ' . $guia->emitenteRazaoSocial,
            'CNPJ/CPF/Insc. Est.: ' . trim($guia->emitenteDocumento . '   ' . $guia->emitenteIE),
            'Endereço: ' . $guia->emitenteEndereco,
            'Município: ' . $guia->emitenteMunicipio . '   UF: ' . $guia->emitenteUF
                . '   CEP: ' . $guia->emitenteCEP . '   Telefone: ' . $guia->emitenteTelefone,
        ]);
        $yb += 24;
        $this->bloco($x, $yb, $wEsq, 16, 'Dados do Destinatário', [
            'CNPJ/CPF/Insc. Est.: ' . trim($guia->destinatarioDocumento . '   ' . $guia->destinatarioIE),
            'Razão Social: ' . $guia->destinatarioRazaoSocial,
            'Município: ' . $guia->destinatarioMunicipio,
        ]);
        $yb += 16;
        $validade = $guia->dataLimitePagamento !== '' ? $guia->dataLimitePagamento : $guia->dataVencimento;
        $this->bloco($x, $yb, $wEsq, 26, 'Informações à Fiscalização', [
            'Convênio/Protocolo: ' . $guia->convenio . '   Produto: ' . $guia->produto,
            'Informações Complementares: ' . str_replace("\n", ' ', $guia->informacoesComplementares),
            $validade !== '' ? 'Documento válido para pagamento até ' . $validade : '',
        ]);

        // coluna direita
        $campos = [
            ['UF Favorecida', $guia->ufFavorecida],
            ['Código da Receita', $guia->receita],
            ['Nº de Controle', $guia->numeroControle],
            ['Data de Vencimento', $guia->dataVencimento],
            ['Nº do Documento de Origem', $guia->documentoOrigem],
            ['Período de Referência', $guia->periodoReferencia],
            ['Nº Parcela', $guia->parcela],
            ['Valor Principal', $this->moeda($guia->valorPrincipal)],
            ['Atualização Monetária', $this->moeda($guia->atualizacaoMonetaria)],
            ['Juros', $this->moeda($guia->juros)],
            ['Multa', $this->moeda($guia->multa)],
            ['Total a Recolher', $this->moeda($guia->valorTotal)],
        ];
        $hCampo = 66 / count($campos);
        $yc = $y + 8;
        foreach ($campos as [$rotuloCampo, $valor]) {
            $negrito = $rotuloCampo === 'Total a Recolher' ? 'B' : '';
            $this->pdf->textBox($xDir, $yc, $wDir, $hCampo, '', $this->fonte(6));
            $this->pdf->textBox(
                $xDir + 1,
                $yc + 0.3,
                $wDir * 0.5,
                $hCampo,
                $rotuloCampo,
                $this->fonte(6),
                'C',
                'L',
                false
            );
            $this->pdf->textBox(
                $xDir + $wDir * 0.45,
                $yc + 0.3,
                $wDir * 0.55 - 1,
                $hCampo,
                $valor,
                $this->fonte(8, $negrito),
                'C',
                'R',
                false
            );
            $yc += $hCampo;
        }

        // linha digitável, código de barras e PIX
        $yr = $y + 75;
        $temPix = $guia->qrcodePayload !== '';
        $wCodigo = $temPix ? $w - 30 : $w;
        $this->pdf->textBox(
            $x,
            $yr,
            $wCodigo,
            5,
            DadosGuia::formatarLinhaDigitavel($guia->linhaDigitavel),
            $this->fonte(10, 'B'),
            'C',
            'L',
            false
        );
        if (strlen($guia->codigoBarras) === 44) {
            $this->codigoBarras($guia->codigoBarras, $x, $yr + 5, 103, 10);
        } else {
            $this->pdf->textBox(
                $x,
                $yr + 5,
                103,
                10,
                'Código de barras indisponível',
                $this->fonte(8, 'I'),
                'C',
                'C',
                true
            );
        }
        if ($temPix) {
            $this->qrCode($guia->qrcodePayload, $x + $w - 17, $yr - 1, 16);
            $this->pdf->textBox(
                $x + $w - 47,
                $yr + 6,
                29,
                4,
                'Pagamento via PIX',
                $this->fonte(7, 'B'),
                'C',
                'R',
                false
            );
        }
    }

    /**
     * Caixa com título e linhas de texto
     *
     * @param array<int, string> $linhas
     */
    protected function bloco(float $x, float $y, float $w, float $h, string $titulo, array $linhas): void
    {
        $this->pdf->textBox($x, $y, $w, $h, '', $this->fonte(7));
        $this->pdf->textBox($x + 1, $y + 0.5, $w - 2, 3, $titulo, $this->fonte(7, 'B'), 'T', 'L', false);
        $altura = ($h - 4) / max(1, count($linhas));
        $yl = $y + 4;
        foreach ($linhas as $linha) {
            $this->pdf->textBox($x + 1, $yl, $w - 2, $altura, $linha, $this->fonte(7), 'T', 'L', false, '', false);
            $yl += $altura;
        }
    }

    /**
     * Código de barras de arrecadação (2 de 5 intercalado)
     */
    protected function codigoBarras(string $codigo, float $x, float $y, float $w, float $h): void
    {
        $png = (new Barcode())
            ->getBarcodeObj(self::TIPO_CODIGO_BARRAS, $codigo, -1, -12, 'black')
            ->setBackgroundColor('white')
            ->getPngData();
        $this->pdf->image('data://text/plain;base64,' . base64_encode($png), $x, $y, $w, $h, 'PNG');
    }

    /**
     * QR Code PIX
     */
    protected function qrCode(string $payload, float $x, float $y, float $tamanho): void
    {
        $png = (new Barcode())
            ->getBarcodeObj('QRCODE,M', $payload, -4, -4, 'black', [-2, -2, -2, -2])
            ->setBackgroundColor('white')
            ->getPngData();
        $this->pdf->image('data://text/plain;base64,' . base64_encode($png), $x, $y, $tamanho, $tamanho, 'PNG');
    }

    protected function rodape(float $y): void
    {
        $x = (float) $this->margesq;
        $w = (float) $this->wPrint;
        $fonte = $this->fonte(6, 'I');
        $texto = 'Impresso em ' . date('d/m/Y H:i:s') . ' ' . (string) $this->creditos;
        $this->pdf->textBox($x, $y, $w, 4, $texto, $fonte, 'T', 'L', false);
        if ($this->powered) {
            $this->pdf->textBox($x, $y, $w, 4, 'Powered by NFePHP®', $fonte, 'T', 'R', false);
        }
    }

    protected function moeda(string $valor): string
    {
        return $valor === '' ? '' : 'R$ ' . $valor;
    }

    /**
     * @return array{font: string, size: int, style: string}
     */
    protected function fonte(int $tamanho, string $estilo = ''): array
    {
        return ['font' => $this->fontePadrao, 'size' => $tamanho, 'style' => $estilo];
    }
}
