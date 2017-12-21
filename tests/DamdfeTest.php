<?php
namespace NFePHP\DA\Tests;

/**
 * Created by PhpStorm.
 * User: Adélio Júnior
 * Date: 20/12/2017
 * Time: 17:54
 */
use NFePHP\DA\MDFe\Damdfe;
use PHPUnit\Framework\Assert;

class DamdfeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function imprimirDamdfe() {
        $pathBase = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
        $pathXml =   $pathBase . 'xml' . DIRECTORY_SEPARATOR . 'mdfe_modelo_nao_valido.xml';
        $pathPdf =   $pathBase . 'pdf' . DIRECTORY_SEPARATOR;
        $pdfName = 'mdfe_modelo_nao_valido.pdf';

        if (file_exists($pathPdf . $pdfName)) {
            unlink($pathPdf . $pdfName);
        }

        $xmlFile = file_get_contents($pathXml);
        $damdfe = new Damdfe($xmlFile, 'P', 'A4', '', 'F', $pathPdf, '', '0');
        $damdfe->printMDFe($pdfName, 'F');
        Assert::assertFileExists($pathPdf . $pdfName);
    }
}
