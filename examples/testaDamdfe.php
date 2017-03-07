<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../bootstrap.php';
use NFePHP\DA\MDFe\Damdfe;
$xmlfile = __DIR__ . DIRECTORY_SEPARATOR . 'xml/mod58-mdfe.xml';
try {
    $damdfe = new Damdfe($xmlfile, 'P', 'A4', 'images/logo.jpg');
    $id = $damdfe->printMDFe('', 'I');
} catch (Exception $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}    