# sped-da

Classes para geração dos documentos auxiliares usados pelos padrões Sped

> NOTA: Este repositório é para desenvolvimento usando as novas bibliotecas do TCPDF.

# UNDER DEVELOPMENT 

[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![License][ico-license]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

[![Issues][ico-issues]][link-issues]
[![Forks][ico-forks]][link-forks]
[![Stars][ico-stars]][link-stars]
[![Chat][ico-gitter]][link-gitter]


# Orientação
Abaixo seguem as orientações gerais para desenvolvedores que desejarem contribuir para a construção e melhoria dos códigos.


## Estrutura das classes

Para a geração dos documentos auxiliares devem feitas as seguintes considerações:

* Esta biblioteca deverá ser escrita para PHP7, não será testado ou aceito o seu uso em ambientes com php menor que o 5.6 (por ora, pelo menos durante o seu desenvolvimento) e apartir da sua primeira versão estável somente PHP >= 7.0 será aceitável.
* Este pacote *"sped-da"* se tornará uma dependência (sugerida) dos demais, e será de competência do desenvolvedor coloca-la como dependência de sua aplicação ou não, usando o composer.
* A renderização das classes principais (Danfe, Dacte, Damdfe, Dacce e o NFCe, este com ressalvas) devem ser feita em PDF ou em HTML.
* Essas classes principais devem extender a classe Da.php que é a construtora básica.
* Os documentos auxiliares podem ser renderizados a partir dos XMLs ou das classes construtoras, estabelecidas em cada pacote. Ou seja, tanto pode ser passada uma classe com os dados do documento como o próprio documento em XML.
* Deve ser permitida e facilitada a criação de um PDF com múltiplos documentos.
* Todas as classes devem observar os principios S.O.L.I.D. e atender aos PSR-2 e PSR-4.
* Todos os métodos devem possuir testes unitários utilizando o phpunit, de forma a evitar a quebra do funcionamento das classes.
* Será montado um esquema que permitirá o "pull request" apenas se os testes unitários não falharem.
* No caso especifico da NFC-e (Nota fiscal do consumidor) existe um outro pacote que poderá vir a ser usado, trata-se do [*"posprint"*](https://github.com/nfephp-org/posprint) devido ao fato desse documento auxiliar normalmente ser impresso em impressoras térmicas POS, que não trabalham adequadamente com PDF e devem receber os dados em sua pópria linguagem (RAW data). Dessa forma o pacote *"posprint"* poderá tornar-se ser uma das dependências desse pacote.
* Para a conversão dos dados em PDF é necessário o uso de uma biblioteca que seja ativamente mantida, usada por um grande contingênte de programadores e que atenda minimamente os PSR. Uma grande atenção deve ser dedicada a esse ponto pois com a inclusão do PHP7 podem surgir problemas de incompatibilidade com bibliotecas que não sejam mantidas atualizadas.


[ico-stars]: https://img.shields.io/github/stars/nfephp-org/sped-nfe.svg?style=flat-square
[ico-forks]: https://img.shields.io/github/forks/nfephp-org/sped-da.svg?style=flat-square
[ico-issues]: https://img.shields.io/github/issues/nfephp-org/sped-da.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/nfephp-org/sped-da/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/nfephp-org/sped-da.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/nfephp-org/sped-da.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/nfephp-org/sped-da.svg?style=flat-square
[ico-version]: https://img.shields.io/packagist/v/nfephp-org/sped-da.svg?style=flat-square
[ico-license]: https://poser.pugx.org/nfephp-org/nfephp/license.svg?style=flat-square
[ico-gitter]: https://img.shields.io/badge/GITTER-4%20users%20online-green.svg?style=flat-square


[link-packagist]: https://packagist.org/packages/nfephp-org/sped-da
[link-travis]: https://travis-ci.org/nfephp-org/sped-da
[link-scrutinizer]: https://scrutinizer-ci.com/g/nfephp-org/sped-da/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/nfephp-org/sped-da
[link-downloads]: https://packagist.org/packages/nfephp-org/sped-da
[link-author]: https://github.com/nfephp-org
[link-issues]: https://github.com/nfephp-org/sped-da/issues
[link-forks]: https://github.com/nfephp-org/sped-da/network
[link-stars]: https://github.com/nfephp-org/sped-da/stargazers
[link-gitter]: https://gitter.im/nfephp-org/sped-da?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge

