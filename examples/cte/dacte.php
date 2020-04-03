<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\CTe\Dacte;

$xml = file_get_contents(__DIR__ . '/fixtures/cte_hom_com_prot.xml');
//$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/tulipas.png')));
$logo = realpath(__DIR__ . '/../images/tulipas.png');

try {
    //instanciação da classe (OBRIGATÓRIO)
    $da = new Dacte($xml);
    
    //Métodos públicos (TODOS OPCIONAIS)
    $da->debugMode(true);
    //$da->printParameters('P', 'A4', 2, 2);
    $da->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
    $da->setDefaultFont('times');
    $da->logoParameters($logo, 'C', false);
    $da->setDefaultDecimalPlaces(2);
    //$da->depecNumber('12345678');
    
    
    //Renderização do PDF  (OBRIGATÓRIO)
    $pdf = $da->render();
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (InvalidArgumentException $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}    
