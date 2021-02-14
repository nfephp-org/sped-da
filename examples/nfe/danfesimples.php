<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\NFe\DanfeSimples;

$xml = file_get_contents(__DIR__ . '/fixtures/mod55-nfe.xml');
$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/tulipas.png')));
//$logo = realpath(__DIR__ . '/../images/tulipas.png');

try {
    $danfe = new DanfeSimples($xml);
    $danfe->debugMode(false);
    // Caso queira mudar a configuracao padrao de impressao
    //Informe o numero DPEC
    /*  $danfe->depecNumber('123456789'); */
    //Configura a posicao da logo
    /*  $danfe->logoParameters($logo, 'C', false);  */
    //Configura o tamanho do papel. O padrão é A5,
        // $danfe->papel = 'A5';
    //mandar array com o tamanho para o caso etiquetas de tamanhos personalizados.
        // $danfe->papel = [100, 150];
    //Gera o PDF
    $pdf = $danfe->render($logo);
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (InvalidArgumentException $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}
