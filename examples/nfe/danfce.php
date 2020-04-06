<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\NFe\Danfce;

try {
    $docxml = file_get_contents(__DIR__ . "/fixtures/NFCeProd1.xml");
    $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/logo.jpeg')));
    //$logo = realpath(__DIR__ . '/../images/tulipas.png');

    $danfce = new Danfce($docxml);
    $danfce->debugMode(true);
    $danfce->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
    $pdf = $danfce->render($logo);
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (\Exception $e) {
    echo $e->message;
}