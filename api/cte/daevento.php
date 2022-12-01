<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\CTe\Daevento;

// $xml = file_get_contents(__DIR__ . '/fixtures/proccce.xml');
//$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/tulipas.png')));
// $logo = realpath(__DIR__ . '/../images/tulipas.png');
$xml = file_get_contents('php://input');

$dadosEmitente = [
    'razao' => 'CONTRAIL LOGISTICA S.A',
    'logradouro' => 'AV ANTONIO FREDERICO OZANAN',
    'numero' => '1805',
    'complemento' => '',
    'bairro' => 'VILA SANTANA II',
    'CEP' => '13219-001',
    'municipio' => 'CAJAMAR',
    'UF' => 'SP',
    'telefone' => '(13)3367-1303',
    'email' => 'sim@sidedoor.com.br'
];

try {
    $daevento = new Daevento($xml, $dadosEmitente);
    $daevento->debugMode(true);
    $daevento->creditsIntegratorFooter('| Sidedoor { } - https://sidedoor.com.br');
    $pdf = $daevento->render();
    header('Content-Type: application/pdf;base64; charset=UTF-8');
    echo  base64_encode($pdf);
} catch (\Exception $e) {
    echo $e->getMessage();
}