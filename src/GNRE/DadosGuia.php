<?php

namespace NFePHP\DA\GNRE;

use stdClass;

/**
 * Normaliza as guias do resultado do lote para um modelo único de impressão.
 *
 * Origens aceitas:
 * - deResultado(): elemento de ResultadoLote::paraStd()->resultado->guia, versão 2.00 ou 1.00;
 * - deTxtV1(): item de ResultadoLote::lerTxtV1()->guias (resultado TXT do lote 1.00 puro).
 *
 * Todos os campos são strings já formatadas para exibição ('' quando ausentes), exceto
 * motivosRejeicao (array de {codigo, descricao, campo}).
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 */
class DadosGuia
{
    protected const CAMPOS = [
        'versao', 'situacaoGuia', 'identificadorGuia', 'ufFavorecida', 'receita', 'detalhamentoReceita',
        'produto', 'documentoOrigem', 'convenio',
        'emitenteDocumento', 'emitenteIE', 'emitenteRazaoSocial', 'emitenteEndereco', 'emitenteMunicipio',
        'emitenteUF', 'emitenteCEP', 'emitenteTelefone',
        'destinatarioDocumento', 'destinatarioIE', 'destinatarioRazaoSocial', 'destinatarioMunicipio',
        'periodoReferencia', 'parcela', 'dataVencimento', 'dataLimitePagamento',
        'valorPrincipal', 'atualizacaoMonetaria', 'juros', 'multa', 'valorTotal',
        'informacoesComplementares', 'numeroControle', 'linhaDigitavel', 'codigoBarras', 'qrcodePayload',
    ];

    /**
     * Guia de TResultLote_GNRE (2.00 ou 1.00), já convertida por ResultadoLote::paraStd()
     */
    public static function deResultado(stdClass $guia): stdClass
    {
        $versao = (string) ($guia->attributes->versao ?? '2.00');
        $dados = $versao === '1.00' ? self::de100($guia) : self::de200($guia);
        $dados->versao = $versao;
        $dados->situacaoGuia = self::texto($guia->situacaoGuia ?? '');
        $dados->dataLimitePagamento = $dados->dataLimitePagamento !== ''
            ? $dados->dataLimitePagamento
            : self::data(self::texto($guia->dataLimitePagamento ?? ''));
        $dados->numeroControle = $dados->numeroControle !== ''
            ? $dados->numeroControle
            : self::texto($guia->nossoNumero ?? '');
        $dados->informacoesComplementares = $dados->informacoesComplementares !== ''
            ? $dados->informacoesComplementares
            : self::informacoes($guia->informacoesComplementares ?? null);
        $dados->linhaDigitavel = self::digitos(self::texto($guia->linhaDigitavel ?? ''));
        $dados->codigoBarras = self::digitos(self::texto($guia->codigoBarras ?? ''));
        $dados->qrcodePayload = self::texto($guia->qrcodePayload ?? '');
        $dados->motivosRejeicao = [];
        foreach (self::lista($guia->motivosRejeicao->motivo ?? null) as $motivo) {
            $dados->motivosRejeicao[] = (object) [
                'codigo' => self::texto($motivo->codigo ?? ''),
                'descricao' => self::texto($motivo->descricao ?? ''),
                'campo' => self::texto($motivo->campo ?? ''),
            ];
        }
        return $dados;
    }

