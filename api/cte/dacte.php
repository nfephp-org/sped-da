<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once realpath(__DIR__ . '/../../bootstrap.php');

use NFePHP\DA\CTe\Dacte;

$xml = file_get_contents('php://input');
//$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/tulipas.png')));
// $logo = realpath(__DIR__ . '/../images/contrail.jpg');

try {

    if ($_SERVER['REQUEST_METHOD'] != 'POST') {

        throw new Exception('Método incorreto!');
    }
    if (empty($xml)) {
        throw new Exception('Você deve enviar um XML de um CT-e');
    }

    //instanciação da classe (OBRIGATÓRIO)
    $da = new Dacte($xml);

    //Métodos públicos (TODOS OPCIONAIS)
    $da->debugMode(true);
    //$da->printParameters('P', 'A4', 2, 2);
    $da->creditsIntegratorFooter('| Sidedoor { } - https://sidedoor.com.br');
    $da->setDefaultFont('times');
    $da->logoParameters(false, 'C', false);
    $da->setDefaultDecimalPlaces(2);
    //$da->depecNumber('12345678');


    //Renderização do PDF  (OBRIGATÓRIO)
    $pdf = $da->render();
    header('Content-Type: application/pdf;base64; charset=UTF-8');
    echo  base64_encode($pdf);
    // print_r($da);
} catch (Exception $e) {

    header('Content-Type: application/json');
    header('HTTP/1.1 400 Bad Request', true, 400);
    echo json_encode(["Error" => $e->getMessage()]);
}