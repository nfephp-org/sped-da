<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\MDFe\Damdfe;

$xml = file_get_contents(__DIR__ . '/mdfe.xml');

try {
    $damdfe = new Damdfe($xml, 'P', 'A4', 'images/logo.jpg');
    $id = $damdfe->printMDFe('', 'I');
} catch (Exception $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}    