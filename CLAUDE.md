# sped-da — Constituição do Módulo

> SDK PHP para geração de Documentos Auxiliares do SPED (DANFE, DACTE, CCe). SDK externo — não modificar sem aprovação.

## Identidade
- Módulo: sped-da — Geração de documentos auxiliares fiscais (DANFE/DACTE)
- Fork: `zucchetti-pos/sped-da` (base: `nfephp-org/sped-da`)
- Parte do monorepo: zweb-projects

## Stack
- **Linguagem:** PHP ≥7.4
- **Dependências:** `nfephp-org/sped-common`, `tecnickcom/tc-lib-barcode`
- **Testes:** PHPUnit 9 + phpcs (PSR-2) + phpstan + phpcpd
- **Gerenciador:** Composer

## Estrutura de pastas
```
sped-da/
├── src/           # Código-fonte (PSR-4: NFePHP\DA)
├── tests/         # Testes PHPUnit
├── docs/          # Documentação
└── examples/      # Exemplos de uso
```

## Comandos do projeto
```bash
# Instalar dependências
composer install

# Executar testes
vendor/bin/phpunit -c phpunit.xml.dist

# Lint (PSR-2)
vendor/bin/phpcs --standard=psr2 src
vendor/bin/phpcbf --standard=psr2 src

# Análise estática
vendor/bin/phpstan analyse src/ --level 1
```

## Restrições
- SDK fiscal externo (fork do nfephp-org) — modificações exigem aprovação explícita
- PHP mínimo: 7.4
- Dados fiscais (CNPJ, CPF, chaves de acesso NF-e) nunca em logs
- Estrutura XML dos documentos auxiliares não alterar sem validação fiscal