    /**
     * Guia do resultado TXT do lote 1.00 puro (ResultadoLote::lerTxtV1())
     */
    public static function deTxtV1(stdClass $guia): stdClass
    {
        $dados = self::vazio();
        $dados->versao = '1.00';
        $dados->situacaoGuia = $guia->situacaoGuia ?? '';
        $dados->identificadorGuia = ltrim($guia->identificadorGuia ?? '', '0');
        $dados->ufFavorecida = $guia->ufFavorecida ?? '';
        $dados->receita = $guia->receita ?? '';
        $dados->produto = $guia->produto ?? '';
        $dados->documentoOrigem = ltrim($guia->docOrigem ?? '', '0');
        $dados->convenio = $guia->convenio ?? '';
        $dados->emitenteDocumento = self::documentoTxt($guia->docEmitente ?? '', $guia->tipoDocEmitente ?? '');
        $dados->emitenteRazaoSocial = $guia->razaoSocialEmitente ?? '';
        $dados->emitenteEndereco = $guia->enderecoEmitente ?? '';
        $dados->emitenteMunicipio = $guia->municipioEmitente ?? '';
        $dados->emitenteUF = $guia->ufEmitente ?? '';
        $dados->emitenteCEP = $guia->cepEmitente ?? '';
        $dados->emitenteTelefone = $guia->telefoneEmitente ?? '';
        $dados->destinatarioDocumento = self::documentoTxt(
            $guia->docDestinatario ?? '',
            $guia->tipoDocDestinatario ?? ''
        );
        $dados->destinatarioMunicipio = $guia->municipioDestinatario ?? '';
        $mesAno = $guia->mesAnoReferencia ?? '';
        $dados->periodoReferencia = strlen($mesAno) === 6 && $mesAno !== '000000'
            ? substr($mesAno, 0, 2) . '/' . substr($mesAno, 2)
            : '';
        $dados->parcela = ltrim($guia->parcela ?? '', '0') === '' ? '' : $guia->parcela;
        $dados->dataVencimento = self::data($guia->dataVencimento ?? '');
        $dados->dataLimitePagamento = self::data($guia->dataLimitePagamento ?? '');
        $dados->valorPrincipal = self::formatarValor($guia->valorPrincipal ?? '');
        $dados->atualizacaoMonetaria = self::formatarValor($guia->atualizacaoMonetaria ?? '');
        $dados->juros = self::formatarValor($guia->juros ?? '');
        $dados->multa = self::formatarValor($guia->multa ?? '');
        $total = (float) ($guia->valorPrincipal ?? 0) + (float) ($guia->atualizacaoMonetaria ?? 0)
            + (float) ($guia->juros ?? 0) + (float) ($guia->multa ?? 0);
        $dados->valorTotal = self::formatarValor((string) $total);
        $dados->informacoesComplementares = $guia->informacoesComplementares ?? '';
        $dados->numeroControle = self::semZeros($guia->numeroControle ?? '');
        $dados->linhaDigitavel = self::semZeros(self::digitos($guia->representacaoNumerica ?? ''));
        $dados->codigoBarras = self::semZeros(self::digitos($guia->codigoBarras ?? ''));
        $dados->motivosRejeicao = [];
        foreach ($guia->rejeicoes ?? [] as $rejeicao) {
            $dados->motivosRejeicao[] = (object) [
                'codigo' => $rejeicao->codigoMotivo,
                'descricao' => $rejeicao->descricaoMotivo,
                'campo' => $rejeicao->nomeCampo,
            ];
        }
        return $dados;
    }

    /**
     * 48 dígitos em 4 blocos de 11 + DV: 85850000002-9 43009999200-6 ...
     */
    public static function formatarLinhaDigitavel(string $linha): string
    {
        $digitos = self::digitos($linha);
        if (strlen($digitos) !== 48) {
            return $linha;
        }
        $blocos = [];
        foreach (str_split($digitos, 12) as $bloco) {
            $blocos[] = substr($bloco, 0, 11) . '-' . substr($bloco, 11, 1);
        }
        return implode(' ', $blocos);
    }

    /**
     * CNPJ 00.000.000/0000-00, CPF 000.000.000-00; outros valores sem alteração
     */
    public static function formatarDocumento(string $documento): string
    {
        $digitos = self::digitos($documento);
        if (strlen($digitos) === 14 && $digitos === $documento) {
            return vsprintf('%s.%s.%s/%s-%s', [
                substr($digitos, 0, 2), substr($digitos, 2, 3), substr($digitos, 5, 3),
                substr($digitos, 8, 4), substr($digitos, 12, 2),
            ]);
        }
        if (strlen($digitos) === 11 && $digitos === $documento) {
            return vsprintf('%s.%s.%s-%s', [
                substr($digitos, 0, 3), substr($digitos, 3, 3), substr($digitos, 6, 3), substr($digitos, 9, 2),
            ]);
        }
        return $documento;
    }

    /**
     * 1234.5 -> 1.234,50; zero ou vazio -> ''
     */
    public static function formatarValor(string $valor): string
    {
        if (trim($valor) === '' || !is_numeric($valor) || (float) $valor == 0) {
            return '';
        }
        return number_format((float) $valor, 2, ',', '.');
    }

