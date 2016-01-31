# sped-da
Classes para geração dos documentos auxiliares usados pelos padrões Sped

# DANFE
O Danfe como outros documentos auxiliares tem diversas peculiaridades que devem ser atendidas.

Formato: A4 ou Oficio (somente)
Font: Times (somente)
Orientação: Retrato ou Paisagem (definido por tag do xml)

Existem áreas de tamanho que podem ser fixados e outras áreas que dependem dos dados a serem impressos.
Por exemplo:

1 Canhoto
   Pode não ser desejado ou necessário (pode ser suprimido)
   Presente apenas na primeira página
   Área ocupada pode ser fixada, não varia com os dados no xml

2 Cabeçalho da Nota
   Presente em todas as páginas, da primeira até a última
   Área ocupada pode ser fixada, não varia com os dados no xml

3 Dados do destinatário/remetente
   Presente apenas na prímeira página
   Área ocupada pode ser fixada, não varia com os dados no xml
 
4 Dados de fatura/duplicatas
   Pode não ser desejado ou necessário (pode ser suprimido)
   Área ocupada varia com os dados no xml

5 Dados de impostos
   Presente apenas na prímeira página
   Área ocupada pode ser fixada, não varia com os dados no xml

6 Dados de transporte
   Presente apenas na prímeira página
   Área ocupada pode ser fixada, não varia com os dados no xml

7 Dados dos itens
   Presente em todas as páginas, da primeira até a última
   Área ocupada varia com os dados no xml

8 Informações Adicionais
   Presente apenas na prímeira página
   Área ocupada varia com os dados no xml


