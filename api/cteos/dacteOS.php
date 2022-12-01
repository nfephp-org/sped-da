<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\CTe\DacteOS;

$xml = file_get_contents(__DIR__ . '/fixtures/cte_hom_com_prot.xml');

$logo = 'data://text/plain;base64,' . base64_encode(file_get_contents(__DIR__ . '/../images/tulipas.png'));

try {
    $dacte = new DacteOS($xml);
    $dacte->debugMode(true);
    $dacte->creditsIntegratorFooter('Seu Software Ltd');
    $pdf = $dacte->render($logo);
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (InvalidArgumentException $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}    
