<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../bootstrap.php';

use NFePHP\DA\NFe\DanfcePos;
use Posprint\Printers\Epson;
use Posprint\Connectors;

//como não foi estabelecido um conector os dados serão retornados ao browser
//ajustados para permitir leitura (lembre-se que os comandos são dados binários)
$conn = null;
//define a impressora e o connector
$printer = new Epson($conn);

//instancia a classe DANFE e carrega a impressora
$danfe = new DanfcePos($printer);

//carrega a NFCe
$danfe->loadNFCe(file_get_contents('xml/NFCeHom2.xml'));

//monta a estrutura
$danfe->monta();

//envia para o conector
$danfe->printDanfe();

