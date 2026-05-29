# Danfe2

Classe que constroi o DANFE observando os paramêtros estabelecidos pela SEFAZ, com algums adicionais para facilitação de obtenção de informações para quem os recebe.

> NOTA: exclusivo para XML de NFe (modelo 55)

# Funções Publicas

## Instanciação

Na instanciação da classe DANFE é obrigatório a passagem do XML da NFe modelo 55.

```php

    /**
     * Construtor
     * @param string $xml 
     * @throws \Exception
     */
    //somente são aceitos documentos XML devidamente formados e apenas NFe modelo 55 com ou sem protocolo de autorização   
    $xml = file_exists(__DIR__. '/arquivo_xml_nfe_modelo_55.xml');
    $danfe2 = new Danfe2($xml);

```

## Parametrização

```php

    /**
     * Define parametros de impressão
     * Este método não necessita ser invocado pois é setado por padrão com as condições default 
     * use apenas se desejar alterar as condições default  
     * @param string $orientacao
     * @param string $papel
     * @param int $margSup
     * @param int $margEsq
     * @return void
     */
    $orientacao = ''; //se deixar em branco será usada a orientação indicada no XML, default = '', opções 'P' ou 'L', qualquer outra opção será ignorada 
    $papel = 'A4'; //default = 'A4', opção 'LEGAL' => recomenda-se usar sempre A4, qualquer outra opção será ignorada
    $margSup = 2; //default = 2, cuidado com margens muito grandes pois diminuem em excesso a área de impressão
    $margEsq = 2;  //default = 2, cuidado com margens muito grandes pois diminuem em excesso a área de impressão
    $danfe2->printParameters($orientacao, $papel, $margSup, $margEsq);

    //NOTA: as margens inferior e direita são iguais à superior e esquerda, usadas como referencia

```

## Modificadores

São os métodos que modificam e/ou acrescentam comportamentos e informações ao pdf que será criado.

Todos esses métodos são opcionais caso queira alterar o comportamento padrão da Danfe. 

```php
    /**
     * Define a font padrão a ser usada
     * @param string $font
     * @return void
     */
    $font = 'times'; //opções times é a fonte obrigatória segundo a SEFAZ !! mas existe escolha para arial ou helvetica (ambas são iguais) 
    $danfe2->setDefaultFont($font); //default times exigido pelas SEFAZ

    /**
     * Exibe ou não, os textos referentas as tag obsItem/obsCont e obsItem/obsFisco
     * @param bool $val
     * @return void
     */
    $danfe2->exibirObservacoesNFe(false); //default true
    
    /**
     * Exibe ou não, textos referentes a fatura caso não existam duplicatas e exista fatura
     * @param bool $val
     * @return void
     */
    $danfe2->exibirFaturaNFe(false); //default true
    
    /**
     * Exibe ou não, o email do destinatário no bloco infCpl, caso seja informado o email
     * @param bool $val
     * @return void
     */
    $danfe2->exibirEmailDestinatarioNFe(false); //default true

    /**
     * Exibe ou não, os dados de rastreamento dos ??????medicamentos em cada item, se existirem
     * @param bool $val
     * @return void
     */
    $danfe2->exibirRastroItem(false); //default true

    /**
     * Exibe ou não, os dados do pedido do destinatário em cada item, se existirem
     * @param bool $val
     * @return void
     */
    $danfe2->exibirPedidoItem(false); //default true

    /**
     * Exibe ou não, os dados de unidade tributável de cada item onde a unidade tributável for diferente da unidade comercial
     * @param bool $val
     * @return void
     */
    $danfe2->exibirDadosTributaveisItem(false); //default true

    /**
     * Exibe ou não, informações de impostos adicionais como FCP na descrição de cada item, se houverem
     * @param bool $val
     * @return void
     */
    $danfe2->exibirImpostosAdicionaisItem(false); //default true
    
    /**
     * Exibe ou não, o numero do item junto com o codigo de cada item
     * @param bool $val
     * @return void
     */
    $danfe2->exibirNumeroItem(false); //default true
    
    

    

```