    protected static function de200(stdClass $guia): stdClass
    {
        $dados = self::vazio();
        $dados->identificadorGuia = self::texto($guia->identificadorGuia ?? '');
        $dados->ufFavorecida = self::texto($guia->ufFavorecida ?? '');
        $emitente = $guia->contribuinteEmitente ?? new stdClass();
        $dados->emitenteDocumento = self::documentoXml($emitente->identificacao ?? null);
        $dados->emitenteIE = self::texto($emitente->identificacao->IE ?? '');
        $dados->emitenteRazaoSocial = self::texto($emitente->razaoSocial ?? '');
        $dados->emitenteEndereco = self::texto($emitente->endereco ?? '');
        $dados->emitenteMunicipio = self::texto($emitente->municipio ?? '');
        $dados->emitenteUF = self::texto($emitente->uf ?? '');
        $dados->emitenteCEP = self::texto($emitente->cep ?? '');
        $dados->emitenteTelefone = self::texto($emitente->telefone ?? '');

        $itens = self::lista($guia->itensGNRE->item ?? null);
        $item = $itens[0] ?? new stdClass();
        $dados->receita = self::texto($item->receita ?? '');
        $dados->detalhamentoReceita = self::texto($item->detalhamentoReceita ?? '');
        $dados->produto = self::texto($item->produto ?? '');
        $dados->documentoOrigem = self::texto($item->documentoOrigem ?? '');
        $dados->convenio = self::texto($item->convenio ?? '');
        $dados->dataVencimento = self::data(self::texto($item->dataVencimento ?? ''));
        $referencia = $item->referencia ?? new stdClass();
        $dados->periodoReferencia = self::periodo($referencia);
        $dados->parcela = self::texto($referencia->parcela ?? '');
        $destinatario = $item->contribuinteDestinatario ?? new stdClass();
        $dados->destinatarioDocumento = self::documentoXml($destinatario->identificacao ?? null);
        $dados->destinatarioIE = self::texto($destinatario->identificacao->IE ?? '');
        $dados->destinatarioRazaoSocial = self::texto($destinatario->razaoSocial ?? '');
        $dados->destinatarioMunicipio = self::texto($destinatario->municipio ?? '');

        $somas = array_fill_keys(['11', '12', '31', '32', '41', '42', '51', '52'], 0.0);
        foreach ($itens as $cada) {
            foreach (self::lista($cada->valor ?? null) as $valor) {
                $tipo = (string) ($valor->attributes->tipo ?? '');
                if (isset($somas[$tipo])) {
                    $somas[$tipo] += (float) self::texto($valor);
                }
            }
        }
        $dados->valorPrincipal = self::formatarValor((string) ($somas['11'] + $somas['12']));
        $dados->multa = self::formatarValor((string) ($somas['31'] + $somas['32']));
        $dados->juros = self::formatarValor((string) ($somas['41'] + $somas['42']));
        $dados->atualizacaoMonetaria = self::formatarValor((string) ($somas['51'] + $somas['52']));
        $dados->valorTotal = self::formatarValor(self::texto($guia->valorGNRE ?? ''));
        return $dados;
    }

    protected static function de100(stdClass $guia): stdClass
    {
        $dados = self::vazio();
        $dados->identificadorGuia = self::texto($guia->c42_identificadorGuia ?? '');
        $dados->ufFavorecida = self::texto($guia->c01_UfFavorecida ?? '');
        $dados->receita = self::texto($guia->c02_receita ?? '');
        $dados->detalhamentoReceita = self::texto($guia->c25_detalhamentoReceita ?? '');
        $dados->produto = self::texto($guia->c26_produto ?? '');
        $dados->documentoOrigem = self::texto($guia->c04_docOrigem ?? '');
        $dados->convenio = self::texto($guia->c15_convenio ?? '');
        $dados->emitenteDocumento = self::documentoXml($guia->c03_idContribuinteEmitente ?? null);
        $dados->emitenteIE = self::texto($guia->c17_inscricaoEstadualEmitente ?? '');
        $dados->emitenteRazaoSocial = self::texto($guia->c16_razaoSocialEmitente ?? '');
        $dados->emitenteEndereco = self::texto($guia->c18_enderecoEmitente ?? '');
        $dados->emitenteMunicipio = self::texto($guia->c19_municipioEmitente ?? '');
        $dados->emitenteUF = self::texto($guia->c20_ufEnderecoEmitente ?? '');
        $dados->emitenteCEP = self::texto($guia->c21_cepEmitente ?? '');
        $dados->emitenteTelefone = self::texto($guia->c22_telefoneEmitente ?? '');
        $dados->destinatarioDocumento = self::documentoXml($guia->c35_idContribuinteDestinatario ?? null);
        $dados->destinatarioIE = self::texto($guia->c36_inscricaoEstadualDestinatario ?? '');
        $dados->destinatarioRazaoSocial = self::texto($guia->c37_razaoSocialDestinatario ?? '');
        $dados->destinatarioMunicipio = self::texto($guia->c38_municipioDestinatario ?? '');
        $referencia = $guia->c05_referencia ?? new stdClass();
        $dados->periodoReferencia = self::periodo($referencia);
        $dados->parcela = self::texto($referencia->parcela ?? '');
        $dados->dataVencimento = self::data(self::texto($guia->c14_dataVencimento ?? ''));
        $dados->dataLimitePagamento = self::data(self::texto($guia->c29_dataLimitePagamento ?? ''));
        $dados->valorPrincipal = self::formatarValor(self::texto($guia->c06_valorPrincipal ?? ''));
        $dados->atualizacaoMonetaria = self::formatarValor(self::texto($guia->c07_atualizacaoMonetaria ?? ''));
        $dados->juros = self::formatarValor(self::texto($guia->c08_juros ?? ''));
        $dados->multa = self::formatarValor(self::texto($guia->c09_multa ?? ''));
        $dados->valorTotal = self::formatarValor(self::texto($guia->c10_valorTotal ?? ''));
        $dados->informacoesComplementares = self::informacoes($guia->c23_informacoes ?? null);
        $dados->numeroControle = self::texto($guia->c30_nossoNumero ?? '');
        return $dados;
    }

