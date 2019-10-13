<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\MDFe\Damdfe;

$xml = file_get_contents('../xml/mdfe/mdfe.xml');

try {
    $damdfe = new Damdfe($xml);
    $damdfe->monta('../images/logo.jpg');
    $pdf = $damdfe->render();
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (Exception $e) {
    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
}    