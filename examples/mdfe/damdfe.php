<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\MDFe\Damdfe;

$xml = file_get_contents(__DIR__ . '/fixtures/mdfe.xml');
$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/tulipas.png')));
//$logo = realpath(__DIR__ . '/../images/tulipas.png');

try {
    $damdfe = new Damdfe($xml);
    $damdfe->debugMode(true);
    $damdfe->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
    $pdf = $damdfe->render($logo);
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (Exception $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}    