<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\NFe\DanfeSimples;

$xml = file_get_contents(__DIR__ . '/fixtures/mod55-nfe.xml');

try {
    $danfe = new DanfeSimples($xml);
    $danfe->debugMode(false);

    $pdf = $danfe->render();
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (InvalidArgumentException $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}
