<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\NFe\Danfce;

try {
    $docxml = file_get_contents(__DIR__ . "/fixtures/NFCeProd1.xml");
    $logo = __DIR__ . '/images/logo.jpg';

    $danfce = new Danfce($docxml);
    $danfce->debugMode(true);
    $danfce->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
    $danfce->monta($logo);
    $pdf = $danfce->render();

    header('Content-Type: application/pdf');
    echo $pdf;
} catch (\Exception $e) {
    echo $e->message;
}