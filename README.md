# sped-da
Classes para geração dos documentos auxiliares usados pelos padrões Sped

## Versão : Alpha-dev, ainda sem classes funcionais, apenas estruturação do pacote

# Orientação
Abaixo seguem as orientações gerais para desenvolvedores que desejarem contribuir para a construção e melhoria dos códigos.

## Estrutura das classes

Para a geração dos documentos auxiliares devem feitas as seguintes considerações:

* Esta biblioteca deverá ser escrita para PHP7, não será testado ou aceito o seu uso em ambientes com php menor que o 5.6 (por ora, pelo menos durante o seu desenvolvimento) e apartir da sua primeira versão estável somente PHP >= 7.0 será aceitável.
* Este pacote *"sped-da"* se tornará uma dependência (sugerida) dos demais, e será de competência do desenvolvedor coloca-la como dependência de sua aplicação ou não, usando o composer.
* A rederização das classes principais (Danfe, Dacte, Damdfe, Dacce e o NFCe, este com ressalvas) devem ser feita em PDF ou em HTML.
* Essas classes principais devem extender a classe Da.php que é a construtora básica.
* Os documentos auxiliares podem ser renderizados a partir dos XML ou das classes construtoras, estabelecidas em cada pacote. Ou seja, tanto pode ser passada uma classe com os dados do documento como o próprio documento em xml.
* Deve ser permitida e facilitada a criação de um PDF com multiplos documentos.
* Todas as classes devem observar os principios S.O.L.I.D. e atender aos PSR-2 e PSR-4.
* Todas os métodos devem possuir teste unitários utilizando o phpunit, de forma a evitar a quebra do funcionamento das classes.
* Será montado um esquema que permitirá o "pull request" apenas se os testes unitários não falharem.
* No caso especifico do NFCe (Nota fiscal do consumidor) existe um outro pacote que poderá vir a ser usado, trata-se do [*"posprint"*](https://github.com/nfephp-org/posprint) devido ao fato desse documento auxiliar normalmente ser impresso em impressoras térmicas POS, que não trabalham adequadamente com PDF e devem receber os dados em sua pópria linguagem (RAW data). Dessa forma o pacote *"posprint"* poderá tornar-se ser uma das dependências desse pacote.
* Para a conversão dos dados em PDF é necessário o uso de uma biblioteca que seja ativamente mantida, usada por um grande contingênte de programadores e que atenda minimamente os PSR. Uma grande atenção deve ser dedicada a esse ponto pois com a inclusão do PHP7 podem surgir problemas de incompatibilidade com bibliotecas que não seja mantidas atualizadas.

## DANFE
O Danfe, como outros documentos auxiliares, tem diversas peculiaridades que devem ser atendidas.

- Formato: A4 ou Oficio (somente)
- Font: Times (somente)
- Orientação: Retrato ou Paisagem (definido por tag do xml)
- Como o formato pode ser mudado, bem como a orientação, isso implica em áreas disponíveis diferentes para cada uma dessas combinações, tanto no comprimento como na largura.
- Existem blocos de impressão em que seu tamanho pode ser fixado e outros blocos cuja área ocupada dependem dos dados a serem impressos.

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
   * Aqui é onde está o maior problema, pois temos que pre-renderizar o DANFE e verificar quantos itens cabem na primeira página, em função da área que sobrou e distribuir os itens restantes pelas paginas seguintes.
   * _Área ocupada varia com os dados no xml_

9. **Informações Adicionais**

   * Presente apenas na prímeira página
   * _Área ocupada varia com os dados no xml_

10. **Rodapé**

   * Pode ser desativada ou alterada pelo desenvolvedor na sua aplicação
   * Este rodapé deve ocupar uma única linha no final da área implimivel e com fonte tamanho 8dpi ou menor.  
   * Presente em todas as páginas
   * Indica a versão, a origem do processo de impressão e outros dados relevantes como data e hora
   * É **recomendado** que seja seja mantida mensagem *"Powered by NFePHP (GNU/GPLv3 GNU/LGPLv3) © www.nfephp.org"*  como forma de divulgação e contribuição pelo o uso da biblioteca.
