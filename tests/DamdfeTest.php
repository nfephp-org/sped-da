<?php

namespace NFePHP\DA\Tests;

/**
 * Created by PhpStorm.
 * User: Adélio Júnior
 * Date: 20/12/2017
 * Time: 17:54
 */

use NFePHP\DA\MDFe\Damdfe;
use PHPUnit\Framework\TestCase;

class DamdfeTest extends TestCase
{
    /**
     * @test
     */

    public function imprimirDamdfe()
    {
        // return true;
        $pathBase = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
        $pathXml =   $pathBase . 'xml' . DIRECTORY_SEPARATOR . 'mdfe_modelo_nao_valido.xml';
        $pathPdf =   $pathBase . 'pdf' . DIRECTORY_SEPARATOR;
        $pdfName = 'mdfe_modelo_nao_valido.pdf';

        if (file_exists($pathPdf . $pdfName)) {
            unlink($pathPdf . $pdfName);
        }

        $xmlFile = file_get_contents($pathXml);

        $damdfe = new Damdfe($xmlFile, 'P', 'A4', '', 'F', $pathPdf);
        $pdfContent = $damdfe->render();

        if ($pdfContent) {
            file_put_contents($pathPdf . $pdfName, $pdfContent);
        }

        $this->assertFileExists($pathPdf . $pdfName);
    }
}
