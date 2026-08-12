<?php

namespace NFePHP\DA\NFe;

class DanfeFontSizes
{
    public const DEFAULTS = [
        'cabecalho' => [
            'emitente_identificacao' => 6,
            'emitente_identificacao_paisagem' => 8,
            'emitente_razao_social' => 12,
            'emitente_endereco' => 8,
            'titulo_danfe' => 14,
            'descricao_danfe' => 8,
            'tipo_operacao_texto' => 8,
            'tipo_operacao_numero' => 12,
            'numero' => 10,
            'serie_folha' => 8,
            'chave_acesso' => 8,
            'consulta' => 8,
            'rotulo' => 6,
            'valor' => 10,
            'marca_dagua' => 48,
            'marca_dagua_texto' => 20,
        ],
        'destinatario' => [
            'titulo' => 7,
            'rotulo' => 6,
            'valor' => 10,
        ],
        'local_entrega' => [
            'titulo' => 7,
            'rotulo' => 6,
            'valor' => 10,
        ],
        'local_retirada' => [
            'titulo' => 7,
            'rotulo' => 6,
            'valor' => 10,
        ],
        'fatura' => [
            'titulo' => 7,
            'texto' => 9,
            'numero' => 8,
            'rotulo' => 6,
            'valor' => 7,
        ],
        'pagamento' => [
            'titulo' => 7,
            'rotulo' => 6,
            'valor' => 7,
            'texto' => 7,
        ],
        'impostos' => [
            'titulo' => 7,
            'rotulo' => 6,
            'valor' => 10,
        ],
        'transporte' => [
            'titulo' => 7,
            'rotulo' => 6,
            'valor' => 10,
        ],
        'itens' => [
            'titulo' => 7,
            'cabecalho' => 6,
            'dados' => 7,
            'unidade_tributavel' => 5,
            'veiculo' => 7,
        ],
        'issqn' => [
            'titulo' => 7,
            'rotulo' => 6,
            'valor' => 10,
        ],
        'dados_adicionais' => [
            'texto_calculo' => 8,
            'titulo' => 7,
            'subtitulo' => 6,
            'texto' => null,
            'reservado_titulo' => 6,
            'reservado_texto' => 7,
        ],
        'rodape' => [
            'creditos' => 6,
        ],
        'canhoto' => [
            'texto' => 7,
            'titulo' => 14,
            'numero' => 10,
            'rotulo' => 6,
            'identificacao' => 5.7,
            'serie' => 8,
        ],
    ];

    /**
     * @var array
     */
    private $sizes = [];

    public function __construct(array $sizes = [])
    {
        $this->sizes = $this->normalizeSizes($sizes);
    }

    public static function fromArray(array $sizes): self
    {
        return new self($sizes);
    }

    /**
     * @param int|float|null $default
     * @return int|float|null
     */
    public function get(string $path, $default = null)
    {
        $value = $this->valueByPath($this->sizes, $path);
        if ($value === null) {
            return $default;
        }
        return $value;
    }

    public function toArray(): array
    {
        return $this->sizes;
    }

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    private function normalizeSizes(array $sizes): array
    {
        $normalized = [];
        foreach ($sizes as $key => $value) {
            if (is_array($value)) {
                $children = $this->normalizeSizes($value);
                if (!empty($children)) {
                    $normalized[$key] = $children;
                }
                continue;
            }
            if ($value === null || !is_numeric($value) || (float) $value <= 0) {
                continue;
            }
            $normalized[$key] = $value + 0;
        }
        return $normalized;
    }

    /**
     * @return int|float|null
     */
    private function valueByPath(array $sizes, string $path)
    {
        $current = $sizes;
        foreach (explode('.', $path) as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }
        return is_numeric($current) ? $current : null;
    }
}
