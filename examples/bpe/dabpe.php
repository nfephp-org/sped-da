<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\BPe\Dabpe;

try {
    $xml = file_get_contents(__DIR__ . "/fixtures/bpe.xml");
    //$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/tulipas.png')));
    $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/logo.jpeg')));
    //$logo = realpath(__DIR__ . '/../images/tulipas.png');

    $da = new Dabpe($xml);
    //metodos publicos
    $da->debugMode(true);
    $da->setPaperWidth(80);
    $da->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
    //renderiza o PDF e retorna como uma scring
    $pdf = $da->render($logo);
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (\Exception $e) {
    echo $e->message;
}