<?php

namespace NFePHP\DA\GNRE;

use Com\Tecnick\Barcode\Barcode;
use NFePHP\DA\Common\DaCommon;
use NFePHP\DA\Legacy\Pdf;
use InvalidArgumentException;
use stdClass;

/**
 * Documento auxiliar da GNRE: PDF com 3 vias por guia (Banco, Contribuinte, Contribuinte/Fisco),
 * código de barras de arrecadação (2 de 5 intercalado, desenhado em vetor, razão 3:1) e
 * QR Code PIX quando informado.
 *
 * Entrada: o retorno de Tools::sefazConsultaLote() (TResultLote_GNRE, com ou sem envelope SOAP),
 * do layout 2.00 (guias 2.00 ou 1.00) ou do lote 1.00 puro (resultado em TXT).
 * Só as guias com situacaoGuia = 0 (processadas com sucesso) são impressas.
 * Layout fixo: A4 retrato, uma guia por página.
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
    protected const ALTURA_VIA = 95.0;
    protected const LARGURA_CODIGO_BARRAS = 103.0;
    protected const ALTURA_CODIGO_BARRAS = 13.0;
    protected const TAMANHO_QRCODE = 18.0;
    protected const LARGURA_MAXIMA_LOGO = 30.0;
    /**
     * Altura de uma linha de texto em mm por ponto de fonte (1 pt = 0,3528 mm), com folga
     */
    protected const MM_POR_PONTO = 0.3528 * 1.1;

    /**
     * Padrões do 2 de 5 intercalado (n = estreita, w = larga) por dígito
     */
    private const PADROES_I25 = [
        '0' => 'nnwwn', '1' => 'wnnnw', '2' => 'nwnnw', '3' => 'wwnnn', '4' => 'nnwnw',
        '5' => 'wnwnn', '6' => 'nwwnn', '7' => 'nnnww', '8' => 'wnnwn', '9' => 'nwnwn',
    ];

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
        $resultado = ResultadoLote::ler($xmlResultado);
        foreach ($resultado->guias as $guia) {
            if ($guia->situacaoGuia === '0') {
                $this->guias[] = $guia;
            } else {
                $this->naoImpressas[] = $guia;
            }
        }
        if (empty($this->guias)) {
            throw new InvalidArgumentException(sprintf(
                'O documento de resposta relata um erro: nenhuma guia processada com sucesso para imprimir '
                . '(situação do lote: %s - %s; guias não impressas: %d).',
                $resultado->situacaoProcess->codigo,
                $resultado->situacaoProcess->descricao,
                count($this->naoImpressas)
            ));
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
     * Barras do código 2 de 5 intercalado: lista de [é barra, largura em módulos (1 estreita, 3 larga)]
     *
     * @return array<int, array{0: bool, 1: int}>
     * @throws InvalidArgumentException se o código não tiver só dígitos em quantidade par
     */
    public static function barrasI25(string $codigo): array
    {
        if ($codigo === '' || !ctype_digit($codigo) || strlen($codigo) % 2 !== 0) {
            throw new InvalidArgumentException("Código 2 de 5 intercalado inválido: [$codigo].");
        }
        $barras = [[true, 1], [false, 1], [true, 1], [false, 1]];
        foreach (str_split($codigo, 2) as $par) {
            $pretas = self::PADROES_I25[$par[0]];
            $brancas = self::PADROES_I25[$par[1]];
            for ($i = 0; $i < 5; $i++) {
                $barras[] = [true, $pretas[$i] === 'w' ? 3 : 1];
                $barras[] = [false, $brancas[$i] === 'w' ? 3 : 1];
            }
        }
        $barras[] = [true, 3];
        $barras[] = [false, 1];
        $barras[] = [true, 1];
        return $barras;
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
            [$wImagem, $hImagem] = $this->dimensoesLogo(7.0);
            $this->pdf->image($this->logomarca, $x + 1, $y + 0.5 + (7 - $hImagem) / 2, $wImagem, $hImagem, 'jpeg');
            $wLogo = $wImagem + 2;
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
        $wRotulo = 27.0;
        $wValor = $wDir - $wRotulo - 1.5;
        $yc = $y + 8;
        foreach ($campos as [$rotuloCampo, $valor]) {
            $estilo = $rotuloCampo === 'Total a Recolher' ? 'B' : '';
            $this->pdf->textBox($xDir, $yc, $wDir, $hCampo, '', $this->fonte(6));
            $this->pdf->textBox(
                $xDir + 1,
                $yc + 0.3,
                $wRotulo,
                $hCampo,
                $rotuloCampo,
                $this->fonte(6),
                'C',
                'L',
                false
            );
            $this->pdf->textBox(
                $xDir + $wRotulo + 0.5,
                $yc + 0.3,
                $wValor,
                $hCampo,
                $valor,
                $this->fonte($this->fonteQueCabe($valor, $wValor, 8, $estilo), $estilo),
                'C',
                'R',
                false
            );
            $yc += $hCampo;
        }

        // linha digitável, código de barras e PIX
        $yr = $y + 75;
        $temPix = $guia->qrcodePayload !== '';
        $this->pdf->textBox(
            $x,
            $yr,
            $temPix ? $w - 30 : $w,
            5,
            DadosGuia::formatarLinhaDigitavel($guia->linhaDigitavel),
            $this->fonte(10, 'B'),
            'C',
            'L',
            false
        );
        if (strlen($guia->codigoBarras) === 44 && ctype_digit($guia->codigoBarras)) {
            $this->codigoBarras(
                $guia->codigoBarras,
                $x,
                $yr + 5,
                self::LARGURA_CODIGO_BARRAS,
                self::ALTURA_CODIGO_BARRAS
            );
        } else {
            $this->pdf->textBox(
                $x,
                $yr + 5,
                self::LARGURA_CODIGO_BARRAS,
                self::ALTURA_CODIGO_BARRAS,
                'Código de barras indisponível',
                $this->fonte(8, 'I'),
                'C',
                'C',
                true
            );
        }
        if ($temPix) {
            $this->qrCode($guia->qrcodePayload, $x + $w - self::TAMANHO_QRCODE - 0.5, $yr - 0.5, self::TAMANHO_QRCODE);
            $this->pdf->textBox(
                $x + $w - self::TAMANHO_QRCODE - 31,
                $yr + 7,
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
     * Caixa com título e linhas de texto que sempre cabem na altura disponível
     *
     * @param array<int, string> $linhas
     */
    protected function bloco(float $x, float $y, float $w, float $h, string $titulo, array $linhas): void
    {
        $this->pdf->textBox($x, $y, $w, $h, '', $this->fonte(7));
        $this->pdf->textBox($x + 1, $y + 0.5, $w - 2, 3, $titulo, $this->fonte(7, 'B'), 'T', 'L', false);
        $yl = $y + 4;
        foreach ($this->distribuirLinhas($linhas, $w - 2, $h - 4.5) as $item) {
            $this->pdf->textBox(
                $x + 1,
                $yl,
                $w - 2,
                $item['altura'],
                $item['texto'],
                $this->fonte($item['fonte']),
                'T',
                'L',
                false,
                '',
                false
            );
            $yl += $item['altura'];
        }
    }

    /**
     * Distribui as linhas na altura disponível: reduz a fonte (7 -> 5) e, se ainda não couber,
     * encurta as linhas mais longas (exceto a última, que traz a validade do documento).
     *
     * @param array<int, string> $linhas
     * @return array<int, array{texto: string, fonte: int, altura: float}>
     */
    protected function distribuirLinhas(array $linhas, float $largura, float $altura): array
    {
        $linhas = array_values(array_filter($linhas, function (string $linha): bool {
            return trim($linha) !== '';
        }));
        if (empty($linhas)) {
            return [];
        }
        for ($fonte = 7; $fonte >= 5; $fonte--) {
            $alturas = $this->alturasNecessarias($linhas, $largura, $fonte);
            if (array_sum($alturas) <= $altura) {
                return $this->montarDistribuicao($linhas, $alturas, $fonte, $altura);
            }
        }
        $fonte = 5;
        $alturas = $this->alturasNecessarias($linhas, $largura, $fonte);
        $ultima = count($linhas) - 1;
        while (array_sum($alturas) > $altura) {
            $maior = 0;
            foreach ($linhas as $indice => $linha) {
                if (($indice < $ultima || $ultima === 0) && mb_strlen($linha) > mb_strlen($linhas[$maior])) {
                    $maior = $indice;
                }
            }
            if (mb_strlen($linhas[$maior]) <= 10) {
                break;
            }
            $linhas[$maior] = rtrim(mb_substr($linhas[$maior], 0, mb_strlen($linhas[$maior]) - 13)) . '...';
            $alturas = $this->alturasNecessarias($linhas, $largura, $fonte);
        }
        return $this->montarDistribuicao($linhas, $alturas, $fonte, $altura);
    }

    /**
     * @param array<int, string> $linhas
     * @return array<int, float>
     */
    protected function alturasNecessarias(array $linhas, float $largura, int $fonte): array
    {
        $alturas = [];
        foreach ($linhas as $linha) {
            $alturas[] = $this->pdf->getNumLines($linha, $largura, $this->fonte($fonte)) * $fonte * self::MM_POR_PONTO;
        }
        return $alturas;
    }

    /**
     * @param array<int, string> $linhas
     * @param array<int, float> $alturas
     * @return array<int, array{texto: string, fonte: int, altura: float}>
     */
    protected function montarDistribuicao(array $linhas, array $alturas, int $fonte, float $altura): array
    {
        $sobra = max(0.0, $altura - array_sum($alturas)) / count($linhas);
        $distribuicao = [];
        foreach ($linhas as $indice => $linha) {
            $distribuicao[] = ['texto' => $linha, 'fonte' => $fonte, 'altura' => $alturas[$indice] + $sobra];
        }
        return $distribuicao;
    }

    /**
     * Maior tamanho de fonte (de $maximo até 4) em que o texto cabe numa linha da largura dada
     */
    protected function fonteQueCabe(string $texto, float $largura, int $maximo, string $estilo = ''): int
    {
        $iso = (string) mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
        for ($fonte = $maximo; $fonte > 4; $fonte--) {
            $this->pdf->setFont($this->fontePadrao, $estilo, $fonte);
            if ($this->pdf->getStringWidth($iso) <= $largura) {
                return $fonte;
            }
        }
        return 4;
    }

    /**
     * Largura da logo (mm) para a altura dada, proporcional e limitada a 30 mm
     */
    protected function larguraLogo(float $altura): float
    {
        return $this->dimensoesLogo($altura)[0];
    }

    /**
     * [largura, altura] da logo, proporcionais, cabendo em LARGURA_MAXIMA_LOGO x $altura
     *
     * @return array{0: float, 1: float}
     */
    protected function dimensoesLogo(float $altura): array
    {
        if (empty($this->logomarca)) {
            return [0.0, 0.0];
        }
        $info = @getimagesize($this->logomarca);
        if ($info === false || empty($info[0]) || empty($info[1])) {
            return [$altura, $altura];
        }
        $largura = $altura * $info[0] / $info[1];
        if ($largura > self::LARGURA_MAXIMA_LOGO) {
            return [self::LARGURA_MAXIMA_LOGO, self::LARGURA_MAXIMA_LOGO * $info[1] / $info[0]];
        }
        return [$largura, $altura];
    }

    /**
     * Código de barras de arrecadação: 2 de 5 intercalado em vetor, razão larga:estreita 3:1
     */
    protected function codigoBarras(string $codigo, float $x, float $y, float $w, float $h): void
    {
        $barras = self::barrasI25($codigo);
        $modulo = $w / array_sum(array_column($barras, 1));
        $this->pdf->setFillColor(0, 0, 0);
        $xb = $x;
        foreach ($barras as [$barra, $largura]) {
            if ($barra) {
                $this->pdf->rect($xb, $y, $modulo * $largura, $h, 'F');
            }
            $xb += $modulo * $largura;
        }
        $this->pdf->setFillColor(255, 255, 255);
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
