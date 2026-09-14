<?php

namespace NFePHP\DA\Tests;

use Smalot\PdfParser\Parser;

class Utils
{
    public static function pdfContemTexto(string $conteudoPdf, string $textoProcurado): bool
    {
        return strpos(self::textoPdf($conteudoPdf), $textoProcurado) !== false;
    }

    public static function textoPdf(string $conteudoPdf): string
    {
        $parser = new Parser();

        return $parser->parseContent($conteudoPdf)->getText();
    }
}
