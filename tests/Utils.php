<?php

namespace NFePHP\DA\Tests;

use Smalot\PdfParser\Parser;

class Utils
{
    public static function pdfContemTexto(string $conteudoPdf, string $textoProcurado): bool
    {
        $parser = new Parser();
        $pdf = $parser->parseContent($conteudoPdf);

        $texto = $pdf->getText();

        return strpos($texto, $textoProcurado) !== false;
    }
}
