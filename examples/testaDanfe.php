<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../bootstrap.php';

use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\Legacy\FilesFolders;

$xml = 'xml/mod55-nfe.xml';
$docxml = FilesFolders::readFile($xml);

$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents('images/logo.jpg'));

try {
    $danfe = new Danfe($docxml, 'P', 'A4', $logo, 'I', '');
    $id = $danfe->montaDANFE();
    $pdf = $danfe->render();
    //o pdf porde ser exibido como view no browser
    //salvo em arquivo
    //ou setado para download forçado no browser 
    //ou ainda gravado na base de dados
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (InvalidArgumentException $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}    