    protected static function vazio(): stdClass
    {
        $dados = new stdClass();
        foreach (self::CAMPOS as $campo) {
            $dados->$campo = '';
        }
        return $dados;
    }

    /**
     * Texto de um nó convertido (string ou {attributes, value})
     *
     * @param mixed $no
     */
    protected static function texto($no): string
    {
        if (is_string($no)) {
            return trim($no);
        }
        if ($no instanceof stdClass && isset($no->value)) {
            return trim((string) $no->value);
        }
        return '';
    }

    /**
     * Um único elemento vem como objeto/string; vários, como array
     *
     * @param mixed $no
     * @return array<int, mixed>
     */
    protected static function lista($no): array
    {
        if ($no === null || $no === '') {
            return [];
        }
        return is_array($no) ? $no : [$no];
    }

    /**
     * @param mixed $identificacao {CNPJ|CPF[, IE]}
     */
    protected static function documentoXml($identificacao): string
    {
        if (!$identificacao instanceof stdClass) {
            return '';
        }
        $documento = self::texto($identificacao->CNPJ ?? '') ?: self::texto($identificacao->CPF ?? '');
        return $documento !== '' ? self::formatarDocumento($documento) : self::texto($identificacao->IE ?? '');
    }

    /**
     * Documento do TXT (16 posições, alinhado à direita com zeros): 1 CNPJ, 2 CPF, outro: sem zeros à esquerda
     */
    protected static function documentoTxt(string $documento, string $tipo): string
    {
        if ($tipo === '1') {
            return self::formatarDocumento(substr(str_pad($documento, 14, '0', STR_PAD_LEFT), -14));
        }
        if ($tipo === '2') {
            return self::formatarDocumento(substr(str_pad($documento, 11, '0', STR_PAD_LEFT), -11));
        }
        return ltrim($documento, '0');
    }

    /**
     * @param mixed $informacoes string ou {informacao: string|string[]}
     */
    protected static function informacoes($informacoes): string
    {
        if (is_string($informacoes)) {
            return trim($informacoes);
        }
        if (!$informacoes instanceof stdClass) {
            return '';
        }
        $linhas = array_map(function ($linha) {
            return self::texto($linha);
        }, self::lista($informacoes->informacao ?? null));
        return implode("\n", array_filter($linhas, function (string $linha): bool {
            return $linha !== '';
        }));
    }

    protected static function periodo(stdClass $referencia): string
    {
        $mes = self::texto($referencia->mes ?? '');
        $ano = self::texto($referencia->ano ?? '');
        return $mes !== '' && $ano !== '' ? str_pad($mes, 2, '0', STR_PAD_LEFT) . '/' . $ano : '';
    }

    /**
     * AAAA-MM-DD ou DDMMAAAA -> DD/MM/AAAA
     */
    protected static function data(string $data): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $data, $m)) {
            return "{$m[3]}/{$m[2]}/{$m[1]}";
        }
        if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $data, $m) && $data !== '00000000') {
            return "{$m[1]}/{$m[2]}/{$m[3]}";
        }
        return '';
    }

    protected static function digitos(string $valor): string
    {
        return (string) preg_replace('/\D/', '', $valor);
    }

    protected static function semZeros(string $valor): string
    {
        return trim($valor, '0') === '' ? '' : $valor;
    }
}
