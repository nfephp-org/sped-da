<?php

namespace NFePHP\DA\GNRE;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use stdClass;

/**
 * Leitura do resultado do processamento do lote GNRE (TResultLote_GNRE) para impressão.
 *
 * Aceita a resposta SOAP completa ou só o TResultLote_GNRE, com ou sem prefixo de namespace
 * (os retornos reais do Portal GNRE usam "ns1:").
 *
 * Conversão sem perda de atributos: elemento com atributos ganha "attributes" e, se tiver texto,
 * "value" (ex.: <valor tipo="12">1.82</valor> vira {attributes: {tipo: "12"}, value: "1.82"});
 * irmãos com o mesmo nome viram array.
 *
 * O resultado do lote 1.00 puro vem em TXT posicional dentro de <resultado> e é lido com as
 * posições do ACBr (PCNGNRE/pgnreRetConsResLoteGNRE.pas).
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 */
class ResultadoLote
{
    /**
     * campo => [início (base 1), tamanho]
     */
    private const TXT_CABECALHO = [
        'tipoIdentificadorSolicitante' => [2, 1],
        'identificadorSolicitante' => [3, 14],
        'numeroProtocoloLote' => [17, 10],
        'ambiente' => [27, 1],
    ];

    private const TXT_GUIA = [
        'sequencialGuia' => [2, 4],
        'situacaoGuia' => [6, 1],
        'ufFavorecida' => [7, 2],
        'receita' => [9, 6],
        'tipoDocEmitente' => [15, 1],
        'docEmitente' => [16, 16],
        'razaoSocialEmitente' => [32, 60],
        'enderecoEmitente' => [92, 60],
        'municipioEmitente' => [152, 50],
        'ufEmitente' => [202, 2],
        'cepEmitente' => [204, 8],
        'telefoneEmitente' => [212, 11],
        'tipoDocDestinatario' => [223, 1],
        'docDestinatario' => [224, 16],
        'municipioDestinatario' => [240, 50],
        'produto' => [290, 255],
        'docOrigem' => [545, 18],
        'convenio' => [563, 30],
        'informacoesComplementares' => [593, 300],
        'dataVencimento' => [893, 8],
        'dataLimitePagamento' => [901, 8],
        'periodoReferencia' => [909, 1],
        'mesAnoReferencia' => [910, 6],
        'parcela' => [916, 3],
        'valorPrincipal' => [919, 15],
        'atualizacaoMonetaria' => [934, 15],
        'juros' => [949, 15],
        'multa' => [964, 15],
        'representacaoNumerica' => [979, 48],
        'codigoBarras' => [1027, 44],
        'quantidadeVias' => [1071, 1],
        'numeroControle' => [1072, 16],
        'identificadorGuia' => [1088, 10],
        'guiaGeradaContingencia' => [1098, 1],
    ];

    private const TXT_VALORES = ['valorPrincipal', 'atualizacaoMonetaria', 'juros', 'multa'];

    private const TXT_REJEICAO = [
        'sequencialGuia' => [2, 4],
        'nomeCampo' => [6, 30],
        'codigoMotivo' => [36, 3],
        'descricaoMotivo' => [39, 355],
    ];

    /**
     * Resultado com as guias já normalizadas para impressão (DadosGuia)
     *
     * @return stdClass {situacaoProcess: {codigo, descricao}, guias: stdClass[]}
     */
    public static function ler(string $xml): stdClass
    {
        $std = self::paraStd($xml);
        $retorno = new stdClass();
        $retorno->situacaoProcess = (object) [
            'codigo' => (string) ($std->situacaoProcess->codigo ?? ''),
            'descricao' => (string) ($std->situacaoProcess->descricao ?? ''),
        ];
        $retorno->guias = [];
        $resultado = $std->resultado ?? null;
        if (is_string($resultado)) {
            foreach (self::lerTxtV1($resultado)->guias as $guia) {
                $retorno->guias[] = DadosGuia::deTxtV1($guia);
            }
            return $retorno;
        }
        $guias = $resultado->guia ?? [];
        foreach (is_array($guias) ? $guias : [$guias] as $guia) {
            $retorno->guias[] = DadosGuia::deResultado($guia);
        }
        return $retorno;
    }

