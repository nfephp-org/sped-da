# DaNFe
O **D**ocumento **A**uxiliar da **NF** **E**letrôncica é a impressão de dados para auxiliar o transporte e a fiscalização. 
Constitui-se basicamente uma impressão ou documento visual eletrônico como PDF, que possui dados do emitente, do destinatário um código de barras para leitura da [Chave da NFe]  dados dos produtos da nota, transportadora, e resumo de totais e impostos.
Nele também consta o número de protocolo no registro na integração junto ao sped.

## Class Danfe

# Métodos

### function __construct()
Método construtor. Instancia a classe

    ```php
    $danfe = new Danfe([String xml]);
    ``` 
### function render()
Método de rederização do PDF

```php
$pdf = $danfe->render();
```
retorna um PDF codificado.

### Personalizacao dos tamanhos de fonte
Os tamanhos de fonte podem ser ajustados por bloco/elemento da DANFE.

```php
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\NFe\DanfeFontSizes;

$danfe = new Danfe($xml);

$danfe->setFontSizes(DanfeFontSizes::fromArray([
    'cabecalho' => [
        'emitente_identificacao' => 5,
        'emitente_razao_social' => 10,
        'titulo_danfe' => 12,
        'descricao_danfe' => 7,
        'tipo_operacao_texto' => 7,
        'tipo_operacao_numero' => 10,
        'numero' => 9,
        'serie_folha' => 7,
        'chave_acesso' => 7,
        'consulta' => 7,
        'rotulo' => 5,
        'valor' => 9,
    ],
    'destinatario' => [
        'titulo' => 6,
        'rotulo' => 5,
        'valor' => 8,
    ],
    'itens' => [
        'titulo' => 6,
        'cabecalho' => 5,
        'dados' => 6,
        'unidade_tributavel' => 4,
    ],
    'dados_adicionais' => [
        'titulo' => 6,
        'subtitulo' => 5,
        'texto' => 5,
    ],
]));

$pdf = $danfe->render();
```

As chaves não informadas mantêm os tamanhos originais da biblioteca.
