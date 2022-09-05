<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\NFe\DanfeEtiqueta;

$xml = file_get_contents(__DIR__ . '/fixtures/mod55-nfe_5.xml');
$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/tulipas.png')));
//$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/logo.jpeg')));
//$logo = realpath(__DIR__ . '/../images/tulipas.png');

try {
    $danfe = new DanfeEtiqueta($xml);
    $danfe->setEmitEmail('linux.rlm@gmail.com');
    $danfe->setMargins(1);
    $pdf = $danfe->render($logo);
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (\Exception $e) {
    echo $e->getMessage();
}
