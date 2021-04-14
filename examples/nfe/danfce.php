<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\NFe\Danfce;

try {
    $docxml = file_get_contents(__DIR__ . "/fixtures/nfce112.xml");
    $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/logo.jpeg')));
    $logo = realpath(__DIR__ . '/../images/logo-nfce.png');

    $danfce = new Danfce($docxml);
    $danfce->debugMode(true);//seta modo debug, deve ser false em produção
    $danfce->setPaperWidth(80); //seta a largura do papel em mm max=80 e min=58
    $danfce->setMargins(2);//seta as margens
    $danfce->setDefaultFont('arial');//altera o font pode ser 'times' ou 'arial'
    $danfce->setOffLineDoublePrint(true); //ativa ou desativa a impressão conjunta das via do consumidor e da via do estabelecimento qnado a nfce for emitida em contingência OFFLINE
    //$danfce->setPrintResume(true); //ativa ou desativa a impressao apenas do resumo
    //$danfce->setViaEstabelecimento(); //altera a via do consumidor para a via do estabelecimento, quando a NFCe for emitida em contingência OFFLINE
    //$danfce->setAsCanceled(); //força marcar nfce como cancelada 
    $danfce->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webnfe.com.br');
    $pdf = $danfce->render($logo);
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (\Exception $e) {
    echo $e->getMessage();
}