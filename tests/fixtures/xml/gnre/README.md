# Fixtures GNRE

| Arquivo | Origem | Alterações |
|---|---|---|
| `retorno-resultado-v2.xml` | ACBr `trunk2/Testes/Recursos/GNRe/GuiaGNRe_Editada_Adicionando_Valores_FCP.xml` (rev. 49272), envolvido em envelope SOAP como os retornos reais do Portal GNRE | `numeroRecibo` completado de 10 para 14 dígitos (exigido pelo XSD 2.17) |
| `retorno-resultado-v1.xml` | `exemplos/xml/resultado-consulta-gnre.xml` do sped-gnre 0.x (resultado TXT posicional do lote 1.00) | finais de linha LF |
| `retorno-recibo.xml` | montado a partir de `lote_gnre_recibo_v1.00.xsd` (sped-gnre) | — |

As mesmas fixtures são usadas no nfephp-org/sped-gnre.
