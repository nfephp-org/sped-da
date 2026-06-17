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
Os tamanhos de fonte podem ser ajustados antes da renderizacao do PDF.

```php
$danfe = new Danfe($xml);

// Reduz todos os tamanhos de fonte em 10%, mantendo no minimo 4 pontos.
$danfe->setFontSizeScale(0.9, 4);

// Opcionalmente substitui tamanhos especificos.
$danfe->setFontSizeMap([
    10 => 9,
    8 => 7,
    '5.7' => 5,
]);

$pdf = $danfe->render();
```
