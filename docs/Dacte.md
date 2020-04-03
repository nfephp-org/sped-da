# DaCTe

## Class NFePHP\DA\CTe\Dacte

Ao construir um documento em PDF tenha certeza de passar apenas caracteres UTF-8.

### public _construct()

O método construtor recebe um UNICO parâmetro que é o conteúdo do xml de um CTe.

> NOTA: não deve ser passado um path e sim o conteúdo completo de um xml de CTe.

```php
$xml = "<conteudo do xml>";
$da = new Dacte($xml);

```

### public creditsIntegratorFooter($message = '')

Esse método permite que os dados do integrador (softwarehouse) sejam inclusos no rodapé do documento.

> NOTA: não é permitido o uso de LF, CR ou outro tipo de caracter de controle, nesse dado.

```php
$da->creditsIntegratorFooter('Sua empresa ltda');
```

### public setDefaultFont(string $font = 'times')

Esse método permite alterar o tipo de fonte padrão a ser usada. "times" é a fonte padrão, que será usada em todos os documentos.

> NOTA: O uso da fonte "times" é estabelecido pela SEFAZ para NFe, CTe, CTeOS e MDFe, portanto não deve ser alterada, para NFCe e BPe é aceita a fonte "arial".

```php
$da->setDefaultFont('times');
```

### public logoParameters($logo, $logoAlign = null, $mode_bw = false)

Esse método permite carregar o logo e suas caracteristicas como posição e conversão para preto e branco (se desejado)

> NOTA: o logo pode ser passado como um path (completo) ou como uma string. Normalmente passar como string é mais útil, pois permite que sejam recuperadas imagens gravadas em bases de dados.

```php
$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/logo.jpg')));
//$logo = 'data://text/plain;base64,'. $image_base64_encoded_direct_from_data_base);
//$logo = realpath(__DIR__ . '/../images/logo.jpg')));

$da->logoParameters($logo, 'C', false);
```

### public setDefaultDecimalPlaces($dec)

Esse método seta o numero de casas decimais a serem usados, nos campos onde isso é autorizado.

> NOTA: o limite superior são 4 casas decimais e o inferior são de 2 casas decimais, nenhum outro valor será aceito.

```php
$da->setDefaultDecimalPlaces(2);
```

### public setPaperWidth($width = 80)

Esse método seta a largura em milimetros do papel de impressão usado na impressora térmica.

A largura padrão é de 80 milímetros.

```php
$da->setPaperWidth(80);
```

### public printParameters($orientacao = '', $papel = 'A4', $margSup = null, $margEsq = null)

Esse método estabelece os parametros de impressão do PDF, forçando essas opções, caso não sejam passados, serão usados os padrões.

```php
$da->printParameters('P', 'A4', 2, 2);
``

### public depecNumber($numdepec)

Esse metodo permite passar o número do DPEC na emissão em contigência DPEC, para ser impresso no pdf.

> NOTA: esse método não afeta o Dacte

```php
$da->depecNumber('12345678');
``

### public render($logo = null)

Esse método é responsável por renderizar o PDF e retorna esse PDF em uma string.

> NOTA: a string com o PDF pode ser gravada, exibida no browser ou alguma outra coisa a sua escolha.

```php
$pdf = $dacte->render();

//exibe o pdf no browser
header('Content-Type: application/pdf');
echo $pdf;
```