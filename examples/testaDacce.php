<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../bootstrap.php';

use NFePHP\DA\NFe\Dacce;
use NFePHP\DA\Legacy\FilesFolders;

$xml = '/var/www/sped/sped-da/examples/xml/110110-53181011028793000173550010000066701204276800-1-procEventoNfe.xml';

$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents('images/logo.jpg'));

$aEnd = array(
    'razao' => 'DF TRANSPORTES E LOGISTICA EIRELI',
    'logradouro' => 'ADE Conjunto 28',
    'numero' => '01',
    'complemento' => 'sobreloja',
    'bairro' => 'Area de Desenvolvimento Economico Aguas Claras',
    'CEP' => '71991360',
    'municipio' => 'Brasilia',
    'UF' => 'DF',
    'telefone' => '6130220802',
    'email' => '' 
);

try {
    $docxml = FilesFolders::readFile($xml);
    $dacce = new Dacce($docxml, 'P', 'A4', $logo, 'I', $aEnd);
    $id = $dacce->chNFe . '-CCE';
    $pdf = $dacce->render();
    header('Content-Type: application/pdf');
    echo $pdf;
    
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
} catch (RuntimeException $e) {
    echo $e->getMessage();
}
