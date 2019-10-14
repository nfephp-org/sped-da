<?php
ini_set('display_errors', 1);
require_once '../../bootstrap.php';

use NFePHP\DA\CTe\Dacce;

$xml = file_get_contents(__DIR__ . '/fixtures/proccce.xml');

$dadosEmitente = [
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
];

try {
    $dacce = new Dacce($xml, $dadosEmitente);
    $dacce->debugMode(true);
    $dacce->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
    $dacce->monta($logo);
    $dacce->render();
    //o pdf porde ser exibido como view no browser
    //salvo em arquivo
    //ou setado para download forçado no browser 
    //ou ainda gravado na base de dados
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (\Exception $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}
