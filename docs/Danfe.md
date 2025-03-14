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
