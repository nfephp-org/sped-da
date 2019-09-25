<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../bootstrap.php';

use NFePHP\DA\NFe\Danfce;

try {
    $docxml = file_get_contents(__DIR__."/xml/NFCeProd1.xml");
    $pathLogo = __DIR__ . '/images/logo.jpg';

    $danfce = new Danfce($docxml, $pathLogo, 0);
    $id = $danfce->monta();
    $pdf = $danfce->render();

    header('Content-Type: application/pdf');
    echo $pdf;
    
} catch (\Exception $e) {
    echo $e->message;
}