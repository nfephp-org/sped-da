# sped-da
Classes para geração dos documentos auxiliares usados pelos padrões Sped

# Orientação
Abaixo seguem as orientações gerais para desenvolvedores que desejarem contribuir para a construção e melhoria dos códigos.

## Estrutura das classes

Para a geração dos documentos auxiliares devem feitas as seguintes considerações:

* Esta biblioteca deverá ser escrita para PHP7, não será testado ou aceito o seu uso em ambientes com php menor que o 5.6 (por ora, pelo menos durante o seu desenvolvimento) e apartir da sua primeira versão estável somente PHP >= 7.0 será aceitável.    
* Este pacote *"sped-da"* se tornará uma dependência (sugerida) dos demais, e será de competência do desenvolvedor coloca-la como dependência de sua aplicação ou não, usando o composer
* A rederização das classes principais (Danfe, Dacte, Damdfe, Dacce e o NFCe, este com ressalvas) devem ser feita em PDF ou em Html
* Essas classes principais devem extender a classe Da.php que é a construtora básica
* Os documentos auxiliares podem ser renderizados a partir dos XML ou das classes construtoras, estabelecidas em cada pacote. Ou seja, tanto pode ser passada uma classe com os dados dos documentos como o próprio documentos em xml. 
* Todas as classes devem observar os principios S.O.L.I.D. e atender aos PSR-2 e PSR-4
* Todas os métodos devem possuir teste unutários utilizando o phpunit, de forma a evitar a quebra do funcionamento das classes
* Será montado um esquema que permitirá o "pull request" apenas se os testes não falharem 
* No caso especifico do NFCe (Nota fiscal do consumidor) existe um outro pacote que poderá vir a ser usado trata-se do [*"posprint"*](https://github.com/nfephp-org/posprint) devido ao fato desse documento axuliar normalmente ser impresso em impressoras térmicas POS, que não trabalham adequadamente com PDF e devem receber os dados em sua pópria linguagem. Dessa forma o pacote *"posprint"* poderá tornar-se ser uma dependência.
* Para a conversão dos dados em PDF é necessário o uso de uma biblioteca que seja ativamente mantida, usada por um grande contingênte de programadores e que atenda minimamente os PSR. Uma grnade atenção deve ser dedicada a esse ponto pois com a inclusão do PHP7 podem surgir problemas de incompatibilidade.

## DANFE
O Danfe como outros documentos auxiliares tem diversas peculiaridades que devem ser atendidas.

- Formato: A4 ou Oficio (somente)
- Font: Times (somente)
- Orientação: Retrato ou Paisagem (definido por tag do xml)

Existem áreas de tamanho que podem ser fixados e outras áreas que dependem dos dados a serem impressos.
Por exemplo:

1. Canhoto
   Pode não ser desejado ou necessário (pode ser suprimido)
   Presente apenas na primeira página
   Área ocupada pode ser fixada, não varia com os dados no xml

2. Cabeçalho da Nota
   Presente em todas as páginas, da primeira até a última
   Área ocupada pode ser fixada, não varia com os dados no xml

3. Dados do destinatário/remetente
   Presente apenas na prímeira página
   Área ocupada pode ser fixada, não varia com os dados no xml
 
4. Dados de fatura/duplicatas
   Pode não ser desejado ou necessário (pode ser suprimido)
   Área ocupada varia com os dados no xml

5. Dados de impostos
   Presente apenas na prímeira página
   Área ocupada pode ser fixada, não varia com os dados no xml

6. Dados de transporte
   Presente apenas na prímeira página
   Área ocupada pode ser fixada, não varia com os dados no xml

7. Dados dos itens
   Presente em todas as páginas, da primeira até a última
   Área ocupada varia com os dados no xml

8. Informações Adicionais
   Presente apenas na prímeira página
   Área ocupada varia com os dados no xml


