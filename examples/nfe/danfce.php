<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\NFe\Danfce;

try {
    $docxml = file_get_contents(__DIR__ . "/fixtures/nfce111.xml");
    $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/logo.jpeg')));
    $logo = realpath(__DIR__ . '/../images/logo-nfce.png');

    $danfce = new Danfce($docxml);
    $danfce->debugMode(true);//seta modo debug, deve ser false em produção
    $danfce->setPaperWidth(80); //seta a largura do papel em mm max=80 e min=58
    $danfce->setMargins(2);//seta as margens
    $danfce->setDefaultFont('arial');//altera o font pode ser 'times' ou 'arial'
    //$danfce->setPrintResume(true); //seta para imprimir apenas o resumo
    $danfce->setAsCanceled(); //marca nfce como cancelada 
    $danfce->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
    $pdf = $danfce->render($logo);
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (\Exception $e) {
    echo $e->getMessage();
}