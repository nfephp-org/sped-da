# Dagnre

## Class NFePHP\DA\GNRE\Dagnre

Gera o PDF da GNRE (Guia Nacional de Recolhimento de Tributos Estaduais) com **3 vias por guia**
(1ª via - Banco, 2ª via - Contribuinte, 3ª via - Contribuinte/Fisco), uma guia por página A4.

- Código de barras de arrecadação (padrão Febraban, 44 dígitos, **2 de 5 intercalado**).
- Linha digitável formatada (4 blocos de 11 dígitos + DV).
- **QR Code PIX** quando a SEFAZ informa `qrcodePayload`.
- Só as guias processadas com sucesso (`situacaoGuia` = 0) são impressas.

O XML de entrada é obtido com o [nfephp-org/sped-gnre](https://github.com/nfephp-org/sped-gnre)
(`Tools::sefazConsultaLote()`).

### public __construct(string $xmlResultado)

Recebe o resultado do processamento do lote (`TResultLote_GNRE`), com ou sem o envelope SOAP:

- layout 2.00 (guias 2.00 ou guias 1.00 dentro do lote 2.00);
- lote 1.00 puro (resultado em TXT posicional).

Lança `\InvalidArgumentException` se o documento não for o resultado do lote ou se nenhuma guia
tiver sido processada com sucesso.

### public render($logo = ''): string

Retorna o PDF (conteúdo binário). A logo é opcional (caminho ou `data://` de um JPG/PNG).

### public getGuias(): array

Guias impressas, já normalizadas (`NFePHP\DA\GNRE\DadosGuia`).

### public getGuiasNaoImpressas(): array

Guias não impressas (rejeitadas pelo Portal ou pela UF), com `situacaoGuia` e `motivosRejeicao`.

### Métodos herdados de DaCommon

`creditsIntegratorFooter()`, `logoParameters()`, `printParameters()`, `setDefaultFont()`, `debugMode()`.

## Exemplo

```php
use NFePHP\DA\GNRE\Dagnre;

$dagnre = new Dagnre($tools->sefazConsultaLote($recibo));
$dagnre->creditsIntegratorFooter('Minha Empresa');
$pdf = $dagnre->render();

foreach ($dagnre->getGuiasNaoImpressas() as $guia) {
    foreach ($guia->motivosRejeicao as $motivo) {
        echo "{$motivo->codigo} - {$motivo->descricao}\n";
    }
}
```

## Classes auxiliares

- `NFePHP\DA\GNRE\ResultadoLote`: lê o `TResultLote_GNRE` sem perder atributos
  (`<valor tipo="12">` vira `{attributes: {tipo: "12"}, value: ...}`) e o TXT do lote 1.00.
- `NFePHP\DA\GNRE\DadosGuia`: normaliza as guias para o modelo de impressão.
