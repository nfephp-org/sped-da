<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../../bootstrap.php';

use NFePHP\DA\GNRE\Dagnre;

/**
 * Documento auxiliar da GNRE a partir do resultado do lote (TResultLote_GNRE),
 * obtido com o nfephp-org/sped-gnre:
 *   $xml = $tools->sefazConsultaLote($recibo);
 */
$xml = file_get_contents(__DIR__ . '/../../tests/fixtures/xml/gnre/retorno-resultado-v2.xml');
$logo = 'data://text/plain;base64,' . base64_encode(file_get_contents(realpath(__DIR__ . '/../images/tulipas.png')));

try {
    $dagnre = new Dagnre($xml);
    // $dagnre->debugMode(true); // não use com o tc-lib-barcode 1.x: o E_USER_DEPRECATED dele vira exceção
    $dagnre->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
    $pdf = $dagnre->render($logo);
    header('Content-Type: application/pdf');
    echo $pdf;
} catch (\Exception $e) {
    echo $e->getMessage();
}