    /**
     * TResultLote_GNRE convertido em stdClass (sem prefixos, atributos preservados)
     *
     * @throws InvalidArgumentException se não for XML ou não contiver TResultLote_GNRE
     */
    public static function paraStd(string $xml): stdClass
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        if (trim($xml) === '' || !@$dom->loadXML($xml)) {
            throw new InvalidArgumentException('O argumento passado não é um XML válido.');
        }
        $no = $dom->getElementsByTagNameNS('*', 'TResultLote_GNRE')->item(0);
        if (!$no instanceof DOMElement) {
            $no = $dom->getElementsByTagName('TResultLote_GNRE')->item(0);
        }
        if (!$no instanceof DOMElement) {
            throw new InvalidArgumentException(
                'O arquivo indicado não é um documento GNRE válido: esperado o resultado do lote (TResultLote_GNRE).'
            );
        }
        $std = self::converter($no);
        return $std instanceof stdClass ? $std : (object) ['value' => $std];
    }

    /**
     * Resultado TXT do lote 1.00 puro
     *
     * @return stdClass {cabecalho: ?stdClass, guias: stdClass[], rejeicoes: stdClass[], rodape: ?stdClass}
     */
    public static function lerTxtV1(string $txt): stdClass
    {
        $retorno = new stdClass();
        $retorno->cabecalho = null;
        $retorno->guias = [];
        $retorno->rejeicoes = [];
        $retorno->rodape = null;
        $porSequencial = [];
        foreach (preg_split('/\r\n|\n|\r/', $txt) ?: [] as $linha) {
            $linha = ltrim($linha);
            if (trim($linha) === '') {
                continue;
            }
            switch ($linha[0]) {
                case '0':
                    $retorno->cabecalho = self::campos($linha, self::TXT_CABECALHO);
                    break;
                case '1':
                    $guia = self::campos($linha, self::TXT_GUIA);
                    foreach (self::TXT_VALORES as $campo) {
                        $guia->$campo = self::centavos($guia->$campo);
                    }
                    $guia->rejeicoes = [];
                    $retorno->guias[] = $guia;
                    $porSequencial[$guia->sequencialGuia] = $guia;
                    break;
                case '2':
                    $rejeicao = self::campos($linha, self::TXT_REJEICAO);
                    $retorno->rejeicoes[] = $rejeicao;
                    if (isset($porSequencial[$rejeicao->sequencialGuia])) {
                        $porSequencial[$rejeicao->sequencialGuia]->rejeicoes[] = $rejeicao;
                    }
                    break;
                case '9':
                    $retorno->rodape = (object) ['conteudo' => rtrim($linha)];
                    break;
            }
        }
        return $retorno;
    }

    /**
     * @return stdClass|string
     */
    private static function converter(DOMElement $no)
    {
        $filhos = [];
        $texto = '';
        foreach ($no->childNodes as $filho) {
            if ($filho instanceof DOMElement) {
                $filhos[$filho->localName][] = self::converter($filho);
            } elseif ($filho->nodeType === XML_TEXT_NODE || $filho->nodeType === XML_CDATA_SECTION_NODE) {
                $texto .= $filho->nodeValue;
            }
        }
        $atributos = new stdClass();
        $temAtributos = false;
        if ($no->attributes !== null) {
            foreach ($no->attributes as $atributo) {
                $atributos->{$atributo->localName} = $atributo->nodeValue;
                $temAtributos = true;
            }
        }
        if (empty($filhos) && !$temAtributos) {
            return trim($texto);
        }
        $obj = new stdClass();
        if ($temAtributos) {
            $obj->attributes = $atributos;
        }
        foreach ($filhos as $nome => $valores) {
            $obj->$nome = count($valores) === 1 ? $valores[0] : $valores;
        }
        if (empty($filhos) && trim($texto) !== '') {
            $obj->value = trim($texto);
        }
        return $obj;
    }

    /**
     * @param array<string, array{0: int, 1: int}> $layout
     */
    private static function campos(string $linha, array $layout): stdClass
    {
        $campos = new stdClass();
        foreach ($layout as $campo => [$inicio, $tamanho]) {
            $campos->$campo = trim((string) substr($linha, $inicio - 1, $tamanho));
        }
        return $campos;
    }

    private static function centavos(string $valor): string
    {
        $numero = preg_replace('/\D/', '', $valor);
        return number_format(((int) ($numero === '' ? '0' : $numero)) / 100, 2, '.', '');
    }
}
