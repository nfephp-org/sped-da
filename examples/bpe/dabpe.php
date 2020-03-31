<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\BPe\Dabpe;

try {
    $docxml = file_get_contents(__DIR__ . "/fixtures/bpe.xml");
    $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/logo.jpg')));
    //$logo = realpath(__DIR__ . '/../images/tulipas.png');

    $dabpe = new Dabpe($docxml);
    $dabpe->debugMode(true);
    $dabpe->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
    $pdf = $dabpe->render($logo);
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (\Exception $e) {
    echo $e->message;
}