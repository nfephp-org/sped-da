<?php
ini_set('display_errors', 1);
require_once '../../bootstrap.php';

use NFePHP\DA\CTe\Dacce;

$xml = file_get_contents(__DIR__ . '/../xml/proccce.xml');

$aEnd = array(
    'razao' => 'QQ Comercio e Ind. Ltda',
    'logradouro' => 'Rua vinte e um de março',
    'numero' => '200',
    'complemento' => 'sobreloja',
    'bairro' => 'Nova Onda',
    'CEP' => '99999-999',
    'municipio' => 'Onda',
    'UF' => 'MG',
    'telefone' => '33333-3333',
    'email' => 'qq@gmail.com'
);

try {
    $docxml = file_get_contents($xml);
    $dacce = new Dacce($xml, 'L', 'A4', '', 'I', $aEnd, '', '', 1);
    $id = $dacce->monta();
    $dacce->printDACCE("dacce.pdf", "I");
} catch (Exception $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}