## Elementos Adicionais

São métodos que incluem informações ao DANFE.

```php
    
    /**
     * Exibe ou não, os dados do integrador e a mensagem "Powered by NFePHP®"
     * @param string $message
     * @param bool $powered
     * @return void
     */
    $message = 'WEBNFe Sistemas - http://www.webnfe.com.br'; //dados do integrador no rodapé da pagina 
    $powered = false; //exibe ou não o texto "Powered by NFePHP®" no rodapé da página
    $danfe->creditsIntegratorFooter($message, $powered);

    /**
     * Estabelece o logo e sua posição ou use o render para passar a imagem usando posição default 
     * Este método não necessita ser invocado apenas para inserir a logo sem alterar sua posição
     * apenas use esse método caso deseja alterar a posição da logomarca
     * Dê preferencia ao método render() para inserir a logo, nos padrões default  
     * @param string $logo
     * @param string $logoAlign
     * @param bool $mode_bw se true converte a imagem em branco e preto
     * @return void
     */
    //as imagens devem ser JPEG ou PNG obrigatóriamente e com um tamanho reazoável, evite imagens muito grandes ou com elevada resolução 
    $logo = 'path da imagem'; //usar um caminho à imagem ou 'data://text/plain;base64,'. {string da imagem em base64}
    //logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(__DIR__ . '/tulipas.png')); 
    $logoAlign = 'C'; //default C-centro, opções L-Left ou R-Right
    $mode_bw = false; //default false, converte a imagem para preto e branco 
    $danfe2->logoParameters($logo, $logoAlign, $mode_bw);

    
    $danfe2->setCancelFlag(true): //default false

```

## Finalizadores

```php
    /**
     * Renderizador, executa a montagem do PDF e retorna o pdf como string
     * @param string $logo
     * @return string 
     */
    $pdf = $danfe2->render($logo);

 

```

## Exemplo

```php


try {

    //dados
    $xml = file_get_contents(__DI__.'/arquivo_xml_nfe_modelo_55.xml');
    $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(__DIR__ . '/tulipas.png'));

    //inicialização
    $danfe2 = new Danfe2($xml);
    
    //Parametrização
    //$orientacao = ''; //se deixar em branco será usada a orientação indicada no XML, default = '', opções 'P' ou 'L', qualquer outra opção será ignorada 
    //$papel = 'A4'; //default = 'A4', opção 'LEGAL' => recomenda-se usar sempre A4, qualquer outra opção será ignorada
    //$margSup = 2; //default = 2, cuidado com margens muito grandes pois diminuem em excesso a área de impressão
    //$margEsq = 2;  //default = 2, cuidado com margens muito grandes pois diminuem em excesso a área de impressão
    //$danfe2->printParameters($orientacao, $papel, $margSup, $margEsq);
    
    //modificadores
    $danfe2->exibirFatura(false); //default true
    $danfe2->exibirRastro(false); //default true
    $danfe2->exibirPedido(false); //default true
    $danfe2->exibirUnidadeTributavel(false); //default true
    $danfe2->exibirImpostosAdicionais(false); //default true
    $danfe2->exibirEmail(false); //default true
    $danfe2->exibirNumeroItem(false); //default true
    $danfe2->exibirObservacoes(false); //default true
    
    //adicionais
    $danfe->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br', true);
    $danfe->logoParameters($logo, 'L', true);
    
    //finalização
    $pdf = $danfe->render();
    //o pdf porde ser exibido como view no browser
    //salvo em arquivo
    //ou setado para download forçado no browser
    //ou ainda gravado na base de dados
    header('Content-Type: application/pdf');
    echo $pdf;
    
} catch (\Exception $e) {
    echo "Ocorreu um erro durante o processamento: " . $e->getMessage();
}
```
