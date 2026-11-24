# DaMDFe

No modal rodoviário, o quadro de vale-pedágio exibe o responsável pelo pagamento
(CNPJ ou CPF), o CNPJ da fornecedora, o número do comprovante quando informado e
o valor de `vValePed` em reais, com duas casas decimais.

Os seguros são agrupados pelo responsável (`infResp/respSeg`): emitente do MDF-e
ou contratante. Cada grupo apresenta dois pares de colunas de seguradora e número
da apólice. A ausência dos campos opcionais `infSeg`, `nApol` e `nCompra` não
interrompe a geração do PDF; os respectivos campos permanecem em branco.
