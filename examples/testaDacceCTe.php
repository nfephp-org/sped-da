<?php

/**
 * ATENÇÃO : Esse exemplo usa classe PROVISÓRIA que será removida assim que
 * a nova classe DACCE estiver refatorada e a pasta EXTRAS será removida.
 */
ini_set('display_errors', 1);
require_once '../bootstrap.php';

use NFePHP\DA\CTe\Dacce;
use NFePHP\DA\Legacy\FilesFolders;

$xml = 'proccce.xml';

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
    $docxml = FilesFolders::readFile($xml);
    $dacce = new Dacce($docxml, 'L', 'A4', '', 'I', $aEnd);
    $id = $dacce->monta();
    $dacce->printDACCE("dacce.pdf", "I");
} catch (Exception $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}
