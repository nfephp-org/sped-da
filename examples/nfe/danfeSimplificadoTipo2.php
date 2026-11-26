<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\NFe\DanfeSimplificadoTipo2;

try {
    $docxml = file_get_contents(__DIR__ . "/fixtures/mod55-nfe-danfeSimplificadoTipo2.xml");

    $danfe = new DanfeSimplificadoTipo2($docxml);
    $danfe->setPaperWidth(58); //seta a largura do papel em mm, min=56 (NT 2026.003)
    $danfe->setMargins(2);//seta as margens
    $pdf = $danfe->render();
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (\Exception $e) {
    echo $e->getMessage();
}
