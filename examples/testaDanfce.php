<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../bootstrap.php';

use NFePHP\DA\NFe\Danfce;

$docxml = file_get_contents(realpath(__DIR__."/xml/NFCeProd1.xml"));
$pathLogo = realpath(__DIR__.'/images/logo.jpg');//use somente imagens JPEG

$danfce = new Danfce($docxml, $pathLogo, 0);
$id = $danfce->monta();
$pdf = $danfce->render();

header('Content-Type: application/pdf');
echo $pdf;