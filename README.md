# sped-da

Classes para geração dos documentos auxiliares usados pelos padrões Sped

> NOTA: Este repositório contêm as classes "LEGADAS", para criação dos PDF's do projeto original NFePHP.
> Porém essas classes foram ajustadas e alguns recursos estarão ausentes ou pelo menos diferentes das suas contrapartes originais.

> Serão retiradas das classes todas os recursos considerados como não "pertencentes" ao escopo das mesmas e não serão mais aceitas inclusões de métodos referentes a particuliaridades de qualquer sistema.
> Deve se ter mente que esses documentos auxiliares (Danfe, Dacte e Damdfe) tem como ÚNICO proposito acompanhar a marcadoria durante o seu transporte. E não tem a intenção de serem usadas como FONTE de informações administrativas ou operacionais. Para essas funções o XML é mais apropriado.
> Estas classes deverão observar o quanto possivel as orientações da SEFAZ.
> Estas classes devem ser e permanecer tão genéricas e simples quanto possivel.
> Se você necessita que o DANFE ou qualquer outro documento seja diferente em termos de recursos que esses disponíveis, CRIE o seu próprio gerador de PDF.
 

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

**As classes deste repositório serão refatoradas e divididas em 4 outros repositórios:**

- sped-da-common (com as classes de uso comum para a criação dos pdfs)
- sped-da-nfe (com as classes para a criação de PDF dos documentos referentes a NFe)
- sped-da-cte (com as classes para a criação de PDF dos documentos referentes a CTe)
- sped-da-mdfe (com as classes para a criação de PDF dos documentos referentes a MDFe)

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
* Para a conversão dos dados em PDF é necessário o uso de uma biblioteca que seja ativamente mantida, usada por um grande contingente de programadores e que atenda minimamente os PSR. Uma grande atenção deve ser dedicada a esse ponto pois com a inclusão do PHP7 podem surgir problemas de incompatibilidade com bibliotecas que não sejam mantidas atualizadas.

## Contribuindo
Este é um projeto totalmente *OpenSource*, para usa-lo e modifica-lo você não paga absolutamente nada. Porém para continuarmos a mante-lo é necessário qua alguma contribuição seja feita, seja auxiliando na codificação, na documentação ou na realização de testes e identificação de falhas e BUGs.

**Este pacote esta listado no [Packgist](https://packagist.org/) foi desenvolvido para uso do [Composer](https://getcomposer.org/), portanto não será explicitada nenhuma alternativa de instalação.**

*Durante a fase de desenvolvimento e testes este pacote deve ser instalado com:*
```bash
composer require nfephp-org/sped-da:dev-master
```

*Ou ainda alterando o composer.json do seu aplicativo inserindo:*
```json
"require": {
    "nfephp-org/sped-da" : "dev-master"
}
```

> NOTA: Ao utilizar este pacote ainda na fase de desenvolvimento não se esqueça de alterar o composer.json da sua aplicação para aceitar pacotes em desenvolvimento, alterando a propriedade "minimum-stability" de "stable" para "dev".
> ```json
> "minimum-stability": "dev"
> ```

*Os stable realeases estão disponíveis (mas com algumas classes ainda em desenvolvimento), pode ser instalado com:*
```bash
composer require nfephp-org/sped-da
```
Ou ainda alterando o composer.json do seu aplicativo inserindo:
```json
"require": {
    "nfephp-org/sped-da" : "^0.1"
}
```

## Forma de uso
[DANFE](docs/DANFE.md) 

## Log de mudanças e versões
Acompanhe o [CHANGELOG](CHANGELOG.md) para maiores informações sobre as alterações recentes.

## Testing

Todos os testes são desenvolvidos para operar com o PHPUNIT

## Security

Caso você encontre algum problema relativo a segurança, por favor envie um email diretamente aos mantenedores do pacote ao invés de abrir um ISSUE.

## Credits

Roberto L. Machado (owner and developer)

## License

Este pacote está diponibilizado sob LGPLv3 ou MIT License (MIT). Leia  [Arquivo de Licença](LICENSE) para maiores informações.


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

