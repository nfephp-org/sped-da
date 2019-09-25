# DANFE
O Danfe, como outros documentos auxiliares, tem diversas peculiaridades que devem ser atendidas.

- Visualização: PDF (talvez em HTML também)
- Saída: Tela, ou direta para "file" ou para impressora e deve ser considerado também o possivel uso com o [Qz Tray](https://qz.io/download/)
- Formato da página: A4 ou Oficio (somente)
- Font: Times (somente)
- Orientação: Retrato ou Paisagem (definido por tag do xml)

> Como o formato da página, pode ser mudado, bem como a orientação, isso implica em áreas disponíveis diferentes para cada uma dessas combinações, tanto no comprimento como na largura.

> Existem blocos de impressão em que seu tamanho pode ser fixado e outros blocos cuja área ocupada dependem dos dados a serem impressos.

Por exemplo:

1. **Canhoto** *(área fixa, se existir)*

   * Pode não ser desejado ou necessário (esse bloco pode ser suprimido)
   * Sua presença ocorre apenas na primeira página do documento
   * Área ocupada pode ser fixada, ou seja, não varia com os dados no xml

2. **Dados da Nota** *(área fixa)*

   * Presente em todas as páginas, da primeira até a última
   * Área ocupada pode ser fixada, ou seja, não varia com os dados no xml

3. **Dados do destinatário/remetente** *(área fixa)*

   * Presente apenas na prímeira página
   * Área ocupada pode ser fixada, ou seja, não varia com os dados no xml
 
4. **Dados de fatura/duplicatas** *(se existir)*

   * Esse bloco pode não existir, ser desejado ou necessário (esse bloco pode ser suprimido)
   * Presente apenas na primeira página (se existir)
   * _A área total ocupada varia com os dados no xml_

5. **Dados de impostos** *(área fixa)*

   * Presente apenas na prímeira página
   * Área ocupada pode ser fixada, ou seja, não varia com os dados no xml

6. **Dados do ISSQN** *(área fixa, se existir)*

   * Presente apenas se a nota for de serviços, e somente na primeira página
   * Área ocupada pode ser fixada, ou seja, não varia com os dados no xml

7. **Dados de transporte** *(área fixa)*

   * Presente apenas na prímeira página
   * Área ocupada pode ser fixada, não varia com os dados no xml

8. **Dados dos itens**

   * Presente em todas as páginas, da primeira até a última
   * _Área ocupada varia com os dados no xml_

9. **Informações Adicionais**

   * Presente apenas na prímeira página
   * _Área ocupada varia com os dados no xml_

10. **Rodapé**

   * Pode ser desativado ou alterado pelo desenvolvedor na sua aplicação;
   * Este rodapé deve ocupar uma única linha no final da área imprimível e com fonte tamanho 8dpi ou menor.
   * Deve estar presente em todas as páginas
   * Deve indica a versão, a origem do processo de impressão e outros dados relevantes como data e hora
   * É **recomendado** que seja seja mantida mensagem *"Powered by NFePHP (GNU/GPLv3 GNU/LGPLv3) © www.nfephp.org"*  como forma de divulgação e contribuição pelo o uso da biblioteca.
